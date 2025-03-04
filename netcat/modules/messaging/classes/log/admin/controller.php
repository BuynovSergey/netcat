<?php

class nc_messaging_log_admin_controller extends nc_messaging_admin_controller {


    /**
     * @param string|null $view_path
     */
    public function __construct($view_path = null) {
        parent::__construct($view_path);
        $this->ui_config->subheaderText = NETCAT_MODULE_SEARCH_ADMIN_EVENT_LOG_FILTER;
        $this->ui_config->set_location_hash("log(index)");
        $this->ui_config->set_tree_node("log");
    }

    /**
     * @return nc_ui_view
     */
    protected function action_index() {
        global $perm;

        $logs = nc_messaging_log_record::get_all_logs($this->site_id);

        if ($logs && $perm->isSupervisor()) {
            $this->ui_config->add_button("clear", NETCAT_MODULE_LOGGING_BUTTON_CLEAR, "mainViewIframe.clearLogs()",
                "left",
                true);

        }

        return $this->view("log")->with("logs", $logs);
    }

    /**
     * @return string
     */
    protected function action_clear() {
        global $perm;

        if (!$perm->isSupervisor()) {
            return $this->json_error(NETCAT_MODERATION_ERROR_NORIGHT);
        }

        return nc_messaging_log_record::clear_all_logs($this->site_id) ? $this->json_success() : $this->json_error();
    }
}
