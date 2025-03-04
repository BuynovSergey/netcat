<?php

class nc_a2f_field_select_static extends nc_a2f_field_select {

    public function get_extend_parameters() {
        return array('values' => array('type' => 'static', 'caption' => NETCAT_CUSTOM_EX_ELEMENTS));
    }

}
