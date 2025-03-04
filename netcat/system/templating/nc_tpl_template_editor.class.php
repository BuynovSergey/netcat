<?php

class nc_tpl_template_editor {
    private $hash = null;
    private $template = null;

    public function __construct($path, nc_Db $db) {
        $this->template = new nc_tpl($path, $db);
    }

    public function load_template($id, $relative_path = null, $hash = null) {
        $this->hash = $hash;
        $this->template->load($id, 'Template', $relative_path);
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

    public function get_standart_fields() {
        return $this->template->fields->standart;
    }

    public function save_fields($fields, $template = null) {
        $template = $template ? $template : $this->template;
        foreach ($fields as $name => $value) {
            nc_save_file($template->fields->get_path($name), $value);
        }
        nc_tpl_parser::parts2main($template);
    }

    public function save_new_template($fields, $child = true) {
        $template = $child && isset($this->template->child) ? $this->template->child : $this->template;
        nc_create_folder($template->absolute_path);
        $this->save_fields($fields, $template);
        $template->update_file_path_and_mode();
    }

    public function load_new_child($id) {
        $this->template->load_child($id);
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

    public function update_keyword($new_keyword) {
        $this->template->update_relative_path_last_fragment($new_keyword);
    }

}