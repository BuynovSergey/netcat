<?php

class nc_selftest_controller extends nc_ui_controller {

    /**
     * @var ui_config
     */
    protected $ui_config;

    /**
     * @param string|null $view_path
     */
    public function __construct($view_path = null) {
        parent::__construct(nc_core::get_object()->ADMIN_FOLDER . "/views/selftest");
    }

    /**
     * @return string
     */
    public function action_index() {
        $selftest = new nc_selftest();

        return $this->view("index")
                    ->with("sites", array_values(nc_core::get_object()->catalogue->get_enabled_sites()))
                    ->with("initial_test_name", $selftest->get_current_test())
                    ->with("tests_count", $selftest->get_total_tests_count());
    }

    /**
     * @return string
     */
    public function action_start_test() {
        $selftest = new nc_selftest($this->input->fetch_post("step"));

        try {
            $result = $selftest->start();

            return $this->json_success($result);
        } catch (nc_selftest_exception $e) {
            return $this->json_error($e->getMessage());
        }
    }

    /**
     * @inheritDoc
     */
    protected function init() {
        $this->ui_config = new nc_selftest_ui();
    }

    /**
     * @inheritDoc
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
     * @inheritDoc
     */
    protected final function after_action($result) {
        if (!$this->use_layout) {
            return $result;
        }

        BeginHtml();
        echo "<div class='nc-selftest-admin'>$result</div>";
        EndHtml();

        return "";
    }

    /**
     * @return string
     */
    protected final function get_script_path() {
        global $SUB_FOLDER, $HTTP_ROOT_PATH;

        return $SUB_FOLDER . $HTTP_ROOT_PATH . "action.php?ctrl=" . $this->get_short_controller_name() . "&action=";
    }

    /**
     * @return string
     */
    protected final function get_short_controller_name() {
        preg_match("/^nc_(.+)_controller$/", get_class($this), $matches);

        if ($matches) {
            return $matches[1];
        }

        die ('Non-standard controller class name; please override ' . __METHOD__ . '() or methods that use it');
    }


}
