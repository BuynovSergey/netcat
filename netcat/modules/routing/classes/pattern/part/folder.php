<?php

class nc_routing_pattern_part_folder extends nc_routing_pattern_part {

    /**
     * @param nc_routing_request $request
     * @param nc_routing_result $result
     * @return bool
     */
    public function match(nc_routing_request $request, nc_routing_result $result) {
        $remainder = $result->get_remainder();

        // ensure remainder starts and ends with a slash:
        $starts_with_slash = ($remainder[0] === '/');
        if (!$starts_with_slash) {
            $remainder = '/' . $remainder;
        }
        if (substr($remainder, -1) !== '/') {
            $remainder .= '/';
        }

        // try to get the folder that corresponds to the unresolved path remainder:
        $folder_settings = $this->get_folder_settings($request->get_site_id(), $remainder);
        if ($folder_settings) {
            $result->set_resource_parameter('folder_id', $folder_settings['Subdivision_ID']);

            // do not remove trailing slash, but remove the slash added above
            $chars_to_remove = strlen($folder_settings['Hidden_URL']) - ($starts_with_slash ? 1 : 2);
            $result->truncate_remainder($chars_to_remove);

            return true;
        }

        return false;
    }

    /**
     * @param nc_routing_path $path
     * @param nc_routing_pattern_parameters $parameters
     * @return bool|string
     */
    public function substitute_values_for(nc_routing_path $path, nc_routing_pattern_parameters $parameters) {
        $folder = $path->get_resource_parameter('folder');

        if (!$folder) {
            $folder_id = $path->get_resource_parameter('folder_id');
            try {
                $folder = nc_core::get_object()->subdivision->get_by_id($folder_id, 'Hidden_URL');
            } catch (Exception $e) {
            }
        }

        if ($folder) {
            return trim($folder, '/');
        }

        return false;
    }

    /**
     * @param int $site_id
     * @param string $path_remainder
     * @param null|string $stop_at_path остановить проверку на указанном пути
     * @return mixed
     */
    protected function get_folder_settings($site_id, $path_remainder, $stop_at_path = null) {
        static $cache = array();

        if (!isset($cache[$path_remainder])) {
            $nc_core = nc_core::get_object();
            $current_remainder = $path_remainder;
            while ($last_slash = strrpos($current_remainder, '/')) {
                $current_remainder = substr($current_remainder, 0, $last_slash);
                $checked_path = $current_remainder . '/';
                if ($checked_path === $stop_at_path) {
                    break;
                }
                $sub = $nc_core->subdivision->get_by_uri($checked_path, $site_id, null, false, true);
                if ($sub) {
                    $cache[$path_remainder] = $sub;
                    break; // --- exit while() ---
                }

                $cache[$path_remainder] = false;
            }
        }

        return $cache[$path_remainder];
    }

}