<?php

class nc_messaging_settings_admin_controller extends nc_messaging_admin_controller {

    protected $required_module_permission = "settings_edit";

    /**
     * @param string|null $view_path
     */
    public function __construct($view_path = null) {
        parent::__construct($view_path);
        $this->ui_config->subheaderText = NETCAT_MODULE_MESSAGING_SETTINGS;
        $this->ui_config->set_location_hash("settings");
        $this->ui_config->set_tree_node("settings");
    }

    /**
     * @return nc_ui_view
     * @throws nc_record_exception
     */
    protected function action_index() {
        $this->ui_config->add_submit_button();

        $settings_provider = nc_messaging_settings::get_instance($this->site_id);
        $payload = $this->input->fetch_post();

        if ($payload) {
            try {
                $settings_provider->save_settings_batch($payload["settings"]);
            } catch (nc_messaging_exception $e) {
                return $this->error_view($e->getMessage());
            }
        }

        $available_services = nc_messaging_service_collection::get_available_services($this->site_id);

        return $this->view("settings")
            ->with("settings", $settings_provider->get_settings())
            ->with("prefixes", require $_SERVER["DOCUMENT_ROOT"] . nc_module_path(NETCAT_MODULE_MESSAGING_NAME) .
                "phone_prefixes.php")
            ->with("services", array(
                "all" => $available_services->to_array(),
                "sms" => $available_services->where("type", nc_messaging_service_record::MESSAGING_TYPE_SMS)
                    ->to_array(),
                "messenger" => $available_services->where("type",
                    nc_messaging_service_record::MESSAGING_TYPE_MESSENGER)->to_array(),
            ));
    }

}
