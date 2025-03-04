#!/usr/local/bin/php
<?php
/* $Id: console.php 8366 2012-11-07 16:30:14Z aix $ */

/**
 * Запуск из crontab/консоли
 */
if (isset($_SERVER['REMOTE_ADDR'])) {
    die("Access denied.");
}

$NETCAT_FOLDER = realpath(dirname(__FILE__)."/../../../../");
putenv("DOCUMENT_ROOT=$NETCAT_FOLDER");
putenv("HTTP_HOST=localhost");
putenv("REQUEST_URI=/");

require_once("$NETCAT_FOLDER/vars.inc.php");
require_once($ROOT_FOLDER."connect_io.php");

$nc_core = nc_Core::get_object();
$nc_core->modules->load_env('ru');

$lang = $nc_core->lang->detect_lang();
require_once($ADMIN_FOLDER."lang/".$lang.".php");
error_reporting(E_PARSE | E_ERROR | E_WARNING | E_USER_ERROR | E_USER_WARNING);

// замедление работы при необходимости
$delay = trim(nc_search::get_setting('IndexerConsoleSlowdownDelay')); // секунды
if ($delay) {
    define('NC_SEARCH_INDEXER_DELAY_VALUE', (int) ($delay * 1000000));

 // микросекунды
    function nc_search_indexer_delay() {
        usleep(NC_SEARCH_INDEXER_DELAY_VALUE);
    }

    register_tick_function('nc_search_indexer_delay');
    declare(ticks=10000);
}

while (@ob_end_flush());

// Поменяйте nc_search::LOG_CONSOLE на другое значение, если хотите получать
// больше или меньше информации о переиндексации
nc_search::register_logger(new nc_search_logger_plaintext(nc_search::LOG_CONSOLE));
nc_search_scheduler::run(nc_search::INDEXING_CONSOLE);
