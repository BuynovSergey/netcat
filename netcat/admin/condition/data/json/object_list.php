<?php

$NETCAT_FOLDER = realpath(__DIR__ . '/../../../../../') . '/';
require_once $NETCAT_FOLDER . 'vars.inc.php';
require_once $ROOT_FOLDER . 'connect_io.php';
require_once $nc_core->ADMIN_FOLDER . 'function.inc.php';

/**
 * Subdivision list for the <select> (subdivision selection dialog)
 */

$infoblock_id = (int)$nc_core->input->fetch_get_post('infoblock_id');

if (!$infoblock_id) {
    trigger_error("'infoblock_id' parameter is required", E_USER_ERROR);
}

$result = array();

try {
    $sub_class = $nc_core->sub_class->get_by_id($infoblock_id);
    $class_id = $sub_class['Class_ID'];
    $class_template_id = $sub_class['Class_Template_ID'] ?: $sub_class['Class_ID'];
} catch (Exception $e) {
    die(nc_condition_admin_helpers::key_value_json($result));
}

$component = $nc_core->get_component($class_template_id);
$field_with_object_name = $component->get_possible_object_name_field();

$items = $db->get_results("SELECT `Message_ID`, `$field_with_object_name` FROM `Message$class_id`", ARRAY_A);

foreach ($items as $item) {
    if ($field_with_object_name === 'Message_ID' || !$item[$field_with_object_name]) {
        $template = $component->get_default_object_name_template();
        $object_name = $item['Message_ID'];
    } else {
        $template = $component->get_object_name_template();
        $object_name = $item[$field_with_object_name];
    }
    $template = $item['Message_ID'] . '. ' . $template;

    $result["$class_id:$item[Message_ID]"] = sprintf($template, $object_name);
}

echo nc_condition_admin_helpers::key_value_json($result);