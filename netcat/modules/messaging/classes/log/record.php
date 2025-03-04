<?php

class nc_messaging_log_record extends nc_record {

    const EVENT_TYPE_SYSTEM = "system";
    const EVENT_TYPE_AUTH_LOGIN = "auth:login";
    const EVENT_TYPE_AUTH_REGISTER = "auth:register";

    const STATUS_SUCCESS = "success";
    const STATUS_ERROR = "error";
    const STATUS_WARNING = "warning";
    const STATUS_INFO = "info";


    protected $table_name = "Messaging_Log";

    protected $properties = array(
        "id",
        "created_at",
        "site_id",
        "service_id" => null,
        "message_id" => null,
        "log_message" => null,
        "message",
        "recipients",
        "messaging_type" => nc_messaging_service_record::MESSAGING_TYPE_SMS,
        "event_type" => self::EVENT_TYPE_SYSTEM,
        "status" => self::STATUS_SUCCESS,
        "details",
    );

    protected $mapping = array(
        "id" => "Log_ID",
        "created_at" => "Created",
        "site_id" => "Catalogue_ID",
        "service_id" => "Service_ID",
        "message_id" => "Message_ID",
        "log_message" => "Log_Message",
        "message" => "Message",
        "recipients" => "Recipients",
        "messaging_type" => "Messaging_Type",
        "event_type" => "Event_Type",
        "status" => "Status",
        "details" => "Details",
    );

    protected $serialized_properties = array("details");


    /**
     * @param int $site_id
     *
     * @return array
     */
    public static function get_all_logs($site_id) {
        return nc_db()->get_results("SELECT log.*, service.Name as Service_Name 
                                            FROM `Messaging_Log` log 
                                            left join `Messaging_Service` service on service.Service_ID = log.Service_ID
                                            where log.Catalogue_ID = $site_id
                                            order by `Created` desc", ARRAY_A) ?: array();
    }

    /**
     * @param int $service_id
     *
     * @return bool
     */
    public static function delete_by_service_id($service_id) {
        return (bool)nc_db()->query("delete from `Messaging_Log` WHERE `Service_ID` = $service_id");
    }

    /**
     * @param int $site_id
     *
     * @return bool
     */
    public static function clear_all_logs($site_id) {
        return (bool)nc_db()->query("delete from `Messaging_Log` WHERE `Catalogue_ID` = $site_id");
    }
}
