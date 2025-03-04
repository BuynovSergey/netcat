<?php

class nc_routing_pattern_part_string extends nc_routing_pattern_part {

    protected $string;
    protected $length;

    public function __construct($string) {
        $this->string = $string;
        $this->length = strlen($string);
    }

    public function match(nc_routing_request $request, nc_routing_result $result) {
        $remainder = $result->get_remainder();
        for ($i = 0; $i < $this->length; $i++) {
            if ($remainder[$i] != $this->string[$i]) {
                return false;
            }
        }

        $result->truncate_remainder($this->length);
        return true;
    }

    public function substitute_values_for(nc_routing_path $path, nc_routing_pattern_parameters $parameters) {
        return $this->string;
    }

}