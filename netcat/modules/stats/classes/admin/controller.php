<?php

/**
 * Типовой контроллер страниц административного интерфейса модуля.
 *
 * Серая магия:
 *  — К view автоматически добавляются переменные:
 *    — current_url
 *    — site_id
 *    — stats
 *    — controller_name — короткое название контроллера, например 'analytics'
 */
abstract class nc_stats_admin_controller extends nc_ui_controller
{

    protected $use_layout = true;

    /** @var  nc_stats */
    protected $stats;

    /** @var  ui_config_module_stats */
    protected $ui_config;

    /** @var  string to be overridden in the child class */
    protected $ui_config_tab;

    /**
     *
     */
    protected function init() {
        $this->stats = nc_stats::get_instance($this->site_id);
    }

    /**
     *
     */
    protected function before_action() {
        require_once nc_module_folder('stats') . 'ui_config.php';
        $this->ui_config = new ui_config_module_stats($this->ui_config_tab, null, null);
    }

    protected function after_action($result) {
        if (!$this->use_layout) {
            return $result;
        }

        BeginHtml(NETCAT_MODULE_STATS, '', '');
        echo $result;
        EndHtml();
        return '';
    }

    /**
     * @return string
     */
    protected function get_script_path() {
        return nc_module_path('stats') . 'admin/?controller=' . $this->get_short_controller_name() . '&action=';
    }

    /**
     *
     */
    protected function get_short_controller_name() {
        preg_match("/^nc_stats_(.+)_admin_controller$/", get_class($this), $matches);
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

        $view = nc_core::get_object()->ui->view($view_file_path . '/' . $view_file_name, $data)
                    ->with('current_url', $this->get_script_path())
                    ->with('stats', $this->stats)
                    ->with('site_id', $this->site_id)
                    ->with('controller_name', $this->get_short_controller_name());

        return $view;
    }

    /**
     * @param string $action
     * @param string $params
     */
    protected function redirect_to_index_action($action = 'index', $params = '') {
        $location = $this->get_script_path() . $action . ($params[0] == '&' ? $params : "&$params");
        ob_clean();
        header("Location: $location");
        die;
    }

}
