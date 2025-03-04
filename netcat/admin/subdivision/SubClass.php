<?php

/* $Id: SubClass.php 7232 2012-06-18 05:42:06Z alive $ */

$NETCAT_FOLDER = join(strstr(__FILE__, "/") ? "/" : "\\", array_slice(preg_split("/[\/\\\]+/", __FILE__), 0, -4)).( strstr(__FILE__, "/") ? "/" : "\\" );
include_once ($NETCAT_FOLDER."vars.inc.php");
require ($ADMIN_FOLDER."function.inc.php");
require ($ADMIN_FOLDER."subdivision/function.inc.php");
require ($ADMIN_FOLDER."subdivision/subclass.inc.php");

$Delimeter = " &gt ";
$CatalogueURL = "".$ADMIN_PATH."catalogue/?phase=6&CatalogueID=";
$SubdivisionURL = "".$ADMIN_PATH."subdivision/?phase=4&SubdivisionID=";

if (!isset($CatalogueID)) {
    $CatalogueID = null;
}
if (!isset($ParentSubID)) {
    $ParentSubID = null;
}
if (!isset($SubClassID)) {
    $SubClassID = null;
}
if (!isset($phase)) {
    $phase = null;
}

$loc = new SubdivisionLocation($CatalogueID, $ParentSubID, $SubdivisionID);
$sh = new SubdivisionHierarchy($Delimeter, $CatalogueURL, $SubdivisionURL);

$main_section = "control";
$item_id = 1;
$Title1 = "<a href=".$ADMIN_PATH."catalogue/>".SECTION_CONTROL_CONTENT_CATALOGUE."</a>";
$Title1 .= $Delimeter.$sh->Link;

$Title2 = CONTROL_CONTENT_SUBDIVISION_SUBCLASS_CLASSLIST_SECTION;
$Title2_1 = CONTROL_CONTENT_SUBDIVISION_SUBCLASS_CLASSLIST_SITE;
$Title3 = "<a href=\"".$ADMIN_PATH."subdivision/SubClass.php?CatalogueID=".$CatalogueID."&SubdivisionID=".$SubdivisionID."\">".CONTROL_CONTENT_SUBDIVISION_SUBCLASS_CLASSLIST_SECTION."</a>";
$Title4 = CONTROL_CONTENT_SUBDIVISION_SUBCLASS_ADDCLASS;
$Title5 = GetSubClassName($SubClassID);
$Title6 = CONTROL_CONTENT_SUBDIVISION_SUBCLASS_ADDCLASS;
$Title7 = CONTROL_CONTENT_SUBDIVISION_SUBCLASS_OPTIONSCLASS;
$Title8 = CONTROL_CONTENT_SUBDIVISION_SUBCLASS_ADDCLASSSITE;
$Title9 = "<a href=".$ADMIN_PATH."subdivision/SubClass.php?CatalogueID=".$CatalogueID.">".CONTROL_CONTENT_SUBDIVISION_SUBCLASS_CLASSLIST_SITE."</a>";

if (in_array($phase, array(2, 4, 5))) {
    if (!$nc_core->token->verify()) {
        BeginHtml($Title2, $Title2, "");
        nc_print_status(NETCAT_TOKEN_INVALID, 'error');
        EndHtml();
        exit;
    }
}

try {
    if (isset($phase)) {

        if ($SubClassID || $SubdivisionID) {
            $UI_CONFIG = new ui_config_subdivision_subclass($SubdivisionID, 'add');
        }
        $error = "";
        // сохранение формы
        if ($phase == 2 || $phase == 4) {

            $CustomSettings = $nc_core->input->fetch_post('CustomSettings');
            // получение значения дополнительных настроек (CustomSettings)
            if (empty($Class_ID)) {
                $SQL = "SELECT `Class_ID`
                            FROM `Sub_Class`
                                WHERE `Sub_Class_ID` = ".+$SubClassID;
                $Class_ID = $db->get_var($SQL);

                if (!$Class_ID) {
                    $error = '$Class_ID is not set. See ' . __FILE__;
                }
            }
            $settings_array = $nc_core->component->get_by_id(!empty($Class_Template_ID) ? $Class_Template_ID : $Class_ID, 'CustomSettingsTemplate');

            if ($settings_array) {
                $entity = $SubClassID ?: null;
                $entity_type = $SubClassID ? 'class_settings' : null;

                $a2f = new nc_a2f($settings_array, 'CustomSettings', false, $entity, $entity_type);
                if ($SubClassID) {
                    $a2f->set_values($nc_core->sub_class->get_by_id($SubClassID, 'CustomSettings'));
                }
                else {
                    $a2f->set_initial_values();
                }
                if (!$a2f->validate($CustomSettings)) {
                    $error = $a2f->get_validation_errors();
                } else {
                    $a2f->save_from_request_data('CustomSettings');
                    $CustomSettings = $a2f->get_values_as_string();
                }
            } else {
                $CustomSettings = "";
            }
            // транслитерация, если пустой EnglishName
            if (empty($EnglishName)) {
              // здесь не нужна проверка на уникальность, поскольку далее
              // значение используется лишь для проверки на валидность
              $EnglishName = nc_transliterate($SubClassName, true);
            }

            // проверка значений
            if ($SubClassName == "") {
                $error = CONTROL_CONTENT_SUBDIVISION_SUBCLASS_ERROR_NAME;
            } elseif (!$nc_core->sub_class->validate_english_name($EnglishName)) {
                $error = CONTROL_CONTENT_SUBDIVISION_SUBCLASS_ERROR_KEYWORD_INVALID;
            } elseif ((!IsAllowedSubClassEnglishName($EnglishName, $SubdivisionID, (int) $SubClassID) ) || ( $EnglishName == "" )) {
                $error = CONTROL_CONTENT_SUBDIVISION_SUBCLASS_ERROR_KEYWORD;
            } elseif (!empty($a2f) && $a2f->has_errors()) {
                $error = CONTROL_CLASS_CUSTOM_SETTINGS_HAS_ERROR."<br>".$a2f->get_validation_errors();
            }
        } // of сохранение формы


        switch ($phase) {
            case 1:
                // Форма добавления инфоблока
                if (!$SubdivisionID) {
                    BeginHtml(
                        $Title8,
                        $Title1 . $Delimeter . $Title9 . $Delimeter . $Title8,
                        "http://" . $DOC_DOMAIN . "/management/sites/sections/class/add/"
                    );
                } else {
                    BeginHtml(
                        $Title6,
                        $Title1 . $Delimeter . $Title3 . $Delimeter . $Title4,
                        "http://" . $DOC_DOMAIN . "/management/sites/sections/class/add/"
                    );
                }

                $perm->ExitIfNotAccess(NC_PERM_ITEM_SUB, NC_PERM_ACTION_SUBCLASSADD, $SubdivisionID, 0, 0);
                ActionForm(0, 2, 1);

                break;

            case 2:
                // Добавление инфоблока
                BeginHtml($Title2, $Title1.$Delimeter.$Title2, "http://".$DOC_DOMAIN."/management/sites/sections/class/");
                $perm->ExitIfNotAccess(NC_PERM_ITEM_SUB, NC_PERM_ACTION_SUBCLASSADD, $SubdivisionID, 0, 1);

                if ($error) {
                    nc_print_status($error, 'error');
                    ActionForm(0, 2, 1);
                    break;
                }

                $SubClassID = ActionSubClassCompleted(1);

                $UI_CONFIG = new ui_config_subdivision_subclass($SubdivisionID, 'edit', $SubClassID);

                if ($SubClassID) {
                    nc_print_status(CONTROL_CONTENT_SUBDIVISION_SUBCLASS_SUCCESS_ADD, 'ok');
                } else {
                    nc_print_status(CONTROL_CONTENT_SUBDIVISION_SUBCLASS_ERROR_ADD, 'error');
                }

                ActionForm($SubClassID, 4, 2);
                $UI_CONFIG->treeChanges['updateNode'][] = array("nodeId" => "sub-$SubdivisionID","href" => "#object.list(".$SubClassID.")");
                break;

            case 3:
                // Форма обновления инфоблока
                BeginHtml($Title7, $Title1.$Delimeter.$Title3.$Delimeter.$Title5, "http://".$DOC_DOMAIN."/management/sites/sections/class/settings/");
                $perm->ExitIfNotAccess(NC_PERM_ITEM_CC, NC_PERM_ACTION_EDIT, array($SubdivisionID, $SubClassID), 0, 0);
                $UI_CONFIG = new ui_config_subdivision_subclass($SubdivisionID, 'edit', $SubClassID);
                ActionForm($SubClassID, 4, 2);
                break;

            case 4:
                // Обновление инфоблока
                BeginHtml($Title2, $Title1.$Delimeter.$Title2, "http://".$DOC_DOMAIN."/management/sites/sections/class/");
                $perm->ExitIfNotAccess(NC_PERM_ITEM_CC, NC_PERM_ACTION_EDIT, array($SubdivisionID, $SubClassID), 0, 1);

                $UI_CONFIG = new ui_config_subdivision_subclass($SubdivisionID, 'edit', $SubClassID);

                if ($error) {
                    nc_print_status($error, 'error');
                    ActionForm($SubClassID, 4, 2);
                    break;
                }

                if (ActionSubClassCompleted(2) !== false) {
                    $UI_CONFIG = new ui_config_subdivision_subclass($SubdivisionID, 'edit', $SubClassID);
                    nc_print_status(CONTROL_CONTENT_SUBDIVISION_SUBCLASS_SUCCESS_EDIT, 'ok');
                } else {
                    nc_print_status(CONTROL_CONTENT_SUBDIVISION_SUBCLASS_ERROR_EDIT, 'error');
                }

                if (+$_REQUEST['isNaked']) {
                    ob_clean();
                    echo 'OK';
                    exit;
                }

                ActionForm($SubClassID, 4, 2);
                break;

            case 5:
                // Операции с инфоблоками в разделе
                BeginHtml($Title2, $Title1.$Delimeter.$Title2, "http://".$DOC_DOMAIN."/management/sites/sections/class/");
                $perm->ExitIfNotAccess(NC_PERM_ITEM_SUB, NC_PERM_ACTION_SUBCLASSDEL, $SubdivisionID, 0, 1);
                $nc_core = nc_core::get_object();

                $subdivision = $nc_core->subdivision->get_by_id($SubdivisionID);
                $changeset_name = NETCAT_VERSION_ACTION_SUBCLASS_PRIORITIES_UPDATED . " " .
                    sprintf(NETCAT_VERSION_OF_SUBDIVISION, $subdivision["Subdivision_Name"]);
                $nc_core->version->init_changeset($changeset_name);

                if (UpdateSubClassPriority() !== false) {
                    nc_print_status(CONTROL_CONTENT_SUBDIVISION_SUBCLASS_LIST_SUCCESS_EDIT, 'ok');
                } else {
                    nc_print_status(CONTROL_CONTENT_SUBDIVISION_SUBCLASS_LIST_ERROR_EDIT, 'error');
                }

                reset($_POST);

                foreach ($_POST AS $key => $val) {
                    if (strpos($key, 'Delete') === 0) {
                        if ($nc_core->sub_class->disallow_move_and_delete($val) === true) {
                            nc_print_status(sprintf(CONTROL_CONTENT_SUBDIVISION_DISALLOW_ERROR_DELETE_SUB_CLASS, " ".$nc_core->sub_class->get_by_id($val, 'Sub_Class_Name')), 'error');
                        } elseif ($nc_core->sub_class->delete($val) === false) {
                            nc_print_status(CONTROL_CONTENT_SUBDIVISION_SUBCLASS_ERROR_DELETE, 'error');
                        }
                    }
                }

                reset($_GET);

                foreach ($_GET AS $key => $val) {
                    if (strpos($key, 'Delete') === 0) {
                        if ($nc_core->sub_class->disallow_move_and_delete($val) === true) {
                            nc_print_status(sprintf(CONTROL_CONTENT_SUBDIVISION_DISALLOW_ERROR_DELETE_SUB_CLASS, " ".$nc_core->sub_class->get_by_id($val, 'Sub_Class_Name')), 'error');
                        } elseif ($nc_core->sub_class->delete($val) === false) {
                            nc_print_status(CONTROL_CONTENT_SUBDIVISION_SUBCLASS_ERROR_DELETE, 'error');
                        }
                    }
                }

                $update = "UPDATE `Subdivision` SET ";
                $update.= "`UseMultiSubClass`= '".$UseMultiSubClass."'";
                $update.= " WHERE `Subdivision_ID` = ".$SubdivisionID;
                $Result = $db->query($update);

                $nc_core->version->commit_changeset();

                if ($db->get_var("SELECT COUNT(Sub_Class_ID) FROM Sub_Class WHERE Subdivision_ID = '".$SubdivisionID."'")) {
                    $UI_CONFIG = new ui_config_subdivision_subclass($SubdivisionID, 'list');
                    ShowList();
                } else {
                    $UI_CONFIG = new ui_config_subdivision_subclass($SubdivisionID, 'add');
                    ActionForm(0, 2, 1);
                    $UI_CONFIG->treeChanges['updateNode'][] = array("nodeId" => "sub-$SubdivisionID",
                            "href" => "#subclass.add(".$SubdivisionID.")",
                            "align" => "left"
                    );
                }

                if (+$_REQUEST['isNaked']) {
                    ob_clean();
                    echo 'OK';
                    exit;
                }

                break;

//            case 6:
//
//                # покажем меню операций для шаблона в разделе
//                BeginHtml($Title5, $Title1.(($loc->SubdivisionID) ? $Delimeter.$Title3.$Delimeter.$Title5 : $Delimeter.$Title9.$Delimeter.$Title5), "http://".$DOC_DOMAIN."/management/sites/sections/class/info/");
//                $perm->ExitIfNotAccess(NC_PERM_ITEM_CC, NC_PERM_ACTION_INFO, array($SubdivisionID, $SubClassID), 0, 0);
//                ShowSubClassMenu($SubClassID, 9, "index.php", 3, "SubClass.php", 12, "index.php");
//
//                break;
        }
    } else {
        if (!$SubdivisionID) {
            BeginHtml($Title2_1, $Title1.$Delimeter.$Title2_1, "http://".$DOC_DOMAIN."/management/sites/sections/class/");
        } else {
            BeginHtml($Title2, $Title1.$Delimeter.$Title2, "http://".$DOC_DOMAIN."/management/sites/sections/class/");
        }

        $perm->ExitIfNotAccess(NC_PERM_ITEM_SUB, NC_PERM_ACTION_SUBCLASSLIST, $SubdivisionID, 0, 0);

        if (+$_REQUEST['isNaked']) {
            ob_clean();
            $SubClassID_list = ShowList_for_modal();
            if ($_REQUEST['sub_class_id']) {
                ob_clean();
                $sub_class_id = +$_REQUEST['sub_class_id'];
                foreach ($SubClassID_list as $SubClass) {
                    if ($SubClass['ID'] == $sub_class_id) {
                        $SubClassID_list = array(array('ID' => $SubClass['ID'], 'name' => $SubClass['name']));
                        break;
                    }
                }
            }

            ActionForm_for_modal_prefix($SubClassID_list, +$_REQUEST['sub_class_id']);
            foreach ($SubClassID_list as $SubClass) {
                ActionForm_for_modal($SubClass['ID']);
            }
            ActionForm_for_modal_suffix();
            exit;
        } else {
            $UI_CONFIG = new ui_config_subdivision_subclass($SubdivisionID, 'list', $SubClassID);
            ShowList();
        }
    }
} catch (nc_Exception_DB_Error $e) {
    nc_print_status(sprintf(NETCAT_ERROR_SQL, $e->query(), $e->error()), 'error');
} catch (Exception $e) {
    nc_print_status($e->getMessage(), 'error');
}


if (isset($SubdivisionID) && $SubdivisionID && isset($subdivisionTreeChange) && $subdivisionTreeChange && isset($UI_CONFIG)) {
    $subdivision = nc_Core::get_object()->subdivision->get_by_id($SubdivisionID);

    $buttons = array();

    $buttons[] = array(
        "label"  => CONTROL_CONTENT_CATALOUGE_FUNCS_SHOWMENU_A_VIEW,
        "action" => "window.open('" . nc_folder_url($SubdivisionID) . "');",
        'icon'   => 'arrow-right',
        'sprite' => true
    );

    $buttons[] = array(
        "label"  => CONTROL_CONTENT_SUBDIVISION_FUNCS_MAINDATA_A_ADDSUBSECTION,
        "action" => "parent.location.hash = 'subdivision.add(".$subdivision['Subdivision_ID'].")'",
        'icon'   => 'folder-add',
        'sprite' => true
    );

    $buttons[] = array(
        "label"  => CONTROL_CONTENT_CATALOUGE_FUNCS_SHOWMENU_A_KILL,
        "action" => "parent.location.hash = 'subdivision.delete(".$subdivision['Subdivision_ID'].")'",
        'icon'   => 'remove',
        'sprite' => true
    );

    $tree_image = "folder".($subdivision["Checked"] ? "" : " nc--disabled");

    $UI_CONFIG->treeChanges['addNode'][] = array("nodeId" => "sub-$subdivision[Subdivision_ID]",
        "parentNodeId"  => nc_array_value($subdivision, 'Parent_Sub_ID') ? "sub-$subdivision[Parent_Sub_ID]" : "site-$subdivision[Catalogue_ID]",
        "name"          => nc_array_value($subdivision, 'Subdivision_ID') . '. ' . strip_tags(nc_array_value($subdivision, 'Subdivision_Name')),
        "href"          => "#subdivision.edit($subdivision[Subdivision_ID])",
        "sprite"        => $tree_image,
        "dragEnabled"   => true,
        "buttons"       => $buttons,
        "acceptDropFn"  => "treeSitemapAcceptDrop",
        "onDropFn"      => "treeSitemapOnDrop",
        "className"     => nc_array_value($subdivision, "Checked") ? "" : "disabled",
        "checked"       => nc_array_value($subdivision, "Checked"),
        "hasChildren"   => nc_array_value($subdivision, 'hasChildren') ? true : false,
        "subclasses"    => array());
}

EndHtml ();
?>
