<?php

/**
 * Генерирует страницы для админки
 *
 * Входные параметры в $_REQUEST, управляющие поведением контроллера:
 *  → action — действие, которое будет выполнено (CRUD); соответствует имени
 *    метода в данном классе; допустимы 'save', 'delete'
 *     — data_class
 *     — id, ids[]
 *  → view — шаблон, который будет показан (после выполнения action, если оно
 *    задано)
 *
 * Don't let the name confuse you, it's not a pure/classic MVC
 *
 */
class nc_search_admin_controller {

    /**
     * @var array
     */
    protected $input;
    /**
     * @var array
     */
    protected $add_to_footer = array();
    /**
     *
     * @var nc_search_data_persistent
     */
    protected $action_record;

    /**
     *
     * @param array $input
     */
    public static function process_request(array $input) {
        $c = new self;
        $c->process($input);
    }

    /**
     *
     */
    protected function __construct() {
        ob_start();
    }

    /**
     * Выдать указанное сообщение об ошибке и остановить выполнение скрипта
     * @param string $message
     * @param string $param  any number of parameters to substitute in the $message (using sprintf())
     */
    protected function halt($message, $param = '') {
        $args = func_get_args();
        $message = call_user_func_array('sprintf', $args);
        nc_print_status($message, 'error');
        $this->print_footer();
        die;
    }

    /**
     * Выдать сообщение о неправильном параметре и остановить выполнение скрипта
     * @param <type> $param
     */
    protected function halt_param($param) {
        $this->halt(NETCAT_MODULE_SEARCH_ADMIN_INVALID_REQUEST, $param);
    }

    /**
     *
     * @param integer|string $key
     * @param mixed $default
     * @return mixed
     */
    protected function get_input($key, $default = null) {
        return isset($this->input[$key]) &&
               (is_array($this->input[$key]) || strlen($this->input[$key]))
                    ? $this->input[$key]
                    : $default;
    }

    /**
     * Возвращает параметр запроса в годном для использования в HTML виде,
     * опционально форматирует значение
     * @param type $key
     * @param type $format   format (sprintf())
     * @return string
     */
    protected function format_input($key, $format = null) {
        $value = $this->get_input($key, '');
        if (strlen($value) && $format) {
            $value = sprintf($format, $value);
        }
        return htmlspecialchars($value);
    }

    /**
     *
     */
    protected function escape_input($key) {
        return htmlspecialchars($this->get_input($key, ''));
    }

    /**
     *
     * @param array $input
     */
    public function process(array $input) {
        $this->input = $input;

        $ui_config    = isset($this->input['ui_config']) ? $this->input['ui_config'] : true;
        $print_header = isset($this->input['print_header']) ? $this->input['print_header'] : true;
        $print_footer = isset($this->input['print_footer']) ? $this->input['print_footer'] : true;

        if ($print_header) $this->print_header();
        $ui_location_params = null;
        $data_class = null;

        $action = $this->get_input('action');
        if ($action) {
            // check 'action' parameter
            if (!in_array($action, array('save', 'delete'))) {
                $this->halt_param('action');
            }

            // check 'data_class' parameter
            $data_class = $this->get_input('data_class');
            if (!$data_class || !is_subclass_of($data_class, 'nc_search_data_persistent')) {
                $this->halt_param('data_class');
            }
        }

        $view = $this->get_input('view', 'info');

        if ($ui_config) {
            $GLOBALS["UI_CONFIG"] = new nc_search_admin_ui($view, $ui_location_params);
        }

        if (!nc_search_permission::check($data_class ?: $view)) {
            $this->halt(NETCAT_MODERATION_ERROR_NORIGHTS);
        }

        if ($action) {
            $this->$action($data_class);
        }

        $this->show($view);
        if ($print_footer) $this->print_footer();
    }

    /**
     *
     */
    protected function get_ui() {
        return $GLOBALS["UI_CONFIG"];
    }

    /**
     *
     * @param string $help_page
     */
    protected function print_header($help_page = '') {
        BeginHtml($this->get_input('.page_title', NETCAT_MODULE_SEARCH), NETCAT_MODULES, '');
        // it's a dirty hack
        // уберите это, когда в конце концов перепишете админку
        $path = nc_search::get_module_url();
        $content = ob_get_clean();
        print str_replace("</head>",
                        "\n<link type='text/css' rel='Stylesheet' href='" . nc_add_revision_to_url("$path/admin.css") . "'>\n".
                        "<script type='text/javascript' src='" . nc_add_revision_to_url("$path/admin.js") . "'></script>\n".
                        "</head>",
                        $content);
        print "<div id='nc_search_admin'>";
    }

    /**
     *
     */
    protected function print_footer() {
        print join("\n", $this->add_to_footer);
        print "</div>"; // div id='nc_search_admin'
        EndHtml();
    }

    /**
     *
     * @param string $name
     * @return string
     */
    protected function get_template_path($name) {
        $path = nc_search::get_module_path()."/views/$name.php";
        if (!preg_match("/^\w+$/", $name) || !file_exists($path)) {
            $this->halt_param('view');
        }

        return $path;
    }

    /**
     *
     * @param string $template
     */
    protected function show($template) {
        $nc_core = nc_Core::get_object();
        $file_path = $this->get_template_path($template); // WILL VALIDATE THE TEMPLATE NAME
        require $file_path;
    }

    /**
     * @param string $data_class
     */
    public function save($data_class) {
        nc_core::get_object()->token->exit_if_invalid();
        $data = $this->get_input('data');
        $id = $this->get_input('id');
        if (!is_array($data)) {
            $this->halt_param('data');
        }
        /** @var nc_search_data_persistent $record */
        $record = new $data_class;
        $record->set_output_encoding(nc_core('NC_CHARSET'));

        // try to load the record (saved data might be partial, and we don't want to
        // throw away data that is already there
        if ($id) {
            try {
                $record->load($id);
            } catch (nc_search_data_exception $e) {

            }
        }

        try {
            $record->set_values($data, true)->set_id($id)->save();
            nc_print_status(NETCAT_MODULE_SEARCH_ADMIN_SAVE_OK, 'ok');
            $this->action_record = $record;
        } catch (nc_search_data_exception $e) {
            nc_print_status(NETCAT_MODULE_SEARCH_ADMIN_SAVE_ERROR."<br />".$e->getMessage(), 'error');
        }
    }

    /**
     *
     * @param string $data_class
     */
    public function delete($data_class) {
        nc_core::get_object()->token->exit_if_invalid();
        $ids = (array) $this->get_input('id', $this->get_input('ids'));
        foreach ($ids as $id) {
            /** @var nc_search_data_persistent $record */
            $record = new $data_class;
            $record->set_id($id)->delete();
        }
        // по-хорошему тут нужно использовать паттерн GET-POST-REDIRECT
        nc_print_status(NETCAT_MODULE_SEARCH_ADMIN_STATUS_DELETED, 'info');
    }

    /**
     *
     * @return null|nc_search_data_persistent
     */
    protected function get_action_record() {
        return $this->action_record;
    }

    /**
     * null || ''
     * @param mixed $value
     * @param mixed $if_null_value
     * @return mixed
     */
    protected function if_null($value, $if_null_value) {
        return ($value == null ? $if_null_value : $value);
    }

    /**
     *
     * @param string $data_class
     * @param string $next_view
     * @param bool|string $action_on_next_step
     * @return nc_search_data_persistent
     */
    protected function data_form($data_class, $next_view, $action_on_next_step = 'save') {
        /** @var nc_search_data_persistent $record */
        if (!preg_match('/^nc_search_\w+$/', $data_class)) {
            throw new InvalidArgumentException();
        }

        $record = new $data_class();
        $record->set_output_encoding(nc_core('NC_CHARSET'));

        $id = (int)$this->get_input('id') ?: '';
        if ($id) {
            try {
                $record->load($id);
            } catch (nc_search_data_exception $exception) {
                $this->halt(NETCAT_MODULE_SEARCH_ADMIN_RECORD_NOT_FOUND, $id);
            }
            $this->get_ui()->add_location_parameters($id);
        }

        echo "<form class='search' action='?view=$next_view' method='post'>",
            nc_core::get_object()->token->get_input(),
            ($action_on_next_step ? "<input type='hidden' name='action' value='$action_on_next_step' />" : ""),
            "<input type='hidden' name='data_class' value='$data_class' />",
            "<input type='hidden' name='id' value='$id' />";

        $this->add_to_footer[] = "</form>";

        return $record;
    }

    /**
     *
     */
    protected function get_language_list($include_empty = false) {
        $prepend = ($include_empty ? array('' => NETCAT_MODULE_SEARCH_ADMIN_LANGUAGE_ANY_LANGUAGE) : array());
        return array_merge($prepend, nc_Core::get_object()->lang->get_all());
    }

    /**
     *
     */
    protected function hidden($name, $value) {
        return "<input type='hidden' name='$name' value='".htmlspecialchars($value)."' />";
    }

    /**
     *
     */
    protected function redirect($url) {
        ob_end_clean();
        header("Location: $url");
        die;
    }

    /**
     *
     */
    protected function hash_href($hash) {
        return $GLOBALS["ADMIN_PATH"].$hash;
    }

    /**
     *
     */
    protected function hash_link($hash, $text) {
        return "<a target='_top' href='{$this->hash_href($hash)}'>$text</a>";
    }

    /**
     * @return nc_Db
     */
    protected function get_db() {
        return nc_Core::get_object()->db;
    }

    /**
     *
     */
    protected function link_if($condition, $href, $text) {
        if ($condition) {
            return "<a href='$href'>$text</a>";
        }
        return $text;
    }

    /**
     *
     */
    protected function make_page_query($exclude = array(), $absolute_path = false) {
        $params = $this->input;
        foreach ($exclude as $param) {
            unset($params[$param]);
        }
        $path = ($absolute_path ? nc_search::get_module_url()."/admin.php" : "");
        return "$path?".http_build_query($params, null, "&amp;");
    }

    /**
     * Setting checkbox
     */
    protected function setting_cb($option, $caption, $override_value = null) {
        $value = ($override_value === null ? nc_search::should($option) : $override_value);
        return
            "<div class='setting'>
            <input type='hidden' name='s[$option]' value='0' />
            <input type='checkbox' name='s[$option]' value='1' id='cb_$option'".
            ($value ? " checked" : "")."/> ".
            "<label for='cb_$option'>$caption</label>
            </div>\n";
    }

    /**
     *
     */
    protected function result_count($first, $per_page, $total) {
        if ($per_page >= $total) {
            return "";
        }
        $last = $first + $per_page - 1;
        if ($last > $total) {
            $last = $total;
        }
        if ($total == $first) {
            $msg = sprintf(NETCAT_MODULE_SEARCH_ADMIN_RESULTS_ONE, $first, $total);
        } else {
            $msg = sprintf(NETCAT_MODULE_SEARCH_ADMIN_RESULTS_MANY, $first, $last, $total);
        }
        return "<div class='result_count'>$msg</div>";
    }

}
