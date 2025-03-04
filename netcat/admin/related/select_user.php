<?php
/* $Id: select_user.php 5946 2012-01-17 10:44:36Z denis $ */
// выбор связанной записи из User ($relation_class)

require("./head.php");
require_once($INCLUDE_FOLDER."s_common.inc.php");

if ($field_id) {
    $field_data = field_relation_factory::get_instance_by_field_id($field_id);
} else {
    $classname = 'nc_a2f_field_'.$cs_type;
    if (!class_exists($classname)) {
        trigger_error("Wrong params", E_USER_ERROR);
    }
    $fl = new $classname ();
    $field_data = $fl->get_relation_object();
}

$qry = $field_data->get_list_query();

$cc_list = listQuery($qry, "<tr>
  <td>
    <a class='nc--blocked' href='#' onclick='top.selectItem(\$data[ItemID])' title='\".NETCAT_MODERATION_SELECT_RELATED.\"'>
      <i class='nc-icon nc--user'></i> \$data[ItemCaption] (#\$data[ItemID])
    </a>
  </td>
</tr>");
?>

<body class='nc-admin nc-padding-10' style='overflow: auto !important;'>
<table class='nc-table nc--bordered nc--small nc--hovered nc--wide nc--striped'>
  <?=$cc_list ?>
</table>
</body>

</html>