<?php

if (!class_exists("nc_System")) {
    die("Unable to load file.");
}

require_once $nc_core->NC_UNICODE ? "ru_utf8.lang.php" : "ru_cp1251.lang.php";
