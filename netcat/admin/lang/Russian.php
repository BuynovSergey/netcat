<?php

/* $id$ */
$NETCAT_FOLDER = join(strstr(__FILE__, "/") ? "/" : "\\", array_slice(preg_split("/[\/\\\]+/", __FILE__), 0, -4)).( strstr(__FILE__, "/") ? "/" : "\\" );
include_once ($NETCAT_FOLDER."vars.inc.php");

if ($nc_core->NC_UNICODE) {
    setlocale(LC_ALL, 'ru_RU.UTF-8');
    require_once $nc_core->ADMIN_FOLDER."lang/Russian_utf8.php";
} else {
    setlocale(LC_ALL, 'ru_RU.CP1251', 'rus_RUS.CP1251', 'Russian_Russia.1251', 'ru_RU', 'russian');
    require_once $nc_core->ADMIN_FOLDER."lang/Russian_cp1251.php";
}
?>