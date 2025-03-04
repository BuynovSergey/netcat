<?php

class nc_routing_pattern_part_format extends nc_routing_pattern_part {

    public function match(nc_routing_request $request, nc_routing_result $result) {
        if (preg_match('/^(html|xml|rss)\b/', $result->get_remainder(), $matches)) {
            $format = $matches[1];

            if ($format == 'rss' || $format == 'xml') {
                $infoblock_id = $result->get_infoblock_id();

                if (!$infoblock_id) { return false; }

                $infoblock_settings = nc_core('sub_class')->get_by_id($infoblock_id);

                $format_mismatch =
                    ($format == 'rss' && !$infoblock_settings['AllowRSS']) ||
                    ($format == 'xml' && !$infoblock_settings['AllowXML']);

                if ($format_mismatch) { return false; }
            }

            $result->set_resource_parameter('format', $format);
            $result->truncate_remainder(strlen($format));

            return true;
        }
        else {
            return false;
        }
    }

    public function substitute_values_for(nc_routing_path $path, nc_routing_pattern_parameters $parameters) {
        $format = $path->get_resource_parameter('format');
        $format = $format ? $format : 'html';
        $parameters->format = $format;
        return $format;
    }

}