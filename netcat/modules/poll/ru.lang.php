<?php

/* $Id $ */

if (!class_exists("nc_System")) die("Unable to load file.");

if ($nc_core->NC_UNICODE) {
    require_once "ru_utf8.lang.php";
} else {
    require_once "ru_cp1251.lang.php";
}
?>