<?php

/* $Id: showpreview.php 7863 2012-07-30 15:18:50Z lemonade $ */

$NETCAT_FOLDER = join(strstr(__FILE__, "/") ? "/" : "\\", array_slice(preg_split("/[\/\\\]+/", __FILE__), 0, -4)).( strstr(__FILE__, "/") ? "/" : "\\" );
include_once ($NETCAT_FOLDER."vars.inc.php");
require ($INCLUDE_FOLDER."index.php");

$error = false;
$id = (int) $_GET['id'];

# если пользователь не зарегистрирован или не объявлен $perm
if (!$current_user || !class_exists('Permission') || !$perm instanceof Permission) {
    $error = NETCAT_MODERATION_ERROR_NORIGHT;
} else {
    # права администратора
    $AdmRights = ( $perm->isDirector() || # директор
            $perm->isSupervisor()     # супервизор

            );
    # если есть какие либо из этих прав, доступ разрещён
    if (!$AdmRights) $error = NETCAT_MODERATION_ERROR_NORIGHT;
}

// if no auth module, object $perm not instantiated and error is true
// try to authorize and unset error or error is really true
if ($error && Authorize() && $perm instanceof Permission && ($perm->isDirector() || $perm->isSupervisor())) {
    unset($error);
}

echo "<!DOCTYPE HTML PUBLIC '-//W3C//DTD HTML 4.01 Transitional//EN'>
<html>
<head>
<meta http-equiv='content-type' content='text/html; charset=".$nc_core->NC_CHARSET."'>
<title>".(!$error ? NETCAT_MODULE_CALENDAR_PREVIEW : $error)."</title>
".(!$error ? nc_set_calendar($id) : "")."
</head>
<body>
".(!$error ? "<table cellspacing='1' cellpadding='1' border='0' style='width:100%; height:100%'>
<tr valign='middle'>
<td align='center'>
".nc_show_calendar($id, 0, date('Y-m-d'), 'Created', true, false, true)."
</td>
</tr>
</table>" : $error)."
</body>
</html>";