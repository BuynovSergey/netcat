<?php

/* Cron API
 *
 * Данный файл должен подключаться для каждого cron задания, перед его выполнением
 * Данный файл инициализирует контекст работы с системой Netcat и осуществляет проверку соответствия переданного get параметра cron_key
 */

$NETCAT_FOLDER = realpath(__DIR__ . "/../../");

require_once($NETCAT_FOLDER . "/vars.inc.php");
require_once($ROOT_FOLDER . "connect_io.php");

if (!isset($nc_core)) {
    $nc_core = nc_core::get_object();
}

$key = $nc_core->get_settings("SecretKey");

if ($key === "") {
    die("Access denied");
}

if (!isset($_GET["cron_key"]) || $_GET["cron_key"] !== $key) {
    die("Access denied");
}

$nc_core->modules->load_env();
