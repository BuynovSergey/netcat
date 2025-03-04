<?php

class nc_messaging_service_admin_controller extends nc_messaging_admin_controller {

    protected $ui_config_class = "nc_messaging_service_admin_ui";
    protected $required_module_permission = "service_edit";


    /**
     * @return nc_ui_view
     * @throws nc_record_exception
     */
    protected function action_index() {
        $this->ui_config->clear_action_buttons();
        $this->ui_config->set_location_hash("service(index)");
        $this->ui_config->add_create_button("service(add)");

        return $this->view("index")
            ->with("services", nc_messaging_service_collection::get_services($this->site_id)->to_array());
    }

    /**
     * @return nc_ui_view
     */
    protected function action_add() {
        $this->ui_config->set_location_hash("service(add)");
        $this->ui_config->add_submit_button();
        $this->ui_config->add_back_button();

        $payload = $this->input->fetch_post();

        if ($payload) {
            try {
                $service = new nc_messaging_service_record($payload);
                $service->save();

                $this->redirect_to_action("index");

                return null;
            } catch (nc_record_exception $e) {
                return $this->error_view($e->getMessage());
            }
        }

        $providers = nc_messaging_service_collection::get_available_providers();

        return $this->view("add")
            ->with("providers", $providers)
            ->with("prefixes", require $_SERVER["DOCUMENT_ROOT"] . nc_module_path(NETCAT_MODULE_MESSAGING_NAME) .
                "phone_prefixes.php");
    }

    /**
     * @throws nc_messaging_exception
     */
    protected function action_edit() {
        $service_id = $this->input->fetch_get("id");
        $this->ui_config->set_location_hash("service.edit($service_id)");
        $this->ui_config->add_submit_button();
        $this->ui_config->add_back_button();

        $post = $this->input->fetch_post();

        if (!$post) {
            return $this->action_show($service_id);
        }

        try {
            nc_messaging_service_record::get_by_id($service_id)->set_values($post)->save();

            $this->redirect_to_action("index");

            return null;
        } catch (nc_record_exception $e) {
            return $this->error_view($e->getMessage());
        }
    }

    /**
     * @return nc_ui_view
     * @throws nc_messaging_exception
     */
    protected function action_show($service_id) {
        try {
            $service_record = nc_messaging_service_record::get_by_id($service_id);
        } catch (nc_record_exception $e) {
            return $this->error_view($e->getMessage());
        }

        $this->ui_config->subheaderText = $service_record["provider_name"];
        nc_messaging_service_record::map_settings_to_form_values($service_record);

        return $this->view("edit")
            ->with("prefixes", require $_SERVER["DOCUMENT_ROOT"] . nc_module_path(NETCAT_MODULE_MESSAGING_NAME) .
                "phone_prefixes.php")
            ->with("service", $service_record->to_array());
    }

    /**
     * @return void
     * @throws nc_record_exception
     */
    protected function action_toggle() {
        nc_messaging_service_record::get_by_id($this->input->fetch_post("id"))->toggle_service_state();
        $this->redirect_to_action("index");
    }

    /**
     * @return void
     * @throws nc_record_exception
     */
    protected function action_remove() {
        nc_messaging_service_record::get_by_id($this->input->fetch_get("id"))->delete();
        $this->redirect_to_action("index");
    }

    /**
     * @return nc_ui_view
     */
    protected function action_get_provider_settings() {
        $this->use_layout = false;

        $provider_id = $this->input->fetch_get("provider_id");
        $provider = nc_core::get_object()->list->get_item("MessageProvider", $provider_id);

        try {
            $settings = $provider["Value"]::get_settings_mapping();
        } catch (Error $e) {
            return $this->error_view($e->getMessage());
        }

        return $this->view("custom_settings")->with("settings", $settings);
    }

}
