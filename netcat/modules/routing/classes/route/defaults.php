<?php

class nc_routing_route_defaults {

    /**
     * Создаёт маршруты на всех сайтах системы
     */
    static public function create_for_all_sites() {
        foreach (array_keys(nc_core::get_object()->catalogue->get_all()) as $site_id) {
            self::create($site_id);
        }
    }

    /**
     * Создаёт стандартные маршруты на указанном сайте.
     *
     * @param int $site_id
     * @return bool
     */
    static public function create($site_id) {
        if (!nc_core::get_object()->catalogue->get_by_id($site_id, 'Catalogue_ID')) {
            return false;
        }

        // Пути по умолчанию в порядке убывания приоритета
        $all_patterns = array(
            "folder /{folder}/{date}/",
            "folder /{folder}/{date}",
            "folder /{folder}/",
            "folder /{folder}",

            "infoblock /{folder}/{infoblock_action}_{infoblock_keyword}.{format}",
            "infoblock /{folder}/{date}/{infoblock_action}_{infoblock_keyword}.{format}",

            "object /{folder}/{date}/{object_action}_{object_keyword}.{format}",
            "object /{folder}/{object_action}_{object_keyword}.{format}",

            "object /{folder}/{date}/{object_action}_{infoblock_keyword}_{object_id}.{format}",
            "object /{folder}/{object_action}_{infoblock_keyword}_{object_id}.{format}",

            "object /{folder}/{date}/{object_keyword}.{format}",
            "object /{folder}/{object_keyword}.{format}",

            "object /{folder}/{date}/{infoblock_keyword}_{object_id}.{format}",
            "object /{folder}/{infoblock_keyword}_{object_id}.{format}",

            "infoblock /{folder}/{infoblock_keyword}.{format}",
            "infoblock /{folder}/{date}/{infoblock_keyword}.{format}",
        );

        $all_patterns = array_reverse($all_patterns);

        foreach ($all_patterns as $pattern_data) {
            list($resource_type, $pattern) = explode(" ", $pattern_data, 2);
            $route = new nc_routing_route(array(
                 'site_id' => $site_id,
                 'description' => '',
                 'is_builtin' => true,
                 'pattern' => $pattern,
                 'resource_type' => $resource_type,
                 'enabled' => true,
             ));

             $route->save();
        }

        return true;
    }

}