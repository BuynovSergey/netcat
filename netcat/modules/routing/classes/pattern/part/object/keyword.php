<?php

class nc_routing_pattern_part_object_keyword extends nc_routing_pattern_part_keyword {

    public function match(nc_routing_request $request, nc_routing_result $result) {
        if (!nc_preg_match($this->get_keyword_regexp(), $result->get_remainder(), $matches)) {
            return false; // --- RETURN ---
        }

        $possible_keywords = $this->get_possible_keywords($matches[1]);
        $infoblocks_in_folder = array();

        $infoblock_id = $result->get_resource_parameter('infoblock_id'); // sic, not get_infoblock_id()
        $folder_id = $result->get_resource_parameter('folder_id');

        $infoblock_manager = nc_core::get_object()->sub_class;

        if ($infoblock_id) {
            $infoblocks_in_folder = array($infoblock_manager->get_by_id($infoblock_id));
        }
        else if ($folder_id) {
            $infoblocks_in_folder = $infoblock_manager->get_all_by_subdivision_id($folder_id);
        }

        if ($possible_keywords && $infoblocks_in_folder) {
            foreach ($possible_keywords as $object_keyword) {
                foreach ($infoblocks_in_folder as $infoblock_settings) {
                    list($object_id, $real_object_keyword, $is_exact_match) = (array)ObjectExists(
                        $infoblock_settings['Class_ID'],
                        $infoblock_settings['sysTbl'],
                        $infoblock_settings['Sub_Class_ID'],
                        $object_keyword,
                        $result->get_resource_parameter('date')
                    );

                    if ($object_id) {
                        $result->set_resource_parameter('infoblock_id', $infoblock_settings['Sub_Class_ID']);
                        $result->set_resource_parameter('object_keyword', $real_object_keyword);
                        $result->set_resource_parameter('object_id', $object_id);
                        $result->truncate_remainder(strlen($object_keyword));
                        $result->set_is_exact_match($is_exact_match);
                        return true; // --- RETURN ---
                    }
                }
            }
        }

        return false; // --- RETURN ---
    }

    public function substitute_values_for(nc_routing_path $path, nc_routing_pattern_parameters $parameters) {
        $keyword = $path->get_resource_parameter('object_keyword');
        if ($keyword || $keyword === '0') {
            //$parameters->object_keyword = $object_keyword;
            return $keyword;
        }
        else {
            return false;
        }
    }

}
