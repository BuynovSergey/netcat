<?php

class nc_tpl_widget_editor {

    private $template = null;

    public function __construct($path, nc_Db $db) {
        $this->template = new nc_tpl($path, $db);
    }

    public function load($id, $path = null) {
        $this->template->load($id, 'Widget_Class', $path);
        return $this;
    }

    public function fill_fields(array $fields = array ()) {
        if (empty($fields)) {
            foreach ($this->template->fields->standart as $field_name => $tmp) {
                $this->template->fields->standart[$field_name] = $this->get_content($field_name);
            }
        } else {
            $this->template->fields->standart = $fields;
        }
    }

    private function get_content($field) {
        $field_path = $this->template->fields->get_path($field);
        return file_exists($field_path) ? nc_get_file($field_path) : false;
    }

    public function get_field($field) {
        return $this->template->fields->standart[$field];
    }

    public function get_fields() {
        return $this->template->fields->standart;
    }

    public function save_fields($params = null, $only_isset_post = false) {
        foreach ($this->template->fields->standart as $field => $tmp) {
            if (!$only_isset_post || isset($_POST[$field])) {
                nc_save_file($this->template->fields->get_path($field), stripslashes($params ? $params[$field] : $_POST[$field]));
            }
        }
    }

    public function save_new($params) {
        nc_create_folder($this->template->absolute_path);
        $this->save_fields($params);
        $this->template->update_file_path_and_mode();
    }

    public function delete_template() {
        $this->template->delete_template_file_and_folder();
    }

    public function get_template_id() {
        return $this->template->id;
    }

    public function get_absolute_path() {
        return $this->template->absolute_path;
    }

    public function get_relative_path() {
        return $this->template->relative_path;
    }

    public function get_clear_filds($params_text) {
        $keys = array_keys($this->template->fields->standart);
        $fields = array_flip($keys);
        return array_diff($params_text, $fields);
    }

    public function update_keyword($new_keyword) {
        $this->template->update_relative_path_last_fragment($new_keyword);
    }

}