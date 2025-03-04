<?php

/* $Id: cron.php 6210 2012-02-10 10:30:32Z denis $ */

// Удалите эту и следующую строку, если вы используете этот скрипт
exit;

// if register_globals==off
$param = $_GET['param'];

// Укажите значение параметра, заданного в 'Управление задачами'
$check = "test";

if ($check != $param) {
    echo "Non-authorized access!";
    exit;
}

$NETCAT_FOLDER = join(strstr(__FILE__, "/") ? "/" : "\\", array_slice(preg_split("/[\/\\\]+/", __FILE__), 0, -4)).( strstr(__FILE__, "/") ? "/" : "\\" );
include_once $NETCAT_FOLDER . 'vars.inc.php';
require $ROOT_FOLDER . 'connect_io.php';
require nc_module_folder('stats') . 'admin.inc.php';


//LoadModuleEnv();
$MODULE_VARS = $nc_core->modules->get_module_vars();

$isConsole = 1;


if ($phase == 0) {
    $phase = 9;
}

$phase+= 0;

Stats_CreateReports();

echo "OK";
?>