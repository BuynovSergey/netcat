<?php
$NETCAT_FOLDER = realpath(__DIR__ . str_repeat('/..', 4)) . '/';
require_once $NETCAT_FOLDER . "vars.inc.php";
require_once $ROOT_FOLDER . 'connect_io.php';

Hybrid_Endpoint::process();