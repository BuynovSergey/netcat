<?php

/**
 *
 */
class nc_captcha {

    /** @var self[] */
    static protected $instances = array();

    /** @var  int */
    protected $site_id;

    /** @var  nc_captcha_provider */
    protected $provider;

    /**
     * @param int|null $site_id
     * @return nc_captcha
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
     * @param $site_id
     */
    protected function __construct($site_id) {
        $this->site_id = $site_id;
    }

    /**
     * @return nc_captcha_provider
     */
    public function get_provider() {
        if (!$this->provider) {
            $class = $nc_core = nc_core::get_object()->get_settings('Provider', 'captcha', false, $this->site_id) ?: 'nc_captcha_provider_image';
            if (!class_exists($class) || !is_subclass_of($class, 'nc_captcha_provider')) {
                $class = 'nc_captcha_provider_image';
            }
            $this->provider = new $class($this->site_id);
        }
        return $this->provider;
    }

}