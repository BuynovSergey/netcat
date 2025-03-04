<?php

const NC_ADMIN_DISABLE_LOGIN_PAGE = true;

$NETCAT_FOLDER = join(strstr(__FILE__, "/") ? "/" : "\\", array_slice(preg_split("/[\/\\\]+/", __FILE__), 0, -4)) . (strstr(__FILE__, "/") ? "/" : "\\");
include_once($NETCAT_FOLDER . "vars.inc.php");
require($ADMIN_FOLDER . "function.inc.php");
$nc_core = nc_core::get_object();
$db = $nc_core->db;

// INPUT: $dragged_type, $dragged_id, $target_type, $target_id, $position [inside|below]

$dragged_type = $nc_core->input->fetch_get('dragged_type');
$dragged_id = (int)$nc_core->input->fetch_get('dragged_id');
$target_type = $nc_core->input->fetch_get('target_type');
$target_id = (int)$nc_core->input->fetch_get('target_id');
$position = $nc_core->input->fetch_get('position');

/** @var Permission $perm */

$wrong_params =
    !$perm->isAccess(NC_PERM_TEMPLATE, 0, 0, 1) ||
    $dragged_type != 'template' || $target_type != 'template' ||
    !$dragged_id || !$target_id || $dragged_id == $target_id;

if ($wrong_params) {
    die("0 /* Wrong request */");
}


$sql = "SELECT `Parent_Template_ID`, `File_Path`, `Keyword` FROM `Template` WHERE `Template_ID` = {$dragged_id}";
list($old_parent_id, $old_file_path, $dragged_keyword) = (array)$db->get_row($sql, ARRAY_N);

if ($old_parent_id === null) {
    die("0 /* Template $dragged_id not found */");
}

$target_parent_id = (int)$db->get_var("SELECT `Parent_Template_ID` FROM `Template` WHERE `Template_ID` = {$target_id}");

if ($position == 'inside') {
    $new_parent_id = $target_id;
} else {
    $new_parent_id = $target_parent_id;
}

if ($old_parent_id != $new_parent_id) {
    $db->query(
        "UPDATE `Template` 
            SET `Parent_Template_ID` = {$new_parent_id} 
          WHERE `Template_ID` = {$dragged_id}"
    );

    if ($old_file_path) { // i.e. File_Mode = 1
        $new_parent_file_path = $db->get_var("SELECT `File_Path` FROM `Template` WHERE `Template_ID` = {$new_parent_id}") ?: '/';
        $new_file_path = $new_parent_file_path . ($dragged_keyword ?: $dragged_id) . '/';

        $path_suffix_start = strlen($old_file_path) + 1;
        $db->query(
            "UPDATE `Template`
                SET `File_Path` = CONCAT('" . $db->escape($new_file_path) . "', SUBSTRING(`File_Path` FROM {$path_suffix_start}))
              WHERE `File_Path` LIKE '" . $db->escape($old_file_path) . "%'"
        );

        $template_folder = rtrim($nc_core->TEMPLATE_FOLDER, '\\/');
        nc_move_directory($template_folder . rtrim($old_file_path, '/'), $template_folder . rtrim($new_file_path, '/'));
    }
}

//change priorities
$priority = 0;

if ($position == 'inside') {
    $sql = "UPDATE `Template` SET `Priority` = {$priority} WHERE `Template_ID` = {$dragged_id}";
    $db->query($sql);
    $priority++;
}

$sql = "SELECT `Template_ID` FROM `Template` "  .
       "WHERE `Template_ID` <> {$dragged_id} " .
       "AND `Parent_Template_ID` = {$new_parent_id} " .
       "ORDER BY `Priority`, `Template_ID`";

foreach((array)$db->get_col($sql) AS $template_id) {
    $sql = "UPDATE `Template` SET `Priority` = {$priority} WHERE `Template_ID` = {$template_id}";
    $db->query($sql);

    $priority++;

    if ($position =='below' && $target_id == $template_id) {
        $sql = "UPDATE `Template` SET `Priority` = {$priority} WHERE `Template_ID` = {$dragged_id}";
        $db->query($sql);
        $priority++;
    }
}

die("1 /* OK */");
