<?php

/* $Id: entry.php 6209 2012-02-10 10:28:29Z denis $ */

/**
 *
 */
class nc_search_logger_database_entry extends nc_search_data_persistent {

    protected $properties = array(
            'id' => null,
            'timestamp' => null, // integer
            'type' => null, // nc_search::LOG_xx constants, integer
            'message' => null,
    );
    protected $table_name = 'Search_Log';
    protected $mapping = array(
            'id' => 'Log_ID',
            '_generate' => true,
    );

    /**
     *
     * @return nc_search_data_persistent
     */
    public function save() {
        $this->set('timestamp', nc_search_util::sql_datetime());
        return parent::save();
    }

}