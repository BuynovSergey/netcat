<?php
/* $Id: function.inc.php 4290 2011-02-23 15:32:35Z denis $ */
$NETCAT_FOLDER = join( strstr(__FILE__, "/") ? "/" : "\\", array_slice( preg_split("/[\/\\\]+/", __FILE__), 0, -4 ) ).( strstr(__FILE__, "/") ? "/" : "\\" );
@include_once $NETCAT_FOLDER . 'vars.inc.php';
// get global value (for admin mode)
global $MODULE_FOLDER;
if ( !class_exists("nc_System") ) {
    die("Unable to load file.");
}
// include need classes
include_once nc_module_folder('comments') . 'nc_comments.class.php';