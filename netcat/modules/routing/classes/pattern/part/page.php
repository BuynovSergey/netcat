<?php

class nc_routing_pattern_part_page extends nc_routing_pattern_part {

    public function match(nc_routing_request $request, nc_routing_result $result) {
        if (nc_preg_match('/^(\d+)/', $result->get_remainder(), $matches)) {
            $match = $matches[1];
            $result->set_variable('nc_page', $match);
            $result->truncate_remainder(strlen($match));
            return true;
        }
        else {
            return false;
        }
    }

    public function substitute_values_for(nc_routing_path $path, nc_routing_pattern_parameters $parameters) {
        // параметр ресурса page может быть установлен в nc_browse_messages(),
        // он имеет приоритет над $nc_page:
        $page = $path->get_resource_parameter('page');

        if (!$page) {
            // также значение может быть взято из дополнительной переменной nc_page:
            $page = $path->get_variable('nc_page');
        }

        $page = (int)$page;

        if ($page) {
            $parameters->used_variables['nc_page'] = true;
            $parameters->used_variables['curPos'] = true;
            return $page;
        }
        else {
            return false;
        }
    }

}