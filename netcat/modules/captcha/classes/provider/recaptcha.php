<?php

/**
 * Recaptcha
 *
 * Настройки в Settings:
 *   Recaptcha_SiteKey — публичный ключ
 *   Recaptcha_SecretKey — секретный ключ
 *   RemovedLegacyText — текст, который убирается из формы
 *   RemovedLegacyBlocks — блоки, которые убираются из формы
 *
 */
class nc_captcha_provider_recaptcha extends nc_captcha_provider {

    const VERIFY_URL = 'https://www.google.com/recaptcha/api/siteverify';

    /** @var int таймаут ожидания ответа от google.com в секундах */
    protected $request_timeout = 10;

    /** @var int время жизни кэша ответов от google.com в секундах (для проверки результата без инвалидации задания) */
    protected $cache_lifetime = 300;

    /** @var array локальный кэш результатов (чтобы не лазить лишний раз в БД в пределах одного запроса) */
    protected $result_cache = array();

    /** @var bool  */
    protected $script_emitted = false;

    /**
     * @return null
     */
    public function generate_challenge_id() {
        return null;
    }

    /**
     * Создаёт задание для каптчи.
     *
     * @param null $challenge_id
     * @return null
     */
    public function generate_challenge($challenge_id = null) {
        return null;
    }

    /**
     * Создаёт новое задание и возвращает информацию о нём (обновляет капчу)
     * Задание возвращается в виде, необходимом для работы конкретной капчи
     * @see /netcat/modules/captcha/index.php
     *
     * @return null
     */
    public function get_new_challenge_data() {
        return null;
    }

    /**
     * Возвращает код для вставки в форму.
     *
     * @param string $challenge_id
     * @param array $parameters
     *     — context => контекст, в котором выполнен вызов метод
     *       Используемые значения:
     *       — js_template — генерируется JavaScript-шаблон (метод вызван из nc_comments::wall())
     *     – data-* => атрибуты data- для кастомизации (https://developers.google.com/recaptcha/docs/display)
     *     Параметры скрипта recaptcha (https://developers.google.com/recaptcha/docs/display)
     *     – onload => колбэк после загрузки скрипта recaptcha
     *     – render => тип отрисовки
     *     – hl => код языка (https://developers.google.com/recaptcha/docs/language)
     * @return string
     */
    public function get_challenge_html($challenge_id = null, array $parameters = array()) {
        $attributes = array(
            'class' => 'g-recaptcha',
            'data-sitekey' => $this->get_setting('Recaptcha_SiteKey'),
            'data-callback' => 'nc_recaptcha_save',
        );

        foreach ($parameters as $key => $value) {
            if (substr($key, 0, 5) === 'data-') {
                $attributes[$key] = $value;
            }
        }

        $data_attributes_string = nc_make_attribute_string_from_array($attributes);

        return
            "<div " . $data_attributes_string['result'] . "></div>" .
            $this->get_challenge_js($parameters);
    }

    /**
     * @param array $parameters
     * @return string
     */
    protected function get_challenge_js(array $parameters) {
        if (nc_array_value($parameters, 'context') !== 'js_template') {
            // Скрипты отдаются только один раз
            if ($this->script_emitted) {
                return '';
            }
            $this->script_emitted = true;
        }

        $js = '<script>'.
               // Скрипт, прячущий лишний текст от встроенной каптчи
               $this->get_remove_legacy_markup_js() .
               // Скрипт, отрисовывающий рекапчу и переносящий значение в nc_captcha_code
               $this->get_js_file('nc_recaptcha.min.js') .
               '</script>';

        // Для совместимости с шаблонами v4 меняем кавычки на одинарные
        // (предполагается, что все кавычки в .min.js — двойные)
        $js = str_replace('"', "'", $js);

        // Скрипт reCAPTCHA
        $script_params = http_build_query(array(
            'hl' => nc_array_value($parameters, 'hl') ?: nc_core::get_object()->subdivision->get_current('Language') ?: 'ru',
            'onload' => nc_array_value($parameters, 'onload') ?: 'nc_recaptcha_render',
            'render' => nc_array_value($parameters, 'render') ?: 'explicit',
        ), null, '&amp;');

        $js .= "<script src='https://www.google.com/recaptcha/api.js?$script_params' async></script>";

        return $js;
    }

    /**
     * Проверяет ответ пользователя на задание.
     *
     * @param string $user_response
     * @param string $challenge_id
     * @param bool $invalidate_challenge
     * @return bool
     */
    public function verify_user_response($user_response, $challenge_id = null, $invalidate_challenge = true) {
        $result = $this->get_verification_result($user_response, !$invalidate_challenge);
        if ($invalidate_challenge) {
            $this->delete_cached_verification_result($user_response);
        }
        return !empty($result['success']);
    }

    /**
     * Возвращает результат проверки ответа пользователя в виде массива.
     *
     * @param string $user_response
     * @param bool $should_cache_result
     * @return array
     */
    protected function get_verification_result($user_response, $should_cache_result) {
        $cached_verification_result = $this->get_cached_verification_result($user_response);
        $verification_result = $cached_verification_result ?: $this->make_verification_request($user_response);

        if (!$verification_result) {
            return array('success' => false, 'error-codes' => array('netcat-no-response'));
        }

        $result = json_decode($verification_result, true);
        if (!$result) {
            return array('success' => false, 'error-codes' => array('netcat-json-error'));
        }

        // запоминаем успешные результаты
        if (!$cached_verification_result && $should_cache_result && !empty($result['success'])) {
            $this->cache_verification_result($user_response, $verification_result);
        }

        return $result;
    }

    /**
     * Выполняет запрос к серверу google.com для проверки ответа пользователя.
     *
     * @param $user_response
     * @return string|false
     */
    protected function make_verification_request($user_response) {
        $data = array(
            'secret' => $this->get_setting('Recaptcha_SecretKey'),
            'response' => $user_response,
        );

        $curl = curl_init(self::VERIFY_URL);
        curl_setopt_array($curl, array(
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($data, null, '&'),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_USERAGENT => 'Netcat CMS',
            CURLOPT_TIMEOUT => $this->request_timeout,
        ));

        $recaptcha_response = curl_exec($curl);

        return $recaptcha_response;
    }

    /**
     * Встроенная каптча имеет возможность проверить код несколько раз без отметки
     * его как недействительного/использованного (аргумент $delete_hash в функции
     * nc_captcha_verify_code(). Это использовалось в шаблонах компонентах
     * (могла быть добавлена предварительная проверка результатов каптчи в условия
     * добавления объекта или при аякс-запросах; затем проверка вызывается ещё раз
     * при добавлении записи в message_fields.php).
     *
     * reCAPTCHA позволяет проверить код только один раз, после чего ответ пользователя
     * становится * недействительным.
     *
     * Чтобы не нарушить работу каптчи с такими шаблонами ведётся кэш:
     * — в свойстве result_cache — для проверки в пределах одного запроса;
     * — в базе данных — для ajax и т. п. проверок, происходящих не за один запрос.
     *
     * @param string $user_response
     * @param string $result
     */
    protected function cache_verification_result($user_response, $result) {
        if (!$user_response) { // нет ответа, либо проверка в get_configuration_errors()
            return;
        }

        $this->result_cache[$user_response] = $result;

        $db = nc_core::get_object()->db;
        $db->query(
            "INSERT INTO `Captchas_Recaptcha` 
                SET `Response` = '" . $db->escape($user_response) . "',
                    `Result` = '" . $db->escape($result) . "',
                    `Expires` = DATE_ADD(NOW(), INTERVAL $this->cache_lifetime SECOND)"
        );
    }

    /**
     * Получение результата из кэша
     *
     * @param string $user_response
     * @return string|null
     */
    protected function get_cached_verification_result($user_response) {
        if (!$user_response) { // нет ответа, либо проверка в get_configuration_errors()
            return null;
        }

        if (isset($this->result_cache[$user_response])) {
            return $this->result_cache[$user_response];
        }

        $db = nc_core::get_object()->db;
        // удаление устаревших данных
        $db->query("DELETE FROM `Captchas_Recaptcha` WHERE `Expires` < NOW()");

        return $db->get_var(
            "SELECT `Result` 
               FROM `Captchas_Recaptcha`
              WHERE `Response` = '" . $db->escape($user_response) . "'"
        );
    }

    /**
     * Удаляет закэшированный результат проверки ответа пользователя.
     *
     * @param string $user_response
     */
    protected function delete_cached_verification_result($user_response) {
        unset($this->result_cache[$user_response]);
        $db = nc_core::get_object()->db;
        $db->query("DELETE LOW_PRIORITY FROM `Captchas_Recaptcha` WHERE `Response` = '" . $db->escape($user_response) . "'");
    }

    /**
     * @return array
     */
    public function get_configuration_errors() {
        if (!function_exists('curl_init')) {
            return array(NETCAT_MODULE_CAPTCHA_SETTINGS_RECAPTCHA_NO_CURL);
        }

        if (!$this->get_setting('Recaptcha_SecretKey')) {
            return array(NETCAT_MODULE_CAPTCHA_SETTINGS_RECAPTCHA_ADD_KEYS);
        }

        $response = $this->get_verification_result('', false);
        if (!empty($response['error-codes'])) {
            if (in_array('netcat-no-response', $response['error-codes']) || in_array('netcat-json-error', $response)) {
                return array(NETCAT_MODULE_CAPTCHA_SETTINGS_RECAPTCHA_NO_CONNECTION);
            }

            if (in_array('invalid-input-secret', $response['error-codes'])) {
                return array(NETCAT_MODULE_CAPTCHA_SETTINGS_RECAPTCHA_INVALID_SECRET);
            }
        }

        return array();
    }
}