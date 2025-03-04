<?php

/**
 * Сохранение настроек 2ФА состоит из этапов:
 *  1. Страница настроек содержит общие настройки и настройки способов получения второго фактора,
 *     см. nc_security_2fa_method::get_settings_form()
 *     action = show_settings
 *  2. Страница подтверждения настроек, обычно выводит список пользователей и поле для ввода кода подтверждения,
 *     см. nc_security_2fa_method::get_settings_check_page(),
 *         nc_security_2fa_method::get_settings_errors() — для проверки ошибок, которые не дадут сохранять форму
 *     action = check_settings
 *  3. Страница подтверждения кода. Если код верный, сохраняет настройки и переадресует на (1), иначе выводит (2) с
 *     добавлением переменной errors (массив с ошибками).
 *     см. nc_security_2fa_method::check_settings_code()
 *     action = check_settings_code
 *
 */
class nc_security_2fa_admin_controller extends nc_security_admin_controller {

    /**
     * @return nc_ui_view
     */
    protected function action_show_settings() {
        $this->ui_config->set_action('auth', '2fa', $this->site_id);
        $this->ui_config->add_submit_button(NETCAT_SECURITY_SETTINGS_SAVE);

        $view = $this->view('auth/2fa_settings', array(
            'saved' => false,
            'method_classes' => nc_security_2fa::get_available_methods(),
        ));

        return $view;
    }

    /**
     * @return nc_ui_view
     */
    protected function action_check_settings() {
        $nc_core = nc_core::get_object();
        $nc_core->token->exit_if_invalid();
        $settings = $nc_core->input->fetch_post('settings');
        $params = $nc_core->input->fetch_post('params');

        // 2ФА выключена и была выключена — не производим дополнительных проверок
        if (!$settings['AuthCodeMode']) {
            return $this->action_save_settings();
        }

        /** @var class-string<nc_security_2fa_method> $method_class */
        $method_class = nc_array_value($settings, 'AuthCodeMethod');
        $method_validation_errors = $method_class::get_settings_errors($settings);
        $this->ui_config->set_action('auth', '2fa');
        $this->ui_config->add_back_button();
        if (!$method_validation_errors) {
            $this->ui_config->add_submit_button();
        }

        return $this->view('auth/2fa_settings_check', array(
            'settings' => $settings,
            'errors' => $method_validation_errors,
            'code_checks' => $method_class::get_settings_check_page_code_part($settings, $params),
            'method_checks' => $method_class::get_settings_check_page($settings, $params),
        ));
    }

    /**
     * @return nc_ui_view|string
     */
    protected function action_check_settings_code() {
        $nc_core = nc_core::get_object();
        $nc_core->token->exit_if_invalid();
        // settings — общие настройки 2ФА
        $settings = $nc_core->input->fetch_post('settings');
        // params — дополнительные данные от способа 2ФА из get_settings_check_page, например, код подтверждения
        $params = $nc_core->input->fetch_post('params');

        if (!$settings['AuthCodeMode']) {
            return $this->action_save_settings();
        }

        /** @var class-string<nc_security_2fa_method> $method_class */
        $method_class = nc_array_value($settings, 'AuthCodeMethod');
        $errors = $method_class::check_settings_code($settings, $params ?: array());

        if ($errors) {
            $check_settings_view = $this->action_check_settings();
            if ($check_settings_view instanceof nc_ui_view) {
                $check_settings_view->with('errors', $errors);
            }
            return $check_settings_view;
        }

        // ошибок нет, значит проверка второго фактора прошла нормально
        $nc_core->user->get_2fa_for_current_session()->set_passed();

        return $this->action_save_settings();
    }

    /**
     * @return nc_ui_view
     */
    protected function action_save_settings() {
        /** @var Permission $perm */
        global $perm;
        if ($perm->isGuest()) {
            $saved = false;
        } else {
            $nc_core = nc_core::get_object();
            $nc_core->token->exit_if_invalid();
            $settings = (array)$nc_core->input->fetch_post('settings');

            foreach ($settings as $key => $value) {
                $nc_core->set_settings($key, $value, 'system', 0);
            }

            $saved = true;
        }

        return $this->action_show_settings()->with('saved', $saved);
    }

    /**
     * @return nc_ui_view|string
     */
    protected function action_preview_users() {
        $this->use_layout = false;

        $nc_core = nc_core::get_object();

        $mode = $nc_core->input->fetch_post('mode');
        $field_name = $nc_core->input->fetch_post('field_name');

        $field_data = $nc_core->get_component('User')->get_field($field_name);

        // Выбираем пользователей, у которых есть какие-то явные права
        $users = $nc_core->db->get_results(
            "SELECT u.* 
               FROM `User` AS u
              WHERE `User_ID` IN (
                       SELECT DISTINCT `User_ID` FROM `Permission`
                       UNION
                           SELECT DISTINCT ug.`User_ID` 
                             FROM `User_Group` AS ug
                             JOIN `Permission` AS p USING (`PermissionGroup_ID`)
                       UNION
                           SELECT DISTINCT `User_ID` FROM `Module_Permission`
                       UNION
                           SELECT DISTINCT ug.`User_ID` 
                             FROM `Module_Permission` AS mp
                             JOIN `User_Group` AS ug USING (`PermissionGroup_ID`) 
                    )",
            ARRAY_A
        );

        $params = array(
            'users' => $users,
            'mode' => $mode,
            'field_name' => $field_name,
            'field_description' => nc_array_value($field_data, 'description') ?: nc_array_value($field_data, 'name'),
        );

        return $this->view('auth/2fa_preview_users', $params);
    }

}
