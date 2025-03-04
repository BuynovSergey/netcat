<?php

/**
 * Системный файл, в котором происходит подключение и инициализация основных 
 * классов и расширений системы. Также здесь стартует сессия.
 */

$NETCAT_FOLDER = realpath(dirname(__FILE__) . '/../..') . DIRECTORY_SEPARATOR;

include_once $NETCAT_FOLDER . "vars.inc.php";

// запрет прямого обращения
if (!function_exists('nc_strlen')) {
    die('Unable to load file.');
}

// подключаем основные классы системы
include_once ($SYSTEM_FOLDER . 'nc_system.class.php');
include_once ($SYSTEM_FOLDER . 'nc_core.class.php');
include_once ($SYSTEM_FOLDER . 'nc_essence.class.php');


try {
    // Проверка значения куки с ID сессии, чтобы в session_start() не возникало предупреждение, когда передан некорректный ID:
    $nc_session_name = session_name();
    if (
        !empty($_COOKIE[$nc_session_name]) &&
        !preg_match('/^[a-zA-Z0-9,-]{22,' . (ini_get('session.sid_length') ?: 32) . '}$/', $_COOKIE[$nc_session_name])
    ) {
        unset($_COOKIE[$nc_session_name]);
    }
    unset($nc_session_name);

    session_start();
    ob_start();

    // initialize superior system object
    $nc_core = nc_Core::get_object();
    // load default extensions
    $nc_core->init();

    if (!headers_sent()) {
        header('Content-Type: ' . $nc_core->get_content_type());
    }

    //global variables
    $LinkID = &$nc_core->db->dbh;
    $parsed_url = $nc_core->url->parse_url();
    $client_url = $nc_core->url->source_url();
    extract($nc_core->input->prepare_extract());
    $_cache = array();
} catch (Exception $e) {
    //$nc_core->errorMessage($e);
    die($e->getMessage());
}
