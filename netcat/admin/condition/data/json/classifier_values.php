<?php

$NETCAT_FOLDER = realpath(__DIR__ . '/../../../../../') . '/';
require_once $NETCAT_FOLDER . 'vars.inc.php';
require_once $ROOT_FOLDER . 'connect_io.php';
require_once $nc_core->ADMIN_FOLDER . 'function.inc.php';

/**
 * Classifier values as JSON
 */

/* @var nc_db $db */
$db = nc_core('db');

/** @var nc_input $input */
$input = nc_core('input');
$classifier = $input->fetch_get_post("classifier");
$table = "Classificator_$classifier";
if (!preg_match("/^\w+$/", $classifier) || count($db->get_results("SHOW TABLES LIKE '$table'")) == 0) {
    trigger_error("Wrong 'classifier' parameter", E_USER_ERROR);
}

$result = array();
$rows = $db->get_results("SELECT `{$classifier}_ID`, `{$classifier}_Name`
                            FROM `$table`
                           WHERE `Checked` = 1
                          ORDER BY `{$classifier}_Priority`",
                          ARRAY_A);

foreach ($rows as $row) {
    $result[$row["{$classifier}_ID"]] = $row["{$classifier}_Name"];
}

echo nc_condition_admin_helpers::key_value_json($result);