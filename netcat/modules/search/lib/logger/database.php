<?php

/* $Id: database.php 6209 2012-02-10 10:28:29Z denis $ */

/**
 *
 */
class nc_search_logger_database extends nc_search_logger {

    public function log($type_string, $message) {
        $entry = new nc_search_logger_database_entry(array(
                        "type" => $type_string,
                        "message" => $message));
        $entry->save();
    }

}