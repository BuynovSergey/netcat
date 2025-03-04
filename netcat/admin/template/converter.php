<?php

$NETCAT_FOLDER = join(strstr(__FILE__, "/") ? "/" : "\\", array_slice(preg_split("/[\/\\\]+/", __FILE__), 0, -4)).( strstr(__FILE__, "/") ? "/" : "\\" );
include_once ($NETCAT_FOLDER."vars.inc.php");
require ($ADMIN_FOLDER."function.inc.php");
require ($ADMIN_FOLDER."template/function.inc.php");

$main_section = "settings";
$item_id = 11;
$Delimeter = " &gt ";
$Title4 = CONTROL_TEMPLATE_CLASSIFICATOR;


$perm->ExitIfNotAccess(NC_PERM_TEMPLATE, 0, 0, 0, 1);

if (!isset($phase)) {
    $phase = 1;
}

BeginHtml($Title4, $Title4, "http://".$DOC_DOMAIN."/settings/converter/");

ConvertForm($phase, $source);

EndHtml ();
?>