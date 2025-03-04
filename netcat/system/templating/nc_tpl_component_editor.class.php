<?php

class nc_tpl_component_editor {
    private $hash = null;
    private $template = null;
    private static $instanse = null;

    public function __construct($path, nc_Db $db, $type = null) {
        self::$instanse = $this;
        $this->template = new nc_tpl($path, $db, $type);
    }

    public function load($id, $path = null, $hash = null) {
        $this->hash = $hash;
        $this->template->load($id, 'Class', $path);
    }

    public function fill_fields() {
        if ($this->hash) {
            nc_tpl_parser::main2parts($this->template, $this->hash);
        }

        foreach ($this->template->fields->standart as $field_name => $tmp) {
            $this->template->fields->standart[$field_name] = $this->get_content($field_name);
        }
    }


    private function get_content($field) { 
        $field_path = $this->template->fields->get_path($field);
        return nc_check_file($field_path) ? nc_get_file($field_path) : false;
    }

    public function get_fields() {
        return $this->template->fields->standart;
    }

    public function save_fields($only_isset_post = false, $template = null, $fld_content = null) {
        if (null == $template) {
            $template = $this->template;
        }

        if ($fld_content === null) {
            $fld_content = nc_core('input')->fetch_post();
        }

        foreach ($template->fields->standart as $field => $field_settings) {
            if (!$only_isset_post || isset($fld_content[$field])) {
                if ($field == 'RecordTemplate') {
                    $fld_content['RecordTemplate'] = nc_get_string_service_prefix_for_RecordTemplate()
                        . nc_cleaned_RecordTemplate_of_string_service(nc_array_value($fld_content, 'RecordTemplate', ''))
                        . nc_get_string_service_suffix_for_RecordTemplate();
                }

                $file_path = $template->fields->get_path($field);

                if (nc_array_value($field_settings, 'is_optional') && !strlen(trim(nc_array_value($fld_content, $field)))) {
                    if (file_exists($file_path)) {
                        unlink($file_path);
                    }
                }
                else {
                    nc_save_file($file_path, nc_array_value($fld_content, $field));
                }
            }
        }
        nc_tpl_parser::parts2main($template);
    }

//    public function get_parent_class_id($id) {
//        return $this->template->get_parent_class_id($id);
//    }

    public function save_new_class($id = null, $fld_content = null) {
        if ($id) {
            $this->template->load_child($id);
            $template = $this->template->child;
        } else {
            $template = $this->template;
        }
        nc_create_folder($template->absolute_path);
        $this->save_fields(false, $template, $fld_content);
        $template->update_file_path_and_mode();
    }

    public function delete_template() {
        $this->template->delete_template_file_and_folder();
    }

    public function get_template_id() {
        return $this->template->id;
    }

    public function get_relative_path() {
        return $this->template->relative_path;
    }

    public function get_absolute_path() {
        return $this->template->absolute_path;
    }
    
    public function get_clear_fields($params_text) {
        return array_diff($params_text, array_keys($this->template->fields->standart));
    }

    public static function get_instanse() {
        return self::$instanse;
    }

    public function update_keyword($new_keyword) {
        $this->template->update_relative_path_last_fragment($new_keyword);
    }
}