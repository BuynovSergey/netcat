<?php

/* $Id: ExportToFile.php 7302 2012-06-25 21:12:35Z alive $ */

$NETCAT_FOLDER = join(strstr(__FILE__, "/") ? "/" : "\\", array_slice(preg_split("/[\/\\\]+/", __FILE__), 0, -4)).( strstr(__FILE__, "/") ? "/" : "\\" );
include_once ($NETCAT_FOLDER."vars.inc.php");

$widget_class_id = intval($_GET['widget_class_id']);

require ($ADMIN_FOLDER."function.inc.php");

if (!$nc_core->token->verify()) {
    BeginHtml("", "");
    nc_print_status(NETCAT_TOKEN_INVALID, 'error');
    EndHtml();
    exit;
}

$widget_keyword = $db->get_var("SELECT `Keyword` FROM `Widget_Class` WHERE `Widget_Class_ID`='".$widget_class_id."'");

header("Content-type: text/plain");
header("Content-Disposition: attachment; filename=netcat_".urlencode($widget_keyword)."_widgetclass.wct");
header("Expires: 0");
header("Cache-Control: must-revalidate, post-check=0,pre-check=0");
header("Pragma: public");

require ($ADMIN_FOLDER."widget/function.inc.php");

echo nc_widgetclass_export($widget_class_id);