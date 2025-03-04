<?php

class nc_condition_dummy extends nc_condition {

    public function __construct($parameters = array()) {}

    /**
     * Короткое описание (только значение, для повторяющихся условий)
     * @return string
     */
    public function get_short_description() {
        return '';
    }

}