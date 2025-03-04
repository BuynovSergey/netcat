<?php

function GetSubClassName($SubClassID) {
    try {
        return nc_core::get_object()->sub_class->get_by_id($SubClassID, 'Sub_Class_Name');
    } catch (Exception $e) {
        return null;
    }
}

function GetSubdivisionBySubClass($SubClassID) {
    try {
        return nc_core::get_object()->sub_class->get_by_id($SubClassID, 'Subdivision_ID');
    } catch (Exception $e) {
        return null;
    }
}

function GetClassIDBySubClass($SubClassID) {
    try {
        return nc_core::get_object()->sub_class->get_by_id($SubClassID, 'Class_ID');
    } catch (Exception $e) {
        return null;
    }
}

/**
 * Delete all objects from SubClass
 *
 * @param int $SubClassID
 * @return null
 */
function SubClassClear($SubClassID) {
    $nc_core = nc_Core::get_object();
    $SubClassID = (int)$SubClassID;
    $sub_class_info = $nc_core->db->get_row(
        "SELECT `Catalogue_ID`, `Subdivision_ID`, `Class_ID` FROM `Sub_Class` WHERE `Sub_Class_ID` = '{$SubClassID}'",
        ARRAY_A
    );

    if (!$sub_class_info['Class_ID']) {
        // контейнер
        return;
    }

    if (nc_module_check_by_keyword('comments')) {
        include_once nc_module_folder('comments') . 'function.inc.php';
        nc_comments::dropRuleSubClass($nc_core->db, $SubClassID);
        nc_comments::dropComments($nc_core->db, $SubClassID, 'Sub_Class');
    }

    if ($nc_core->db->get_var('SELECT System_Table_ID FROM Class WHERE Class_ID = ' . $sub_class_info['Class_ID'])) {
        return null;
    }

    DeleteSubClassFiles($SubClassID, $sub_class_info['Class_ID']);

    $messages = $nc_core->db->get_col(
        "SELECT `Message_ID`
         FROM `Message{$sub_class_info['Class_ID']}`
         WHERE `Subdivision_ID` = '{$sub_class_info['Subdivision_ID']}'
         AND `Sub_Class_ID` = '{$SubClassID}'");

    $nc_core->message->delete_by_id($messages, $sub_class_info['Class_ID'], $nc_core->get_settings('TrashUse'));

    return null;
}

function DeleteSubClass($SubClassID) {
    return nc_core::get_object()->sub_class->delete($SubClassID);
}

function IsAllowedSubClassEnglishName($EnglishName, $SubdivisionID, $SubClassID) {
    global $db;

    $db->query("SELECT `EnglishName`
                FROM `Sub_Class`
                WHERE `EnglishName` = '{$EnglishName}'
                AND `Subdivision_ID` = '{$SubdivisionID}'
                AND `Sub_Class_ID` <> '{$SubClassID}'");

    if ($db->num_rows == 0) {
        return 1;
    }

    return 0;
}
