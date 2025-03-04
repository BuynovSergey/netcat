<?php

/**
 * Неполный путь к разделу (начиная от указанного родительского раздела)
 */
class nc_routing_pattern_part_subfolder extends nc_routing_pattern_part_folder {

    /** @var  string|null путь родительского раздела (с '/' на конце).
     *  Для получения всегда использовать метод get_parent_folder_path(). */
    protected $parent_folder_path;
    /** @var  int|null ID родительского раздела */
    protected $parent_folder_id;

    /**
     * @param null $parent_folder
     */
    public function __construct($parent_folder = null) {
        if (is_numeric($parent_folder)) {
            $this->parent_folder_id = $parent_folder;
        }
    }

    /**
     * @param nc_routing_request $request
     * @param nc_routing_result $result
     * @return bool
     */
    public function match(nc_routing_request $request, nc_routing_result $result) {
        $remainder = $result->get_remainder();
        $parent_folder_path = $checked_path = $this->get_parent_folder_path();

        // проверяем, чтобы в проверяемом пути не было двойных слешей и он заканчивался на слеш
        $remainder_starts_with_slash = ($remainder[0] === '/');
        if ($remainder_starts_with_slash) {
            $checked_path .= substr($remainder, 1);
        } else {
            $checked_path .= $remainder;
        }

        if (substr($checked_path, -1) !== '/') {
            $checked_path .= '/';
        }

        if ($checked_path === $parent_folder_path) {
            return false;
        }

        // try to get the folder that corresponds to the unresolved path remainder:
        $folder_settings = $this->get_folder_settings($request->get_site_id(), $checked_path, $parent_folder_path);
        if ($folder_settings) {
            $result->set_resource_parameter('folder_id', $folder_settings['Subdivision_ID']);

            $chars_to_remove =
                strlen($folder_settings['Hidden_URL']) -
                strlen($parent_folder_path) -
                ($remainder_starts_with_slash ? 2 : 1); // do not remove trailing slash, but remove the slash added above

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
            $parent_folder_path = $this->get_parent_folder_path();
            if ($folder === $parent_folder_path || strpos($folder, $parent_folder_path) !== 0) {
                return false;
            }
            $folder = substr($folder, strlen($this->get_parent_folder_path()) - 1);
            return trim($folder, '/');
        }

        return false;
    }

    /**
     * @return string
     */
    public function get_parent_folder_path() {
        if (!isset($this->parent_folder_path)) {
            $this->parent_folder_path = 0x0; // гарантированно не совпадёт ни с чем, если такого раздела нет
            if ($this->parent_folder_id) {
                try {
                    $this->parent_folder_path = nc_core::get_object()->subdivision->get_by_id($this->parent_folder_id, 'Hidden_URL');
                } catch (Exception $e) {
                }
            }
        }
        return $this->parent_folder_path;
    }

}