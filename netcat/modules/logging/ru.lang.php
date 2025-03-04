<?php

/* $Id: ru.lang.php 6207 2012-02-10 10:14:50Z denis $ */
if (!class_exists("nc_System")) die("Unable to load file.");

if ($nc_core->NC_UNICODE) {
    require_once "ru_utf8.lang.php";
} else {
    require_once "ru_cp1251.lang.php";
}
?>