<?php

class nc_requests_form_admin_controller extends nc_requests_admin_controller {

    protected $use_layout = false;

    /**
     *
     */
    protected function init() {
        parent::init();
    }

    /**
     * @param $infoblock_id
     * @param int $action
     */
    protected function check_infoblock_permissions($infoblock_id, $action = NC_PERM_ACTION_ADMIN) {
        /** @var Permission $perm */
        global $perm;
        $perm->ExitIfNotAccess(NC_PERM_ITEM_CC, $action, $infoblock_id, null, 1);
    }

    /**
     * @param nc_a2f $a2f
     * @return string
     */
    protected function render_a2f(nc_a2f $a2f) {
    }

    /**
     * @return string
     */
    protected function action_save_setting() {
        $input = nc_core::get_object()->input;
        $infoblock_id = $input->fetch_post_get('infoblock_id');
        $form_type = $input->fetch_post_get('form_type');
        $setting = $input->fetch_post('setting');
        $value = $input->fetch_post('value');

        $this->check_infoblock_permissions($infoblock_id);
        if (!$setting) {
            return 'ERROR';
        }

        $form = nc_requests_form::get_instance($infoblock_id, $form_type);
        $result = $form->save_setting($setting, $value);

        return $result ? 'OK' : 'ERROR';
    }

    /**
     * @return string
     */
    protected function action_save_settings() {
        $input = nc_core::get_object()->input;
        $infoblock_id = $input->fetch_post_get('infoblock_id');
        $form_type = $input->fetch_post_get('form_type');
        $settings = $input->fetch_post('settings');

        $this->check_infoblock_permissions($infoblock_id);
        if (!$settings || !is_array($settings)) {
            return 'ERROR';
        }

        $form = nc_requests_form::get_instance($infoblock_id, $form_type);
        $form->save_settings($settings);

        if (isset($settings['Subdivision_VisibleFields']) || isset($settings['Subdivision_FieldProperties'])) {
            return 'ReloadPage=1';
        } else {
            return 'OK';
        }
    }

    /**
     * @return nc_ui_view
     * @throws Exception
     */
    protected function action_show_subdivision_fields_dialog() {
        $nc_core = nc_core::get_object();
        $input = $nc_core->input;
        $infoblock_id = $input->fetch_post_get('infoblock_id');
        $form_type = $input->fetch_post_get('form_type');

        $this->check_infoblock_permissions($infoblock_id);

        $site_id = $nc_core->sub_class->get_by_id($infoblock_id, 'Catalogue_ID');
        $form = nc_requests_form::get_instance($infoblock_id, $form_type);
        $requests = nc_requests::get_instance($site_id);

        return $this->view('form/subdivision_fields_dialog', array(
            'infoblock_id' => $infoblock_id,
            'form_type' => $form_type,
            'saved_field_properties' => $form->get_setting('Subdivision_FieldProperties'),
            'selectable_fields' => $requests->get_request_component_visible_fields(),
            'enabled_fields' => $form->get_visible_fields(),
            'has_item_variants' => $form->has_item_variants(),
            'notification_email' => $form->get_setting('Subdivision_NotificationEmail'),
        ));
    }

    /**
     * @return nc_ui_view
     */
    protected function action_show_button_settings_dialog() {
        $nc_core = nc_core::get_object();
        $input = $nc_core->input;
        $infoblock_id = $input->fetch_post_get('infoblock_id');
        $form_type = $input->fetch_post_get('form_type');
        $button_id = $input->fetch_post_get('button_id');
        $button_type = $input->fetch_post_get('button_type');

        $this->check_infoblock_permissions($infoblock_id);

        $analytics_notice = false;
        if (!nc_module_check_by_keyword('stats')) {
            $url = $nc_core->ADMIN_PATH . '#module.list';
            $analytics_notice = sprintf(NETCAT_MODULE_REQUESTS_FORM_BUTTON_ACTION_HINT_STATS_DISABLED, $url);
        }
        else {
            $url = $nc_core->ADMIN_PATH . '#module.stats.analytics';
            $analytics = nc_stats::get_instance($this->site_id)->analytics;

            if (!$analytics->is_enabled()) {
                $analytics_notice = sprintf(NETCAT_MODULE_REQUESTS_FORM_BUTTON_ACTION_HINT_ANALYTICS_DISABLED, $url);
            }
            else if (!$analytics->is_configured()) {
                $analytics_notice = sprintf(NETCAT_MODULE_REQUESTS_FORM_BUTTON_ACTION_HINT_ANALYTICS_NO_COUNTERS, $url);
            }
        }

        $view = $button_type == 'OpenPopupButton'
                    ? 'form/popup_button_settings_dialog'
                    : 'form/submit_button_settings_dialog';

        return $this->view($view, array(
            'infoblock_id' => $infoblock_id,
            'form_type' => $form_type,
            'form' => nc_requests_form::get_instance($infoblock_id, $form_type),
            'button_id' => $button_id,
            'analytics_notice' => $analytics_notice,
        ));
    }

}
