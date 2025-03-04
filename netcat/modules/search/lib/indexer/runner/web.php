<?php

/* $Id: web.php 8366 2012-11-07 16:30:14Z aix $ */

/**
 *
 */
class nc_search_indexer_runner_web extends nc_search_indexer_runner_interrupted {

    protected function get_time_threshold() {
        return nc_search::get_setting('IndexerTimeThreshold'); // int|float|empty string|numeric string
    }

    protected function get_memory_threshold() {
        return nc_search::get_setting('IndexerMemoryThreshold'); // int|float|empty string|numeric string
    }

    protected function get_max_cycles_number() {
        return 0;
    }

    protected function check_connection() {
        return (connection_status() === CONNECTION_NORMAL);
    }

}
