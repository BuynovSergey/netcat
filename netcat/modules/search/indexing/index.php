<?php

/* $Id: index.php 8366 2012-11-07 16:30:14Z aix $ */

/**
 * Запуск переиндексации "в реальном времени"
 */
$NETCAT_FOLDER = realpath("../../../../");
require_once("$NETCAT_FOLDER/vars.inc.php");
$use_gzip_compression = false;
require_once("$ADMIN_FOLDER/function.inc.php");
require_once("../function.inc.php");

// замедление работы при необходимости
$delay = trim(nc_search::get_setting('IndexerInBrowserSlowdownDelay')); // секунды
if ($delay) {
    define('NC_SEARCH_INDEXER_DELAY_VALUE', (int) ($delay * 1000000));

 // микросекунды
    function nc_search_indexer_delay() {
        usleep(NC_SEARCH_INDEXER_DELAY_VALUE);
        print " ";
    }

    register_tick_function('nc_search_indexer_delay');
    declare(ticks=10000);
}

$input = nc_Core::get_object()->input;
$request = array(
        '.page_title' => NETCAT_MODULE_SEARCH_ADMIN_INDEXING_TITLE,
        'view' => 'indexing_on_request',
        'rule_id' => $input->fetch_get('rule_id'),
        'token' => $input->fetch_get('token'),
        'continue' => $input->fetch_get('continue'),
);

nc_search_admin_controller::process_request($request);
