<?php

// Если скрипт вызывают напрямую а не через modules_json.php
if (!class_exists('nc_core')) {

    $NETCAT_FOLDER = realpath(dirname(__FILE__) . '/../../..') . DIRECTORY_SEPARATOR;
    require_once $NETCAT_FOLDER . "vars.inc.php";
    require_once $ADMIN_FOLDER . "function.inc.php";

    // Показываем дерево разработчика, если у пользователя есть на это права
    if (!$perm->isAccess(array(NC_PERM_MODULE, 'routing'), 0, 0, 0)) {
        exit(NETCAT_MODERATION_ERROR_NORIGHT);
    }
}

//--------------------------------------------------------------------------

if (empty($nc_core)) {
    $nc_core = nc_core();
}

//--------------------------------------------------------------------------

$module_node_id = "module-" . $module['Module_ID'];

// Возвращаем путь (массив с ключами родительских элементов) к текущему разделу
if ($nc_core->input->fetch_get('action') == 'get_path') {
    $ret = array($module_node_id);
    echo nc_array_json($ret);
    exit;
}

//--------------------------------------------------------------------------

$node_children = array();

switch ($node_type) {

    case 'module':
        $node_children = array(
            // Маршруты
            array(
                "nodeId" => "routing-route",
                "parentNodeId" => $module_node_id,
                "name" => NETCAT_MODULE_ROUTING_ROUTES,
                "href" => "#module.routing.route.list",
                "sprite" => "folder-dark",
                "hasChildren" => false,
                "expand" => false,
            ),
            // Настройки
            array(
                "nodeId" => "routing-settings",
                "parentNodeId" => $module_node_id,
                "name" => NETCAT_MODULE_ROUTING_SETTINGS,
                "href" => "#module.routing.settings",
                "sprite" => "settings",
                "hasChildren" => false,
                "expand" => false,
            ),
        );


}


echo nc_array_json($node_children);