<?php
/* $Id: index.php 4259 2011-01-31 17:26:27Z denis $ */

$NETCAT_FOLDER = join( strstr(__FILE__, "/") ? "/" : "\\", array_slice( preg_split("/[\/\\\]+/", __FILE__), 0, -2 ) ).( strstr(__FILE__, "/") ? "/" : "\\" );
include_once ($NETCAT_FOLDER."vars.inc.php");

// redirect to the title page
header("Location: /".$SUB_FOLDER);
exit;
?>