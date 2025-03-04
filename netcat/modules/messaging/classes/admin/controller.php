<?php

class nc_messaging_admin_controller extends nc_ui_controller {

    /**
     * @var nc_messaging_admin_ui
     */
    protected $ui_config;
    protected $ui_config_class = "nc_messaging_admin_ui";

    /** @var string|null  Необходимые для доступа к контроллеру права на модуль (null — не проверять) */
    protected $required_module_permission = null;


    public function __construct($view_path = null) {
        parent::__construct($view_path);
        $this->invoke_ui_class();
    }

    /**
     * @param string $view
     * @param array $data
     *
     * @return nc_ui_view
     */
    protected final function view($view, $data = array()) {
        $view_file_name = "$view.view.php";
        $view_file_path = rtrim($this->view_path, DIRECTORY_SEPARATOR);
        $max_levels_to_inspect = 2;

        while (--$max_levels_to_inspect) {
            if (file_exists($view_file_path . "/" . $view_file_name)) {
                break;
            }

            $view_file_path = dirname($view_file_path);
        }

        return nc_core("ui")->view($view_file_path . "/" . $view_file_name, $data)
            ->with("site_id", $this->site_id)
            ->with("current_url", $this->get_script_path())
            ->with("controller_name", $this->get_short_controller_name());
    }

    /**
     * @return string
     */
    protected final function get_script_path() {
        return nc_module_path("messaging") . "admin/?controller=" . $this->get_short_controller_name() . "&action=";
    }

    /**
     * @return string
     */
    protected final function get_short_controller_name() {
        preg_match("/^nc_messaging_(.+)_admin_controller$/", get_class($this), $matches);

        if ($matches) {
            return $matches[1];
        }

        die ('Non-standard controller class name; please override ' . __METHOD__ . '() or methods that use it');
    }

    /**
     * @param $result
     *
     * @return string
     */
    protected final function after_action($result) {
        if (!$this->use_layout) {
            return $result;
        }

        BeginHtml(NETCAT_MODULE_MESSAGING);
        echo "<div class='nc-messaging-admin'>$result</div>";
        EndHtml();

        return "";
    }

    /**
     * @inheritDoc
     */
    protected final function before_action() {
        if ($this->required_module_permission) {
            $this->check_permissions(array(NC_PERM_MODULE, "messaging"),
                $this->required_module_permission,
                $this->site_id);
        }
    }

    /**
     * @param string $action
     * @param string $params
     */
    protected final function redirect_to_action($action, $params = "") {
        $location = $this->get_script_path() . $action . '&site_id=' . (int)$this->site_id;

        if ($params) {
            $location .= ($params[0] == '&' ? $params : "&$params");
        }

        ob_clean();
        header("Location: $location");
        die;
    }

    /**
     * @return void
     */
    private function invoke_ui_class() {
        $this->ui_config = $this->ui_config_class == "" ?
            new nc_messaging_admin_ui() :
            new $this->ui_config_class();
    }

}
