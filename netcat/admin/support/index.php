<?php

$NETCAT_FOLDER = join(strstr(__FILE__, "/") ? "/" : "\\", array_slice(preg_split("/[\/\\\]+/", __FILE__), 0, -4)).( strstr(__FILE__, "/") ? "/" : "\\" );
include_once ($NETCAT_FOLDER."vars.inc.php");
require ($ADMIN_FOLDER."function.inc.php");

$Delimeter = " &gt ";
$main_section = "support";
$item_id = 1;
$Title1 = SUPPORT;

if ($perm->isGuest() && $phase) {
    BeginHtml($Title1, $Title1, "");
    nc_print_status(NETCAT_MODERATION_ERROR_NORIGHTS, 'error');
    EndHtml ();
    exit;
}


BeginHtml($Title1, $Title1, "http://".$DOC_DOMAIN."/support/message/");

print SUPPORT_HELP_MESSAGE;

EndHtml ();
