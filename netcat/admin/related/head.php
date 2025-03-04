<?php

// общий заголовок для select_*

$NETCAT_FOLDER = join(strstr(__FILE__, "/") ? "/" : "\\", array_slice(preg_split("/[\/\\\]+/", __FILE__), 0, -4)).( strstr(__FILE__, "/") ? "/" : "\\" );
include_once ($NETCAT_FOLDER."vars.inc.php");
require_once ($ADMIN_FOLDER."function.inc.php");
require_once ($ADMIN_FOLDER."related/format.inc.php");
$nc_core = nc_core::get_object();

$field_id = isset($field_id) ? (int)$field_id : 0;
$cs_field_name = htmlspecialchars(isset($cs_field_name) ? $cs_field_name : '', ENT_QUOTES);
$cs_type = htmlspecialchars(isset($cs_type) ? $cs_type : '', ENT_QUOTES);
if (!$field_id && (!$cs_field_name || !$cs_type )) {
    trigger_error("Wrong params", E_USER_ERROR);
}

$component_id = (int)$nc_core->input->fetch_get('component_id');

$field_description = $field_id ? $db->get_var("SELECT `Description` FROM `Field` WHERE Field_ID='".$field_id."'") : $cs_field_name;

echo"
<html>
<head>
 <title>".sprintf(NETCAT_MODERATION_RELATED_POPUP_TITLE, $field_description)."</title>
 " . nc_js() . "
 <link rel='stylesheet' type='text/css' media='screen' href='" . nc_add_revision_to_url($ADMIN_TEMPLATE . 'css/admin.css') . "'>
 <script type='text/javascript' src='" . nc_add_revision_to_url($ADMIN_PATH . 'js/lib.js') . "'></script>
 <script type='text/javascript' src='" . nc_add_revision_to_url($ADMIN_PATH . 'js/container.js') . "'></script>
 <script type='text/javascript'>
  function loadSubMessages(subId) {
    document.getElementById('subViewIframe').contentWindow.location =
      '".$ADMIN_PATH."related/select_message_list.php?component_id=$component_id&".( $field_id ? "field_id=".$field_id : "cs_field_name=".$cs_field_name )."&sub='+subId;
  }

  function loadSubClasses(subId) {
    document.getElementById('subViewIframe').contentWindow.location =
      '".$ADMIN_PATH."related/select_subclass_list.php?component_id=$component_id&".( $field_id ? "field_id=".$field_id : "cs_field_name=".$cs_field_name )."&sub='+subId;
  }

  function selectItem(messageId) {
    window.location = '".$ADMIN_PATH."related/save.php?component_id=$component_id&".( $field_id ? "field_id=".$field_id : "cs_type=".$cs_type."&cs_field_name=".$cs_field_name )."&object_id='+messageId;
  }
 </script>
</head>";