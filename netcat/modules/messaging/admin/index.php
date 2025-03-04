<?php

$NETCAT_FOLDER = realpath(__DIR__ . "/../../../../") . "/";

include_once $NETCAT_FOLDER . "vars.inc.php";
require_once $ADMIN_FOLDER . "function.inc.php";

$nc_core = nc_core::get_object();
require_once $nc_core->SYSTEM_FOLDER . "/admin/ui/components/nc_ui_controller.class.php";

$input = $nc_core->input;
$controller_name = $input->fetch_post_get("controller") ?: 'settings';
$action_name = $input->fetch_post_get("action") ?: 'index';

if (!preg_match("/^[\w]+$/", $controller_name)) {
    die ("Incorrect controller name");
}

$controller_class = "nc_messaging_" . $controller_name . "_admin_controller";

if (!class_exists($controller_class)) {
    die ("Incorrect controller name");
}

$controller_name_parts = explode("_", $controller_name);
$view_path = __DIR__ . "/views/" . $controller_name_parts[0];

/** @var nc_ui_controller $controller */
$controller = new $controller_class($view_path);
echo $controller->execute($action_name);
