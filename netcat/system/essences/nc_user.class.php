<?php

class nc_User extends nc_Essence {

    /** @var nc_Core */
    protected $core;
    /** @var nc_Db */
    protected $db;

    protected $cookie_lifetime = 157680000; // 5 лет

    /** @var bool при авторизации каптча была введена неверно */
    protected $captcha_is_invalid = false;

    /** @var bool при авторизации каптча не была введена */
    protected $captcha_is_missing = false;

    /** @var string  */
    protected $keep_failed_login_info_interval = '1 DAY';

    /**
     * Constructor function
     */
    public function __construct() {
        // load parent constructor
        parent::__construct();

        $this->core = nc_Core::get_object();
        $this->db = $this->core->db;

        $this->register_event_listeners();
    }

    /**
     * Обработчики для обновления и сброса кэша
     */
    protected function register_event_listeners() {
        $event = nc_core::get_object()->event;
        $on_change = array($this, 'update_cache_on_change');
        $event->add_listener(nc_event::AFTER_USER_UPDATED, $on_change);
        $event->add_listener(nc_event::AFTER_USER_ENABLED, $on_change);
        $event->add_listener(nc_event::AFTER_USER_DISABLED, $on_change);
        $event->add_listener(nc_event::AFTER_USER_DELETED, $on_change);
    }

    /**
     * @param int|string $id
     * @param string $item
     * @param bool $reset
     * @return mixed
     */
    public function get_by_id($id, $item = '', $reset = false) {
        $id = (int)$id;

        if ($reset || !isset($this->data[$id]) || !is_array($this->data[$id])) {
            nc_core::get_object()->clear_cache_on_low_memory();
            $this->data[$id] = (array)$this->db->get_row(
                "SELECT *, 0 AS _nc_final FROM `User` WHERE `User_ID` = $id",
                ARRAY_A
            );
        }

        if (empty($this->data[$id]['_nc_final'])) {
            $this->data[$id] = $this->convert_system_vars($this->data[$id]);
            $this->data[$id]['_nc_final'] = 1;
        }

        // if item requested return item value
        if ($item) {
            return array_key_exists($item, $this->data[$id]) ? $this->data[$id][$item] : "";
        }

        return $this->data[$id];
    }

    /**
     * @param array $fields ассоциативный массив, например $fields = array('Email' => 'guest@example.com');
     * @param array|string|int $groups массив, содержащий список групп, в которые которые добавить пользователя, либо строка с перечислением этих групп через запятую
     * @param string $password пароль нового пользователя
     * @param array $add_fields ассоциативный массив, допустимые ключи: 'Checked', 'UserType', 'Catalogue', 'RegistrationCode'
     * @param string|null $registration_code код подтверждения регистрации
     * @return mixed
     * @throws Exception|nc_Exception_DB_Error
     */
    public function add($fields, $groups, $password, $add_fields = array(), $registration_code = null) {
        $auth_by = $this->core->AUTHORIZE_BY;
        if (!array_key_exists($auth_by, $fields)) {
            throw new Exception(CONTROL_USER_REGISTER_ERROR_NO_LOGIN_FIELD_VALUE);
        }

        switch ($this->check_login($fields[$auth_by])) {
            case NC_AUTH_LOGIN_EXISTS:
                throw new Exception(CONTROL_USER_REGISTER_ERROR_LOGIN_ALREADY_EXIST);
                break;
            case NC_AUTH_LOGIN_INCORRECT:
                throw new Exception(CONTROL_USER_REGISTER_ERROR_LOGIN_INCORRECT);
                break;
        }

        $user_table = new nc_Component(0, 3);

        if (!$groups && nc_module_check_by_keyword('auth')) {
            $groups = $this->core->get_settings('group', 'auth');
        }

        if (!is_array($groups)) {
            $groups = explode(',', $groups);
        }

        $groups = array_filter(array_unique(array_map('intval', $groups)));

        if (!$groups) {
            throw new InvalidArgumentException('$groups must be set');
        }

        $insert_fields = array(
            '`Password`',
            '`Created`',
            '`Checked`',
            '`PermissionGroup_ID`',
            '`UserType`',
            '`Catalogue_ID`'
        );

        $insert_values = array(
            $this->core->MYSQL_ENCRYPT . '("' . $password . '")',
            "'" . date('Y-m-d H:i:s') . "'",
            (int)nc_array_value($add_fields, 'Checked', 1),
            min($groups),
            "'" . $this->db->escape(nc_array_value($add_fields, 'UserType', 'normal')) . "'",
            (int)nc_array_value($add_fields, 'Catalogue_ID')
        );

        if ($registration_code || isset($add_fields['RegistrationCode'])) {
            $insert_fields[] = '`RegistrationCode`';
            $insert_values[] = "'" . nc_array_value($add_fields, 'RegistrationCode', $registration_code) . "'";
        }

        $user_fields = $user_table->get_fields();
        $user_files = array();

        if (!empty($user_fields)) {
            foreach ($user_fields as $v) {
                if (isset($fields[$v['name']]) && $v['type'] != NC_FIELDTYPE_FILE) {
                    $insert_fields[] = '`' . $this->db->escape($v['name']) . '`';
                    $insert_values[] = "'" . $this->db->escape($fields[$v['name']]) . "'";
                }
                if (isset($fields[$v['name']]) && $v['type'] == NC_FIELDTYPE_FILE) {
                    $user_files[$v['id']] = array('path' => $fields[$v['name']]);
                }
            }
        }

        $this->core->event->execute(nc_Event::BEFORE_USER_CREATED, 0);

        $this->db->query('INSERT INTO `User`(' . implode(',', $insert_fields) . ') VALUES (' . implode(',', $insert_values) . ')');
        if ($this->db->is_error) {
            throw new nc_Exception_DB_Error($this->db->last_query, $this->db->last_error);
        }

        $user_id = $this->db->insert_id;
        foreach ($groups as $group_id) {
            $this->db->query("INSERT INTO `User_Group` (`User_ID`, `PermissionGroup_ID`) VALUES ('{$user_id}', '{$group_id}') ");
        }

        foreach ($user_files as $field_id => $v) {
            $this->core->files->save_file('User', $field_id, $user_id, $v);
        }

        $this->core->event->execute(nc_Event::AFTER_USER_CREATED, $user_id);

        return $user_id;
    }

    public function authorize_by_token($login, $sign, $text) {
        $user_info = $this->db->get_row("SELECT `User_ID`, `PublicKey` FROM `Auth_Token` WHERE `Login` = '{$this->db->escape($login)}'", ARRAY_A);

        if (!$user_info) {
            return 0;
        }

        // публичный ключ, текст, компоненты ключа и эцп
        $pk = $user_info['PublicKey'];
        $Hash = strtoupper($text);
        $Qx = strtoupper(substr($pk, 0, 64));
        $Qy = strtoupper(substr($pk, 64));
        $R = strtoupper(substr($sign, 0, 64));
        $S = strtoupper(substr($sign, 64));

        $nc_auth_token = new nc_auth_token();
        if ($nc_auth_token->verify($Hash, $Qx, $Qy, $R, $S)) {
            return $this->authorize_by_id($user_info['User_ID'], NC_AUTHTYPE_TOKEN);
        }

        return 0;
    }

    public function change_password($user_id, $password, $delete_reg_code = false) {
        $user_id = (int)$user_id;
        $password_hash = $this->core->MYSQL_ENCRYPT . "('" . $this->db->escape($password) . "')";
        $reg_code_nullification = $delete_reg_code ? ", `RegistrationCode` = ''" : '';

        $this->db->query("UPDATE `User` SET `Password` = {$password_hash} {$reg_code_nullification} WHERE `User_ID` = '{$user_id}'");

        return $this->db->rows_affected;
    }

    /**
     * Проверка логина
     *
     * @param string $login логин
     * @param int $user_id номер пользователя ( 0 - регистрация ), нужна для проверки совпадения логина
     * @return int константы: NC_AUTH_LOGIN_OK, NC_AUTH_LOGIN_INCORRECT, NC_AUTH_LOGIN_EXISTS
     */
    public function check_login($login, $user_id = 0) {
        $allow_cyrillic = $allow_specialchars = true;
        if ($this->core->modules->get_by_keyword('auth')) {
            $allow_cyrillic = $this->core->get_settings('allow_cyrillic', 'auth');
            $allow_specialchars = $this->core->get_settings('allow_specialchars', 'auth');
        }

        $user_id = (int)$user_id;
        $auth_by = $this->db->escape($this->core->AUTHORIZE_BY);
        $escaped_login = $this->db->escape($login);
        $format = $this->db->get_var("SELECT `Format` FROM `Field` WHERE `Field_Name` = '" . $auth_by . "' AND `System_Table_ID` = 3");

        // в качестве логина выступает Email
        if ($format === 'email') {
            if (!nc_check_email($login)) {
                return NC_AUTH_LOGIN_INCORRECT;
            }
        } else {
            // русские символы запрещены
            if (!$allow_cyrillic && nc_preg_match("/[" . NETCAT_RUALPHABET . "]/", $login)) {
                return NC_AUTH_LOGIN_INCORRECT;
            }
            // спецсимволы запрещены
            if (!$allow_specialchars && !nc_preg_match("/^[a-z0-9" . NETCAT_RUALPHABET . "_-]+$/i", $login)) {
                return NC_AUTH_LOGIN_INCORRECT;
            }
        }

        $exclude_given_user = $user_id ? " AND `User_ID` != '{$user_id}'" : '';
        
        $current_catalogue = (array)$this->core->catalogue->get_by_host_name($_SERVER['HTTP_HOST']);
        $catalogue = nc_array_value($current_catalogue, 'Catalogue_ID', 0);
        $bind_to_catalogue = '';

        if ($this->core->modules->get_by_keyword('auth')) {
            if ($this->core->get_settings('bind_to_catalogue', 'auth')) {
                $bind_to_catalogue = " AND `Catalogue_ID` IN (0, {$catalogue})";
            }
        }
        
        // проверка существования логина
        $user_found = $this->db->get_var(
            "SELECT `User_ID` 
               FROM `User`
              WHERE `{$auth_by}` = '{$escaped_login}' " .
                    $exclude_given_user .
                    $bind_to_catalogue
        );

        if ($user_found) {
            return NC_AUTH_LOGIN_EXISTS;
        }

        return NC_AUTH_LOGIN_OK;
    }

    public function delete_by_id($id, $class_id = 0, $trash = false) {
        if (!is_array($id)) {
            $id = array($id);
        }
        $id = array_map('intval', $id);

        $this->core->event->execute(nc_Event::BEFORE_USER_DELETED, $id);

        foreach ($id as $v) {
            DeleteSystemTableFiles('User', $v);
        }

        $ids_str = implode(',', $id);

        $this->db->query("DELETE FROM `User` WHERE `User_ID` IN ({$ids_str})");
        $this->db->query("DELETE FROM `User_Group`  WHERE `User_ID` IN ({$ids_str})");

        if ($this->core->modules->get_by_keyword('auth')) {
            $this->db->query("DELETE FROM `Auth_ExternalAuth` WHERE `User_ID` IN ({$ids_str})");
        }

        if ($this->db->get_var("SHOW TABLES LIKE 'Excursion'")) {
            $this->db->query("DELETE FROM `Excursion` WHERE `User_ID` IN ({$ids_str})");
        }

        $this->core->event->execute(nc_Event::AFTER_USER_DELETED, $id);
    }

    public function authorize_by_pass($login, $password, $captcha = null) {
        $login = trim($login);
        $nc_core = $this->core;
        
        static $results = array();
        $cache_key = "$login\n$password\n$captcha";

        if (isset($results[$cache_key])) {
            return $results[$cache_key];
        }

        // Проверка каптчи (если необходимо)
        // каптча указана неправильно
        if ($this->captcha_is_required() && !nc_captcha_verify_code($captcha)) {
            // каптча была передана
            $this->captcha_is_invalid = true;
            $this->captcha_is_missing = strlen($captcha) === 0;
            $this->update_user_login_counter($login);
            $results[$cache_key] = false;
            return false;
        }
        
        $current_catalogue = (array)$nc_core->catalogue->get_by_host_name($_SERVER['HTTP_HOST']);
        $catalogue = nc_array_value($current_catalogue, 'Catalogue_ID', 0);

        $bind_to_catalogue = '';
        if ($nc_core->modules->get_by_keyword('auth') && $nc_core->get_settings('bind_to_catalogue', 'auth')) {
            $bind_to_catalogue = " AND `u`.Catalogue_ID IN(0, {$catalogue})";
        }

        $user_result = $this->db->get_results(
            "SELECT u.*, ug.`PermissionGroup_ID` AS PermissionGroups_ID
             FROM `User` as u, `User_Group` as ug
             WHERE u.`{$this->core->AUTHORIZE_BY}` = '{$this->db->escape($login)}'
             AND u.`Password` = {$this->core->MYSQL_ENCRYPT}('{$this->db->escape($password)}')
             AND u.`Checked` = 1
             AND u.User_ID = ug.`User_ID`
             {$bind_to_catalogue}
             ORDER BY ug.ID",
            ARRAY_A
        );

        $this->update_user_login_counter($login, (bool)$user_result);

        if (!$user_result) {
            return false;
        }

        $AUTH_USER_ID = $user_result[0]['User_ID'];

        $nc_core->event->execute(nc_Event::BEFORE_USER_AUTHORIZED, $AUTH_USER_ID);

        $this->create_session($AUTH_USER_ID);
        $this->init_user($user_result);

        $nc_core->event->execute(nc_Event::AFTER_USER_AUTHORIZED, $AUTH_USER_ID);

        $results[$cache_key] = $AUTH_USER_ID;

        return $results[$cache_key];
    }

    public function authorize_by_id($user_id, $auth_variant = NC_AUTHTYPE_LOGIN, $isInsideAdmin = 0, $create_session = 1) {
        $db = $this->db;
        $user_id = (int)$user_id;

        $user_result = $db->get_results(
            "SELECT u.*, ug.`PermissionGroup_ID` AS PermissionGroups_ID
             FROM `User` as u, `User_Group` as ug
             WHERE u.`User_ID` = '{$user_id}'
             AND u.`Checked` = 1
             AND u.User_ID = ug.`User_ID`
             ORDER BY ug.ID",
            ARRAY_A
        );

        // пользователь не найден
        if (!$user_result) {
            return false;
        }

        // Авторизованные пользователи
        $AUTH_USER_ID = $user_result[0]['User_ID'];

        $this->core->event->execute(nc_Event::BEFORE_USER_AUTHORIZED, $AUTH_USER_ID);

        $this->create_session($user_id, 'authorize', 0, $auth_variant);
        $this->init_user($user_result);

        $this->core->event->execute(nc_Event::AFTER_USER_AUTHORIZED, $AUTH_USER_ID);

        return $AUTH_USER_ID;
    }

    /**
     * Попытка аутентификации
     *
     * @return bool|int
     */
    public function attempt_to_authorize() {
        global $perm;
        global $PHP_AUTH_USER, $PHP_AUTH_PW;
        global $AUTH_USER_ID, $AUTH_USER_GROUP;

        if ($perm instanceof Permission) {
            return $AUTH_USER_ID;
        }

        $nc_core = $this->core;
        $db = $this->db;

        // Проверка залогиненности на SSO-сервере
        if ($_SERVER['REQUEST_METHOD'] === 'GET' && $nc_core->get_settings('SsoEnabled')) {
            try {
                $sitemanager_auth = nc_sitemanager_auth::get_instance();
                if ($sitemanager_auth->has_access_token() && !$sitemanager_auth->is_logged_in()) {
                    Unauthorize();
                }
            } catch (nc_sitemanager_exception $e) {
                trigger_error($e->getMessage(), E_USER_WARNING);
                Unauthorize();
            }
        }

        $AUTH_USER_ID = 0;
        $AUTH_USER_GROUP = 0;

        $SessionTime = time() + ($nc_core->ADMIN_AUTHTIME ?: 24 * 3600);
        $current_catalogue = (array)$nc_core->catalogue->get_by_host_name($_SERVER['HTTP_HOST']);
        $catalogue = nc_array_value($current_catalogue, 'Catalogue_ID', 0);

        if ($nc_core->AUTHORIZATION_TYPE === 'session') {
            if (isset($_SESSION['User']['IsLogin'])) {
                if ($_SESSION['User']['IP'] !== getenv('REMOTE_ADDR')) {
                    header('Location: ' . $nc_core->SUB_FOLDER);
                    exit;
                }
                if ((time() - $_SESSION['User']['datetime']) > ini_get('session.gc_maxlifetime')) {
                    unset($_SESSION['User']);
                }
            }
            $_SESSION['User']['datetime'] = time();
        }

        if ($nc_core->AUTHORIZATION_TYPE === 'http') {
            $user_result = $db->get_results(
                "SELECT u.*, ug.`PermissionGroup_ID` AS PermissionGroups_ID
                 FROM `User` AS u, `User_Group` AS ug
                 WHERE u.`{$nc_core->AUTHORIZE_BY}` = '{$db->escape($PHP_AUTH_USER)}'
                 AND Password = {$nc_core->MYSQL_ENCRYPT}('{$db->escape($PHP_AUTH_PW)}')
                 AND u.`User_ID` = ug.`User_ID`",
                ARRAY_A
            );
        } else {
            $s = $this->get_session_id();
            $bind_to_catalogue = '';
            $check_ip = '';
    
            if ($nc_core->modules->get_by_keyword('auth')) {
                if ($nc_core->get_settings('bind_to_catalogue', 'auth')) {
                    $bind_to_catalogue = " AND `u`.Catalogue_ID IN(0, {$catalogue})";
                }
                
                $ip_check_level = (int)$this->core->get_settings('ip_check_level', 'auth');
    
                if ($ip_check_level === 0) {
                    $check_ip = '';
                } elseif ($ip_check_level === 4) {
                    $check_ip = ' AND s.UserIP = '.sprintf('%u', ip2long($_SERVER['REMOTE_ADDR']));
                } else {
                    $ip_parts = explode('.', $_SERVER['REMOTE_ADDR'], $ip_check_level + 1);
                    array_pop($ip_parts);
                    $ip_mask = implode('.', $ip_parts);
                    $user_ip_begin = sprintf('%u', ip2long($ip_mask . str_repeat('.0', 4 - $ip_check_level)));
                    $user_ip_end = sprintf('%u', ip2long($ip_mask . str_repeat('.255', 4 - $ip_check_level)));
                    $check_ip = ' AND s.UserIP >= ' . $user_ip_begin . ' AND s.UserIP <= ' . $user_ip_end;
                }
            }
            
            $user_result = $db->get_results(
                "SELECT u.*, ug.`PermissionGroup_ID` AS PermissionGroups_ID, s.`LoginSave`, s.`AuthVariant`, s.SessionTime
                 FROM (`User` AS u, `User_Group` AS ug)
                 RIGHT JOIN Session AS s ON u.User_ID = s.User_ID
                 WHERE u.Checked = 1
                 AND u.`User_ID` = ug.`User_ID`
                 AND s.Session_ID = '{$db->escape($s)}'
                 AND s.SessionTime > " . time() . $check_ip . $bind_to_catalogue,
                ARRAY_A
            );
        }

        // Гости
        if (!$user_result) {
            $session_id = session_id();
            $PHP_AUTH_USER = '';
            $PHP_AUTH_PW = '';

            if ($nc_core->modules->get_by_keyword('auth')) {
                $update_res = $db->query("UPDATE Session SET SessionTime = IF (SessionTime = {$SessionTime}, SessionTime + 1, {$SessionTime}) WHERE Session_ID = '{$session_id}'");
                if (!$update_res) {
                    $db->query(
                        "INSERT INTO Session (Session_ID, User_ID, SessionStart, SessionTime, UserIP, Catalogue_ID)
                         VALUES ('{$session_id}', 0, " . time() . ", {$SessionTime}, " . sprintf('%u', ip2long($_SERVER['REMOTE_ADDR'])) . ", {$catalogue})"
                    );
                    // чистим гостевые сессии
                    if (!rand(0, 50)) {
                        $db->query("DELETE FROM Session WHERE User_ID = 0 AND SessionTime < " . ($SessionTime - 300));
                    }
                }
            }

            return false;
        }

        if ($user_result[0]['AuthVariant'] == NC_AUTHTYPE_HASH) {
            $nc_auth = nc_auth::get_object();
            if (!$nc_auth->hash->check(0, 0)) {
                unset($user_result); // проверка не прошла
                return false;
            }
        }

        // Авторизованные пользователи
        $AUTH_USER_ID = $user_result[0]['User_ID'];

        $this->create_session($AUTH_USER_ID, 'attempt', $user_result[0]['LoginSave']);
        $this->init_user($user_result);

        return $AUTH_USER_ID;
    }

    public function init_user($user_result) {
        global $AUTH_USER_ID, $AUTH_USER_GROUP, $PHP_AUTH_USER;
        global $current_user, $perm, $nc_core;

        $AUTH_USER_ID = $user_result[0]['User_ID'];
        $this->data[$AUTH_USER_ID] = $user_result[0];
        $this->data[$AUTH_USER_ID]['_nc_final'] = 0;
        unset($this->data[$AUTH_USER_ID]['PermissionGroups_ID']);
        foreach ($user_result as $row) {
            $this->data[$AUTH_USER_ID]["Permission_Group"][] = $row['PermissionGroups_ID'];
        }

        $AUTH_USER_GROUP = $this->data[$AUTH_USER_ID]['PermissionGroup_ID'];
        $PHP_AUTH_USER = $this->data[$AUTH_USER_ID][$nc_core->AUTHORIZE_BY];

        $this->current = $current_user = $this->get_by_id($AUTH_USER_ID);
        $perm = new Permission($AUTH_USER_ID, 0, $user_result);

        nc_security_2fa_logon::check($this->get_2fa_for_current_session());

        return $AUTH_USER_ID;
    }

    /**
     * Возвращает экземпляр nc_security_2fa для текущей сессии
     *
     * @return nc_security_2fa
     */
    public function get_2fa_for_current_session() {
        return new nc_security_2fa($this->get_current('User_ID'), $this->get_session_id());
    }

    public function create_session($user_id, $auth_phase = 'authorize', $login_save = 0, $auth_variant = NC_AUTHTYPE_LOGIN) {
        global $PHP_AUTH_LANG;
        $db = $this->db;
        $PHP_AUTH_LANG = $this->core->lang->detect_lang();
        $auth_variant = (int)$auth_variant;
        $post_login_save = $this->core->input->fetch_post('loginsave');

        // сохранять авторизацию ( перенести проверку поста в вызывающий метод )
        $LoginSave = (($login_save || $post_login_save || $this->core->ADMIN_AUTHTYPE === 'always') ? 1 : 0);

        $UserIP = sprintf('%u', ip2long($_SERVER['REMOTE_ADDR']));
        $session_id = $this->get_session_id();
        if (!$session_id) {
            $session_id = md5(mt_rand() . $user_id . $UserIP);
        }

        $session_id = $db->escape($session_id);

        $SessionStart = time();
        $SessionTime = $SessionStart + ($this->core->ADMIN_AUTHTIME ?: 30 * 24 * 3600);

        $current_catalogue = (array)$this->core->catalogue->get_by_host_name($_SERVER['HTTP_HOST']);
        $catalogue = nc_array_value($current_catalogue, 'Catalogue_ID', 0);

        if ($auth_phase === 'authorize') {
            $db->query("DELETE FROM `Session` WHERE `SessionTime` < '{$SessionStart}'" . ($session_id ? " OR `Session_ID` = '{$session_id}'" : ''));
            $db->query(
                "INSERT INTO `Session` SET
                 `Session_ID` = '{$session_id}',
                 `User_ID` = '{$user_id}',
                 `SessionStart` = '{$SessionStart}',
                 `SessionTime` = '{$SessionTime}',
                 `UserIP` = '{$UserIP}',
                 `LoginSave` = '{$LoginSave}',
                 `Catalogue_ID` = '{$catalogue}',
                 `AuthVariant` = '{$auth_variant}'"
            );
        } else {
            $db->query("UPDATE `Session` SET `SessionTime` = '{$SessionTime}', `UserIP` = '{$UserIP}' WHERE `Session_ID` = '{$session_id}'");
        }

        switch ($this->core->AUTHORIZATION_TYPE) {
            // SESSION авторизация/валидация
            case 'session':
                $_SESSION['User']['ID'] = $user_id;
                $_SESSION['User']['PHP_AUTH_LANG'] = $PHP_AUTH_LANG;
                $_SESSION['User']['datetime'] = $SessionStart;
                $_SESSION['User']['IsLogin'] = '1';
                $_SESSION['User']['IP'] = $_SERVER['REMOTE_ADDR'];
                break;

            // COOKIE авторизация/валидация
            case 'cookie':
                // не обновляем куку при каждом запросе, так как это помешает кэшированию
                // страниц веб-сервером (ограничение времени жизни сеанса обеспечивается
                // записью в `Session`)
                $cookie_expiration = $LoginSave ? $SessionStart + $this->cookie_lifetime : 0;
                $cookies = array(
                    'PHP_AUTH_SID' => $session_id,
                    'PHP_AUTH_LANG' => $PHP_AUTH_LANG,
                );

                foreach ($cookies as $cookie_name => $cookie_value) {
                    if ($this->core->input->fetch_cookie($cookie_name) != $cookie_value) {
                        $this->core->cookie->set($cookie_name, $cookie_value, $cookie_expiration, $cookie_name === 'PHP_AUTH_SID');
                    }
                }
                break;
        }

        return $session_id;
    }

    protected function get_session_id() {
        return $this->core->AUTHORIZATION_TYPE === 'session' ?
            session_id() :
            $this->core->input->fetch_cookie('PHP_AUTH_SID');
    }

    /**
     * Обновляет счётчик попыток входа в систему (если есть модуль auth и в нём включена каптча при неудачном входе)
     * @param $login
     * @param bool $reset_counter
     */
    protected function update_user_login_counter($login, $reset_counter = false) {
        $nc_core = nc_core::get_object();
        if ($nc_core->get_settings('AuthCaptchaEnabled')) {
            $attempt_count = $reset_counter ? 0 : '`ncAttemptAuth` + 1';
            $db = $nc_core->db;
            $db->query(
                "UPDATE `User`
                    SET `ncAttemptAuth` = {$attempt_count},
                        `LastUpdated` = `LastUpdated`
                  WHERE `{$nc_core->AUTHORIZE_BY}` = '{$db->escape($login)}'"
            );
            $hash = $this->get_remote_hash_for_captcha_check();
            if ($reset_counter) {
                $db->query("DELETE FROM `Auth_FailedAttempt` WHERE `RemoteHash` = '$hash'");
            } else {
                $db->query(
                    "INSERT INTO `Auth_FailedAttempt` (`RemoteHash`, `AttemptCount`) VALUES ('$hash', 1) 
                     ON DUPLICATE KEY UPDATE `AttemptCount` = `AttemptCount` + 1"
                );
            }
        }
    }

    /**
     * @param $user_id
     */
    public function update_cache_on_change($user_id) {
        nc_core::get_object()->file_info->clear_object_cache('User', $user_id);
        foreach ((array)$user_id as $id) {
            unset($this->data[$id]);
        }
    }

    /**
     * Проверка необходимости вывода каптчи в форме аутентификации.
     * @return bool
     */
    public function captcha_is_required() {
        // капча выключена? модуль captcha выключен? не будет капчи
        if (!$this->core->get_settings('AuthCaptchaEnabled') || !nc_module_check_by_keyword('captcha')) {
            return false;
        }

        $allowed_number_of_logon_attempts_without_captcha = $this->core->get_settings('AuthCaptchaAttempts');
        if (!$allowed_number_of_logon_attempts_without_captcha) {
            return true;
        }

        // Проверка количества предыдущих неудачных попыток аутентификации по логину
        if ($this->core->input->fetch_get_post('AuthPhase') && ($name = $this->core->input->fetch_get_post('AUTH_USER'))) {
            $attempts = $this->db->get_var("SELECT `ncAttemptAuth` FROM `User` WHERE `{$this->core->AUTHORIZE_BY}` = '{$this->db->escape($name)}'");
            if ($attempts >= $allowed_number_of_logon_attempts_without_captcha) {
                return true;
            }
        }

        // Удаляем старые записи о неудачных попытках аутентификации
        $this->db->query("DELETE FROM `Auth_FailedAttempt` WHERE `LastAttempt` < DATE_SUB(NOW(), INTERVAL {$this->keep_failed_login_info_interval})");
        // Проверка количества предыдущих неудачных попыток аутентификации по IP
        $hash = $this->get_remote_hash_for_captcha_check();
        $attempts = $this->db->get_var("SELECT `AttemptCount` FROM `Auth_FailedAttempt` WHERE `RemoteHash` = '$hash'");

        return $attempts >= $allowed_number_of_logon_attempts_without_captcha;
    }

    /**
     * Возвращает истину, если был неправильно введён код каптчи.
     * @return bool
     */
    public function captcha_is_invalid() {
        return $this->captcha_is_invalid;
    }

    /**
     * Возвращает истину, если код каптчи не был введён, когда он был необходим.
     * @return bool
     */
    public function captcha_is_missing() {
        return $this->captcha_is_missing;
    }

    /**
     * @return string
     */
    protected function get_remote_hash_for_captcha_check() {
        return md5($_SERVER['REMOTE_ADDR'] . nc_array_value($_SERVER, 'HTTP_X_FORWARDED_FOR', ''));
    }

}
