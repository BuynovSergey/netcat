<?php

/**
 * Шаблон пути
 */
class nc_routing_pattern {

    /** @var nc_routing_pattern_part[] */
    protected $parts = array();

    /**
     * @param string $pattern_string
     */
    public function __construct($pattern_string) {
        $this->parts = nc_routing_pattern_parser::parse($pattern_string);
    }

    /**
     *
     */
    public function match(nc_routing_request $request, nc_routing_result $result) {
        foreach ($this->parts as $part) {
            if (!$part->match($request, $result)) {
                return false;
            }
        }
        return true;
    }

    /**
     * @param nc_routing_path $path
     * @param nc_routing_pattern_parameters $parameters
     * @return bool|string
     */
    public function substitute_values_for(nc_routing_path $path, nc_routing_pattern_parameters $parameters) {
        $result = "";
        foreach ($this->parts as $part) {
            $part_result = $part->substitute_values_for($path, $parameters);
            if ($part_result === false) { return false; }
            $result .= $part_result;
        }
        return $result;
    }

}