<?php

class nc_messaging_sms_service_smsc extends nc_messaging_sms_service implements nc_messaging_api_multi_messaging {

    /**
     * @inheritDoc
     */
    public static function get_settings_mapping() {
        return array(
            "login" => array(
                "type" => "string",
                "caption" => NETCAT_MODULE_MESSAGING_SERVICE_LOGIN,
                "required" => true,
            ),

            "password" => array(
                "type" => "string",
                "caption" => NETCAT_MODULE_MESSAGING_SERVICE_PASSWORD,
                "required" => true,
            ),

            "sender_id" => array(
                "type" => "string",
                "caption" => NETCAT_MODULE_MESSAGING_SERVICE_SENDER_ID,
                "required" => true,
            ),
        );
    }

    /**
     * @inheritDoc
     */
    public function __construct(nc_messaging_service_record $service_record) {
        parent::__construct($service_record, new nc_messaging_sms_api_smsc());
    }


}
