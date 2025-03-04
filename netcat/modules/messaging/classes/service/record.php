<?php

class nc_messaging_service_record extends nc_record {

    const MESSAGING_TABLE_NAME = "Messaging_Service";
    const MESSAGING_TYPE_SMS = "sms";
    const MESSAGING_TYPE_MESSENGER = "messenger";


    protected $table_name = "Messaging_Service";

    protected $properties = array(
        "id",
        "site_id" => null,
        "provider_id",
        "priority" => 1,
        "checked" => 1,
        "name",
        "provider_name",
        "class",
        "description",
        "type" => self::MESSAGING_TYPE_SMS,
        "settings",
    );

    protected $mapping = array(
        "id" => "Service_ID",
        "site_id" => "Catalogue_ID",
        "provider_id" => "Provider_ID",
        "priority" => "Priority",
        "checked" => "Checked",
        "name" => "Name",
        "provider_name" => "Provider_Name",
        "class" => "Provider_Class",
        "description" => "Description",
        "type" => "Type",
        "settings" => "Settings",
    );

    protected $mapping_only_for_loading = array(
        "class",
        "provider_name",
    );

    protected $serialized_properties = array(
        "settings",
    );

    protected $deep_unserialize = true;

    /**
     * @inheritDoc
     */
    public static function get_by_id($id) {
        $id = (int)$id;
        $data = nc_db()->get_row("select service.*, provider.`MessageProvider_Name` as Provider_Name, provider.`Value` as `Provider_Class` 
                                            from " . self::MESSAGING_TABLE_NAME . " service
                                            left join Classificator_MessageProvider provider
                                            on service.Provider_ID = provider.MessageProvider_ID
                                            where service.Service_ID = $id", ARRAY_A);

        if (!$data) {
            throw new nc_record_exception(NETCAT_MODULE_MESSAGING_SERVICE_NOT_FOUND_ERROR);
        }

        $record = new nc_messaging_service_record();

        return $record->set_values_from_database_result($data);
    }

    /**
     * Добавляет новое свойство в объект для корректной работы с классом nc_a2f, для отрисовки формы с доп.настройками
     *
     * @param nc_messaging_service_record $record
     *
     * @return void
     * @throws nc_messaging_exception
     */
    public static function map_settings_to_form_values(nc_messaging_service_record $record) {
        $settings_mapping = array();
        $result = array();

        try {
            $settings_mapping = $record["class"]::get_settings_mapping();
        } catch (Error $e) {
            if (strpos($e->getMessage(), "undefined method")) {
                throw new nc_messaging_exception(NETCAT_MODULE_MESSAGING_API_UNSUPPORTED_IMPLEMENTATION_ERROR);
            }
        }

        foreach ($settings_mapping as $key => $settings_meta) {
            if (isset($record["settings"][$key])) {
                $result[$key] = array_merge(
                    $settings_meta,
                    array("value" => $record["settings"][$key])
                );
            }
            else {
                $result[$key] = $settings_meta;
            }
        }

        try {
            $record->set("settings_for_form", $result);
        } catch (nc_record_exception $ignored) {
        }
    }

    /**
     * @inheritDoc
     */
    public function delete() {
        parent::delete();
        nc_messaging_log_record::delete_by_service_id($this->get_id());
    }


    /**
     * @return bool
     * @throws nc_record_exception
     */
    public function toggle_service_state() {
        $res = nc_db()->query("update " . $this->table_name .
            " set `Checked` = not `Checked` where `Service_ID` = {$this["id"]}");

        if (!$res) {
            throw new nc_record_exception(NETCAT_MODULE_MESSAGING_UPDATE_ERROR);
        }

        return (bool)$res;
    }

    /**
     * @return bool
     */
    public function is_enabled() {
        return (bool)$this["checked"];
    }
}
