<?php

class nc_routing_pattern_part_date extends nc_routing_pattern_part {

    static protected $date_parts = array(
        'd' => '(?P<d>[012]\d|30|31)',
        'm' => '(?P<m>0\d|1[012])',
        'Y' => '(?P<Y>(?:19|20)\d{2})',
    );

    protected $date_formats_string;
    protected $date_formats_array;
    protected $regexps = array();

    public function __construct($date_formats_string = 'Y/m/d|Y/m|Y') {
        if (strpos($date_formats_string, 'Y') === false) {
            throw new nc_routing_pattern_parser_exception(NETCAT_MODULE_ROUTING_ROUTE_PATTERN_MUST_CONTAIN_YEAR);
        }
        $this->date_formats_string = $date_formats_string;
        $this->date_formats_array = explode("|", $date_formats_string);
        usort($this->date_formats_array, array($this, 'compare_length'));
        foreach ($this->date_formats_array as $format) {
            $this->regexps[] = '!^' .
                               strtr(preg_quote($format, '!'), self::$date_parts) .
                               '!';
        }
    }

    protected function compare_length($a, $b) {
        return strlen($b) - strlen($a);
    }

    public function match(nc_routing_request $request, nc_routing_result $result) {
        $infoblock_id = $result->get_resource_parameter('infoblock_id');
        $folder_id = $result->get_resource_parameter('folder_id');

        if ($infoblock_id && !$this->infoblock_has_event_field($infoblock_id)) {
            return false;
        }
        else if ($folder_id && !$this->folder_has_component_with_event_field($folder_id)) {
            return false;
        }
        else if (!$infoblock_id && !$folder_id) {
            return false;
        }

        foreach ($this->regexps as $regexp) {
            if (preg_match($regexp, $result->get_remainder(), $matches)) {
                $date = $matches['Y'] .
                    ($matches['m']
                        ? "-$matches[m]" .
                            ($matches['d']
                                ? "-$matches[d]"
                                : "")
                        : "");

                $result->set_resource_parameter('date', $date);
                $result->truncate_remainder(strlen($date));
                return true;
            }
        }

        return false;
    }

    public function substitute_values_for(nc_routing_path $path, nc_routing_pattern_parameters $parameters) {
        $date = $path->get_resource_parameter('date');
        if ($date) {
            list($y, $m, $d) = explode('-', $date);
            $suitable_format = $this->get_suitable_date_format($m ? 1 : 0, $d ? 1 : 0);

            if (!$suitable_format) {
                return false;
            }

            $result = strtr($suitable_format, array("Y" => $y, "m" => $m, "d" => $d));
//            $parameters->date = $result;
            return $result;
        }
        else {
            return false;
        }
    }

    protected function get_suitable_date_format($need_month, $need_day) {
        static $cache = array();
        $key = $this->date_formats_string . "#" . $need_month . $need_day;

        if (!isset($cache[$key])) {
            $cache[$key] = false;
            foreach ($this->date_formats_array as $format) {
                $format_has_m = strpos($format, 'm') !== false;
                $format_has_d = strpos($format, 'd') !== false;
                $format_match =
                    (($need_month && $format_has_m) || (!$need_month && !$format_has_m)) &&
                    (($need_day && $format_has_d) || (!$need_day && !$format_has_d));

                if ($format_match) {
                    $cache[$key] = $format;
                    break;
                }
            }
        }

        return $cache[$key];
    }

    protected function infoblock_has_event_field($infoblock_id) {
        $nc_core = nc_core::get_object();
        $component_id = $nc_core->sub_class->get_by_id($infoblock_id, 'Class_ID');
        return ($nc_core->get_component($component_id)->get_date_field());
    }

    protected function folder_has_component_with_event_field($folder_id) {
        $nc_core = nc_core::get_object();
        foreach ((array)$nc_core->sub_class->get_all_by_subdivision_id($folder_id) as $infoblock_settings) {
            if ($infoblock_settings['Class_ID'] && $nc_core->get_component($infoblock_settings['Class_ID'])->get_date_field()) {
                return true;
            }
        }
        return false;
    }

}