<?php

class nc_routing_pattern_parser {

    /**
     * @param $pattern_string
     * @throws nc_routing_pattern_parser_exception
     * @return nc_routing_pattern_part[]
     */
    static public function parse($pattern_string) {
        $result = array();

        $raw_parts = self::split_pattern_string($pattern_string);
        foreach ($raw_parts as $raw_part) {
            // Simple route parts like "{format}"
            if (preg_match('/^\{(\w+)(?::(.+))?\}$/', $raw_part, $matches)) {
                $matcher_class = "nc_routing_pattern_part_" . $matches[1];
                if (class_exists($matcher_class)) {
                    $args = nc_array_value($matches, 2);
                    if ($args) {
                        $result[] = new $matcher_class($args);
                    }
                    else {
                        $result[] = new $matcher_class();
                    }
                }
                else {
                    $error_message = sprintf(NETCAT_MODULE_ROUTING_ROUTE_PATTERN_NOT_RECOGNIZED, $raw_part);
                    throw new nc_routing_pattern_parser_exception($error_message);
                }
            }
            // Query variable equivalent: "{=color}", "{=color:red|green|blue}"
            else if (preg_match('/^\{=(\w+)(?::(.+))?\}$/', $raw_part, $matches)) {
                $result[] = new nc_routing_pattern_part_variable($matches[1], nc_array_value($matches, 2));
            }
            // Treat everything else as a constant string
            else {
                $result[] = new nc_routing_pattern_part_string($raw_part);
            }
        }

        return $result;
    }

    /**
     * @param string $pattern_string
     * @return array
     */
    protected static function split_pattern_string($pattern_string) {
        $opened_curlies = 0;
        $parts = array();
        $part_index = 0;
        $length = strlen($pattern_string);

        for ($i = 0; $i < $length; $i++) {
            if (!isset($parts[$part_index])) {
                $parts[$part_index] = '';
            }

            $char = $pattern_string[$i];
            switch ($char) {
                case '{':
                    if ($opened_curlies === 0) {
                        $part_index++;
                        $parts[$part_index] = '';
                    }
                    $opened_curlies++;
                    $parts[$part_index] .= $char;
                    break;

                case '}':
                    $opened_curlies--;
                    $parts[$part_index] .= $char;
                    if ($opened_curlies === 0) {
                        $part_index++;
                    }
                    if ($opened_curlies < 0) {
                        $opened_curlies = 0;
                    }
                    break;

                default:
                    $parts[$part_index] .= $char;
                    break;
            }
        }

        return $parts;
    }

}