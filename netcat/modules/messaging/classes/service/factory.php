<?php


final class nc_messaging_service_factory {

    private function __construct() {
        throw new InvalidArgumentException();
    }


    /**
     * @param int $service_id
     *
     * @return nc_messaging_service
     * @throws nc_messaging_exception
     */
    public static function create_service_by_id($service_id) {
        try {
            $service_record = nc_messaging_service_record::get_by_id($service_id);
        } catch (nc_record_exception $e) {
            throw new nc_messaging_exception(NETCAT_MODULE_MESSAGING_SERVICE_NOT_FOUND_ERROR);
        }

        if (!$service_record->is_enabled()) {
            throw new nc_messaging_exception(NETCAT_MODULE_MESSAGING_SERVICE_DISABLED_ERROR);
        }

        $class = $service_record["class"];

        if (!class_exists($class)) {
            throw new nc_messaging_exception(NETCAT_MODULE_MESSAGING_SERVICE_UNSUPPORTED_IMPLEMENTATION_ERROR);
        }

        $service = new $class($service_record);

        if (!$service instanceof nc_messaging_service) {
            throw new nc_messaging_exception(NETCAT_MODULE_MESSAGING_SERVICE_UNSUPPORTED_IMPLEMENTATION_ERROR);
        }

        return $service;
    }

}
