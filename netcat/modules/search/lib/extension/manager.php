<?php

/* $Id: manager.php 6209 2012-02-10 10:28:29Z denis $ */

/**
 * Управление расширениями (Service Locator)
 */
class nc_search_extension_manager {

    static protected $all_extensions;
    static protected $cache = array();

    /**
     *
     * @param string $interface
     * @param nc_search_context $context
     * @return nc_search_extension_chain с экземплярами классов расширения, подходящих под данный контекст
     * @throws nc_search_exception
     */
    static public function get($interface, nc_search_context $context) {
        $cache_id = $interface."__".$context->get_hash();
        if (isset(self::$cache[$cache_id])) {
            return self::$cache[$cache_id];
        }

        $result = new nc_search_extension_chain();

        foreach (self::get_all_extensions() as $rule) {
            if ($rule->get('extension_interface') == $interface && $context->conforms_to($rule)) {
                $extension_class = $rule->get('extension_class');
                $extension_instance = new $extension_class($context);
                if (!($extension_instance instanceof $interface)) { // WTF? Implement an interface!
                    throw new nc_search_exception("Extension '$extension_class' does not implement the interface '$interface'");
                }
                $result->add($extension_instance);
            }
        }
        self::$cache[$cache_id] = $result;
        return $result;
    }

    /**
     * @return nc_search_persistent_data_collection
     */
    static protected function get_all_extensions() {
        if (!self::$all_extensions) {
            self::$all_extensions = nc_search::load('nc_search_extension_rule',
                            'SELECT * FROM `%t%` WHERE `Checked` = 1 ORDER BY `Priority`');
        }
        return self::$all_extensions;
    }

}
