<?php

class nc_captcha_admin_controller extends nc_ui_controller {

    /** @var  nc_captcha_admin_ui */
    protected $ui_config;

    /** @var bool  */
    protected $use_layout = true;

    /** @var bool  */
    protected $set_current_site_id_as_default = false;

    /**
     *
     */
    protected function before_action() {
        $this->check_permissions(array(NC_PERM_MODULE, 'captcha'), NC_PERM_MODULE_ALL, $this->site_id);
        $this->ui_config = new nc_captcha_admin_ui();
        $this->ui_config->set_site_id($this->site_id);
    }

    /**
     * @return callable
     */
    protected function get_site_list_filter() {
        return $this->get_site_module_checker('captcha', NC_PERM_MODULE_ALL);
    }

    /**
     * @param $result
     * @return string
     */
    protected function after_action($result) {
        if (!$this->use_layout) {
            return $result;
        }

        BeginHtml(NETCAT_MODULE_CAPTCHA, '', '');
        echo $result;
        EndHtml();
        return '';
    }

    /**
     * @return nc_ui_view
     */
    protected function action_show_settings() {
        $nc_core = nc_core::get_object();

        $errors = nc_captcha::get_instance($this->site_id)->get_provider()->get_configuration_errors();

        $settings = $nc_core->get_settings('', 'captcha', false, $this->site_id);
        $providers = array(
            'nc_captcha_provider_image' => NETCAT_MODULE_CAPTCHA_SETTINGS_PROVIDER_IMAGE,
            'nc_captcha_provider_recaptcha' => NETCAT_MODULE_CAPTCHA_SETTINGS_PROVIDER_RECAPTCHA,
        );

        $has_own_settings = true;
        if ($this->site_id) {
            $has_own_settings = (bool)$nc_core->db->get_var(
                "SELECT 1 FROM `Settings` WHERE `Module` = 'captcha' AND `Catalogue_ID` = $this->site_id LIMIT 1"
            );
        }

        return $this->view('settings', array(
            'site_id' => $this->site_id,
            'site_filter' => $this->get_site_list_filter(),
            'errors' => $errors,
            'has_own_settings' => $has_own_settings,
            'default_settings_link' => $nc_core->ADMIN_PATH . '#module.captcha(0)',
            'settings' => $settings,
            'providers' => $providers,
            'saved' => false,
        ));
    }

    /**
     * @return nc_ui_view
     */
    protected function action_save_settings() {
        $nc_core = nc_core::get_object();

        if ($this->site_id && $nc_core->input->fetch_post('use_default_settings')) {
            $nc_core->db->query(
                "DELETE FROM `Settings` 
                  WHERE `Module` = 'captcha'
                    AND `Catalogue_ID` = $this->site_id"
            );
        }
        else {
            $settings = (array)$nc_core->input->fetch_post('settings');
            foreach ($settings as $k => $v) {
                $nc_core->set_settings($k, $v, 'captcha', $this->site_id);
            }
            $nc_core->get_settings('', 'captcha', true, $this->site_id);
            if (!$this->site_id) {
                $nc_core->get_settings('', 'captcha', true, $nc_core->catalogue->get_current('Catalogue_ID'));
            }
        }

        return $this->action_show_settings()->with('saved', true);
    }

    /**
     * @return nc_ui_view
     */
    protected function action_get_provider_settings() {
        $this->use_layout = false;
        $nc_core = nc_core::get_object();

        $provider_class = $nc_core->input->fetch_post('provider');
        if (!is_subclass_of($provider_class, 'nc_captcha_provider')) {
            die('Wrong CAPTCHA provider class');
        }

        /** @var nc_captcha_provider $provider */
        $provider = new $provider_class($this->site_id);

        return $this->view("settings/$provider_class", array(
            'errors' => $provider->get_configuration_errors(),
            'settings' => $nc_core->get_settings('', 'captcha', false, $this->site_id),
        ));
    }
}
