<?php

// Если скрипт вызывают напрямую, а не через modules_json.php
if (empty($NETCAT_FOLDER)) {
    $NETCAT_FOLDER = realpath(dirname(__FILE__) . "/../../..") . DIRECTORY_SEPARATOR;

    require_once $NETCAT_FOLDER . "vars.inc.php";
    require_once $ADMIN_FOLDER . "function.inc.php";
}

$module_node_id = "module-" . $module["Module_ID"];

// Возвращает путь (массив с ключами родительских элементов) к текущему разделу
if (nc_core::get_object()->input->fetch_get("action") == "get_path") {
    echo nc_array_json(array($module_node_id));
    exit;
}

$children_nodes = array();

/** @var Permission $perm */
global $perm;

if ($node_type == "module") {
    if ($perm->hasModulePermission("messaging", "settings_edit")) {
        // Настройки
        $children_nodes[] = array(
            "nodeId" => "messaging-settings",
            "parentNodeId" => $module_node_id,
            "name" => NETCAT_MODULE_MESSAGING_SETTINGS,
            "href" => "#module.messaging.settings",
            "sprite" => "settings",
            "hasChildren" => false,
        );
    }

    if ($perm->hasModulePermission("messaging", "service_edit")) {
        // Службы
        $children_nodes[] = array(
            "nodeId" => "messaging-service",
            "parentNodeId" => $module_node_id,
            "name" => NETCAT_MODULE_MESSAGING_PROVIDERS,
            "href" => "#module.messaging.service(index)",
            "sprite" => "folder-dark",
            "hasChildren" => false,
        );
    }

    if ($perm->hasModulePermission("messaging", "message_send")) {
        // Ручная отправка сообщения
        $children_nodes[] = array(
            "nodeId" => "messaging-send",
            "parentNodeId" => $module_node_id,
            "name" => NETCAT_MODULE_MESSAGING_SERVICE_MANUAL_SEND,
            "href" => "#module.messaging.send(index)",
            "sprite" => "mod-messaging-send",
            "hasChildren" => false,
        );
    }

    // События
    $children_nodes[] = array(
        "nodeId" => "messaging-log",
        "parentNodeId" => $module_node_id,
        "name" => NETCAT_MODULE_SEARCH_ADMIN_EVENT_LOG_FILTER,
        "href" => "#module.messaging.log(index)",
        "sprite" => "mod-logging",
        "hasChildren" => false,
    );
}

echo nc_array_json($children_nodes);
