<?php

class nc_condition_always extends nc_condition {

    public function __construct($parameters = array()) {}

    /**
     * Полное описание: название свойства + значение
     * @return string
     */
    public function get_full_description() {
        return '';
    }

    /**
     * Короткое описание (только значение, для повторяющихся условий)
     * @return string
     */
    public function get_short_description() {
        return '';
    }


}