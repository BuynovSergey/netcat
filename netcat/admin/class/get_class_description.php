<?php

/* $Id: get_class_description.php 5946 2012-01-17 10:44:36Z denis $ */

$_POST["NC_HTTP_REQUEST"] = true;

$NETCAT_FOLDER = join(strstr(__FILE__, "/") ? "/" : "\\", array_slice(preg_split("/[\/\\\]+/", __FILE__), 0, -4)).( strstr(__FILE__, "/") ? "/" : "\\" );
include_once ($NETCAT_FOLDER."vars.inc.php");
require_once ($ADMIN_FOLDER."function.inc.php");

if (!isset($class_id)) trigger_error("Wrong params", E_USER_ERROR);

$class_id+= 0;

$class_description = $db->get_var("SELECT `ClassDescription` FROM `Class` WHERE `Class_ID` = '".$class_id."'");

if ($class_description) {
    echo "<br><table border='0' cellpadding='0' cellspacing='0' width='100%'><tr><td bgcolor='#CCCCCC'>\n<table border='0' cellpadding='6' cellspacing='1' width='100%'>";
    echo "<tr><td colspan='2' bgcolor='#FFFFFF'><b>".CONTROL_CLASS_CLASS_DESCRIPTION.":</b><br><br>".$class_description."</td></tr>";
    echo "</table>\n</td></tr></table>";
}
?>