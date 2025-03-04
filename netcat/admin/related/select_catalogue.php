<?
/* $Id: select_catalogue.php 6950 2012-05-12 09:04:54Z alkich $ */
// выбор связанной записи из MessageXX ($relation_class)

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

$cc_list = listQuery($qry,
                "<a href='#' onclick='top.selectItem(\$data[ItemID])' title='\".NETCAT_MODERATION_SELECT_RELATED.\"'>
       <span class='id'>\$data[ItemID].</span> \$data[ItemCaption]
    <div class='icons icon_related icon_site_select'></div>  
    </a>");

?>
<body style='margin:6px; overflow: auto !important;'>
    <div class='related_list related_list_catalogue'>
       <?=$cc_list ?>
    </div>
</body>

</html>