<?php

abstract class nc_routing_admin_controller extends nc_ui_controller {

    /**
     *
     */
    protected function after_action($result) {
        BeginHtml(NETCAT_MODULE_ROUTING_TITLE, '', '');
        echo $result;
        EndHtml();
        return '';
    }

    /**
     *
     */
    protected function get_short_controller_name() {
        preg_match("/^nc_routing_(.+)_admin_controller$/", get_class($this), $matches);
        if ($matches) { return $matches[1]; }
        die ('Non-standard controller class name; please override ' . __METHOD__ . '() or methods that use it');
    }

    /**
     * @return string
     */
    protected function get_script_path() {
        return nc_module_path('routing') . 'admin/?controller=' . $this->get_short_controller_name() . '&action=';
    }

    /**
     * @param string $action
     * @param string $params
     */
    protected function redirect_to_index_action($action = 'index', $params = '') {
        $location = $this->get_script_path() . $action .
                    '&site_id=' . (int)$this->site_id .
                    ($params[0] == '&' ? $params : "&$params");

        ob_clean();
        header("Location: $location");
        die;
    }

    /**
     * @param $view
     * @param array $data
     * @return nc_ui_view
     */
    protected function view($view, $data = array()) {
        $view = parent::view($view, $data);
        return $view->with('site_id', $this->site_id);
    }

}
