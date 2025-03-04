<?php

/**
 * @property nc_stats_analytics $analytics
 */
class nc_stats {

    /** @var  int */
    protected $site_id;

    /**
     * @var array
     */
    protected $sub_modules = array(
        'analytics' => true,
    );

    /**
     * @param int|null $site_id
     * @return nc_stats
     */
    public static function get_instance($site_id = null) {
        static $instances = array();
        $site_id = (int)$site_id;

        if (!$site_id) {
            $site_id = nc_core::get_object()->catalogue->get_current('Catalogue_ID');
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
    }

    /**
     *
     * @param $sub_module_name
     * @return null|object
     */
    public function __get($sub_module_name) {
        if (!isset($this->sub_modules[$sub_module_name])) {
            return null;
        }

        if ($this->sub_modules[$sub_module_name] === true) {
            $class_name = "nc_stats_" . $sub_module_name;
            $this->sub_modules[$sub_module_name] = new $class_name($this);
        }

        return $this->sub_modules[$sub_module_name];
    }

    /**
     * @return int
     */
    public function get_site_id() {
        return $this->site_id;
    }

    /**
     * @param $setting_name
     * @param bool $reload
     * @return mixed
     */
    public function get_setting($setting_name, $reload = false) {
        return nc_core::get_object()->get_settings($setting_name, 'stats', $reload, $this->site_id);
    }

    /**
     * @param $setting_name
     * @param $setting_value
     * @return bool
     */
    public function set_setting($setting_name, $setting_value) {
        return nc_core::get_object()->set_settings($setting_name, $setting_value, 'stats', $this->site_id);
    }

}
