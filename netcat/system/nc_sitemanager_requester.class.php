<?php

abstract class nc_sitemanager_requester {

    /**
     * @return static
     */
    public static function get_instance() {
        if (!nc_core::get_object()->get_settings('SsoEnabled')) {
            throw new nc_sitemanager_exception('SSO is not enabled');
        }
        return new static();
    }

    /**
     *
     */
    protected function __construct() {
    }

    /**
     * Выполняет POST-запрос на указанный адрес с указанными параметрами;
     * если получен JSON-ответ — десериализует его и возвращает его в виде массива.
     *
     * @param $url
     * @param $parameters
     * @return bool|array
     */
    protected function fetch_json_data($url, $parameters) {
        $result = $this->make_post_request($url, $parameters);
        if (!$result) {
            return false;
        }

        $data = json_decode($result, true);

        return is_array($data) ? $data : false;
    }

    /**
     * @return bool
     */
    protected function can_make_curl_requests() {
        return function_exists('curl_init');
    }

    /**
     * @return bool
     */
    protected function can_make_stream_requests() {
        return in_array('https', stream_get_wrappers());
    }

    /**
     * @param $url
     * @param $parameters
     * @return null|string
     */
    protected function make_post_request($url, $parameters) {
        if ($this->can_make_curl_requests()) {
            $h = curl_init();

            curl_setopt($h, CURLOPT_URL, $url);
            curl_setopt($h, CURLOPT_POST, true);
            curl_setopt($h, CURLOPT_POSTFIELDS, http_build_query($parameters, null, '&'));
            curl_setopt($h, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($h, CURLOPT_HEADER, false);

            if (!nc_core::get_object()->get_settings('SsoVerifyCertificate')) {
                curl_setopt($h, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($h, CURLOPT_SSL_VERIFYHOST, false);
            }

            $result = curl_exec($h);

            curl_close($h);
        } elseif ($this->can_make_stream_requests()) {
            $request_context = stream_context_create(array(
                'http' => array(
                    'method' => 'POST',
                    'header' => "Content-type: application/x-www-form-urlencoded\r\n",
                    'content' => http_build_query($parameters, null, '&'),
                ),
            ));

            $result = file_get_contents($url, null, $request_context);
        } else {
            throw new nc_sitemanager_exception('Cannot make HTTPS request: neither cURL nor HTTPS stream reading are available');
        }

        return $result;
    }

    /**
     * @return string
     */
    protected function get_copy_id() {
        return nc_core::get_object()->get_copy_id();
    }

    /**
     * @return string
     */
    protected function get_random_string() {
        $random = '';
        for ($i = 0; $i < 5; $i++) {
            $random .= mt_rand(0, PHP_INT_MAX);
        }
        return trim(base64_encode(sha1($random, true)), '=');
    }

    /**
     * Возвращает значение настройки AuthSecret (инициализирует её при необходимости).
     *
     * @return string
     */
    public function get_client_secret() {
        $nc_core = nc_core::get_object();
        $secret = $nc_core->get_settings('AuthSecret');
        if (!$secret) {
            $secret = $this->get_random_string();
            $nc_core->set_settings('AuthSecret', $secret);
        }
        return $secret;
    }

    /**
     * Текущий протокол + хост + порт
     *
     * @return string
     */
    protected function get_current_host() {
        static $uri_host;

        if (!$uri_host) {
            $https = nc_array_value($_SERVER, 'HTTPS');
            $uri_host = (($https && $https != 'off') ? 'https' : 'http') .
                '://' .
                $_SERVER['HTTP_HOST'] .
                (($_SERVER['SERVER_PORT'] != 80 && $_SERVER['SERVER_PORT'] != 443)
                    ? ":$_SERVER[SERVER_PORT]"
                    : '');
        }

        return $uri_host;
    }

    /**
     * Возвращает access token (если его действие истекло — пытается получить
     * новый access token с использованием refresh token)
     *
     * @return string|bool
     */
    protected function get_access_token() {
        return nc_sitemanager_auth::get_instance()->get_access_token();
    }

}