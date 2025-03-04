<?php

$_POST["NC_HTTP_REQUEST"] = true;
$NETCAT_FOLDER = join(strstr(__FILE__, "/") ? "/" : "\\", array_slice(preg_split("/[\/\\\]+/", __FILE__), 0, -4)).( strstr(__FILE__, "/") ? "/" : "\\" );
include_once ($NETCAT_FOLDER."vars.inc.php");
require($ADMIN_FOLDER."function.inc.php");

$nc_core = nc_core::get_object();
$class_id = $nc_core->input->fetch_get_post('class_id');
$infoblock_id = $nc_core->input->fetch_get_post('infoblock_id');

$custom_settings_template = $nc_core->component->get_by_id($class_id, 'CustomSettingsTemplate');

if ($custom_settings_template) {
    require $GLOBALS['ADMIN_FOLDER'] . 'subdivision/subclass.inc.php';

    $a2f = new nc_a2f($custom_settings_template, 'CustomSettings');
    if ($infoblock_id) {
        $a2f->set_values($nc_core->sub_class->get_by_id($infoblock_id, 'CustomSettings'));
    }
    else {
        $a2f->set_initial_values();
    }

    echo nc_sub_class_get_CustomSettings($a2f);
}
