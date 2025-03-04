<?php

class nc_a2f_field_hidden extends nc_a2f_field {

    public function render($html = true) {
        return $this->render_value_field();
    }

    public function render_value_field($html = true) {
        $ret = "<input name='" . $this->get_field_name() .
               "' type='hidden' value='" . $this->get_value_for_input() . "'>";

        return $ret;
    }


}