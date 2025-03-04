<?php

/**
 * Class nc_Security
 */

class nc_Security extends nc_System {

    /** @var  nc_security_filter_sql */
    public $sql_filter;

    /** @var  nc_security_filter_php */
    public $php_filter;

    /** @var  nc_security_filter_xss */
    public $xss_filter;

    /**
     *
     */
    public function __construct() {
        parent::__construct();
        $this->sql_filter = new nc_security_filter_sql();
        $this->php_filter = new nc_security_filter_php();
        $this->xss_filter = new nc_security_filter_xss();
    }

    /**
     * @internal
     */
    public function init_filters() {
        $nc_core = nc_core::get_object();
        $this->sql_filter->set_mode($nc_core->get_settings('SecurityInputFilterSQL'));
        $this->php_filter->set_mode($nc_core->get_settings('SecurityInputFilterPHP'));
        $this->xss_filter->set_mode($nc_core->get_settings('SecurityInputFilterXSS'));
    }

    /**
     * Добавляет данные к проверяемым значениям
     * @param array[] $additional_input (ключ — '_GET', '_POST', '_COOKIE', '_SESSION')
     */
    public function add_checked_input(array $additional_input) {
        $this->sql_filter->add_checked_input($additional_input);
        $this->php_filter->add_checked_input($additional_input);
        $this->xss_filter->add_checked_input($additional_input);
    }

    /**
     * Проверяет, находится ли путь в пределах одного из сайтов под управлением системы
     *
     * @param $url
     * @return bool
     */
    public function url_matches_local_site($url) {
        $double_slash_position = strpos($url, '//');
        if ($double_slash_position === false) {
            return true;
        }

        // parse_url() до PHP 5.4.7 не поддерживает ссылки без указания протокола (https://bugs.php.net/bug.php?id=66274)
        if ($double_slash_position === 0) {
            $url = 'http:' . $url;
        }

        $host_name = parse_url($url, PHP_URL_HOST);
        $host_name_regexp = '/\s' . preg_quote($host_name, '/') . '\s/';
        foreach (nc_core::get_object()->catalogue->get_all() as $site) {
            if (preg_match($host_name_regexp, " $site[Domain] $site[Mirrors] ")) {
                return true;
            }
        }

        return false;
    }

}