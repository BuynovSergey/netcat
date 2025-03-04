<?php

class nc_tpl_template_view {

    private $is_preview = null;
    private $template   = null;
    private $root_template_id;

    public function __construct($path, nc_Db $db) {
        $this->template = new nc_tpl($path, $db);
    }

    public function load_template($template_id, $relative_path = null, $is_preview = false) {
        $this->template->load($template_id, 'Template', $relative_path);
        $this->is_preview = $is_preview;

        $core_template = nc_Core::get_object()->template;
        $all_id = array();
        $current_template_id = $template_id;
        while ($current_template_id) {
            $all_id[] = $this->root_template_id = $current_template_id;
            $current_template_id = $core_template->get_by_id($current_template_id, 'Parent_Template_ID');
        }

        foreach ($all_id as $id) {
            $hash = $core_template->get_by_id($id, 'File_Hash');
            if ($hash) {
                $template = new nc_tpl($this->template->path_to_root_folder, $this->template->db);
                $template->load($id, 'Template', $core_template->get_by_id($id, 'File_Path'));
                nc_tpl_parser::main2parts($template, $hash);
            }
        }
    }

    public function get_all_settings_path_in_array() {
        $settings_path_array = array();
        $file = $this->template->fields->get_path('Settings');
        for ($i = 0; $i < $this->template->count_parent; $i++) {
            if (nc_check_file($file)) {
                $settings_path_array[] = $file;
                $file = nc_get_path_to_parent_file($file);
            }
        }
        return array_reverse($settings_path_array);
    }

    public function get_all_header_path_in_array() {
        $settings_path_array = array();
        $file = $this->template->fields->get_path('Header');
        for ($i = 0; $i < $this->template->count_parent; $i++) {
            if (nc_check_file($file)) {
                $settings_path_array[] = $file;
                $file = nc_get_path_to_parent_file($file);
            }
        }
        return array_reverse($settings_path_array);
    }

    /**
     * Подключает все библиотеки, перечисленные в RequiredAssets.html макета
     * и его родителей
     */
    public function include_all_required_assets() {
        return $this->template->include_all_required_assets();
    }

    public function fill_fields() {
        $this->fill_standart_fields();
        //if ($this->is_preview) { #weird hack, for nc_browse_messages, load all tpl settings in fs mode
            $this->fill_settings();
        //}
    }

    private function fill_standart_fields() {
        foreach ($this->template->fields->standart as $name => $value) {
            if ($name == 'Settings') {
                continue;
            }
            $this->template->fields->standart[$name] = $this->get_template($name);
        }
    }

    private function fill_settings() {
        $result = '';
        $all_settings_path = $this->get_all_settings_path_in_array();
        //array_pop($all_settings_path); #weird hack, for nc_browse_messages, load all tpl settings in fs mode
        foreach ($all_settings_path as $path) {
            $result .= $this->get_template_preview_one_level($path);
        }

        if (empty($_SESSION['PreviewTemplate'][$this->template->id]['Settings'])) {
            $template_settings = '';
        } else {
            $template_settings = $this->prepare($_SESSION['PreviewTemplate'][$this->template->id]['Settings']);
        }

        $this->template->fields->Settings = $result . $template_settings;
    }

    private function get_template($template_type) {
        $template_path = $this->template->fields->get_path($template_type);
        $template_method = !$this->is_preview ? 'get_template_one_level' : 'get_template_preview_one_level';
        $template = !$this->is_preview ? $this->$template_method($template_path) : $this->prepare($_SESSION['PreviewTemplate'][$this->template->id][$template_type]);
        $template = $template || $this->template->count_parent == 1 ? $template : "%$template_type";
        $count_position = 1;

        while (stripos($template, "%$template_type") !== false && $this->template->count_parent > $count_position) {
            $template_path = nc_get_path_to_parent_file($template_path);
            $parent_template = $this->$template_method($template_path);
            $parent_template = $parent_template || $this->template->count_parent == $count_position ? $parent_template : "%$template_type";
            $template = str_ireplace("%$template_type", $parent_template, $template);
            $count_position++;
        }

        return $template;
    }

    private function get_template_one_level($file) {
        if (nc_check_file($file)) {
            extract($GLOBALS, EXTR_SKIP);
            ob_start();
            include $file;
            return ob_get_clean();
        }
    }

    private function get_template_preview_one_level($file) {
        return nc_check_file($file) ? nc_get_file($file) : false;
    }

    public function get_header() {
        return $this->template->fields->standart['Header'];
    }

    public function get_footer() {
        return $this->template->fields->standart['Footer'];
    }

    public function get_settings() {
        return $this->template->fields->Settings;
    }

    private function prepare($str) {
        return function_exists('get_magic_quotes_gpc') && get_magic_quotes_gpc() ? stripcslashes($str) : $str;
    }

    public function partial($keyword, $data = array()) {
        return new nc_partial_template_partial($this->root_template_id, $keyword, $data);
    }

}