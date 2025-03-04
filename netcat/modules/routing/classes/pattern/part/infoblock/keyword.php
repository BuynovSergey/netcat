<?php

class nc_routing_pattern_part_infoblock_keyword extends nc_routing_pattern_part_keyword {

    public function match(nc_routing_request $request, nc_routing_result $result) {
        $folder_id = $result->get_resource_parameter('folder_id');
        $date = $result->get_resource_parameter('date');

        if ($folder_id && nc_preg_match($this->get_keyword_regexp(), $result->get_remainder(), $matches)) {
            // Сначала попробовать максимально возможное совпадение;
            // затем, если есть возможность частичного совпадения —
            // более короткие варианты (для того, чтобы, к примеру,
            // была возможность использовать символ подчёркивания в
            // ключевых словах или "-" в качестве разделителя частей
            // адреса)

            $keyword_parts = nc_preg_split($this->get_keyword_delimiter(), $matches[1], -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
            $possible_keywords = array();

            while (sizeof($keyword_parts)) {
                $possible_keywords[] = join('', $keyword_parts);
                array_pop($keyword_parts);
            }

            $infoblocks_in_folder = nc_core::get_object()->sub_class->get_all_by_subdivision_id($folder_id);
            if ($possible_keywords && $infoblocks_in_folder) {
                foreach ($possible_keywords as $keyword) {
                    foreach ($infoblocks_in_folder as $infoblock) {
                        if ($infoblock['EnglishName'] == $keyword) { // KEYWORD MATCH!
                            if ($date) {
                                $has_event_field = nc_core::get_object()
                                    ->get_component($infoblock['Class_ID'])
                                    ->get_date_field();

                                if (!$has_event_field) { continue; }
                            }

                            $result->set_resource_parameter('infoblock_id', $infoblock['Sub_Class_ID']);
                            $result->truncate_remainder(strlen($keyword));
                            return true;
                        }
                    }
                }
            }
        }
        return false;
    }

    public function substitute_values_for(nc_routing_path $path, nc_routing_pattern_parameters $parameters) {
        $infoblock_keyword = $path->get_resource_parameter('infoblock_keyword');

        if (!$infoblock_keyword && $infoblock_keyword !== '0') {
            $infoblock_id = $path->get_resource_parameter('infoblock_id');
            if ($infoblock_id) {
                try {
                    $infoblock_keyword = nc_core::get_object()->sub_class->get_by_id($infoblock_id, 'EnglishName');
                }
                catch (Exception $e) {}
            }
        }

        if ($infoblock_keyword || strlen($infoblock_keyword)) {
//            $path->set_route_resource_parameter('infoblock_keyword', $infoblock_keyword);
//            $parameters->infoblock_keyword = $infoblock_keyword;
            return $infoblock_keyword;
        }
        else {
            return false;
        }
    }

}