<?php

$NETCAT_FOLDER = join(strstr(__FILE__, "/") ? "/" : "\\", array_slice(preg_split("/[\/\\\]+/", __FILE__), 0, -4)).( strstr(__FILE__, "/") ? "/" : "\\" );
include_once ($NETCAT_FOLDER."vars.inc.php");
require ($ADMIN_FOLDER."function.inc.php");
require ($ADMIN_FOLDER."class/function.inc.php");
require ($ADMIN_FOLDER."field/function.inc.php");
require ($ADMIN_FOLDER."widget/function.inc.php");

InitVars(); // Initialization field name, field types

if (!isset($isSys)) {
    $isSys = 0;
}

if (!isset($priority)) {
    $priority = null;
}

if (!isset($ClassID)) {
    $ClassID = $nc_core->input->fetch_get_post('ClassID');
}
if (!isset($SystemTableID)) {
    $SystemTableID = $nc_core->input->fetch_get_post('SystemTableID');
}

if (isset($ClassID)) {
    $Id = $ClassID;
}

if (isset($SystemTableID) && $SystemTableID) {
    $Id = $SystemTableID;
}



if (isset($widgetclass_id) && $widgetclass_id) {
    $Id = $widgetclass_id;
    $isWidget = 1;
} else {
    $isWidget = 0;
}

if (!isset($Id)) $Id = $isSys ? GetSystemTableIDByFieldID($FieldID) : GetClassIDByFieldID($FieldID);

if ($isSys) {
    $main_section = "settings";
    $item_id = 1;
    $Title1 = "<a href=\"".$ADMIN_PATH."field/system.php\">".SECTION_SECTIONS_OPTIONS_SYSTEM."</a>";
    $Title2 = CONTROL_FIELD_FIELDS." (".GetSystemTableRusName($SystemTableID ? $SystemTableID : $Id).")";
    $Title3 = "<a href=\"".$ADMIN_PATH."field/systemField.php?SystemTableID=".$SystemTableID."\">".CONTROL_FIELD_FIELDS." (".GetSystemTableRusName($SystemTableID ? $SystemTableID : $Id).")</a>";
    $DocPath = "http://".$DOC_DOMAIN."/settings/systables/fields/";
} else {
    $main_section = "control";
    $item_id = 8;

    $Title1 = "<a href=\"".$ADMIN_PATH."class/\">".CONTROL_CLASS."</a>";
    $Title2 = CONTROL_FIELD_FIELDS." (".GetClassNameByID($ClassID).")";
    $Title3 = "<a href=\"".$ADMIN_PATH."field/?ClassID=".$ClassID."\">".CONTROL_FIELD_FIELDS." (".GetClassNameByID($ClassID).")</a>";
    $DocPath = "http://".$DOC_DOMAIN."/management/class/fields/";
}
if (!isset($FieldID)) {
    $FieldID = $nc_core->input->fetch_get_post('FieldID');
}
if ($FieldID) {
    $Title4 = CONTROL_FIELD_ADDING;
    $Title5 = GetFieldName($FieldID);
    $Title6 = CONTROL_FIELD_EDITING;
}

if (!isset($Title1)) {
    $Title1 = null;
}

if (!isset($Title2)) {
    $Title2 = null;
}

if (!isset($Title3)) {
    $Title3 = null;
}

if (!isset($Title4)) {
    $Title4 = null;
}

if (!isset($Title5)) {
    $Title5 = null;
}

if (!isset($Title1)) {
    $Title6 = null;
}

$Delimeter = " &gt ";


if (!isset($phase)) { 
    $phase = 1;
}

if (in_array($phase, array(3, 5, 7))) {
    if (!$nc_core->token->verify()) {
        BeginHtml($Title2, $Title1.$Delimeter.$Title2, $DocPath);
        nc_print_status(NETCAT_TOKEN_INVALID, 'error');
        EndHtml();
        exit;
    }
}


switch ($phase) {
    case 1:
        # покажем список полей
        BeginHtml($Title2, $Title1.$Delimeter.$Title2, $DocPath);
        $perm->ExitIfNotAccess(NC_PERM_FIELD, 0, $isSys, 0, 0);
        $class_widget = $isWidget ? new ui_config_widgetclass('fields', $Id) : new ui_config_class('fields', $Id);
        $UI_CONFIG = $isSys ? new ui_config_system_class('fields', $Id) : $class_widget;
        FieldList($Id, $isSys, 0, $isWidget);
        break;

    case 2:
        # покажем форму добавления поля
        BeginHtml($Title4, $Title1.$Delimeter.$Title3.$Delimeter.$Title4, $DocPath."form/");
        $perm->ExitIfNotAccess(NC_PERM_FIELD, 0, $isSys, 0, 0);
        $UI_CONFIG = new ui_config_field('add', 0, $Id, $isSys, $isWidget);
        $UI_CONFIG->treeSelectedNode = "dataclass-{$Id}";
        FieldForm(0, $Id, $isSys, 'index.php', 'admin_form', 'admin_form', '', $isWidget);
        break;

    case 3:
        # собственно добавим поле и покажем список
        global $field_types, $field_types_sprites, $type_of_error;
        BeginHtml($Title2, $Title1.$Delimeter.$Title2, $DocPath);
        $perm->ExitIfNotAccess(NC_PERM_FIELD, 0, $isSys, 0, 1);

        $new_id = FieldCompleted();
        if ($new_id <= 0) { #error
            $UI_CONFIG = new ui_config_field('add', 0, $Id, $isSys);
            nc_print_status($type_of_error[-$new_id], 'error');
            FieldForm(0, $Id, $isSys, 'index.php', '', '', '', $isWidget);
        } else { #ok
            nc_print_status(CONTROL_FIELD_MSG_ADDED, 'ok');
            $class_widget = $isWidget ? new ui_config_widgetclass('fields', $Id) : new ui_config_class('fields', $Id);
            $UI_CONFIG = $isSys ? new ui_config_system_class('fields', $Id) : $class_widget;
            FieldList($Id, $isSys, 0, $isWidget);

            $field = $db->get_row("SELECT field.`Field_ID`, field.`Field_Name`, field.`TypeOfData_ID`, field.`Description`
                             FROM `Field` AS field
                             LEFT JOIN `Classificator_TypeOfData` AS type
                             ON type.`TypeOfData_ID` = field.`TypeOfData_ID`
                             WHERE field.`Field_ID` = '".$new_id."'
                             ORDER BY field.`Priority`", ARRAY_A);

            $suffix = +$_REQUEST['fs'] ? '_fs' : '';

            $field_buttons[] = array(
                    "image" => "i_field_delete.gif",
                    "icon" => "icons nc-icon nc--remove nc--hovered",
                    "label" => CONTROL_FIELD_LIST_DELETE,
                    "action" => "parent.location.hash = '" . ($isSys ? "systemfield{$suffix}.delete(".$Id.",".$new_id.")" : "field{$suffix}.delete(".$Id.",".$new_id.")") .  "'",
                    "href" => $isSys ? "systemfield{$suffix}.delete(".$Id.",".$new_id.")" : "field{$suffix}.delete(".$Id.",".$new_id.")"
            );

            $sql = "SELECT `NotNull` FROM `Field` WHERE `Field_ID` = {$new_id}";
            $not_null = nc_core('db')->get_var($sql);

            $UI_CONFIG->treeChanges['addNode'][] = array("nodeId" => $isSys ? "systemfield-".$new_id : "field-".$new_id,
                    "parentNodeId" => $isSys ? "systemclass-".$Id : ($isWidget ? "widgetclass-".$Id : "dataclass-".$Id),
                    "name" => $new_id.". ".$field["Field_Name"],
                    "href" => $isSys ? "#systemfield".$suffix.".edit(".$new_id.")" : ($isWidget ? "#widgetfield".$suffix.".edit(".$new_id.")" : "#field".$suffix.".edit(".$new_id.")"),
                    "image" => $field_types[$field["TypeOfData_ID"]],
                    "title" => $field["Description"],
                    "sprite" => $field_types_sprites[$field["TypeOfData_ID"]] . ($not_null ? ' nc--required' : ''),
                    "buttons" => $field_buttons,
                    "acceptDropFn" => $isSys ? "treeSystemFieldAcceptDrop" : "treeFieldAcceptDrop",
                    "onDropFn" => $isSys ? "treeSystemFieldOnDrop" : "treeFieldOnDrop",
                    "hasChildren" => false,
                    "dragEnabled" => true);
        }
        break;

    case 4:
        # покажем форму редактирования поля
        BeginHtml($Title6, $Title1.$Delimeter.$Title3.$Delimeter.$Title5, $DocPath."form/");
        $perm->ExitIfNotAccess(NC_PERM_FIELD, 0, $isSys, 0, 0);
        $UI_CONFIG = new ui_config_field('edit', $FieldID, $Id, $isSys, $isWidget);
        FieldForm($FieldID, 0, $isSys, 'index.php', '', '', '', $isWidget);
        break;

    case 5:
        # собственно проапдейтим поле
        BeginHtml($Title2, $Title1.$Delimeter.$Title2, $isSys);
        $perm->ExitIfNotAccess(NC_PERM_FIELD, 0, $isSys, 0, 1);

        if (($errorcode = FieldCompleted()) > 0) {
            nc_print_status(CONTROL_FIELD_MSG_EDITED, 'ok');
            $UI_CONFIG = new ui_config_field('edit', $FieldID, 0, $isSys, $isWidget);
            $UI_CONFIG->updateTreeFieldNode($FieldID, $TypeOfData_ID, $FieldName);
        } else {
            $UI_CONFIG = new ui_config_field('add', 0, $Id, $isSys);
            nc_print_status($type_of_error[-$errorcode], 'error');
        }

        FieldForm($FieldID, 0, $isSys);
        break;

    case 6:
        # подтверждение удаления поля или нескольких полей
        BeginHtml($Title2, $Title1.$Delimeter.$Title2, $DocPath);
        $perm->ExitIfNotAccess(NC_PERM_FIELD, 0, $isSys, 0, 1);

        if (!empty($Delete)) {
            $UI_CONFIG = new ui_config_field('delete', 0, $Id, $isSys);
            ConfirmFieldsRemoval($Delete, $Id, $isSys, isset($widgetclass_id) ? $widgetclass_id : null);
        } else {
            $class_widget = $isWidget ? new ui_config_widgetclass('fields', $Id) : new ui_config_class('fields', $Id);
            $UI_CONFIG = $isSys ? new ui_config_system_class('fields', $Id) : $class_widget;
            UpdateFieldPriority($priority);
            nc_print_status(CONTROL_FIELD_MSG_FIELDS_CHANGED, 'ok');
            FieldList($Id, $isSys, 0, $isWidget);
        }
        break;

    case 7:
        # удалим поля и покажем список
        BeginHtml($Title2, $Title1.$Delimeter.$Title2, $DocPath);
        $perm->ExitIfNotAccess(NC_PERM_FIELD, 0, $isSys, 0, 1);

        $class_widget = $isWidget ? new ui_config_widgetclass('fields', $Id) : new ui_config_class('fields', $Id);
        $UI_CONFIG = $isSys ? new ui_config_system_class('fields', $Id) : $class_widget;

        UpdateFieldPriority($priority);

        if (DeleteFields($Delete) > 1) {
            nc_print_status(CONTROL_FIELD_MSG_DELETED_MANY, 'ok');
        } else {
            nc_print_status(CONTROL_FIELD_MSG_DELETED_ONE, 'ok');
        }
        FieldList($Id, $isSys, 0, $isWidget);
        break;
}

EndHtml();