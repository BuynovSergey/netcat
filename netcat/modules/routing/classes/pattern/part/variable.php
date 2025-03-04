<?php

class nc_routing_pattern_part_variable extends nc_routing_pattern_part {

    protected $variable_name;
    protected $regexp = '!^([^/]+)!';

    public function __construct($variable_name, $regexp = null) {
        $this->variable_name = $variable_name;
        if ($regexp) {
            $this->regexp = "/^(" . addcslashes($regexp, '/') . ")/";
        }
    }

    public function match(nc_routing_request $request, nc_routing_result $result) {
        if (nc_preg_match($this->regexp, $result->get_remainder(), $matches)) {
            $match = $matches[1];
            $result->set_variable($this->variable_name, $match);
            $result->truncate_remainder(strlen($match));
            return true;
        }
        else {
            return false;
        }
    }

    public function substitute_values_for(nc_routing_path $path, nc_routing_pattern_parameters $parameters) {
        $value = $path->get_variable($this->variable_name);

        if ($value !== null && nc_preg_match($this->regexp, $value)) {
            $parameters->used_variables[$this->variable_name] = true;
            return $value;
        }
        else {
            return false;
        }
    }

}