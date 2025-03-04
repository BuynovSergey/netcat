<?php

/**
 * Класс для реализации поля типа "Связь с компонентов в разделе"
 */
class nc_a2f_field_rel_cc extends nc_a2f_field_rel {

    public function __construct(array $field_settings = null, nc_a2f $parent = null) {
        parent::__construct($field_settings, $parent);
        $this->select_link = "related/select_subclass.php?cs_type=rel_cc&cs_field_name=".$this->name;
        $this->width = 800;
    }

    public function get_relation_object() {
        return new field_relation_subclass();
    }

}

