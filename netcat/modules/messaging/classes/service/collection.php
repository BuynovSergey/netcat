<?php

class nc_messaging_service_collection extends nc_record_collection {


    protected $items_class = "nc_messaging_service_record";


    /**
     * @param int $site_id
     *
     * @return nc_messaging_service_collection
     * @throws nc_record_exception
     */
    public static function get_available_services($site_id) {
        return self::get_services($site_id, null, true);
    }

    /**
     * @param int $site_id
     * @param string|null $type
     * @param bool $only_checked
     *
     * @throws nc_record_exception
     */
    public static function get_services($site_id, $type = null, $only_checked = false) {
        $query = "SELECT service.*, provider.`MessageProvider_Name` AS Provider_Name, provider.`Value` AS `Provider_Class`
                            FROM " . nc_messaging_service_record::MESSAGING_TABLE_NAME . " service
                            LEFT JOIN Classificator_MessageProvider provider
                            ON service.Provider_ID = provider.MessageProvider_ID
                            WHERE service.`Catalogue_ID` = $site_id
                            AND provider.Checked = 1";

        if ($type) {
            $query .= " AND service . `Type` = '$type'";
        }

        if ($only_checked) {
            $query .= " AND service.`Checked` = 1";
        }

        $result = new nc_messaging_service_collection();

        return $result->select_from_database($query);
    }

    public static function get_available_providers() {
        $providers = nc_core::get_object()->list->get_items("MessageProvider");

        foreach ($providers as $i => $provider) {
            $class_parents = class_parents($provider["Value"]);
            $providers[$i]["Type"] = is_array($class_parents) && count($class_parents) > 0 &&
            array_shift($class_parents) == "nc_messaging_sms_service" ?
                nc_messaging_service_record::MESSAGING_TYPE_SMS :
                nc_messaging_service_record::MESSAGING_TYPE_MESSENGER;
        }

        return $providers;
    }

}
