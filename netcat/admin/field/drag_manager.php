<?php

const NC_ADMIN_DISABLE_LOGIN_PAGE = true;

$NETCAT_FOLDER = join(strstr(__FILE__, "/") ? "/" : "\\", array_slice(preg_split("/[\/\\\]+/", __FILE__), 0, -4)).( strstr(__FILE__, "/") ? "/" : "\\" );
include_once ($NETCAT_FOLDER."vars.inc.php");
require ($ADMIN_FOLDER."function.inc.php");

$dragged_id = (int) $dragged_id;
if (!$dragged_id) die("0 /* Wrong parameters */");
if (!$target_id) die("0 /* Wrong parameters */");

// INPUT: $dragged_type, $dragged_id, $target_type, $target_id, $position [inside|below]

if ($dragged_type == 'field' && $target_type == 'field') {

    // dragged field info
    $dragged = $db->get_row("SELECT Class_ID, Priority FROM Field WHERE Field_ID='".$dragged_id."'", ARRAY_A);

    // target field info
    $target = $db->get_row("SELECT Class_ID, Priority FROM Field WHERE Field_ID='".$target_id."'", ARRAY_A);

    if ($perm->isSupervisor() && ($dragged['Class_ID'] == $target['Class_ID'])) {
        if ($dragged['Priority'] <= $target['Priority']) {
            $db->query("UPDATE Field
                     SET Priority = (Priority - 1)
                   WHERE Priority > ".$dragged['Priority']." AND Priority <= ".$target['Priority']." AND Class_ID = '".$dragged['Class_ID']."'");
            $db->query("UPDATE Field
                     SET Priority = ".$target['Priority']."
                   WHERE Field_ID = $dragged_id");
        } else {
            $db->query("UPDATE Field
                     SET Priority = (Priority + 1)
                   WHERE Priority > ".$target['Priority']." AND Priority < ".$dragged['Priority']." AND Class_ID = '".$dragged['Class_ID']."'");
            $db->query("UPDATE Field
                     SET Priority = (".$target['Priority']." + 1)
                   WHERE Field_ID = $dragged_id");
        }
    }
    die("1 /* OK */");
} elseif ($dragged_type == 'systemfield' && $target_type == 'systemfield') {

    // dragged field info
    $dragged = $db->get_row("SELECT System_Table_ID, Priority FROM Field WHERE Field_ID='".$dragged_id."'", ARRAY_A);

    // target field info
    $target = $db->get_row("SELECT System_Table_ID, Priority FROM Field WHERE Field_ID='".$target_id."'", ARRAY_A);

    if ($perm->isSupervisor() && ($dragged['System_Table_ID'] == $target['System_Table_ID'])) {
        if ($dragged['Priority'] <= $target['Priority']) {
            $db->query("UPDATE Field
                     SET Priority = (Priority - 1)
                   WHERE Priority > ".$dragged['Priority']." AND Priority <= ".$target['Priority']." AND System_Table_ID = '".$dragged['System_Table_ID']."'");
            $db->query("UPDATE Field
                     SET Priority = ".$target['Priority']."
                   WHERE Field_ID = $dragged_id");
        } else {
            $db->query("UPDATE Field
                     SET Priority = (Priority + 1)
                   WHERE Priority > ".$target['Priority']." AND Priority < ".$dragged['Priority']." AND System_Table_ID = '".$dragged['System_Table_ID']."'");
            $db->query("UPDATE Field
                     SET Priority = (".$target['Priority']." + 1)
                   WHERE Field_ID = $dragged_id");
        }
    }
    die("1 /* OK */");
} else {
    die("0 /* Wrong request */");
}
?>