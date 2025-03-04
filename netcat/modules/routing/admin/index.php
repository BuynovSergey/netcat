<?php

/**
 * INPUT:
 *  - action: index
 */

$default_controller = 'route';
$default_action = 'index';

require_once './no_header.inc.php';
require_once nc_core('SYSTEM_FOLDER') . '/admin/ui/components/nc_ui_controller.class.php';

$controller_name = nc_core('input')->fetch_post_get('controller');
$action_name = nc_core('input')->fetch_post_get('action');

if (!$controller_name) { $controller_name = $default_controller; }
if (!$action_name) { $action_name = $default_action; }

$controller_class = "nc_routing_" . $controller_name . "_admin_controller";

/** @var nc_ui_controller $controller */
$controller = new $controller_class(dirname(__FILE__) . "/views/");
echo $controller->execute($action_name);