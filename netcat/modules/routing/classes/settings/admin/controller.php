<?php

class nc_routing_settings_admin_controller extends nc_routing_admin_controller {

    /** @var  nc_routing_settings_admin_ui */
    protected $ui_config;

    /**
     *
     */
    protected function before_action() {
        $this->ui_config = new nc_routing_settings_admin_ui(
            $this->get_short_controller_name(),
            NETCAT_MODULE_ROUTING_SETTINGS);
    }

    /**
     *
     */
    protected function action_index() {
        $this->ui_config->locationHash .= "($this->site_id)";
        $this->ui_config->add_submit_button();

        $duplicate_route_action = nc_routing::get_setting('DuplicateRouteAction', $this->site_id);
        return $this->view('settings')
                    ->with('duplicate_route_action', $duplicate_route_action);
    }

    /**
     * (POST only)
     */
    protected function action_save() {
        nc_core::get_object()->token->exit_if_invalid();
        foreach ($this->input->fetch_post('settings') as $k => $v) {
            nc_routing::set_setting($k, $v, $this->site_id);
        }

        $this->redirect_to_index_action();
    }

}