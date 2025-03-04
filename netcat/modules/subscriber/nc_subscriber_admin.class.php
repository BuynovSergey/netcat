<?php

/* $Id: nc_subscriber_admin.class.php 7302 2012-06-25 21:12:35Z alive $ */

global $MODULE_FOLDER;

// load modules env
$MODULE_VARS = $nc_core->modules->get_module_vars();

if (!$MODULE_VARS['subscriber']['VERSION'] || $MODULE_VARS['subscriber']['VERSION'] != 1) {
    include_once nc_module_folder('subscriber') . 'nc_subscriber_admin.class.v2.php';
}