<?php

/**
 * Класс для реализации "разделителя"
 */
class nc_a2f_field_divider extends nc_a2f_field {

    protected $can_have_initial_value = false;

    function render_value_field($html = true) {
        return "";
    }

}