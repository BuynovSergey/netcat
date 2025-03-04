<?php

/**
 * Обрабатывает D&D
 *  - message to message (поместить перетаскиваемый объект *ниже* объекта,
 *    на который он был сброшен - смена Priority)
 */

const NC_ADMIN_DISABLE_LOGIN_PAGE = true;

$NETCAT_FOLDER = join(strstr(__FILE__, "/") ? "/" : "\\", array_slice(preg_split("/[\/\\\]+/", __FILE__), 0, -4)).( strstr(__FILE__, "/") ? "/" : "\\" );
include_once ($NETCAT_FOLDER."vars.inc.php");
require ($ADMIN_FOLDER."function.inc.php");

/**
 * @var Permission $perm
 */

$dragged_id = (int) $dragged_id;
if (!$dragged_id) die("0 /* Wrong parameters */");

$target_id = (int) $target_id;
if (!$target_id) die("0 /* Wrong parameters */");

// CHANGE MESSAGE PRIORITY
if ($dragged_type == 'message' && $target_type == 'message') {

    $dragged_class = (int) $dragged_class;
    $target_class = (int) $target_class;

    if (!$dragged_class) {
        die("0 /* No class */");
    }
    if ($dragged_class != $target_class) {
        die("0 /* Dragged ($dragged_class) and target ($target_class) classes do not match */");
    }

    $dragged = $db->get_row("SELECT Subdivision_ID, Sub_Class_ID, Priority, Parent_Message_ID FROM Message{$dragged_class} WHERE Message_ID=$dragged_id", ARRAY_A);
    $target = $db->get_row("SELECT Subdivision_ID, Sub_Class_ID, Priority, Parent_Message_ID FROM Message{$target_class}  WHERE Message_ID=$target_id", ARRAY_A);

    if (!$dragged || !$target) {
        die("0 /* Object not exists */");
    }

    if ($dragged['Sub_Class_ID'] != $target['Sub_Class_ID']) {
        die("0 /* Message drop on message from another subclass is not supported */");
    }

    if ($dragged['Parent_Message_ID'] != $target['Parent_Message_ID']) {
        die("0 /* Message drop on message with distinct Parent_Message_ID is not supported */");
    }

    $cc_env = $nc_core->sub_class->get_by_id($target["Sub_Class_ID"]);
    if ($cc_env['SortBy'] && !preg_match('/^[\s]*[a.`]*Priority`?[\s]*(desc|asc)?[\s]*$/i', $cc_env['SortBy']) ) {
        die("0 /* Subclass is not sorted by default */");
    }

    if (!$perm->isSubClass($target['Sub_Class_ID'], MASK_MODERATE)) {
        die("0 /* No rights */");
    }

    $direction = ($target['Priority'] < $dragged['Priority']) ? 'down' : 'up';

    if ($direction == 'up') {
        $SQL = "UPDATE Message{$dragged_class}
                    SET Created = Created,
                        Priority = Priority - 1
                        WHERE Sub_Class_ID = {$dragged['Sub_Class_ID']}
                          AND Priority BETWEEN {$dragged['Priority']} AND ({$target['Priority']} - 1)";
        $db->query($SQL);

        $SQL = "UPDATE Message{$dragged_class}
                    SET Created = Created,
                        Priority = ({$target['Priority']} - 1)
                        WHERE Message_ID = $dragged_id";
        $db->query($SQL);
    } elseif ($direction = 'down') {
        $db->query("
                UPDATE Message{$dragged_class}
                    SET Created = Created,
                        Priority = Priority + 1
                        WHERE Sub_Class_ID = {$dragged['Sub_Class_ID']}
                           AND Priority BETWEEN {$target['Priority']} AND ({$dragged['Priority']} - 1)");

        // Change dragged object's priority
        $db->query("
                UPDATE Message{$dragged_class}
                    SET Created = Created,
                        Priority = {$target['Priority']}
                        WHERE Message_ID = $dragged_id");
    }

    die("1 /* OK */");
}

// message to subclass
if ($dragged_type == 'message' && $target_type == 'subclass') {
    $dragged_class = (int) $dragged_class;
    if (!$dragged_class) {
        die("0 /* Wrong message class */");
    }

    $subclass = $db->get_row("SELECT Sub_Class_ID, Subdivision_ID, Class_ID FROM Sub_Class WHERE Sub_Class_ID=$target_id", ARRAY_A);
    $message = $db->get_row("SELECT Subdivision_ID, Sub_Class_ID FROM Message{$dragged_class} WHERE Message_ID=$dragged_id", ARRAY_A);

    if (!$subclass) {
        die("0 /* Subclass [$target_id] doesn't exist */");
    }

    $subdivision_id = $subclass['Subdivision_ID'];
    $sql = "SELECT `Sub_Class_ID` FROM `Sub_Class` WHERE `Subdivision_ID` = {$subdivision_id} AND `Class_ID` = {$dragged_class} " .
        "ORDER BY `Priority` LIMIT 1";

    $subclass = $db->get_row($sql, ARRAY_A);

    if (!$subclass) {
        die("0 /* Can't move message between distinct classes */");
    }

    $new_target_id = $subclass['Sub_Class_ID'];

    $res = nc_move_message($dragged_class, $dragged_id, $new_target_id);
    if (!$res) {
        die("0 /* Error while moving message */");
    }

    die("1 /* OK */");
}

die("0 /* Wrong request */");
?>