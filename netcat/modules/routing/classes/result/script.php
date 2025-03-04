<?php

class nc_routing_result_script extends nc_routing_result {

    protected $match_parameters = array('script_path');

    /**
     *
     */
    protected function has_all_resource_parameters() {
        return strlen($this->get_resource_parameter('script_path')) > 0;
    }

}