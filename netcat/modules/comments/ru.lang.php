<?php
/* $Id: ru.lang.php 4002 2010-09-17 15:05:33Z denis $ */
if ( !class_exists("nc_System") ) die("Unable to load file.");

if ( $nc_core->NC_UNICODE) {
  require_once "ru_utf8.lang.php";
}
else {
  require_once "ru_cp1251.lang.php";
}
?>
