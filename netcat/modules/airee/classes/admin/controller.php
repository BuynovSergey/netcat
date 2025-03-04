<?php

class nc_airee_admin_controller extends nc_ui_controller {

    /** @property ui_config nc_airee_admin_ui */

    /** @var bool */
    protected $use_layout = true;

    /** @var nc_airee */
    protected $airee;


    protected function before_action() {
        $this->check_permissions(array(NC_PERM_MODULE, 'airee'), '', $this->site_id);

        $this->ui_config = new nc_airee_admin_ui();
        $this->ui_config->set_site_id($this->site_id);
        $this->airee = nc_airee::get_instance($this->site_id);
    }

    /**
     * @param $result
     * @return string
     */
    protected function after_action($result) {
        if (!$this->use_layout) {
            return $result;
        }

        BeginHtml(NETCAT_MODULE_AIREE_DESCRIPTION, '', '');
        echo $result;
        EndHtml();
        return '';
    }

    /**
     * @return nc_ui_view
     */
    protected function action_show_settings() {
        $settings = $this->airee->get_settings('');
        if (!empty($settings['Domain']) || !empty($settings['Email'])) {
            return $this->action_show_install();
        }

        if (isset($settings['Domain_Added_At']) && $settings['Domain_Added_At'] > time()) {
            return $this->action_show_installation_progress();
        }

        return $this->view('settings', array(
            'site_id' => $this->site_id,
            'api_key_description' => $this->airee->get_api_key_description(),
            'balance_description' => $this->airee->get_balance_description(),
            'balance_label' => $this->airee->get_balance_label(),
            'balance_add_funds_link' => $this->airee->get_balance_add_funds_link(),
            'errors' => array(),
            'settings' => $settings,
            'saved' => false,
        ));
    }

    /**
     * @return nc_ui_view
     */
    protected function action_save_settings() {
        $nc_core = nc_core::get_object();

        $settings = (array)$nc_core->input->fetch_post('settings');

        foreach ($settings as $k => $v) {
            $this->airee->set_settings($k, $v);
        }

        return $this->action_show_settings()->with('saved', true);
    }

    /**
     * @return nc_ui_view
     */
    protected function action_show_install() {
        $nc_core = nc_Core::get_object();

        try {
            $default_domain = $nc_core->catalogue->get_by_id($this->site_id, 'Domain');
        } catch (Exception $e) {
            $default_domain = '';
        }

        return $this->view('install', array(
            'site_id' => $this->site_id,
            'default_domain' => $default_domain,
            'default_email' => $nc_core->get_settings('NotificationEmail'),
            'errors' => array(),
        ));
    }

    /**
     * @return nc_ui_view
     */
    protected function action_show_installation_progress() {
        return $this->view('installation_progress', array(
            'time_left' => (int)$this->airee->get_settings('Domain_Added_At') - time(),
            'timeout' => nc_airee::TIMEOUT_UNTIL_DOMAIN_REGISTRATION_COMPLETE
        ));
    }

    /**
     * @return nc_ui_view
     */
    protected function action_install() {
        $nc_core = nc_core::get_object();

        $settings = (array)$nc_core->input->fetch_post('settings');
        $errors = array();
        $encoded_domain = $settings['Domain'];

        if (!$settings['Domain']) {
            $errors[] = NETCAT_MODULE_AIREE_DOMAIN_SETTING_REQUIRED_ERROR;
        }

        if (!$settings['Email']) {
            $errors[] = NETCAT_MODULE_AIREE_ADMINISTRATOR_EMAIL_SETTING_REQUIRED_ERROR;
        } elseif (!filter_var($settings['Email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = NETCAT_MODULE_AIREE_SETTINGS_ADMINISTRATOR_EMAIL_NOT_VALID;
        }

        try {
            $encoded_domain = nc_airee::encode_host($settings['Domain']);
            $this->airee->set_settings('Domain', $encoded_domain);
        } catch (Exception $e) {
            $errors[] = NETCAT_MODULE_AIREE_SETTINGS_DOMAIN_NOT_ENCODED;
        }

        if ($errors) {
            return $this->action_show_install()->with('errors', $errors);
        }

        $this->airee->set_settings('Email', $settings['Email']);
        $api_key = $this->airee->register($settings['Email']);
        $this->airee->save_balance(nc_airee::START_BALANCE_VALUE);

        if ($api_key) {
            $this->airee->set_settings('API_Key', $api_key);

            if ($this->airee->add_domain($encoded_domain)) {
                $this->airee->set_settings('Domain_Added_At', time() + nc_airee::TIMEOUT_UNTIL_DOMAIN_REGISTRATION_COMPLETE);
            } else {
                $this->airee->set_settings('API_Key', '');
            }
        }

        return $this->action_show_settings()->with('installed', true);
    }
}
