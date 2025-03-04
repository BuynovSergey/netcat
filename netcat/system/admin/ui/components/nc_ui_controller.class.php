<?php


class nc_ui_controller {

    protected $view_path = null;
    protected $current_action = 'index';
    protected $binds = array();

    /**
     * @var ui_config
     */
    protected $ui_config;

    /**
     * @var nc_core
     */
    protected $nc_core;

    /**
     * @var nc_db
     */
    protected $db;

    /**
     * @var nc_input
     */
    protected $input;

    /**
     * @var  int
     */
    protected $site_id;

    /**
     * @var bool
     */
    protected $set_current_site_id_as_default = true;

    /**
     * @var bool
     */
    protected $use_layout = true;


    public function __construct($view_path = null) {
        if ($view_path) {
            $this->set_view_path($view_path);
        }

        $this->ui_config =& $GLOBALS['UI_CONFIG'];
        $this->nc_core = nc_core();
        $this->db = nc_core('db');
        $this->input = nc_core('input');

        $this->site_id = $this->determine_site_id();

        $this->init();
    }


    /**
     * @return void
     */
    protected function init() {
    }

    /**
     * $view->with(...)
     *
     * @param nc_ui_view $view
     *
     * @return nc_ui_view|void
     */
    protected function init_view(nc_ui_view $view) {
    }

    /**
     * @return int
     */
    public function determine_site_id() {
        $nc_core = nc_core::get_object();
        $cookie_name = 'nc_admin_site_id';
        $cookie_id = $nc_core->input->fetch_cookie($cookie_name);
        $new_id = $nc_core->input->fetch_post_get('site_id');

        if (strlen($new_id)) {
            $nc_core = nc_core::get_object();
            $site_id = (int)$new_id;
            $nc_core->cookie->set($cookie_name, $site_id, 0);
        } else if (strlen($cookie_id)) {
            $site_id = (int)$cookie_id;
        } else if ($this->set_current_site_id_as_default) {
            $site_id = (int)$nc_core->catalogue->get_current('Catalogue_ID');
        } else {
            $site_id = null;
        }

        if ($site_id) {
            // Проверка сайта на существование
            try {
                $nc_core->catalogue->get_by_id($site_id);
            } catch (Exception $e) {
                $site_id = (int)$nc_core->catalogue->get_current('Catalogue_ID');
            }
        }

        $filter = $this->get_site_list_filter($this->current_action);

        if ($site_id && !$filter($site_id)) {
            $site_id = null;
        }

        if (!$site_id && $this->set_current_site_id_as_default) {
            $all_sites = array_keys((array)$nc_core->catalogue->get_all());

            foreach ($all_sites as $site) {
                if ($filter($site)) {
                    $site_id = $site;
                    break;
                }
            }
        }

        if (!$site_id) {
            $site_id = 0;
        }

        return $site_id;
    }

    /**
     * @return callable
     */
    protected function get_site_list_filter() {
        return function () {
            return true;
        };
    }

    /**
     * Генерирует функцию для проверки прав для модуля на сайте (для метода nc_ui_controls::site_select())
     *
     * @param string $module_keyword
     * @param string|null $action
     *
     * @return Closure
     */
    protected function get_site_module_checker($module_keyword, $action = null) {
        /** @var Permission $perm */
        global $perm;

        $check_function = function ($site_id) use ($perm, $module_keyword, $action) {
            return $action
                ? $perm->hasModulePermission($module_keyword, $action, $site_id)
                : $perm->hasAnyModulePermission($module_keyword, $site_id);
        };

        return $check_function;
    }

    /**
     * @return string
     */
    protected function before_action() {
        return '';
    }

    /**
     * @param mixed $result
     *
     * @return true
     */
    protected function after_action($result) {
        return true;
    }


    /**
     *
     * $this->bind('save', array('id', 'message'));
     * $this->bind('save', array('id', 'file'=>$input->fetch_files('file')) );
     *
     * @param string $action
     * @param array $request_keys
     *
     * @return void
     */
    protected function bind($action, array $request_keys) {
        $this->binds[$action] = $request_keys;
    }

    /**
     * @param string $path
     *
     * @return void
     */
    public function set_view_path($path) {
        $this->view_path = rtrim($path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
    }

    /**
     * @param $action
     *
     * @return void
     */
    public function set_action($action) {
        $this->current_action = $action;
    }

    /**
     * @param $action
     * @param array $args
     *
     * @return bool|mixed|nc_ui_alert
     */
    public function execute($action = null, array $args = array()) {
        if ($action) {
            $this->set_action($action);
        }

        $action_method = 'action_' . $this->current_action;

        if (!method_exists($this, $action_method)) {
            return false;
        }

        $result = $this->before_action();

        if ($result === false) {
            return false;
        }

        if (!$args && isset($this->binds[$action])) {
            foreach ($this->binds[$action] as $key => $value) {
                if (is_numeric($key)) {
                    $key = $value;
                    $value = $this->input->fetch_post_get($key);
                    if (!$value) {
                        $value = $this->input->fetch_files($key);
                    }
                }
                $args[$key] = $value;
            }
        }

        try {
            $result = call_user_func_array(array($this, $action_method), array_values($args));
        } catch (Exception $e) {
            return $this->nc_core->ui->alert->error($e->getMessage() ?: get_class($e));
        }


        $after_result = $this->after_action($result);

        return is_null($after_result) || $after_result === true ? $result : $after_result;
    }


    /**
     * @param $view
     * @param array $data
     *
     * @return nc_ui_view
     */
    protected function view($view, $data = array()) {
        $view = nc_core('ui')->view($this->view_path . $view . '.view.php', $data);
        $result = $this->init_view($view);

        return $result === null ? $view : $result;
    }


    /**
     * Завершает выполнение скрипта при отсутствии прав на действие
     *
     * @param int|array $instance_type константа NC_PERM_* или массив [NC_PERM_MODULE, 'keyword'] для модуля
     * @param int $action константа NC_PERM_ACTION_*
     * @param int $id ID записи
     * @param bool $posting производится запись?
     */
    protected function check_permissions($instance_type, $action = 0, $id = 0, $posting = false) {
        /** @var Permission $perm */
        global $perm;

        if (!$perm->isAccess($instance_type, $action, $id, $posting)) {
            while (@ob_end_clean())
                ;

            BeginHtml();
            nc_print_status(NETCAT_MODERATION_ERROR_NORIGHT, 'error');
            EndHtml();
            exit();
        }
    }

    /**
     * @param string $message
     *
     * @return nc_ui_view
     */
    protected function error_view($message) {
        $this->ui_config->clear_action_buttons();
        $this->ui_config->add_back_button();

        return $this->view("error_message")->with("message", $message);
    }

    /**
     * @param array|null $data
     * @param int $http_status
     *
     * @return string
     */
    protected function json_response(array $data = null, $http_status = nc_http::HTTP_STATUS_OK) {
        $this->use_json_response($http_status);

        return nc_array_json($data ?: array());
    }

    /**
     * @param array|null $data
     * @param int $http_status
     *
     * @return string
     */
    public function json_success(array $data = null, $http_status = nc_http::HTTP_STATUS_OK) {
        $this->use_json_response($http_status);

        return $this->json_response(array(
            "result" => $data ?: array(),
        ));
    }

    /**
     * @param string $message
     * @param int $code
     * @param array $details
     * @param int $http_code
     *
     * @return string
     */
    protected function json_error($message = "Internal error", $code = 0, array $details = array(),
        $http_code = nc_http::HTTP_STATUS_INTERNAL_SERVER_ERROR) {

        $this->use_json_response($http_code);

        $result = array(
            "message" => $message,
            "code" => $code,
        );

        if (!empty($details)) {
            $result["details"] = $details;
        }

        return nc_array_json($result);
    }

    /**
     * @param array $errors
     *
     * @return string
     */
    protected function json_bad_request(array $errors) {
        $this->use_json_response(nc_http::HTTP_STATUS_BAD_REQUEST);

        return nc_array_json(array(
            "message" => "Validation error",
            "details" => $errors,
            "code" => 0,
        ));
    }

    /**
     * @param int $http_code
     *
     * @return void
     */
    private function use_json_response($http_code) {
        $this->use_layout = false;

        nc_set_http_response_code($http_code);
        header("Content-Type: application/json");
    }

}
