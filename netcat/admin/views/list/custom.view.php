<?php
if (!class_exists('nc_core')) {
    die;
}

if (empty($custom_settings)) {
    nc_print_status(NETCAT_CUSTOM_NONE_SETTINGS, 'info');
    return false;
}

if ($saved) {
    nc_print_status(NETCAT_CUSTOM_PARAMETR_UPDATED, 'ok');
}

$a2f = new nc_a2f($custom_settings, '');

echo "<form method='post' action='" . $nc_core->SUB_FOLDER . $nc_core->HTTP_ROOT_PATH . "action.php?ctrl=admin.list&action=custom_drop&classificator_id=" . $classificator_id . "' >";
if ($view_only) {
    $cs_header = " <table class='nc-table nc--striped nc--small nc--hovered' style='width:100%'>
            <tr>
              <th width='30%'>" . NETCAT_CUSTOM_ONCE_FIELD_NAME . "</th>
              <th width='45%'>" . NETCAT_CUSTOM_ONCE_FIELD_DESC . "</th>
              <th  width='20%'>" . NETCAT_CUSTOM_TYPE . "</th>
            </tr>";

    $cs_item = '<tr style="background-color: #FFF;">' .
        '<td>%NAME</td>' .
        '<td>%CAPTION</td>' .
        '<td>%TYPENAME</td>' .
        '</tr>';
} else {
    $cs_header = " <table class='nc-table nc--striped nc--small nc--hovered' style='width:100%'>
            <tr>
              <th width='30%'>" . NETCAT_CUSTOM_ONCE_FIELD_NAME . "</th>
              <th width='45%'>" . NETCAT_CUSTOM_ONCE_FIELD_DESC . "</th>
              <th  width='20%'>" . NETCAT_CUSTOM_TYPE . "</th>
              <td align=center width='5%'>
                " . nc_admin_img('delete', NETCAT_CUSTOM_ONCE_DROP) . "
              </td>
            </tr>";

    $cs_item = '<tr style="background-color: #FFF;">' .
        '<td><a href="' . $nc_core->SUB_FOLDER . $nc_core->HTTP_ROOT_PATH . 'action.php?ctrl=admin.list&action=custom_edit&classificator_id=' . $classificator_id . '&param=%NAME">%NAME</a></td>' .
        '<td>%CAPTION</td>' .
        '<td>%TYPENAME</td>' .
        '<td align="center">' . nc_admin_checkbox_simple("kill[]", "%NAME") . '</td>' .
        '</tr>';
}
$cs_footer = '</table>';

echo $a2f->render_settings($cs_header, $cs_item, $cs_footer);

echo "</form>";