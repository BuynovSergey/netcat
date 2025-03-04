<?php

class nc_requests_settings_admin_controller extends nc_requests_admin_controller {

    protected function before_action() {
        $this->check_permissions(array(NC_PERM_MODULE, 'requests'), 'settings_edit', $this->site_id);
        return parent::before_action();
    }


    protected function get_site_list_filter() {
        return $this->get_site_module_checker('requests', 'settings_edit');
    }


    protected function action_index() {

        $this->ui_config = new ui_config(array(
            'headerText' => NETCAT_MODULE_REQUESTS,
            'subheaderText' => NETCAT_MODULE_REQUESTS_SETTINGS_HEADER,
            'treeMode' => 'modules',
            'treeSelectedNode' => 'module-' . nc_module_check_by_keyword('requests'),
            'locationHash' => '#module.requests',
            'actionButtons' => array(
                array(
                    "id" => "submit",
                    "caption" => NETCAT_MODULE_REQUESTS_SAVE,
                    "action" => "mainView.submitIframeForm()"
                ),
            ),
        ));

        $after_save = nc_core::get_object()->input->fetch_get_post('settings_saved');
        $requests = nc_requests::get_instance($this->site_id);
        return $this->view('settings')
            ->with('requests', $requests)
            ->with('site_filter', $this->get_site_module_checker('requests', 'settings_edit'))
            ->with('after_save', $after_save);
    }


    protected function action_save_settings() {
        $settings = nc_core::get_object()->input->fetch_post('settings');

        if ($settings) {
            $requests = nc_requests::get_instance($this->site_id);
            foreach ($settings as $k => $v) {
                $requests->set_setting($k, $v);
            }
        }

        $this->redirect_to_index_action('index', 'settings_saved=1');
    }

}