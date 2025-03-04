<?php

class nc_messaging_settings {

    const SERVICE_TYPE_AUTH = "auth";
    const SERVICE_TYPE_SECURITY = "security";
    const SERVICE_TYPE_NETSHOP = "netshop";
    const SERVICE_TYPE_REQUESTS = "requests";
    const SERVICE_TYPE_DEFAULT = "default";


    private static $instances;
    private $site_id;
    private $settings;


    /**
     * @param int $site_id
     */
    private function __construct($site_id) {
        $this->site_id = $site_id;
        $this->settings = $this->get_available_settings();
    }

    /**
     * @param $site_id
     *
     * @return nc_messaging_settings
     */
    public static function get_instance($site_id) {
        if (!isset(self::$instances[$site_id])) {
            self::$instances[$site_id] = new self($site_id);
        }

        return self::$instances[$site_id];
    }

    /**
     * Список из предусмотренных настроек модуля, если они не включены в перечень - значит с ними работа не допускается
     *
     * @return array
     */
    public static function get_allowed_settings_mapping() {
        return array(
            "Enabled" => array(
                "default" => "0",
                "serialized" => false,
            ),
            "ServicesMapping" => array(
                "default" => "a:0:{}",
                "serialized" => true,
            ),
        );
    }

    /**
     * @return bool
     */
    public function is_messaging_enabled() {
        return (bool)nc_array_value($this->settings, 'Enabled');
    }

    /**
     * Возвращает список валидных номеров телефонов, указанных в настройках "Организация" модуля "интернет-магазин"
     *
     *
     * @return array
     */
    public function get_default_netshop_phones() {
        $phones = trim(nc_netshop::get_instance($this->site_id)->get_setting("ManagerPhone"));

        if (!$phones) {
            return array();
        }

        return array_unique(array_map(function ($phone) {
            $phone = trim($phone);

            return nc_normalize_phone_number($phone) ? $phone : "";

        }, explode(",", $phones)));
    }

    /**
     * @param string $type
     *
     * @return int
     */
    public function get_messaging_service_id($type = self::SERVICE_TYPE_DEFAULT) {
        if (isset($this->settings["ServicesMapping"][$type])) {
            return (int)$this->settings["ServicesMapping"][$type]["id"];
        }

        if (isset($this->settings["ServicesMapping"][self::SERVICE_TYPE_DEFAULT])) {
            return (int)$this->settings["ServicesMapping"][self::SERVICE_TYPE_DEFAULT]["id"];
        }

        return 0;
    }

    /**
     * @param string $key
     *
     * @return mixed
     */
    public function get_by_key($key) {
        $allowed_mapping = self::get_allowed_settings_mapping();

        if (!isset($allowed_mapping[$key])) {
            return null;
        }

        return nc_array_value($this->settings, $key);
    }

    /**
     * @return array
     */
    public function get_settings() {
        return $this->settings;
    }

    /**
     * @param string $key
     * @param mixed $value
     *
     * @return bool
     */
    public function save_settings($key, $value) {
        $allowed_mapping = self::get_allowed_settings_mapping();

        if (!isset($allowed_mapping[$key])) {
            return false;
        }

        $db_value = $allowed_mapping[$key]["serialized"] ? serialize($value) : $value;

        if (is_bool($db_value)) {
            $db_value = (int)$db_value;
        }

        $saved = nc_core::get_object()->set_settings($key, $db_value, NETCAT_MODULE_MESSAGING_NAME, $this->site_id);

        if ($saved) {
            $this->settings[$key] = $value;
        }

        return $saved;
    }

    /**
     * @param array $parameters
     *
     * @return bool
     * @throws nc_messaging_exception
     */
    public function save_settings_batch(array $parameters) {
        $error_parameters = array();

        foreach ($parameters as $key => $value) {
            $saved = $this->save_settings($key, $value);

            if (!$saved) {
                $error_parameters[] = $key;
            }
        }

        if ($error_parameters) {
            throw new nc_messaging_exception(sprintf(NETCAT_MODULE_MESSAGING_SETTINGS_SAVE_ERROR,
                implode(", ", $error_parameters)));
        }

        return true;
    }

    /**
     * @return array
     */
    private function get_available_settings() {
        $settings = nc_core::get_object()->get_settings(null, 'messaging', false, $this->site_id);

        if (!$settings) {
            return array();
        }

        $result = array();
        $allowed_mapping = self::get_allowed_settings_mapping();

        foreach ($settings as $key => $value) {
            if (isset($allowed_mapping[$key])) {
                $result[$key] = $allowed_mapping[$key]["serialized"] ? unserialize($value) : $value;
            }
        }

        return $result;
    }

}
