<?php

/**
 * Класс, содержащий функциональность для входа с учётной записью sitemanager.ru.
 */
class nc_sitemanager_auth extends nc_sitemanager_requester {

    const GET_AUTH_CODE_PATH = 'https://sitemanager.ru/sso/';
    const GET_ACCESS_TOKEN_PATH = 'https://sitemanager.ru/api/v1/auth/token';
    const GET_USER_DATA_PATH = 'https://sitemanager.ru/api/v1/user/data';
    const GET_USER_STATUS_PATH = 'https://sitemanager.ru/api/v1/user/status';

    const RECEIVE_AUTH_CODE_SCRIPT = 'sso/auth.php';

    /**
     * Сохраняет значение $value для параметра $option между вызовами.
     *
     * @param $option
     * @param $value
     */
    protected function set($option, $value) {
        $_SESSION['sitemanager_auth_' . $option] = $value;
    }

    /**
     * Возвращает значение параметра $option, установленного методом set().
     *
     * @param $option
     * @return mixed|null
     */
    protected function get($option) {
        $key = 'sitemanager_auth_' . $option;
        return (isset($_SESSION[$key]) ? $_SESSION[$key] : null);
    }

    /**
     * Сбрасывает значение параметра $option, установленного методом set().
     *
     * @param $option
     */
    protected function clear($option) {
        unset($_SESSION['sitemanager_auth_' . $option]);
    }

    /**
     *
     */
    protected function get_receive_auth_code_uri() {
        return $this->get_current_host() . nc_core::get_object()->ADMIN_PATH . self::RECEIVE_AUTH_CODE_SCRIPT;
    }

    /**
     * Выполняет переадресацию на скрипт получения auth code на sitemanager.ru (первый шаг)
     *
     * @param string $redirect_path_after_logging_in Путь, на который будет выполнена
     *   переадресация после получения данных о пользователе (протокол, хост и номер
     *   порта будут взяты из текущей страницы, а не из этого параметра)
     */
    public function make_auth_code_request($redirect_path_after_logging_in = '') {
        $nc_core = nc_core::get_object();
        $copy_id = $nc_core->get_copy_id();
        $state = $this->get_random_string();

        if (!$redirect_path_after_logging_in || !$nc_core->security->url_matches_local_site($redirect_path_after_logging_in)) {
            $redirect_path_after_logging_in = $nc_core->ADMIN_PATH;
        }

        $after_path_parts = parse_url($redirect_path_after_logging_in);
        $redirect_after_logging_in_uri = $this->get_current_host() . $after_path_parts['path'];
        if (isset($after_path_parts['query'])) {
            $redirect_after_logging_in_uri .= "?$after_path_parts[query]";
        }
        if (isset($after_path_parts['fragment'])) {
            $redirect_after_logging_in_uri .= "#$after_path_parts[fragment]";
        }

        $this->set('redirect_uri', $redirect_after_logging_in_uri);
        $this->set('state', $state);

        $params = array(
            'redirect_uri' => $this->get_receive_auth_code_uri(),
            'response_type' => 'code',
            'state' => $state,
            'client_id' => $copy_id,
        );

        $uri = self::GET_AUTH_CODE_PATH . '?' . http_build_query($params, null, '&');

        header("Location: $uri");
        die;
    }


    /**
     * Выполняет запрос на обмен auth code на access token (второй шаг)
     *
     * @param string $code
     * @param string $state
     * @param string $session_hash
     * @return bool
     */
    public function make_auth_token_request($code, $state, $session_hash) {
        if (!$code) {
            throw new nc_sitemanager_exception('No auth code');
        }

        if (!$state || $state != $this->get('state')) {
            throw new nc_sitemanager_exception('Invalid state');
        }

        $this->set('session_hash', $session_hash);

        $request_parameters = array(
            'grant_type' => 'authorization_code',
            'code' => $code,
            'client_id' => $this->get_copy_id(),
            'client_secret' => $this->get_client_secret(),
            'redirect_uri' => $this->get_receive_auth_code_uri(),
        );

        $server_response = $this->fetch_json_data(self::GET_ACCESS_TOKEN_PATH, $request_parameters);

        if (!$server_response || !isset($server_response['access_token'])) {
            throw new nc_sitemanager_exception('No access token was received: ' . nc_array_value($server_response, 'error_description'));
        }

        $this->set_access_data($server_response);
        $this->clear('state');

        return true;
    }

    /**
     * Обрабатывает и запоминает данные для текущей сессии, полученные от sitemanager.ru
     *
     * @param array $server_response
     */
    protected function set_access_data($server_response) {
        if (isset($server_response['access_token'])) {
            $time = time();

            $this->set('access_token', $server_response['access_token']);
            $this->set('access_token_expires_at', $time + $server_response['expires_in']);
            $this->set('check_user_status_before', $time + $server_response['check_user_status_interval']);

            if (isset($server_response['refresh_token'])) {
                $this->set('refresh_token', $server_response['refresh_token']);
            }
        } else {
            $this->on_logging_off();
        }
    }

    /**
     * Выполняет запрос на обновление access token по refresh_token
     */
    protected function refresh_access_token() {
        $refresh_token = $this->get('refresh_token');
        if (!$refresh_token) {
            $this->make_auth_code_request();
        }

        $request_parameters = array(
            'grant_type' => 'refresh_token',
            'refresh_token' => $refresh_token,
            'client_id' => $this->get_copy_id(),
            'client_secret' => $this->get_client_secret(),
        );

        $server_response = $this->fetch_json_data(self::GET_ACCESS_TOKEN_PATH, $request_parameters);

        if ($server_response) {
            $this->set_access_data($server_response);
        } else {
            $this->make_auth_code_request();
        }

        return $this->get('access_token');
    }

    /**
     * Проверяет, не истёк ли срок действия access token
     *
     * @return bool
     */
    protected function access_token_has_expired() {
        return !$this->get('access_token') ||
            (time() + 60 > $this->get('access_token_expires_at'));
    }

    /**
     * Возвращает access token (если его действие истекло — пытается получить
     * новый access token с использованием refresh token)
     *
     * @return string|bool
     */
    protected function get_access_token() {
        $access_token = $this->get('access_token');

        if (!$access_token) {
            return false;
        }

        if ($this->access_token_has_expired()) {
            $access_token = $this->refresh_access_token();
        }

        return $access_token;
    }

    /**
     * Запрашивает и возвращает информацию о пользователе
     *
     * @return array|false массив с ключами:
     *    user_id
     *    login
     *    language
     *    email
     *    name
     *    avatar (URI of the avatar image)
     */
    public function get_user_data() {
        $access_token = $this->get_access_token();
        if (!$access_token) {
            return false;
        }

        $request_parameters = array(
            'access_token' => $access_token,
            'client_id' => $this->get_copy_id(),
            'client_secret' => $this->get_client_secret(),
        );

        $user_data = $this->fetch_json_data(self::GET_USER_DATA_PATH, $request_parameters);

        if (isset($user_data['user'])) {
            return $user_data['user'];
        }

        return false;
    }

    /**
     * Проверяет, залогинен ли пользователь на sitemanager.ru
     *
     * @return bool
     */
    public function is_logged_in_at_sitemanager() {
        $access_token = $this->get_access_token();
        if (!$access_token) {
            return false;
        }

        $request_parameters = array(
            'access_token' => $access_token,
            'client_id' => $this->get_copy_id(),
            'client_secret' => $this->get_client_secret(),
            'session_hash' => $this->get('session_hash'),
        );

        $response = $this->fetch_json_data(self::GET_USER_STATUS_PATH, $request_parameters);

        if (isset($response['is_logged_in'])) {
            if (isset($response['check_user_status_interval'])) {
                $this->set('check_user_status_before', time() + $response['check_user_status_interval']);
            }

            return $response['is_logged_in'];
        }

        return false;
    }

    /**
     * Проверяет, есть ли токен доступа к sitemanager.ru (см. также метод is_logged_in())
     *
     * @return bool
     */
    public function has_access_token() {
        return (bool)$this->get('refresh_token');
    }

    /**
     * Проверяет, есть ли токен доступа к sitemanager.ru, каждые check_user_status_interval
     * секунд делает запрос к sitemanager.ru для проверки, не разлогинился ли пользователь
     * там.
     *
     * @return bool
     */
    public function is_logged_in() {
        if (!$this->has_access_token()) {
            return false;
        }

        if (time() >= $this->get('check_user_status_before') && !$this->is_logged_in_at_sitemanager()) {
            return false;
        }

        return true;
    }

    /**
     * Выполняет вход в систему: запрашивает данные на sitemanager.ru, залогинивает пользователя.
     */
    public function log_in() {
        $user_data = $this->get_user_data();
        if ($user_data) {
            $nc_core = nc_core::get_object();

            if (empty($user_data['Email'])) {
                throw new nc_sitemanager_exception('No email in user data', E_USER_ERROR);
            }

            $user_email = $nc_core->db->escape($user_data['Email']);
            $user_id = $nc_core->db->get_var("SELECT `User_ID` FROM `User` WHERE `Email` = '$user_email'");

            if ($user_id) {
                $nc_core->user->authorize_by_id($user_id, NC_AUTHTYPE_EX);
                $this->redirect_to_user_requested_page();
            } else {
                header("Location: $nc_core->ADMIN_PATH");
                exit;
            }
        } else {
            $this->make_auth_code_request();
        }
    }

    /**
     * Сбрасывает информацию о токенах доступа к sitemanager.ru
     */
    public function on_logging_off() {
        $this->clear('refresh_token');
        $this->clear('access_token');
        $this->clear('access_token_expires_at');
        $this->clear('check_user_status_before');
        $this->clear('session_hash');
    }

    /**
     * Выполняет переадресацию на страницу, запрошенную в методе make_auth_code_request().
     */
    public function redirect_to_user_requested_page() {
        header('Location: ' . $this->get('redirect_uri'));
        $this->clear('redirect_uri');
        exit;
    }

}