<?php

class nc_stats_analytics_admin_controller extends nc_stats_admin_controller {

    protected $ui_config_tab = 'analytics';

    /**
     * @return nc_ui_view
     */
    protected function action_index() {
        $this->ui_config->actionButtons[] = array(
            "id" => "submit_form",
            "caption" => NETCAT_MODULE_STATS_SAVE_CHANGES,
            "action" => "mainView.submitIframeForm()"
        );

        $nc_core = nc_core::get_object();
        return $this->view('analytics/settings')
                    ->with('after_save', $nc_core->input->fetch_get_post('after_save'));
    }


    protected function action_save_settings() {
        $nc_core = nc_core::get_object();
        $nc_core->token->exit_if_invalid();

        $site_id = $nc_core->input->fetch_post_get('site_id') ?: $this->site_id;
        $settings = (array)$nc_core->input->fetch_post('settings');

        $stats = nc_stats::get_instance($site_id);
        foreach ($settings as $k => $v) {
            $stats->set_setting($k, $v);
        }

        $this->redirect_to_index_action('index', 'after_save=1');
    }

}
