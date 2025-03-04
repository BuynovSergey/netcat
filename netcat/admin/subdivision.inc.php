<?php

function UpdateHiddenURL($parent_url, $parent_sub, $catalogue) {
    global $nc_core, $db;

    $parent_sub+= 0;
    $catalogue+= 0;

    $res = $db->get_results("SELECT `EnglishName`, `Subdivision_ID` FROM `Subdivision`
      WHERE `Parent_Sub_ID` = '".$parent_sub."' AND `Catalogue_ID` = '".$catalogue."'", ARRAY_N);
    $subCount = $db->num_rows;
    for ($i = 0; $i < $subCount; $i++)
        list($english_name[$i], $sub_id[$i]) = $res[$i];

    for ($i = 0; $i < $subCount; $i++) {
        $new_parent_url = $parent_url.$english_name[$i]."/";

        $res = $db->query("UPDATE `Subdivision` SET `Hidden_URL` = '".$new_parent_url."', `LastUpdated` = `LastUpdated` WHERE `Subdivision_ID` = '".$sub_id[$i]."'");
        UpdateHiddenURL($new_parent_url, $sub_id[$i], $catalogue);
    }
}

function GetHiddenURL($SubdivisionID) {
    $nc_core = nc_Core::get_object();
    return!$SubdivisionID ? "" : $nc_core->subdivision->get_by_id($SubdivisionID, "Hidden_URL");
}

function GetSubSubsOneRegularInfoblock($sub_id) {
    $sub_id = (int)$sub_id;

    return (array)nc_Core::get_object()->db->get_results(
        "SELECT `a`.`Subdivision_ID` AS `Subdivision_ID`, `b`.`Sub_Class_ID` AS `Sub_Class_ID`
         FROM `Subdivision` AS `a`
         LEFT JOIN `Sub_Class` AS `b` ON `a`.`Subdivision_ID` = `b`.`Subdivision_ID`
         WHERE `a`.`Parent_Sub_ID` = '$sub_id' AND `b`.`Class_ID` > 0
         GROUP BY `a`.`Subdivision_ID`",
        ARRAY_A,
        'Subdivision_ID'
    );
}

function GetSubSubsChildrenCount($sub_id) {
    $sub_id = (int)$sub_id;

    return (array)nc_Core::get_object()->db->get_results(
        "SELECT `a`.`Subdivision_ID` AS `Subdivision_ID`, COUNT(`b`.`Subdivision_ID`) AS `ChildrenCount`
         FROM `Subdivision` AS `a`
         LEFT JOIN `Subdivision` AS `b` ON `a`.`Subdivision_ID` = `b`.`Parent_Sub_ID`
         WHERE `a`.`Parent_Sub_ID` = '$sub_id'
         GROUP BY `a`.`Subdivision_ID`",
        ARRAY_A,
        'Subdivision_ID'
    );
}

function ChildrenNumber($subdivision_id) {
    global $db, $perm;

    $subdivision_id = (int)$subdivision_id;

    if ($perm->isAllSiteModerator()) {
        return (int)$db->get_var(
            "SELECT COUNT(`Subdivision_ID`)
             FROM `Subdivision` AS `a`
             WHERE `Parent_Sub_ID` = '$subdivision_id'"
        );
    }

    # права на модерацию
    $catalogue_admin = $perm->listItems('catalogue');
    $sub_admin = $perm->listItems('subdivision');
    $cc_admin = $perm->listItems('subclass');

    if (empty($catalogue_admin) && empty($sub_admin) && empty($cc_admin)) {
        return 0;
    }

    # разделы, которые пользователь может видеть
    $allowed_subs = array();

    # id разделов, которые администрирует пользователь, на основе $sub_admin + $cc_admin
    $sub_and_cc_moderator = $sub_admin;

    if ($cc_admin) {
        $moderated_sub_ids = $db->get_col(
            "SELECT `Subdivision_ID`
             FROM `Sub_Class`
             WHERE `Sub_Class_ID` IN (" . join(',', array_unique($cc_admin)) . ")",
            ARRAY_A
        );

        foreach ($moderated_sub_ids as $moderated_sub_id) {
            $sub_and_cc_moderator[] = $moderated_sub_id;
        }
    }

    if ($sub_and_cc_moderator) {
        # id родительских разделов для тех, которые пользователь может модерировать или администрировать
        $moderated_parent_sub_ids = $db->get_col(
            "SELECT `parent`.`Subdivision_ID`
             FROM `Subdivision` AS `parent`, `Subdivision` AS `allowed`
             WHERE `allowed`.`Subdivision_ID` IN (" . join(',', array_unique($sub_and_cc_moderator)) . ")
             AND `allowed`.`Hidden_URL` LIKE CONCAT(`parent`.`Hidden_URL`, '%')"
        );

        foreach ($moderated_parent_sub_ids as $moderated_parent_sub_id) {
            $allowed_subs[] = $moderated_parent_sub_id;
        }
    }

    if ($catalogue_admin) {
        # id родительских разделов для тех, которые пользователь может модерировать или администрировать
        $moderated_parent_sub_ids = $db->get_col(
            "SELECT `parent`.`Subdivision_ID`
             FROM `Subdivision` AS `parent`, `Subdivision` AS `allowed`
             WHERE `allowed`.`Catalogue_ID` IN (" . join(',', array_unique($catalogue_admin)) . ")
             AND `allowed`.`Hidden_URL` LIKE CONCAT(`parent`.`Hidden_URL`, '%')"
        );

        foreach ($moderated_parent_sub_ids as $moderated_parent_sub_id) {
            $allowed_subs[] = $moderated_parent_sub_id;
        }
    }

    if ($sub_admin) {
        # id дочерних разделов для родителей которых явно указаны права на администрирование -- эти права наследуются (as of 3.0)
        $moderated_children_sub_ids = $db->get_col(
            "SELECT `child`.`Subdivision_ID`
             FROM `Subdivision` AS `child`, `Subdivision` AS `allowed`
             WHERE `allowed`.`Subdivision_ID` IN (" . join(',', array_unique($sub_admin)) . ")
             AND `child`.`Hidden_URL` LIKE CONCAT(`allowed`.`Hidden_URL`, '_%')"
        );

        foreach ($moderated_children_sub_ids as $moderated_children_sub_id) {
            $allowed_subs[] = $moderated_children_sub_id;
        }
    }

    if (!$allowed_subs) {
        return 0;
    }

    return (int)$db->get_var(
        "SELECT COUNT(`Subdivision_ID`)
         FROM `Subdivision` AS `a`
         WHERE `Parent_Sub_ID` = '$subdivision_id' AND `a`.`Subdivision_ID` IN (" . join(',', $allowed_subs) . ")"
    );
}

function GetParentSubID($SubdivisionID) {
    $nc_core = nc_Core::get_object();
    return $nc_core->subdivision->get_by_id($SubdivisionID, "Parent_Sub_ID");
}

function GetSubClassCount($SubdivisionID) {
    global $db;
    return $db->get_var("SELECT COUNT(*) FROM Sub_Class WHERE Subdivision_ID='".intval($SubdivisionID)."'");
}

function DeleteFromSubClass($subdivisionId) {
    $nc_core = nc_Core::get_object();
    $db = $nc_core->db;

    $res = $nc_core->sub_class->get_by_subdivision_id($subdivisionId);

    $catalogueId = null;
    $subClassToDelete = array();
    $classIds = array();

    foreach((array)$res as $subClass) {
        if (!$catalogueId) {
            $catalogueId = $subClass['Catalogue_ID'];
        }
        $subClassToDelete[] = (int)$subClass['Sub_Class_ID'];
        $classIds[$subClass['Sub_Class_ID']] = (int)$subClass['Class_ID'];
    }

    if (count($subClassToDelete) > 0) {
        $nc_core->event->execute(nc_Event::BEFORE_INFOBLOCK_DELETED, $catalogueId, $subdivisionId, $subClassToDelete);

        foreach($subClassToDelete as $subClass) {
            DeleteSubClassFiles($subClass, $classIds[$subClass]);

            if (nc_module_check_by_keyword('comments')) {
                include_once nc_module_folder('comments') . 'function.inc.php';
                // delete comment rules
                nc_comments::dropRuleSubClass($db, $subClass);
                // delete comments
                nc_comments::dropComments($db, $subClass, 'Sub_Class');
            }

            $db->query("DELETE FROM `Sub_Class` WHERE `Sub_Class_ID` = {$subClass}");
        }

        $nc_core->event->execute(nc_Event::AFTER_INFOBLOCK_DELETED, $catalogueId, $subdivisionId, $subClassToDelete);
    }

    return;
}

function DeleteMessages($SubdivisionID) {
    $nc_core = nc_Core::get_object();
    $SubdivisionID = (int)$SubdivisionID;
    $classes = $nc_core->db->get_col(
        "SELECT DISTINCT `Class_ID` FROM `Sub_Class`
         WHERE `Subdivision_ID` = '{$SubdivisionID}'"
    );

    if (!empty($classes)) {
        foreach ($classes as $ClassID) {
            $messages = $nc_core->db->get_col(
                "SELECT Message_ID
                 FROM `Message{$ClassID}`
                 WHERE `Subdivision_ID` = '{$SubdivisionID}'"
            );
            $nc_core->message->delete_by_id($messages, $ClassID, $nc_core->get_settings('TrashUse'));
        }
    }
}

class SubdivisionLocation {

    public $CatalogueID, $ParentSubID, $SubdivisionID;

    function __construct() {
        global $CatalogueID, $ParentSubID, $SubdivisionID;
        global $db, $nc_core;

        if ($SubdivisionID) {
            $Array = $nc_core->subdivision->get_by_id($SubdivisionID);
            $this->SubdivisionID = $SubdivisionID;
            $this->ParentSubID = $Array['Parent_Sub_ID'];
            $this->CatalogueID = $Array['Catalogue_ID'];
        } else {
            $this->SubdivisionID = 0;

            $this->ParentSubID = (isset($ParentSubID) && $ParentSubID) ? $ParentSubID : 0;

            if ($this->ParentSubID) {
                $this->CatalogueID = $nc_core->subdivision->get_by_id($this->ParentSubID, 'Catalogue_ID');
            } else {
                $this->CatalogueID = (isset($CatalogueID) && $CatalogueID) ? $CatalogueID : 0;
            }
        }
    }

    function printVars() {
        print "SubdivisionLocation.CatalogueID=".$this->CatalogueID."<br>\n";
        print "SubdivisionLocation.ParentSubID=".$this->ParentSubID."<br>\n";
        print "SubdivisionLocation.SubdivisionID=".$this->SubdivisionID."<br>\n";
    }

}

function IsAllowedSubdivisionEnglishName($EnglishName, $ParentSubID, $SubdivisionID, $CatalogueID) {
    global $db;

    if (!$EnglishName) return 0;

    $EnglishName = $db->escape($EnglishName);
    $ParentSubID = intval($ParentSubID);
    $SubdivisionID = intval($SubdivisionID);
    $CatalogueID = intval($CatalogueID);

    $select = "SELECT EnglishName FROM Subdivision WHERE EnglishName='".$EnglishName."' AND Parent_Sub_ID='".$ParentSubID."'";
    $select .= " AND EnglishName<>'' AND Subdivision_ID<>'".$SubdivisionID."' AND Catalogue_ID='".$CatalogueID."'";

    $Result = $db->query($select);

    return ($db->num_rows == 0);
}

function CascadeDeleteSubdivision($SubdivisionID) {
    $nc_core = nc_Core::get_object();
    $db = $nc_core->db;
    $SubdivisionID = (int)$SubdivisionID;

    $CatalogueID = $db->get_var("SELECT `Catalogue_ID` FROM `Subdivision` WHERE `Subdivision_ID` = $SubdivisionID");

    // execute core action
    $nc_core->event->execute(nc_Event::BEFORE_SUBDIVISION_DELETED, $CatalogueID, $SubdivisionID);

    if (0 && nc_module_check_by_keyword('comments')) {
        include_once nc_module_folder('comments') . 'function.inc.php';
        // delete comment rules
        nc_comments::dropRule($db, array($CatalogueID, $SubdivisionID));
        // delete comments
        nc_comments::dropComments($db, $SubdivisionID, 'Subdivision');
    }

    DeleteMessages($SubdivisionID);
    DeleteFromSubClass($SubdivisionID);
    DeleteSubdivisionDir($SubdivisionID);

    $db->query("DELETE FROM `Subdivision` WHERE `Subdivision_ID` = $SubdivisionID");

    // Удаление сгенерированных изображений для раздела
    $sys_table_id = $nc_core->get_system_table_id_by_name('Subdivision');
    $sub_file_field_ids = $db->get_col(
        "SELECT `Field_ID` FROM `Field` WHERE `System_Table_ID`={$sys_table_id} AND `TypeOfData_ID`=" . NC_FIELDTYPE_FILE
    );
    if ($sub_file_field_ids) {
        foreach ($sub_file_field_ids as $sub_file_field_id) {
            nc_image_generator::remove_generated_images('subdivision', $sub_file_field_id, $SubdivisionID);
        }
    }

    // execute core action
    $nc_core->event->execute(nc_Event::AFTER_SUBDIVISION_DELETED, $CatalogueID, $SubdivisionID);

    nc_tpl_mixin_cache::delete_subdivision_files($CatalogueID, $SubdivisionID);

}
