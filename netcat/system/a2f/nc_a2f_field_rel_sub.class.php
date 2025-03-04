<?php

/**
 * Класс для реализации поля типа "Связь с разделом"
 */
class nc_a2f_field_rel_sub extends nc_a2f_field_rel {

    public function __construct(array $field_settings = null, nc_a2f $parent = null) {
        parent::__construct($field_settings, $parent);
        $this->select_link = "related/select_subdivision.php?cs_type=rel_sub&cs_field_name=".$this->name;
        $this->width = 350;
    }

    public function get_relation_object() {
        return new field_relation_subdivision();
    }

}

