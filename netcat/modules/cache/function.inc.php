<?php

/* $Id: function.inc.php 6206 2012-02-10 10:12:34Z denis $ */
$NETCAT_FOLDER = join(strstr(__FILE__, "/") ? "/" : "\\", array_slice(preg_split("/[\/\\\]+/", __FILE__), 0, -4)).( strstr(__FILE__, "/") ? "/" : "\\" );
include_once $NETCAT_FOLDER . 'vars.inc.php';

global $MODULE_FOLDER;

// include need classes
include_once nc_module_folder('cache') . 'nc_cache.class.php';
include_once nc_module_folder('cache') . 'nc_cache_list.class.php';
include_once nc_module_folder('cache') . 'nc_cache_full.class.php';
include_once nc_module_folder('cache') . 'nc_cache_browse.class.php';
include_once nc_module_folder('cache') . 'nc_cache_function.class.php';
include_once nc_module_folder('cache') . "nc_cache_io.class.php";

// modules classes
if (nc_module_check_by_keyword('calendar', 0)) {
    include_once nc_module_folder('cache') . 'modules/nc_cache_calendar.class.php';
}

// load objects for inside_admin events
nc_cache_browse::getObject();
nc_cache_full::getObject();
nc_cache_list::getObject();
nc_cache_function::getObject();
if (nc_module_check_by_keyword('calendar', 0)) {
    nc_cache_calendar::getObject();
}
?>