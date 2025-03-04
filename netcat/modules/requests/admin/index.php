<?php

$NETCAT_FOLDER = realpath(__DIR__ . "/../../../../") . "/";
include_once $NETCAT_FOLDER . "vars.inc.php";
require_once $ADMIN_FOLDER . "function.inc.php";
/** @var nc_core $nc_core */
require_once $nc_core->SYSTEM_FOLDER . '/admin/ui/components/nc_ui_controller.class.php';

$controller_name = $nc_core->input->fetch_post_get('controller');
$action_name = $nc_core->input->fetch_post_get('action');

// Проверяем значение параметра controller, т.к. дальше будем использовать его
// для доступа к файловой системе
if (!preg_match("/^[\w]+$/", $controller_name)) {
    die('Incorrect controller name');
}

/** @var Permission $perm */
// Если нет прав на редактирование настроек, пошлём на список заявок
if ($controller_name === 'settings' && $action_name === 'index' && !$perm->hasModulePermission('requests', 'settings_edit')) {
    $controller_name = 'list';
}

/**
 * Если параметр controller содержит знак подчёркивания, то первая часть до подчеркивания
 * определяет папку, в которой находятся шаблоны (views).
 */
$controller_class = "nc_requests_" . $controller_name . "_admin_controller";
$controller_name_parts = explode("_", $controller_name);
$view_path = __DIR__ . "/views/" . $controller_name_parts[0];

/** @var nc_ui_controller $controller */
$controller = new $controller_class($view_path);
echo $controller->execute($action_name);
