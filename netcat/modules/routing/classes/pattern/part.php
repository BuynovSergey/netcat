<?php

abstract class nc_routing_pattern_part {

    /**
     * @param nc_routing_request $request
     * @param nc_routing_result $result
     * @return bool
     */
    abstract public function match(nc_routing_request $request, nc_routing_result $result);

    /**
     * @param nc_routing_path $path
     * @param nc_routing_pattern_parameters $parameters
     * @return string|false
     */
    abstract public function substitute_values_for(nc_routing_path $path, nc_routing_pattern_parameters $parameters);

}