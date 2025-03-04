<?php

class nc_messaging_send_admin_controller extends nc_messaging_admin_controller {

    protected $ui_config_class = "nc_messaging_send_admin_ui";
    protected $required_module_permission = "message_send";


    /**
     * @param string|null $view_path
     */
    public function __construct($view_path = null) {
        parent::__construct($view_path);
    }

    /**
     * @return nc_ui_view
     * @throws nc_record_exception
     */
    protected function action_index() {
        $this->ui_config->add_save_and_cancel_buttons();

        return $this->view("send")
            ->with("settings", nc_messaging_settings::get_instance($this->site_id)->get_settings())
            ->with("services", nc_messaging_service_collection::get_available_services($this->site_id)->to_array());
    }

    /**
     * @return string
     */
    protected function action_send_message() {
        $payload = $this->input->fetch_post();

        try {
            $result = nc_messaging::get_instance($this->site_id)
                ->send_message($payload["phone"], $payload["message"], $payload["service_id"],
                    nc_array_value($payload, "parameters"));

            return $this->json_success($result->get_original_data());
        } catch (nc_messaging_exception $e) {
            return $this->json_error($e->getMessage(), $e->getCode());
        }
    }


}
