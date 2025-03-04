<?php

const NC_ADMIN_DISABLE_LOGIN_PAGE = true;

$NETCAT_FOLDER = join(strstr(__FILE__, "/") ? "/" : "\\", array_slice(preg_split("/[\/\\\]+/", __FILE__), 0, -4)) . (strstr(__FILE__, "/") ? "/" : "\\");
include_once($NETCAT_FOLDER . "vars.inc.php");
require($ADMIN_FOLDER . "function.inc.php");

$dragged_id = (int)$dragged_id;
if (!$dragged_id) die("0 /* Wrong parameters */");
$target_id = $db->escape($target_id);
if (!$target_id) die("0 /* Wrong parameters */");

// INPUT: $dragged_type, $dragged_id, $target_type, $target_id, $position [inside|below]

if ($dragged_type == 'dataclass' && ($target_type == 'group' || $target_type == 'dataclass')) {

    // dragged site info
    $dragged = $db->get_row("SELECT Class_Group FROM Class WHERE Class_ID={$dragged_id}", ARRAY_A);

    // target site info
    if ($target_type == 'group') {
        $target = $db->get_row("SELECT Class_Group FROM Class WHERE md5(Class_Group)='{$target_id}'", ARRAY_A);
    } else {
        $target = $db->get_row("SELECT Class_Group FROM Class WHERE Class_ID='{$target_id}'", ARRAY_A);
    }

    $class_group = $db->escape($target['Class_Group']);

    if ($perm->isAccess(NC_PERM_CLASS, 0, 0, 1)) {
        $db->query("UPDATE Class
                   SET Class_Group = '{$class_group}'
                 WHERE Class_ID = {$dragged_id}");

        //Change priorities
        $priority = 0;

        if ($target_type == 'group') {
            $sql = "UPDATE `Class` SET `Priority` = {$priority} WHERE `Class_ID` = {$dragged_id}";
            $db->query($sql);
            $priority++;
        }

        $sql = "SELECT `Class_ID` FROM `Class` " .
            "WHERE Class_Group = '{$class_group}' " .
            "AND `Class_ID` <> '{$dragged_id}'" .
            "ORDER BY `Priority`, `Class_ID`";

        foreach((array)$db->get_col($sql) as $class_id) {
            $sql = "UPDATE `Class` SET `Priority` = {$priority} WHERE `Class_ID` = {$class_id}";
            $db->query($sql);

            $priority++;

            if ($target_type =='dataclass' && $target_id == $class_id) {
                $sql = "UPDATE `Class` SET `Priority` = {$priority} WHERE `Class_ID` = {$dragged_id}";
                $db->query($sql);
                $priority++;
            }
        }
    }

    die("1 /* OK */");
} else {
    die("0 /* Wrong request */");
}
?>