<?php

/**
 * Работа с куками
 */
class nc_cookie {

    protected $domain;

    /**
     * @return string
     */
    public function get_domain() {
        if (!$this->domain) {
            $this->domain = $this->determine_current_domain();
        }

        return $this->domain;
    }

    /**
     * @return string
     */
    protected function determine_current_domain() {
        $nc_core = nc_core::get_object();
        $domain = $nc_core->url->get_parsed_url('host');
        $domain = trim($domain, '[]'); // скобки в IPv6-адресе

        // IP-адрес: устанавливаем куку на него
        if (filter_var($domain, FILTER_VALIDATE_IP)) {
            return $domain;
        }

        // Определяем домен минимального уровня, который есть в настройках сайтов
        $netcat_domains = $nc_core->catalogue->get_all_domains();
        $cookie_domain = $domain;
        $subdomains = explode('.', $cookie_domain);
        while (count($subdomains) > 1) { // убираем поддомены, пока не дойдём до второго уровня
            $host = implode('.', $subdomains);
            if (in_array($host, $netcat_domains, true)) {
                $cookie_domain = $host;
            }
            array_shift($subdomains);
        }

        // Если нет модуля auth, кука ставится для домена и для поддомена;
        // если есть модуль, то для поддомена, если установлен параметр with_subdomain (по дефолту установлен)
        if ($cookie_domain !== $domain || !nc_module_check_by_keyword('auth') || $nc_core->get_settings('with_subdomain', 'auth')) {
            $cookie_domain = ".$cookie_domain";
        }

        return $cookie_domain;
    }


    /**
     * @param string $name Имя куки
     * @param mixed $value Значение
     * @param int $expires Timestamp, до которого действует кука
     * @param bool $http_only Поставить флаг httponly
     */
    public function set($name, $value, $expires = 0, $http_only = false) {
        setcookie($name, $value, $expires, '/', $this->get_domain(), null, $http_only);
        nc_core::get_object()->input->set('_COOKIE', $name, $value);
        $_COOKIE[$name] = $value;
    }

    /**
     * @param $key
     * @return string|null
     */
    public function get($key){
        return nc_core::get_object()->input->fetch_cookie($key);
    }

    public function remove($name) {
        setcookie($name, null, 1, '/', $this->get_domain());
        nc_core::get_object()->input->set('_COOKIE', $name, false);
        unset($_COOKIE[$name]);
    }

}
