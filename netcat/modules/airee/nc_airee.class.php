<?php

class nc_airee {

    /** @var self[] */
    static protected $instances = array();

    /** @var int */
    protected $site_id;

    /** @var nc_Core */
    protected $core;

    static protected $idn;
    static protected $idn_cache = array();

    const ACCOUNT_LINK = 'https://xn--80aqc2a.xn--p1ai/my/site/';
    const API_LINK = 'https://xn--80aqc2a.xn--p1ai/my/site/api/';
    const BALANCE_LINK = 'https://xn--80aqc2a.xn--p1ai/my/site/balance/';
    const PROFILE_LINK = 'https://xn--80aqc2a.xn--p1ai/my/site/profile/';
    const DOMAIN_SUFFIX = '.airee.ru';
    const TIMEOUT_UNTIL_DOMAIN_REGISTRATION_COMPLETE = 120;
    const NUMBER_OF_ATTEMPTS_TO_GET_A_BALANCE = 3;
    const START_BALANCE_VALUE = 500;
    const DEFAULT_SOURCE = 'netcat';

    /**
     * @param int|null $site_id
     * @return self
     */
    public static function get_instance($site_id = null) {
        static $instances = array();
        $site_id = (int)$site_id;
        if (!$site_id) {
            $site_id = nc_Core::get_object()->catalogue->get_current('Catalogue_ID');
        }

        if (!isset($instances[$site_id])) {
            $instances[$site_id] = new self($site_id);
        }

        return $instances[$site_id];
    }

    /**
     * @param int $site_id
     */
    protected function __construct($site_id) {
        $this->site_id = $site_id;
        $this->core = nc_Core::get_object();
    }

    /**
     * @return Net_IDNA2
     */
    protected static function get_idn_converter() {
        if (!self::$idn) {
            require_once 'Net/IDNA2.php'; // netcat/require/lib
            self::$idn = new Net_IDNA2;
        }

        return self::$idn;
    }

    /**
     * @param string $host ТОЛЬКО домен, например "испытание.рф"
     * @return string
     * @throws Exception
     */
    public static function encode_host($host) {
        if (!preg_match("/[^\w\-\.]/", $host)) {
            return $host;
        }
        $host = trim($host, " \t\n\r");
        if (!isset(self::$idn_cache[$host])) {
            try {
                self::$idn_cache[$host] = self::get_idn_converter()->encode($host);
            } catch (Net_IDNA2_Exception $e) {
                trigger_error("Cannot convert host name '$host' to punycode: {$e->getMessage()}", E_USER_WARNING);
                return $host;
            } catch (UnexpectedValueException $e) {
                trigger_error("Cannot convert host name '$host' to punycode: {$e->getMessage()}", E_USER_WARNING);
                return $host;
            }
        }
        return self::$idn_cache[$host];
    }

    /**
     * @param string $action
     * @param array $params
     * @return string
     */
    protected function make_link($action, array $params = array()) {
        if ($action !== 'register' && !($key = $this->get_settings('API_Key'))) {
            return false;
        }
       
        $source = self::DEFAULT_SOURCE;

        return self::API_LINK . '?' . http_build_query(compact('key', 'action', 'source')) . ($params ? '&' . http_build_query($params) : '');
    }

    /**
     * @param string $action
     * @param array $params
     * @param string $item
     * @return bool|mixed
     */
    public function get_response($action, array $params = array(), $item) {
        $response = null;
        if ($this->make_link($action, $params) && !($response = file_get_contents($this->make_link($action, $params)))) {
            return false;
        }

        if (!($decoded_response = json_decode($response, true))) {
            return false;
        }

        if (!empty($decoded_response['error'])) {
            return false;
        }

        if ($item) {
            return isset($decoded_response[$item]) ? $decoded_response[$item] : false;
        }

        return $decoded_response;
    }

    /**
     * @param string $email
     * @return string API_KEY
     */
    public function register($email) {
        return $this->get_response('register', compact('email'), 'success');
    }

    /**
     * @param string $domain
     * @return bool
     */
    public function add_domain($domain) {
        return $this->get_response('add', compact('domain'), 'success') === 'OK';
    }

    /**
     * @param int $attempt_number
     * @return bool|mixed
     */
    public function get_balance($attempt_number = 1) {
        $settings = $this->get_settings('', true);

        if (!empty($settings['Last_Balance_Check_At'])) {
            $last_check_date = DateTime::createFromFormat('U', $settings['Last_Balance_Check_At']);
            $last_check_date->modify('+1 day');

            if (date('H') < 8 || (time() - $last_check_date->getTimestamp()) < 0) {
                return $settings['Balance'];
            }
        }

        if ($balance = $this->get_response('get.balance', array(), 'balance')) {
            $this->save_balance($balance);

            return $balance;
        }

        return ($attempt_number > self::NUMBER_OF_ATTEMPTS_TO_GET_A_BALANCE) ? false : $this->get_balance(++$attempt_number);
    }

    /**
     * @param string $balance
     */
    public function save_balance($balance) {
        $this->set_settings('Balance', $balance);
        $this->set_settings('Last_Balance_Check_At', time());
    }

    /**
     * @param string $cdn_type значение из настроек CSS | JavaScript | Images | Media_Files
     * @return bool
     */
    public function is_cdn_enabled($cdn_type) {
        if (!$this->get_settings('API_Key')) {
            return false;
        }

        if ($this->get_balance() < 0) {
            return false;
        }

        return (bool)$this->get_settings('Use_' . $cdn_type . '_CDN');
    }

    /**
     * @param string $item
     * @param bool $reset
     * @return mixed
     */
    public function get_settings($item = '', $reset = false) {
        return $this->core->get_settings($item, 'airee', $reset, $this->site_id);
    }

    /**
     * @param string $key
     * @param bool $value
     * @return bool
     */
    public function set_settings($key = '', $value = false) {
        return $this->core->set_settings($key, $value, 'airee', $this->site_id);
    }

    /**
     * @return bool|string
     */
    public function get_airee_domain() {
        $domain = $this->get_settings('Domain');

        if (!$domain) {
            return false;
        }

        return strpos($domain, self::DOMAIN_SUFFIX) === false ? $domain . self::DOMAIN_SUFFIX : $domain;
    }

    /**
     * @param string $buffer
     * @return null|string|string[]
     */
    public function replace_resources($buffer) {
        $domain = $this->get_settings('Domain');

        if (!$domain) {
            return $buffer;
        }

        $airee_domain = $this->get_airee_domain();

        if (!$airee_domain) {
            return $buffer;
        }

        if ($this->is_cdn_enabled('CSS')) {
            $buffer = preg_replace("!(<link[^>]+rel=['\"]stylesheet['\"][^>]+href=['\"])(https?:)?(//{$domain})?(/[^/].*?)(['\"])!is", "$1//{$airee_domain}$4$5", $buffer);
            $buffer = preg_replace("!(<link[^>]+href=['\"])(https?:)?(//{$domain})?(/[^/].*?)(['\"][^>]+rel=['\"]stylesheet['\"])!is", "$1//{$airee_domain}$4$5", $buffer);
        }

        if ($this->is_cdn_enabled('JavaScript')) {
            $buffer = preg_replace("!(<script[^>]+src=['\"])(https?:)?(//{$domain})?(/[^/].*?)(['\"][^>]*>)!is", "$1//{$airee_domain}$4$5", $buffer);
        }

        if ($this->is_cdn_enabled('Images')) {
            $buffer = preg_replace("!(<img[^>]+src=['\"])(https?:)?(//{$domain})?(/[^/].*?)(['\"][^>]*>)!is", "$1//{$airee_domain}$4$5", $buffer);
        }

        if ($this->is_cdn_enabled('Media_Files')) {
            $buffer = preg_replace("!(<(source|audio|object)[^>]+src=['\"])(https?:)?(//{$domain})?(/[^/].*?)(['\"][^>]*>)!is", "$1//{$airee_domain}$5$6", $buffer);
        }

        return $buffer;
    }

    /**
     * @return string
     */
    public function get_api_key_description() {
        return sprintf(NETCAT_MODULE_AIREE_SETTINGS_API_KEY_DESCRIPTION, self::ACCOUNT_LINK, self::PROFILE_LINK);
    }

    /**
     * @return string
     */
    public function get_balance_description() {
        return sprintf(NETCAT_MODULE_AIREE_SETTINGS_BALANCE_DESCRIPTION, self::BALANCE_LINK);
    }

    /**
     * @return string
     */
    public function get_balance_label() {
        return sprintf(NETCAT_MODULE_AIREE_SETTINGS_BALANCE_VALUE, $this->get_balance());
    }

    /**
     * @return string
     */
    public function get_balance_add_funds_link() {
        return $this->make_link('pay');
    }
}