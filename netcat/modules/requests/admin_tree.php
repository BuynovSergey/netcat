<?php

// Если скрипт вызывают напрямую а не через modules_json.php
if (empty($NETCAT_FOLDER)) {

    $NETCAT_FOLDER = realpath(__DIR__ . '/../../..') . DIRECTORY_SEPARATOR;
    require_once $NETCAT_FOLDER . "vars.inc.php";
    require_once $ADMIN_FOLDER . "function.inc.php";

    // Показываем дерево разработчика, если у пользователя есть на это права
    if (!$perm->hasAnyModulePermission('requests')) {
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
        if ($perm->hasModulePermission('requests', 'requests_view')) {
            $node_children = array(
                array(
                    "nodeId" => "requests-list",
                    "parentNodeId" => $module_node_id,
                    "name" => NETCAT_MODULE_REQUESTS,
                    "href" => "#module.requests.list",
                    "sprite" => "folder-dark",
                    "hasChildren" => false,
                    "expand" => false,
                ),
            );
        }
}


echo nc_array_json($node_children);
