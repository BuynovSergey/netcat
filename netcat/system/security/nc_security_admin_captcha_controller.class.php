<?php

class nc_security_admin_captcha_controller extends nc_security_admin_settings_controller {

    /**
     *
     */
    protected function action_show_settings() {
        $this->ui_config->set_action('auth', 'captcha', $this->site_id);
        $this->ui_config->actionButtons = array(
            array(
                'id' => 'submit',
                'caption' => NETCAT_SECURITY_SETTINGS_SAVE,
                'action' => 'mainView.submitIframeForm()',
                'align' => 'right',
            ),
        );

        $nc_core = nc_core::get_object();

        $view = $this->view('auth/captcha_settings', array(
            'saved' => false,
            'default_settings_link' => $nc_core->ADMIN_PATH . '#security.auth.captcha(0)',
        ));

        $site_has_own_captcha_settings = true;
        if ($this->site_id) {
            $site_has_own_captcha_settings = $this->site_has_own_settings_like('AuthCaptcha%');
        }

        $captcha_enabled = $nc_core->get_settings('AuthCaptchaEnabled', 'system', false, $this->site_id);
        $captcha_attempts = $nc_core->get_settings('AuthCaptchaAttempts', 'system', false, $this->site_id);

        if ($captcha_enabled) {
            $captcha_mode = $captcha_attempts ? 'count' : 'always';
        } else {
            $captcha_mode = 'disabled';
        }

        $view->with('site_has_own_captcha_settings', $site_has_own_captcha_settings)
            ->with('captcha_mode', $captcha_mode)
            ->with('captcha_free_attempts', $captcha_attempts);

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

        if ($this->site_id && $nc_core->input->fetch_post('captcha_use_default_settings')) {
            $this->delete_site_settings_like('AuthCaptcha%');
        } else {
            $mode = $nc_core->input->fetch_post('captcha_mode');
            $attempts = $nc_core->input->fetch_post('captcha_free_attempts');
            $nc_core->set_settings('AuthCaptchaEnabled', $mode === 'disabled' ? 0 : 1, 'system', $this->site_id);
            $nc_core->set_settings('AuthCaptchaAttempts', $mode === 'always' ? 0 : $attempts, 'system', $this->site_id);
        }

        return $this->action_show_settings()->with('saved', true);
    }

}
