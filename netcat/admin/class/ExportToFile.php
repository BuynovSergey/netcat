<?php

/* $Id: ExportToFile.php 7983 2012-08-17 09:34:36Z lemonade $ */

$NETCAT_FOLDER = join(strstr(__FILE__, "/") ? "/" : "\\", array_slice(preg_split("/[\/\\\]+/", __FILE__), 0, -4)).( strstr(__FILE__, "/") ? "/" : "\\" );
include_once ($NETCAT_FOLDER."vars.inc.php");

$class_id = intval($_GET['ClassID']);

require ($ADMIN_FOLDER."function.inc.php");

if (!$nc_core->token->verify()) {
    BeginHtml("", "");
    nc_print_status(NETCAT_TOKEN_INVALID, 'error');
    EndHtml();
    exit;
}

// Выдача файла с шаблоном при экспорте
header("Content-type: text/xml");
header("Content-Disposition: attachment; filename=NetCat_".$class_id."_class.xml");
header("Expires: 0");
header("Cache-Control: must-revalidate, post-check=0,pre-check=0");
header("Pragma: public");



require ($ADMIN_FOLDER."class/function.inc.php");
require ($ADMIN_FOLDER."class/export.inc.php");

echo CascadeExportClass($ClassID);
?>