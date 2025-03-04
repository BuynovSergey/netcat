<?php

/* $Id: function.inc.php 6207 2012-02-10 10:14:50Z denis $ */
if (!class_exists("nc_System")) die("Unable to load file.");
// get global value (for admin mode)
global $MODULE_FOLDER, $SUB_FOLDER, $HTTP_ROOT_PATH;

// include need classes
include_once nc_module_folder('logging') . 'nc_logging.class.php';

// instaninate object
nc_logging::get_object();