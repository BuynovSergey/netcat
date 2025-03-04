<?php

$NETCAT_FOLDER = realpath(__DIR__ . '/../../../../../') . '/';
require_once $NETCAT_FOLDER . 'vars.inc.php';
require_once $ROOT_FOLDER . 'connect_io.php';
require_once $nc_core->ADMIN_FOLDER . 'function.inc.php';

/**
 * Subdivision list for the <select> (subdivision selection dialog)
 */

/** @var nc_input $input */
$input = nc_core('input');
$node = $input->fetch_get_post('node');
$site_id = (int)$input->fetch_get_post('site_id');
$infoblock_id = (int)$input->fetch_get_post('infoblock_id');

if (!$site_id) { trigger_error("'site_id' parameter is required", E_USER_ERROR); }
if (!$infoblock_id) { trigger_error("'infoblock_id' parameter is required", E_USER_ERROR); }

$result = array();
$subdivisions = nc_condition_admin_helpers::get_subdivisions($site_id, $infoblock_id);

foreach ($subdivisions as $sub) {
    $result[$sub['Subdivision_ID']] =
        str_repeat("&nbsp; &nbsp; &nbsp;", $sub["Depth"] - 1) .
        "$sub[Subdivision_ID]. $sub[Subdivision_Name]";
}

echo nc_condition_admin_helpers::key_value_json($result);