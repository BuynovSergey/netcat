<?php

/**
 * Типовой контроллер страниц административного интерфейса модуля.
 *
 * Серая магия:
 *  — К view автоматически добавляются переменные:
 *    — current_url
 *    — site_id
 *    — controller_name — короткое название контроллера
 *
 */
abstract class nc_landing_admin_controller extends nc_ui_controller {

    protected $use_layout = true;

    /** @var  nc_landing */
    protected $landing;

    /** @var  nc_landing_admin_ui */
    protected $ui_config;

    /** @var string  Должен быть задан, или должен быть переопределён метод before_action() */
    protected $ui_config_class = null;

    /**
     *
     */
    protected function init() {
        $this->landing = nc_landing::get_instance($this->site_id);
    }

    /**
     *
     */
    protected function before_action() {
    }

    protected function after_action($result) {
        if (!$this->use_layout) {
            return $result;
        }

        BeginHtml(NETCAT_MODULE_LANDING, '', '');
        echo $result;
        EndHtml();
        return '';
    }

    /**
     * @return string
     */
    protected function get_script_path() {
        return nc_module_path('landing') . 'admin/?controller=' . $this->get_short_controller_name() . '&action=';
    }

    /**
     *
     */
    protected function get_short_controller_name() {
        preg_match("/^nc_landing_(.+)_admin_controller$/", get_class($this), $matches);
        if ($matches) {
            return $matches[1];
        }
        die('Non-standard controller class name; please override ' . __METHOD__ . '() or methods that use it');
    }

    /**
     * @param string $view
     * @param array $data
     * @return nc_ui_view
     */
    protected function view($view, $data = array()) {
        // Если view отсутствует в папке, где он должен быть, пробуем искать
        // в родительской папке (типовые шаблоны, например form.view.php, empty_list.view.php)
        $view_file_name = "$view.view.php";
        $view_file_path = rtrim($this->view_path, DIRECTORY_SEPARATOR);
        $max_levels_to_inspect = 2;
        while (--$max_levels_to_inspect) {
            if (file_exists($view_file_path . '/' . $view_file_name)) {
                break;
            }
            $view_file_path = dirname($view_file_path);
        }

        $view = nc_core('ui')->view($view_file_path . '/' . $view_file_name, $data)
          ->with('current_url', $this->get_script_path())
          ->with('site_id', $this->site_id)
          ->with('controller_name', $this->get_short_controller_name());

        return $view;
    }

    /**
     * @param string $action
     * @param string $params
     */
    protected function redirect_to_index_action($action = 'index', $params = '') {
        $location = $this->get_script_path() . $action .
                    ($params[0] == '&' ? $params : "&$params");
        ob_clean();
        header("Location: $location");
        die;
    }

}
