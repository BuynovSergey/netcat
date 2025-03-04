<?php

class nc_tpl_component_view {
    private $template = null;
    private static $instanse = null;

    public function __construct($path, nc_Db $db) {
        self::$instanse = $this;
        $this->template = new nc_tpl($path, $db);
    }

    public function load($id, $path = null, $hash = null) {
        $this->template->load($id, 'Class', $path);
        if ($hash) {
            nc_tpl_parser::main2parts($this->template, $hash);
        }
    }

    public function get_fields() {
        return $this->template->fields->standart;
    }

    public function get_field_path($field) {
        return $this->template->fields->get_path($field);
    }

    public function get_parent_field_path($field) {
        return $this->template->fields->get_parent_path($field);
    }

    /**
     * Создаёт для супервизора сообщение об исключении или catchable ошибке в шаблоне $file_path компонента
     * @param string $template_path
     * @param Throwable $exception
     * @return string
     */
    public function get_error_message($template_path, $exception) {
        global $perm;
        if (!$perm instanceof Permission || !$perm->isSupervisor()) {
            return '';
        }

        $template_type = pathinfo($template_path, PATHINFO_FILENAME);
        $template_meta = nc_array_value($this->get_fields(), $template_type);
        if ($this->template->id && !empty($template_meta['path'])) {
            $link = nc_core::get_object()->ADMIN_PATH . "#classtemplate_fs.$template_meta[path]({$this->template->id})";
            $template_name =
                '<a href="' . $link . '" target="_blank" style="display: inline; color: white; text-decoration: underline">' .
                constant('CONTROL_CLASS_CLASS_' . $template_meta['name']) .
                '</a>';
        } else if (!empty($template_meta['name'])) {
            $template_name = constant('CONTROL_CLASS_CLASS_' . $template_meta['name']);
        } else {
            $template_name = $template_type;
        }

        $error = $exception->getMessage();
        if (strpos($error, 'Parse error') !== false) {
            // так как для PHP 5 код проверяется собственной функцией через eval, сообщение содержит
            // имя скрипта с этой функцией и номера строк в нём — уберём их, чтобы не запутывать
            $error = strip_tags($error);
            $error = preg_replace('/ in .+$/', '', $error);
        } else {
            if (realpath($template_path) !== realpath($exception->getFile())) {
                $error .= ' in ' . $exception->getFile();
            }
            $error .= ' on line ' . $exception->getLine();
        }

        $template_name = '<i style="display: inline">' . ($template_name ?: $template_type) . '</i>';

        return
            '<div class="nc-component-template-error">' .
            sprintf(CONTROL_CLASS_CLASSFORM_CHECK_ERROR, $template_name) . ': ' . $error .
            '</div>';
    }

    /**
     * Выводит (echo) сообщение об ошибке, полученное в $this->get_error_message()
     * @param string $template_path
     * @param Throwable $exception
     */
    public function print_error_message($template_path, $exception) {
        echo $this->get_error_message($template_path, $exception);
    }

    /**
     * Подключает все библиотеки, перечисленные в RequiredAssets.html шаблона
     * и компонента
     */
    public function include_all_required_assets() {
        return $this->template->include_all_required_assets();
    }

    public static function get_instanse() {
        return self::$instanse;
    }
}