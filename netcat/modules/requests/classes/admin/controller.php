<?php

/**
 * Типовой контроллер страниц административного интерфейса модуля.
 *
 * Серая магия:
 *  — К view автоматически добавляются переменные:
 *    — current_url
 *    — requests
 *    — controller_name — короткое название контроллера
 *
 */
abstract class nc_requests_admin_controller extends nc_ui_controller
{

    protected $use_layout = true;

    /** @var  nc_requests */
    protected $requests;

    /** @var  nc_requests_admin_ui */
    protected $ui_config;

    /** @var string  Должен быть задан, или должен быть переопределён метод before_action() */
    protected $ui_config_class = null;

    /**
     *
     */
    protected function init()
    {
        $this->requests = nc_requests::get_instance($this->site_id);
    }

    /**
     *
     */
    protected function before_action()
    {
        if ($this->ui_config_class) {
            $ui_config_class = $this->ui_config_class;
            $this->ui_config = new $ui_config_class($this->current_action);
        }
    }

    protected function after_action($result)
    {
        if (!$this->use_layout) {
            return $result;
        }

        BeginHtml(NETCAT_MODULE_REQUESTS, '', '');
        echo $result;
        EndHtml();
        return '';
    }

    /**
     * @return string
     */
    protected function get_script_path()
    {
        return nc_module_path('requests') . 'admin/?controller=' . $this->get_short_controller_name() . '&action=';
    }

    /**
     *
     */
    protected function get_short_controller_name()
    {
        preg_match("/^nc_requests_(.+)_admin_controller$/", get_class($this), $matches);
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
    protected function view($view, $data = array())
    {
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
          ->with('requests', $this->requests)
          ->with('controller_name', $this->get_short_controller_name());

        return $view;
    }

    /**
     * @param string $action
     * @param string $params
     */
    protected function redirect_to_index_action($action = 'index', $params = '')
    {
        $location = $this->get_script_path() . $action .
          ($params[0] == '&' ? $params : "&$params");
        ob_clean();
        header("Location: $location");
        die;
    }

}
