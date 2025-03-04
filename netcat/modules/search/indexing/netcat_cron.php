<?php

/* $Id: netcat_cron.php 8456 2012-11-23 10:42:55Z aix $ */

/**
 * Запуск из "крона" неткета
 */
$NETCAT_FOLDER = realpath(dirname(__FILE__)."/../../../../");
require_once("$NETCAT_FOLDER/vars.inc.php");
require_once($ROOT_FOLDER."connect_io.php");

$nc_core = nc_Core::get_object();
$nc_core->modules->load_env('ru');

$lang = $nc_core->lang->detect_lang();
require_once($ADMIN_FOLDER."lang/".$lang.".php");

error_reporting(E_PARSE | E_ERROR | E_WARNING | E_USER_ERROR | E_USER_WARNING);
while (@ob_end_flush());

$secret_key = nc_Core::get_object()->input->fetch_get("secret_key");
if ($secret_key != nc_search::get_setting('IndexerSecretKey')) {
    $file = __FILE__;
    nc_search::log(nc_search::LOG_ERROR,
                    "Attempt to access '$file' with a wrong secret key '$secret_key' from $_SERVER[REMOTE_ADDR]");
    die("Access denied.");
}

nc_search::register_logger(new nc_search_logger_plaintext);
nc_search_scheduler::run(nc_search::INDEXING_NC_CRON);
