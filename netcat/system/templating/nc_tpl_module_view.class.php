<?php

class nc_tpl_module_view {
    private $template = null;
    private static $instanse = null;

    public function __construct() {
        self::$instanse = $this;
        $this->template = new nc_tpl(nc_Core::get_object()->MODULE_TEMPLATE_FOLDER, nc_Core::get_object()->db);
    }

    public function load($id, $type) {
        $this->template->load($id, $type, "/$id/$type/");
    }

    public function get_field($field){
        $field_path = $this->get_field_path($field);
        return nc_check_file($field_path) ? nc_get_file($field_path) : false;
    }

    public function get_field_path($field) {
        return $this->template->fields->get_path($field);
    }

    public static function get_instanse() {
        return self::$instanse;
    }

    public function fill() {
        foreach ($this->module_types as $type => $obj) {
            $this->fields[$type] = $obj->fill_fields()->get_fields();
        }
        return $this;
    }

    public function fill_fields() {
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
        return $this->fields;
    }

    public function get_fields() {
         return $this->template->fields->standart;
    }
}