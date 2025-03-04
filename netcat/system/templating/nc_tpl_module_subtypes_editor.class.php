<?php

class nc_tpl_module_subtypes_editor {

    protected $template    = null;
    private $fields        = array();
    /** @var nc_tpl_module_subtypes_editor[] */
    private $module_types  = array(
        'web'        => null,
        'mobile'     => null,
        'responsive' => null
    );
    private static $instanse = null;

    public function get_module_types() {
        return $this->module_types;
    }

    public function __construct($catalogue_id = false) {
        self::$instanse = $this;

        $nc_core = nc_Core::get_object();

        $this->template = new nc_tpl($nc_core->MODULE_TEMPLATE_FOLDER, $nc_core->db);
    }

    public function load($id) {
        foreach ($this->module_types as $type => $tmp) {
            $this->module_types[$type] = new self;
            $this->module_types[$type]->load_one($id, $type, "/{$id}/{$type}/");
        }

        return $this;
    }

    public function load_one($id, $type, $path) {
        $this->template->load($id, $type, $path);
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

    /**
     * Сохранение значения шаблонов
     *
     * @param array $post_fields Массив шаблонов
     * @param int|boolean $catalogue_id Использовать привязку к сайту | true - текущий сайт
     *
     * @return null
     */
    public function save($post_fields, $catalogue_id = false) {
        foreach ($this->module_types as $module_type) {
            if ($catalogue_id) {
                $module_type->template->use_catalogue($catalogue_id);
            }
            $module_type->save_fields($post_fields);
        }
    }

    public function save_fields($post_fields) {
        $magic_quotes = function_exists('get_magic_quotes_gpc') && get_magic_quotes_gpc();

        foreach ($this->template->fields->standart as $field_name => $tmp) {
            $field_key = $this->template->type . '_' . $field_name;
            if (isset($post_fields[$field_key])) {
                $data = $magic_quotes ? stripslashes($post_fields[$field_key]) : $post_fields[$field_key];
                nc_save_file($this->template->fields->get_path($field_name), $data);
            }
        }
    }

    public static function get_instanse() {
        return self::$instanse;
    }

}
