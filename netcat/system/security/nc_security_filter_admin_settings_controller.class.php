<?php

class nc_security_filter_admin_settings_controller extends nc_security_admin_settings_controller {

    /**
     *
     */
    protected function action_show_settings() {
        $this->ui_config->set_action('filter', 'settings', $this->site_id);
        $this->ui_config->actionButtons = array(
            array(
                'id' => 'submit',
                'caption' => NETCAT_SECURITY_SETTINGS_SAVE,
                'action' => 'mainView.submitIframeForm()',
                'align' => 'right',
            ),
        );

        $nc_core = nc_core::get_object();

        $view = $this->view('filter/settings', array(
            'saved' => false,
            'default_settings_link' => $nc_core->ADMIN_PATH . '#security.filter.settings(0)',
        ));

        $site_has_own_filter_settings = true;

        if ($this->site_id) {
            $site_has_own_filter_settings = $this->site_has_own_settings_like('Security%Filter%');
        }

        $view->with('site_has_own_filter_settings', $site_has_own_filter_settings);

        $nc_core = nc_core::get_object();
        $configuration_errors = array_merge(
            $nc_core->security->xss_filter->get_configuration_errors(),
            $nc_core->security->sql_filter->get_configuration_errors(),
            $nc_core->security->php_filter->get_configuration_errors()
        );

        $view->with('filter_configuration_errors', $configuration_errors);

        return $view;
    }

    /**
     * @return nc_ui_view
     */
    protected function action_save_settings() {
        /** @var Permission $perm */
        global $perm;
        if ($perm->isGuest()) {
            return $this->view('settings', array('saved' => false));
        }

        $nc_core = nc_core::get_object();
        $nc_core->token->exit_if_invalid();

        if ($this->site_id && $nc_core->input->fetch_post('filters_use_default_settings')) {
            $this->delete_site_settings_like('Security%Filter%');
        } else {
            $settings = (array)$nc_core->input->fetch_post('filter_settings');
            foreach ($settings as $k => $v) {
                $nc_core->set_settings($k, $v, 'system', $this->site_id);
            }
        }

        return $this->action_show_settings()->with('saved', true);
    }

}
