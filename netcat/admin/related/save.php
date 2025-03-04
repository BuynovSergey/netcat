<?php

// данный файл существует только потому, что в s_list_class (select_message_list.php)
// и в дереве (tree_json.php mode=select_subdivision)
// неэффективно вычислять название связанного объекта [для формы, открывшей это окно]

$NETCAT_FOLDER = join(strstr(__FILE__, "/") ? "/" : "\\", array_slice(preg_split("/[\/\\\]+/", __FILE__), 0, -4)) . (strstr(__FILE__, "/") ? "/" : "\\");
include_once $NETCAT_FOLDER . 'vars.inc.php';
require_once $ADMIN_FOLDER . 'function.inc.php';
require_once $ADMIN_FOLDER . 'related/format.inc.php';
require_once $INCLUDE_FOLDER . 's_common.inc.php';

$field_id = isset($field_id) ? (int)$field_id : 0;
$object_id = isset($object_id) ? (int)$object_id : 0;
$cs_field_name = htmlspecialchars($cs_field_name, ENT_QUOTES);
$cs_type = htmlspecialchars($cs_type, ENT_QUOTES);
$component_id = (int)$nc_core->input->fetch_get('component_id');
if ((!$field_id && !($cs_field_name || !$cs_type || !$component_id)) || !$object_id) {
    trigger_error('Wrong params', E_USER_ERROR);
}

if ($component_id) {
    $field_caption = NETCAT_MODERATION_OBJECT . ' #' . $object_id;
} else {
    if ($field_id) {
        $field_data = field_relation_factory::get_instance_by_field_id($field_id);
    } else {
        $classname = 'nc_a2f_field_' . $cs_type;
        if (!class_exists($classname)) {
            trigger_error("Wrong params", E_USER_ERROR);
        }
        $fl = new $classname();
        $field_data = $fl->get_relation_object();
    }

    $qry = $field_data->get_object_query($object_id);
    $data = $db->get_row($qry, ARRAY_A);

    $id = $data['ItemID'];
    $caption = $data['ItemCaption'];
    $sub = nc_array_value($data, 'SubID');
    $classid = nc_array_value($data, 'ItemClassID');
    $link = '';
    // данные для шаблона ссылки должны быть в массиве с названием $data ↓
    eval(nc_check_eval('$link = "' . $field_data->get_admin_link_template() . '";'));

    $field_caption = "{$id}. <a href='{$link}' target='_blank'>{$caption}</a>";
    $eng_caption_name = nc_transliterate($caption, true);
}

?>
<html>
<head>
    <title></title>
    <script type='text/javascript'>
        try {
            var $ = window.opener.$nc;
            var $is_adding_mirror_cc = $('[name=subclassType]').length > 0;

            <?php if ($field_id): ?>
                $('#nc_rel_<?= $field_id ?>_value').val("<?= $object_id ?>").change();
                $('#nc_rel_<?= $field_id ?>_caption').html(<?= nc_array_json($field_caption) ?>);
            <?php else: ?>
                $('#cs_<?= $cs_field_name ?>_value').val("<?= $object_id ?>").change();
                $('#cs_<?= $cs_field_name ?>_caption').html(<?= nc_array_json($field_caption) ?>);
                $('#cs_<?= $cs_field_name ?>_inherit').hide().find(':checkbox').prop('checked', false).change();

                if ($is_adding_mirror_cc) {
                    $('input[name=SubClassName]').val(<?= nc_array_json($caption) ?>).change();
                    $('input[name=EnglishName]').val(<?= nc_array_json($eng_caption_name) ?>).change();
                    $('input[name=SrcMirror]').val("<?= $id ?>").change();
                    $('input[name=MirrorClassID]').val("<?= $classid ?>").change();
                }
            <?php endif; ?>
        } catch (e) {
            alert(<?= nc_array_json(NETCAT_MODERATION_RELATED_ERROR_SAVING) ?>);
        }
        window.close();
    </script>
</head>

<body></body>

</html>
