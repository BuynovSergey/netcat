<?php

$NETCAT_FOLDER = join(strstr(__FILE__, "/") ? "/" : "\\", array_slice(preg_split("/[\/\\\]+/", __FILE__), 0, -4)).( strstr(__FILE__, "/") ? "/" : "\\" );
include_once ($NETCAT_FOLDER."vars.inc.php");
require ($ADMIN_FOLDER."function.inc.php");
require ($ADMIN_FOLDER."related/format.inc.php");
require_once ($INCLUDE_FOLDER."s_common.inc.php");

$sub = (int) $sub;
$field_id = isset($field_id) ? (int)$field_id : 0;
$cs_field_name = htmlspecialchars($cs_field_name, ENT_QUOTES);
if (!$sub || (!$field_id && !$cs_field_name)) {
    trigger_error("Not enough data", E_USER_ERROR);
}

if ($field_id) {
    $field_data = field_relation_factory::get_instance_by_field_id($field_id);
} else {
    $cs_type = 'rel_cc';
    $classname = 'nc_a2f_field_'.$cs_type;
    if (!class_exists($classname)) {
        trigger_error("Wrong params", E_USER_ERROR);
    }
    $fl = new $classname ();
    $field_data = $fl->get_relation_object();
}

$qry = $field_data->get_list_query($sub);

$cc_list = listQuery($qry,
                "<a href='#' onclick='top.selectItem(\$data[ItemID])' title='\".NETCAT_MODERATION_SELECT_RELATED.\"'>
     <span class='id'>\$data[ItemID].</span> \$data[ItemCaption]
    <div class='icons icon_related icon_subclass_select'></div></a><br/>");
?>
<html>
    <head>
        <title>Subclass list</title>
        <link type='text/css' rel='Stylesheet' href='<?= nc_add_revision_to_url($ADMIN_TEMPLATE . 'css/sprites.css') ?>'>
        <link type='text/css' rel='Stylesheet' href='<?= nc_add_revision_to_url($ADMIN_TEMPLATE . 'css/admin.css') ?>'>
        <link type='text/css' rel='Stylesheet' href='<?= nc_add_revision_to_url($ADMIN_TEMPLATE . 'css/main.css') ?>'>
    </head>

    <body class='nc-subclass-list'>
        <?php
        if ($cc_list) {
            print "<div class='related_list related_list_subclass'>".$cc_list."</div>";
        } else {
            nc_print_status(NETCAT_MODERATION_RELATED_NO_ANY_CLASS_IN_SUB, 'info');
        }
        ?>
    </body>
</html>