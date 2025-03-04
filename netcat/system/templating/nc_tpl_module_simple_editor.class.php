<?php

class nc_tpl_module_simple_editor {

    private $template = null;
    private $fields = array();
    private $types = array();
    private $id;
    private $template_id;
    private static $instanse = null;

    public function __construct() {
        self::$instanse = $this;
        $this->template = new nc_tpl(nc_Core::get_object()->MODULE_TEMPLATE_FOLDER, nc_Core::get_object()->db);
    }

    public function get_default_fields() {
        $this->fill();
        $fields = array_keys($this->get_all_fields());
        return array_fill_keys($fields, '');
    }

    public function load($id, $type) {
        $this->types[$type] = new self;
        $this->types[$type]->load_one($id, $type, "/$id/$type/");
        $this->template_id = $type;
        $this->id = $id;
        return $this;
    }

    public function load_one($id, $type, $path) {
        $this->template->load($id, $type, $path);
    }

    public function fill() {
        foreach ($this->types as $type => $obj) {
            $this->fields[$type] = $obj->fill_fields($type)->get_fields();
        }

        return $this;
    }

    public function fill_fields($template_id) {
        foreach ($this->template->fields->standart as $field_name => $tmp) {
            $this->template->fields->standart[$field_name] = $this->get_content($field_name);
        }
        return $this;
    }

    private function get_content($field) {
        $field_path = $this->template->fields->get_path($field);
        return nc_check_file($field_path) ? nc_get_file($field_path) : false;
    }

    public function get_all_fields() {
        return $this->fields[$this->template_id];
    }

    public function get_fields() {
         return $this->template->fields->standart;
    }

    public function save($post_fields) {
        foreach ($this->types as $module_type) {
            $module_type->save_fields($post_fields);
        }
    }

    public function save_fields($post_fields) {
        foreach ($this->template->fields->standart as $field_name => $tmp) {
            if (function_exists('get_magic_quotes_gpc') && get_magic_quotes_gpc()) {
                $post_fields[$field_name] = stripslashes($post_fields[$field_name]);
            }
            nc_save_file($this->template->fields->get_path($field_name), $post_fields[$field_name]);
        }
    }

    public function create($type, $settings) {
        $path = $this->template->path_to_root_folder.'/'.$this->id.'/'.$type;

        nc_create_folder(nc_standardize_path_to_folder($path));

        foreach ($settings as $field_name => $tmp) {
            nc_save_file(nc_standardize_path_to_file($path.'/'.$field_name.$this->template->extension), $settings[$field_name]);
        }
    }

    public static function get_instanse() {
        return self::$instanse;
    }

}