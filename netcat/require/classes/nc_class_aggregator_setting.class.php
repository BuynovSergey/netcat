<?php

class nc_class_aggregator_setting {
    public $ignore_catalogue = false;
    
    private $fields = array();
    private $classes = array();
    private static $instanse = null;

    public function __construct() {
        self::$instanse = $this;
    }

    public static function get_instanse() {
        return self::$instanse;
    }

    public function add_class($class_id) {
        $this->classes[$class_id] = new nc_class_aggregator_setting_class($this);
        return $this->classes[$class_id];
    }

    public function classes($class_id) {
        return $this->classes[$class_id];
    }

    public function count_fields() {
        return count($this->fields);
    }

    public function register_fields() {
        $this->fields = func_get_args();
    }

    public function get_complete_settings() {
        $classes_settings = array();
        foreach ($this->classes as $class_id => $obj_fields) {
            $classes_settings[$class_id] = array();
            $fields = $obj_fields->get_fields();

            for ($end = count($this->fields), $i = 0; $i < $end; ++$i) {
                $classes_settings[$class_id][$fields[$i]] = $this->fields[$i];
            }
        }
        return $classes_settings;
    }

}

class nc_class_aggregator_setting_class {
    private $nc_class_aggregator_setting = null;
    private $fields = array();
    private $field_as_message_name = '';

    public function __construct(nc_class_aggregator_setting $nc_class_aggregator_setting) {
        $this->nc_class_aggregator_setting = $nc_class_aggregator_setting;
    }

    public function register_fields() {
        $this->fields = func_get_args();
        if (count($this->fields) != $this->nc_class_aggregator_setting->count_fields()) {
            throw new Exception('number of fields does not match');
        }
        return $this;
    }

    public function field_as_message_name($name) {
        $this->field_as_message_name = $name;
        return $this;
    }

    public function get_field_as_message_name() {
        return $this->field_as_message_name;
    }

    public function get_fields() {
        return $this->fields;
    }
}