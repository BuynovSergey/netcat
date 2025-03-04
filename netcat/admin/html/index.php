<?php

/* $Id: index.php 5946 2012-01-17 10:44:36Z denis $ */

$NETCAT_FOLDER = join(strstr(__FILE__, "/") ? "/" : "\\", array_slice(preg_split("/[\/\\\]+/", __FILE__), 0, -4)).( strstr(__FILE__, "/") ? "/" : "\\" );
include_once ($NETCAT_FOLDER."vars.inc.php");
require ($ADMIN_FOLDER."function.inc.php");
require ($ADMIN_FOLDER."html/function.inc.php");

$UI_CONFIG = new ui_config_tool(TOOLS_HTML, TOOLS_HTML, 'i_netcat_big.gif', 'tools.html');

$Delimeter = ' &gt ';
$main_section = 'settings';
$item_id = 10;
$Title2 = TOOLS_HTML;

if (!isset($phase)) {
    $phase = 1;
}

switch ($phase) {
    case 1:
        BeginHtml($Title2, $Title2, "http://".$DOC_DOMAIN."/settings/html/");
        $perm->ExitIfNotAccess(NC_PERM_TOOLSHTML, 0, 0, 0, 0);
        ShowHTMLForm();
        break;

    case 2:
        BeginHtml($Title2, $Title2, "http://".$DOC_DOMAIN."/settings/html/");
        $perm->ExitIfNotAccess(NC_PERM_TOOLSHTML, 0, 0, 0, 1);

        nc_htmleditor_save();
        nc_print_status(NETCAT_SETTINGS_EDITOR_SAVE, 'ok');

        ShowHTMLForm();
        break;
}

EndHtml();
?>