<?php

class nc_routing_pattern_part_object_id extends nc_routing_pattern_part {

    public function match(nc_routing_request $request, nc_routing_result $result) {
        $infoblock_id = $result->get_infoblock_id();
        if ($infoblock_id && preg_match('/^(\d+)/', $result->get_remainder(), $matches)) {
            $object_id = $matches[0];
            $infoblock_settings = nc_core::get_object()->sub_class->get_by_id($infoblock_id);

            list($object_id, $real_object_keyword) = (array)ObjectExistsByID(
                $infoblock_settings['Class_ID'],
                $infoblock_settings['sysTbl'],
                $object_id,
                $result->get_resource_parameter('date')
            );

            if ($object_id) {
                $result->set_resource_parameter('object_id', $object_id);
                $result->set_resource_parameter('object_keyword', $real_object_keyword);
                $result->truncate_remainder(strlen($object_id));
                return true;
            }
        }
        return false;
    }

    public function substitute_values_for(nc_routing_path $path, nc_routing_pattern_parameters $parameters) {
        $object_id = $path->get_resource_parameter('object_id');
        if ($object_id) {
//            $parameters->object_id = $object_id;
            return $object_id;
        }
        else {
            return false;
        }
    }

}