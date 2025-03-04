<?php

if (!class_exists('nc_core')) {
    die;
}

function render_rows($rows, $padding = 0){
    $nc_core = nc_core::get_object();
    $result = "";
    $prev_area = "";
    foreach ($rows AS $row) {
        if ($row['Parent_Sub_Class_ID'] == 0 && $prev_area != $row['AreaKeyword']) {
            $result .= "<tr>";
            $result .= "<td class='name active' colspan='3' style='font-weight: bold'>" . sprintf(CONTROL_CONTENT_SUBCLASS_AREA, $row['AreaKeyword'])  . "</td>";
            $result .= "</tr>";
        }
        $result .= "<tr>";
        $result .=
            "<td class='name active' style='padding-left: ".($padding + 20)."px;'>" .
            "<img src='" . $nc_core->ADMIN_PATH . "images/arrow_sec.gif' width='14' height='10' alt='' title=''><span>" . $row['Sub_Class_ID'] . ". </span>" .
            ($row['Sub_Class_Name'] ?: CONTROL_CONTENT_SUBCLASS_CONTAINER) .
            "</td>";

        // component
        if ($row['Class_ID']) {
            $result .= "<td><a href='" . $nc_core->ADMIN_PATH . "class/index.php?phase=4&fs=1&ClassID=" . $row['Class_ID'] . "' title=''>" . $row['Class_Name'] . "</a></td>";
        } else {
            $result .= "<td></td>";
        }

        // settings
        $result .= "<td class='nc--compact'><a onclick='nc.load_dialog(this.href); return false'  href='". $nc_core->SUB_FOLDER . $nc_core->HTTP_ROOT_PATH ."action.php?ctrl=admin.infoblock&infoblock_id=" . $row['Sub_Class_ID'] . "&action=show_settings_dialog'><i class='nc-icon nc--settings nc--hovered' title='".CONTROL_CONTENT_SUBDIVISION_FUNCS_TOOPTIONS."'></i></a></td>";
        $result .= "</tr>";
        if ($row['Child']) {
			$result.= render_rows($row['Child'], $padding + 20);
		}
        $prev_area = $row['AreaKeyword'];
    }
    return $result;
}
?>

<table class='nc-table nc--wide nc--hovered' id='nc_site_area_ibfoblocks'>
    <tr>
        <td><?=CONTROL_CONTENT_SUBCLASS_CLASSNAME?></td>
        <td><?=CONTROL_CONTENT_CLASS?></td>
        <td align='center'><?=CONTROL_CONTENT_SUBCLASS_FUNCS_SETTINGS_GOTO?></td>
    </tr>
    <?=render_rows($data)?>
</table>


