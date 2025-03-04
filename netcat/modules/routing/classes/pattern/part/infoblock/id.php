<?php

class nc_routing_pattern_part_infoblock_id extends nc_routing_pattern_part {

    public function match(nc_routing_request $request, nc_routing_result $result) {
        if (preg_match('/^(\d+)/', $result->get_remainder(), $matches)) {
            $infoblock_id = $matches[0];
            $result->set_resource_parameter('infoblock_id', $infoblock_id);
            $result->truncate_remainder(strlen($infoblock_id));
            return true;
        }
        return false;
    }

    public function substitute_values_for(nc_routing_path $path, nc_routing_pattern_parameters $parameters) {
        $infoblock_id = $path->get_resource_parameter('infoblock_id');
        if ($infoblock_id) {
//            $parameters->infoblock_id = $infoblock_id;
            return $infoblock_id;
        }
        else {
            return false;
        }
    }

}