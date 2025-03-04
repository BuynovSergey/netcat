<?php

class nc_routing_pattern_part_infoblock_action extends nc_routing_pattern_part {

    public function match(nc_routing_request $request, nc_routing_result $result) {
        if (nc_preg_match('/^(add|search|subscribe)/', $result->get_remainder(), $matches)) {
            $action = $matches[1];
            $result->set_resource_parameter('action', $action);
            $result->truncate_remainder(strlen($action));
            return true;
        }
        return false;
    }

    public function substitute_values_for(nc_routing_path $path, nc_routing_pattern_parameters $parameters) {
        $action = $path->get_resource_parameter('action');
        if ($action && $action != 'index') {
            $parameters->action = $action;
            return $action;
        }
        else {
            return false;
        }
    }

}