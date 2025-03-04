<?php

final class nc_cache_manager {

    const DEFAULT_FILE_CACHE_SERVICE_ID = "default";

    /**
     * @var nc_cache_manager
     */
    private static $instance;

    /**
     * @var nc_cache_service[]
     */
    private $services = array();


    private function __construct() {
    }

    private function __clone() {

    }

    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * @return nc_cache_service
     */
    public static function get_default_file_cache_service() {
        return nc_array_value(self::$instance->services, self::DEFAULT_FILE_CACHE_SERVICE_ID,
            new nc_file_cache_service(null, self::DEFAULT_FILE_CACHE_SERVICE_ID));
    }

    /**
     * @param nc_cache_service $service
     *
     * @return void
     */
    public function add_cache_service(nc_cache_service $service) {
        $this->services[$service->get_service_id()] = $service;
    }

    /**
     * @param $service
     *
     * @return nc_cache_service|null
     */
    public function get_cache_service($service) {
        return nc_array_value($this->services, $service);
    }

    /**
     * @return void
     */
    public function clear_cache() {
        foreach ($this->services as $service) {
            $service->clear();
        }
    }

    /**
     * @return void
     */
    public function unset_services() {
        $this->services = array();
    }

}
