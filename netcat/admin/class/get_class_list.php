<?php
$_POST['NC_HTTP_REQUEST'] = true;
$NETCAT_FOLDER = realpath(__DIR__ . "/../../../") . '/';

require_once $NETCAT_FOLDER . 'vars.inc.php';
require_once $ROOT_FOLDER . 'connect_io.php';
require_once $ADMIN_FOLDER . 'function.inc.php';

$nc_core = nc_Core::get_object();
$db = $nc_core->db;
$class_list = array();

$classes = (array)$db->get_results(
    "SELECT `Class_ID` as `value`,
            IF (
                `IsAuxiliary` = 1,
                CONCAT(`Class_ID`, '. ', `Class_Name`, ' {$db->escape(CONTROL_CLASS_AUXILIARY)}'),
                CONCAT(`Class_ID`, '. ', `Class_Name`)
            ) AS `text`,
            `Class_Group` as `group_name`,
            `IsAuxiliary` as `is_auxiliary`,
            `File_Mode`,
            `Priority` as `priority`,
            IF (`File_Mode` = 1, 'v5', 'v4') AS `version`,
            CONCAT(IF (`File_Mode` = 1, 'v5', 'v4'), '-', `Class_Group`) AS `group`
            FROM `Class`
            WHERE `ClassTemplate` = 0
            ORDER BY `File_Mode` DESC, `Class_Group`, `Priority`, `Class_ID`",
    ARRAY_A
);

$catalogue_id = (int)$nc_core->input->fetch_get('catalogue_id');
$action = $nc_core->input->fetch_get('action');

$default_class = $nc_core->catalogue->get_default_component_info($catalogue_id, $action);

$groups = array(
    'v5' => array(),
    'v4' => array(),
    'v5-header' => array(
        CONTROL_CLASS . ' v5' => array(
            'is_dummy' => false,
            'is_delimiter' => true,
            'is_auxiliary' => false,
            'selected' => false,
            'name' => CONTROL_CLASS . ' v5',
            'text' => CONTROL_CLASS . ' v5'
        )
    ),
    'v4-header' => array(
        CONTROL_CLASS . ' v4' => array(
            'is_dummy' => false,
            'is_delimiter' => true,
            'is_auxiliary' => false,
            'selected' => false,
            'name' => CONTROL_CLASS . ' v4',
            'text' => CONTROL_CLASS . ' v4'
        )
    )
);

foreach ($classes as $key => $class) {
    if (!isset($groups[$class['version']][$class['group']])) {
        $groups[$class['version']][$class['group']] = array(
            'is_dummy' => false,
            'is_delimiter' => false,
            'is_auxiliary' => true,
            'selected' => $default_class['Class_Group'] == $class['group_name'],
            'name' => $class['group'],
            'text' => '&nbsp;&nbsp;&nbsp;' . $class['group_name'],
            'value' => $class['group']
        );
    }

    if (!$class['is_auxiliary']) {
        $groups[$class['version']][$class['group']]['is_auxiliary'] = false;
    }

    $classes[$key]['is_dummy'] = false;
    $classes[$key]['is_auxiliary'] = (bool)$class['is_auxiliary'];
    $classes[$key]['selected'] = $default_class['Class_ID'] == $class['value'];
}

$dummy_group = array();
$dummy_component = array();

if ($action === 'subdivision.add') {
    $dummy_group[NOT_ADD_CLASS] = array(
        'is_dummy' => true,
        'is_delimiter' => false,
        'is_auxiliary' => false,
        'selected' => !$default_class['Class_Group'],
        'name' => NOT_ADD_CLASS,
        'text' => NOT_ADD_CLASS,
        'value' => 0
    );

    $dummy_component[0] = array(
        'is_dummy' => true,
        'is_auxiliary' => false,
        'selected' => !$default_class['Class_ID'],
        'group' => NOT_ADD_CLASS,
        'text' => NOT_ELSEWHERE_SPECIFIED,
        'value' => 0,
        'priority' => -1
    );
}

if (!$groups['v5'] || !$groups['v4']) {
    $groups['v5-header'] = array();
    $groups['v4-header'] = array();
}

$class_list['groups'] = array_merge($dummy_group, $groups['v5-header'], $groups['v5'], $groups['v4-header'], $groups['v4']);
$class_list['components'] = array_merge($dummy_component, $classes);

header('Content-type: application/json; charset=utf-8');
die(nc_array_json($class_list));