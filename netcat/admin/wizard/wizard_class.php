<?php

/* $Id: wizard_class.php 7667 2012-07-16 10:14:21Z alive $ */

$NETCAT_FOLDER = join(strstr(__FILE__, "/") ? "/" : "\\", array_slice(preg_split("/[\/\\\]+/", __FILE__), 0, -4)).( strstr(__FILE__, "/") ? "/" : "\\" );

include_once ($NETCAT_FOLDER."vars.inc.php");
require ($ADMIN_FOLDER."function.inc.php");
require ($ADMIN_FOLDER."class/function.inc.php");
require ($ADMIN_FOLDER."subdivision/function.inc.php");
require ($ADMIN_FOLDER."subdivision/subclass.inc.php");
require ($ADMIN_FOLDER."class/Message.inc.php");
require ($ADMIN_FOLDER."field/function.inc.php");
require ($ADMIN_FOLDER."wizard/wizard.inc.php");

$Title1 = SECTION_INDEX_WIZARD_SUBMENU_CLASS;
$Title2 = '';



InitVars();

if (!isset($phase)) $phase = 1;

if (in_array($phase, array(2))) {
    if (!$nc_core->token->verify()) {
        BeginHtml($Title1, $Title2, "http://".$DOC_DOMAIN."/management/class/wizard/");
        nc_print_status(NETCAT_TOKEN_INVALID, 'error');
        EndHtml();
        exit;
    }
}

switch ($phase) {

    // Вводим название и выбираем тип шаблона
    case 1:
        BeginHtml($Title1, $Title2, "http://".$DOC_DOMAIN."/management/class/wizard/");
        $perm->ExitIfNotAccess(NC_PERM_CLASS, NC_PERM_ACTION_WIZARDCLASS, 0, 0, 1);
        $UI_CONFIG = new ui_config_wizard_class(1, 0, 0);
        nc_class_wizard_start('', 'Базовые');
        EndHtml();
        break;

    // Проверка данных из первой формы а
    case 2:
        BeginHtml($Title1, $Title2, "http://".$DOC_DOMAIN."/management/class/wizard/");
        $perm->ExitIfNotAccess(NC_PERM_CLASS, NC_PERM_ACTION_WIZARDCLASS, 0, 0, 1);
        if (!$Class_Name) {
            nc_print_status(CONTROL_CONTENT_CLASS_ERROR_NAME, 'error');
            $UI_CONFIG = new ui_config_wizard_class(1, 0, 0);
            nc_class_wizard_start('', 'Базовые');
            EndHtml();
            break;
        }
        $ClassID = ActionClassComleted(1);
    //здесь break не нужен, если все правильно - сразу переходим к добавлению полей
    case 3: #Форма добавления поля + сообственно добавление
        BeginHtml($Title1, $Title2, "http://".$DOC_DOMAIN."/management/class/wizard/");
        $perm->ExitIfNotAccess(NC_PERM_CLASS, NC_PERM_ACTION_WIZARDCLASS, 0, 0, 1);
        $UI_CONFIG = new ui_config_wizard_class(2, $Class_Type, $ClassID);

        if ($addField) {
            if (($new_id = FieldCompleted(0)) <= 0) {
                nc_print_status($type_of_error[-$new_id], 'error');
            } else {
                nc_print_status(CONTROL_FIELD_MSG_ADDED, 'ok');

                $UI_CONFIG->treeChanges['deleteNode'][] = "dataclass-".$ClassID;
                $UI_CONFIG->treeChanges['addNode'][] = array(
                        "parentNodeId" => "group-".md5($Class_Group),
                        "nodeId" => "dataclass-$ClassID",
                        "name" => $ClassID.". ".$ClassName,
                        "href" => "#dataclass.edit(".$ClassID.")",
                        "image" => 'i_class.gif',
                        "buttons" => array("image" => "i_class_delete.gif",
                                "label" => CONTROL_CLASS_DELETE,
                                "href" => "dataclass.delete(".$ClassID.")"),
                        "acceptDropFn" => "treeClassAcceptDrop",
                        "onDropFn" => "treeClassOnDrop",
                        "hasChildren" => true,
                        "dragEnabled" => true);
                $UI_CONFIG->treeChanges['addNode'][] = array(
                        "parentNodeId" => "dataclass-".$ClassID,
                        "nodeId" => "field-$new_id",
                        "name" => $new_id.". ".$FieldName,
                        "href" => "#field.edit($new_id)",
                        "image" => $field_types[$TypeOfDataID],
                        "buttons" => array("image" => "i_field_delete.gif",
                                "label" => CONTROL_FIELD_LIST_DELETE,
                                "href" => "field.delete(".$ClassID.",".$new_id.")"),
                        "acceptDropFn" => "treeFieldAcceptDrop",
                        "onDropFn" => "treeFieldOnDrop",
                        "hasChildren" => false,
                        "dragEnabled" => true);
            }

            FieldList($ClassID, 0, 1);
        }

        $Additional = "<input type='hidden' name='addField' value='1'>\n";
        $Additional .= "<input type='hidden' name='Class_Type' value='".$Class_Type."'>\n";
        // чтобы очистить значения полей при добавлении нового
        unset($_POST);
        FieldForm(0, $ClassID, 0, "wizard_class.php", 'Field', 'Field', $Additional);

        nc_class_wizard_fields_end($ClassID, $Class_Type);

        EndHtml();
        break;

    // Настройки шаблона
    case 4:
        BeginHtml($Title1, $Title2, "http://".$DOC_DOMAIN."/management/class/wizard/");
        $perm->ExitIfNotAccess(NC_PERM_CLASS, NC_PERM_ACTION_WIZARDCLASS, 0, 0, 1);
        $UI_CONFIG = new ui_config_wizard_class(3, $Class_Type, $ClassID);
        $field_count = $db->get_var("SELECT COUNT(Field_ID) FROM Field WHERE Class_ID = '".$ClassID."'");
        if (!$field_count) {
            nc_print_status(WIZARD_CLASS_ERROR_NO_FIELDS, 'error');
            echo "<a href='".$ADMIN_PATH."wizard/wizard_class.php?phase=3&mp;Class_Type=".$Class_Type."&amp;ClassID=".$ClassID."'>".WIZARD_CLASS_LINKS_RETURN_TO_FIELDS_ADDING."</a>";
        } else {
            nc_class_wizard_settings($ClassID, $Class_Type, $ClassName, $Class_Group);
        }
        EndHtml();
        break;

    case 5: //собственно добавление настроек шаблона
        BeginHtml($Title1, $Title2, "http://".$DOC_DOMAIN."/management/class/wizard/");
        $perm->ExitIfNotAccess(NC_PERM_CLASS, NC_PERM_ACTION_WIZARDCLASS, 0, 0, 1);
        $form_prefix = "\$f_AdminCommon\n";
        $record_template = "\$f_AdminButtons\n";
        $form_suffix = '';
        $records_per_page = '20';
        $sorting = '';
        $sort_direction = '';
        $record_template_full = '';

        $add_action_template = '';

        /* генерация шаблона */
        switch ($Class_Type) {
            // Единственный объект на странице
            case 1:
                $objects = $db->get_results("SELECT Field_ID, Field_Name, Description, TypeOfData_ID, Format FROM Field WHERE Class_ID = '".$ClassID."'", ARRAY_A);
                foreach ((array) $objects as $object)
                    $record_template .= nc_class_wizard_field_template($object)."<br>\n";
                $record_template .= "<br>\n";


                break;

            // Веб-форма
            case 4:
                $sender_name = $SettingsSenderName ? $SettingsSenderName : "\$system_env[SpamFromName]";
                $sender_email = $SettingsSenderEmail ? $SettingsSenderEmail : "\$system_env[SpamFromEmail]";
                $notification_email = $SettingsSenderEmail ? "\$system_env[NotificationEmail]" : $sender_email;
                $SettingsMailText = "\"";

                if ($SettingsFormFields) {
                    $fields_string = join(', ', $SettingsFormFields);
                    $objects = $db->get_results("SELECT `Field_ID`, `Field_Name`, `Description` FROM `Field` WHERE `Field_ID` IN (".$fields_string.")", ARRAY_A);
                    foreach ($objects as $object) {
                        // Ставить ссылку или нет
                        $record_template .= $object['Description'].": \$f_".$object['Field_Name']."\n";
                        $SettingsMailText .= "<b>".$object['Description'].":</b> \$f_".$object['Field_Name']."<br>";
                    }
                }
                $SettingsMailText .= "\"";

                $add_action_template .= "
          \";
          \$SettingsMailText = $SettingsMailText;
          \$headers = \"Return-Path: <$sender_email>\" . \"\\r\\n\";
          \$headers .= \"From: \\\"$sender_name\\\" <$sender_email>\" . \"\\r\\n\";
          \$headers .= \"Reply-To: $sender_name\" . \"\\r\\n\";
          \$headers .= \"Organization: \$system_env[ProjectName]\" . \"\\r\\n\";
          \$headers .= \"MIME-Version: 1.0\" . \"\\r\\n\";
          \$headers .= \"Content-Type: text/html; charset=\".MAIN_EMAIL_ENCODING.\"\" . \"\\r\\n\";
          \$headers .= \"X-Mailer: $system_env[Powered]\" . \"\\r\\n\";

        mail('$notification_email','$SettingsMailSubject',$SettingsMailText,\$headers);
        echo \"
        ";



                break;

            case 3:
                $records_per_page = $SettingsRecNum;

                $sorting = "Priority";
                if ($SettingsSort)
                        $sorting = ($SettingsSortDirection == 2) ? $SettingsSort." DESC" : $SettingsSort;

                if ($SettingsObjectListDelimiter == 1)
                        $record_template = "\".opt(\$f_RowNum, \"<hr size='1'>\").\"\n".$record_template;
                else if ($SettingsObjectListDelimiter == 2)
                        $list_delimiter = "<br>";

                if ($SettingsNavigation == 1)
                        $navigation = "\".opt(\$prevLink || \$nextLink,\"<div style='text-align: center;'>\").\"\".opt(\$prevLink,\"<a href='\$prevLink'>Назад</a>\").\" \".opt(\$nextLink,\"<a href='\$nextLink'>Вперед</a>\").\"\".opt(\$prevLink || \$nextLink,\"</div>\").\"<br>\n";
                else if ($SettingsNavigation == 2)
                        $navigation = "\".opt(\$prevLink || \$nextLink,\"Страницы: \".browse_messages(\$cc_env,10).\"<br>\").\"<br>\n";
                else if ($SettingsNavigation == 3)
                        $navigation = "\".opt(\$prevLink || \$nextLink,\"<div style='text-align: center;'>\".opt(\$prevLink,\"<a href='\$prevLink'>Назад</a>\").\" Страницы: \".browse_messages(\$cc_env,10).\"\".opt(\$nextLink,\" <a href='\$nextLink'>Вперед</a>\").\"</div>\").\"<br>\n";

                if ($SettingsNavigationPosition == 1)
                        $form_prefix .= $navigation;
                else if ($SettingsNavigationPosition == 2)
                        $form_suffix .= "<br>".$navigation;
                else if ($SettingsNavigationPosition == 3) {
                    $form_prefix .= $navigation;
                    $form_suffix .= "<br>".$navigation;
                }

                $fields_string = join(', ', $SettingsSearchFields);
                $db->query("UPDATE Field SET DoSearch = '1' WHERE Field_ID IN (".$fields_string.")");

            //Здесь break не нужен


            case 2:  // Список объектов
                if ($SettingsObjectListType == 1) {
                    // Есть элементы для отображения их в списке
                    if ($SettingsObjectList) {
                        $fields_string = join(', ', $SettingsObjectList);
                        $objects = $db->get_results("SELECT Field_ID, Field_Name, Description, TypeOfData_ID, Format FROM Field WHERE Field_ID IN (".$fields_string.")", ARRAY_A);
                        foreach ($objects as $object) {
                            // Ставить ссылку или нет
                            if ($SettingsIsObjectFull) {
                                if (in_array($object['Field_ID'], $SettingsObjectFullLink))
                                        $record_template .= nc_class_wizard_field_template($object, true, true)."<br>\n";
                                else
                                        $record_template .= nc_class_wizard_field_template($object)."<br>\n";
                            } else {
                                $record_template .= nc_class_wizard_field_template($object)."<br>\n";
                            }
                        }
                        $record_template .= $list_delimiter."\n";

                        if ($SettingsObjectFull) {
                            $fields_string = join(', ', $SettingsObjectFull);
                            $objects = $db->get_results("SELECT Field_ID, Field_Name, Description, TypeOfData_ID, Format FROM Field WHERE Field_ID IN (".$fields_string.")", ARRAY_A);
                            foreach ($objects as $object) {
                                $record_template_full .= nc_class_wizard_field_template($object)."<br>\n";
                            }
                        }
                    }
                }

                // Таблица с объектами
                if ($SettingsObjectListType == 2) {
                    // Есть элементы для отображения их в списке
                    if ($SettingsObjectList) {
                        if ($SettingsObjectTableBorder) {
                            $form_prefix .= "<table border='0' cellpadding='5' cellspacing='1' width='100%' style='background: #999'>\n";
                        } else {
                            $form_prefix .= "<table border='0' cellpadding='5' cellspacing='0' width='100%'>\n";
                        }
                        if ($form_suffix) {
                            $form_suffix = "</table>\n".$form_suffix;
                        } else {
                            $form_suffix .= "</table>\n";
                        }

                        $fields_string = join(', ', $SettingsObjectList);
                        $objects = $db->get_results("SELECT Field_ID, Field_Name, Description, TypeOfData_ID, Format FROM Field WHERE Field_ID IN (".$fields_string.")", ARRAY_A);
                        $form_prefix .= "\t<tr style='font-weight:bold".($SettingsObjectTableBorder ? "; background:#FFF" : "")."'>\n";
                        $i = 0;
                        foreach ($objects as $object) {
                            $form_prefix .= "\t\t<td>".$object['Description']."</td>\n";
                            $i++;
                        }
                        $form_prefix .= "\t</tr>";

                        $record_template = "\t\".opt(\$f_AdminButtons, \"<tr".($SettingsObjectTableBorder ? " style='background: #FFF'" : "")."><td".($i > 1 ? " colspan='".$i."'" : "").">\$f_AdminButtons</td></tr>\").\"\n";

                        if ($SettingsObjectTableBackground) {
                            $record_template .= "\t<tr style='background: #\".opt_case(\$f_RowNum % 2,\"FFF\",\"EEE\").\";'>\n";
                        } else {
                            $record_template .= "\t<tr".($SettingsObjectTableBorder ? " style='background: #FFF'" : "").">\n";
                        }
                        foreach ($objects as $object) {
                            // Ставить ссылку или нет
                            if ($SettingsIsObjectFull) {
                                if (in_array($object['Field_ID'], $SettingsObjectFullLink)) {
                                    $record_template .= "\t\t<td>".nc_class_wizard_field_template($object, false, true)."</td>\n";
                                } else {
                                    $record_template .= "\t\t<td>".nc_class_wizard_field_template($object, false)."</td>\n";
                                }
                            } else {
                                $record_template .= "\t\t<td>".nc_class_wizard_field_template($object, false)."</td>\n";
                            }
                        }
                        $record_template .= "\t</tr>\n";

                        if ($SettingsObjectFull) {
                            $fields_string = join(', ', $SettingsObjectFull);
                            $objects = $db->get_results("SELECT Field_ID, Field_Name, Description, TypeOfData_ID, Format FROM Field WHERE Field_ID IN (".$fields_string.")", ARRAY_A);
                            $record_template_full .= "<table border='0' cellpadding='5' cellspacing='0' width='100%'>\n";
                            foreach ($objects as $object) {
                                // Ставить ссылку или нет
                                $record_template_full .= "\t<tr><td>".nc_class_wizard_field_template($object)."</td></tr>\n";
                            }
                            $record_template_full .= "</table>";
                        }
                    }
                }


                break;
        }
        /* генерация шаблона */
        $db->query("UPDATE `Class` SET `FormPrefix`='".$db->escape($form_prefix)."',
                                     `FormSuffix`='".$db->escape($form_suffix)."',
                                     `RecordTemplate`='".$db->escape($record_template)."',
                                     `RecordTemplateFull`='".$db->escape($record_template_full)."',
                                     `RecordsPerPage`='".$db->escape($records_per_page)."',
                                     `SortBy`='".$db->escape($sorting)."',
                                     `AddActionTemplate`='".$db->escape($add_action_template)."'
                   WHERE `Class_ID` = '".$ClassID."'");

        $UI_CONFIG = new ui_config_wizard_class(4, $Class_Type, $ClassID);
        nc_print_status(CONTROL_CONTENT_SUBDIVISION_SUBCLASS_SUCCESS_ADD, 'ok');
        nc_class_wizard_select_action($ClassID, $Class_Type);

        EndHtml();

        break;

    // Выбор сайта и родительского раздела для создания подраздела с созданным шаблоном
    case 6:
        BeginHtml($Title1, $Title2, "http://".$DOC_DOMAIN."/management/class/wizard/");
        $perm->ExitIfNotAccess(NC_PERM_CLASS, NC_PERM_ACTION_WIZARDCLASS, 0, 0, 1);
        $UI_CONFIG = new ui_config_wizard_class(6, $Class_Type, $ClassID);
        nc_class_wizard_subdivision_form($ClassID);
        EndHtml();
        break;

    // Создание раздела с новым шаблоном
    case 7:
        BeginHtml($Title1, $Title2, "http://".$DOC_DOMAIN."/management/class/wizard/");
        $perm->ExitIfNotAccess(NC_PERM_CLASS, NC_PERM_ACTION_WIZARDCLASS, 0, 0, 1);
        $UI_CONFIG = new ui_config_wizard_class(7, $Class_Type, $ClassID);

        $loc = new SubdivisionLocation($CatalogueID, $SubdivisionID);

        // добавление раздела
        if ($posting == 1) {

            // проверка названия раздела
            if (!$Subdivision_Name) {
                nc_print_status(CONTROL_CONTENT_SUBDIVISION_INDEX_ERROR_THREE_NAME, 'error');
                nc_class_wizard_subdivision_form($ClassID);
                break;
            }

            // проверка уникальности ключевого слова для текущего раздела
            if (!IsAllowedSubdivisionEnglishName($EnglishName, $loc->ParentSubID, 0, $loc->CatalogueID)) {
                nc_print_status(CONTROL_CONTENT_SUBDIVISION_INDEX_ERROR_THREE_KEYWORD, 'error');
                nc_class_wizard_subdivision_form($ClassID);
                break;
            }

            // проверка символов для ключевого слова
            if (!$nc_core->subdivision->validate_english_name($EnglishName)) {
                nc_print_status(CONTROL_CONTENT_SUBDIVISION_SUBCLASS_ERROR_KEYWORD_INVALID, 'error');
                nc_class_wizard_subdivision_form($ClassID);
                break;
            }

            if (!$ParentSubID && !$CatalogueID) {
                nc_print_status(CONTROL_CONTENT_SUBDIVISION_INDEX_ERROR_THREE_PARENTSUB, 'error');
                nc_class_wizard_subdivision_form($ClassID);
                break;
            }

            // если раздел добавлен переходим к добавлению шаблонов
            if (($SubdivisionID = ActionSubdivisionCompleted($type))) {

                if ($Class_Type == 1 || $Class_Type == 2)
                        $default_action = 'index';
                if ($Class_Type == 3) $default_action = 'search';
                if ($Class_Type == 4) $default_action = 'add';

                $db->query("INSERT INTO `Sub_Class` (`Subdivision_ID`, `Class_ID`, `Sub_Class_Name`, `Priority`, `Read_Access_ID`, `Write_Access_ID`, `EnglishName`, `Checked`, `Catalogue_ID`, `Edit_Access_ID`, `Subscribe_Access_ID`, `Moderation_ID`, `DaysToHold`, `AllowTags`, `RecordsPerPage`, `SortBy`, `Created`, `DefaultAction`, `NL2BR`, `UseCaptcha`, `CustomSettings`) VALUES (".$SubdivisionID.",".$ClassID.",'".$Subdivision_Name."',0,0,0,'".$EnglishName."',1,".$CatalogueID.",0,0,0,NULL,-1,NULL,'','".date("Y-m-d H:i:s")."','".$default_action."',-1,-1,NULL)");
                ob_end_clean();
                // дерево будет обновлено со страницы, куда ведет редирект
                // (добавление шаблона), поскольку будет запрошен
                // несуществующий узел в дереве sub-$SubdivisionID
                header("Location: ".$SUB_FOLDER.$HTTP_ROOT_PATH."?inside_admin=1&cc=".$db->insert_id);
                exit();
            } else {
                nc_print_status('Error', 'error');
            }
        }

        break;

    // Выбор сайта и родительского раздела для добавления к разделу созданного шаблона
    case 8:
        BeginHtml($Title1, $Title2, "http://".$DOC_DOMAIN."/management/class/wizard/");
        $perm->ExitIfNotAccess(NC_PERM_CLASS, NC_PERM_ACTION_WIZARDCLASS, 0, 0, 1);
        $UI_CONFIG = new ui_config_wizard_class(8, $Class_Type, $ClassID);
        nc_class_wizard_class_form($ClassID, $Class_Type);

        EndHtml();
        break;

    case 9:
        BeginHtml($Title1, $Title2, "http://".$DOC_DOMAIN."/management/class/wizard/");
        $perm->ExitIfNotAccess(NC_PERM_CLASS, NC_PERM_ACTION_WIZARDCLASS, 0, 0, 1);
        $loc = new SubdivisionLocation($CatalogueID, $ParentSubID, $SubdivisionID);
        $UI_CONFIG = new ui_config_wizard_class(9, $Class_Type, $ClassID);

        if ($SubClassName == "") {
            nc_print_status(CONTROL_CONTENT_SUBDIVISION_SUBCLASS_ERROR_NAME, 'error');
            nc_class_wizard_class_form($ClassID, $Class_Type);
            break;
        }
        if (nc_preg_match("/^[0-9]+$/", $EnglishName)) {
            nc_print_status(CONTROL_CONTENT_SUBDIVISION_SUBCLASS_ERROR_KEYWORD_INVALID, 'error');
            nc_class_wizard_class_form($ClassID, $Class_Type);
            break;
        }
        if (!$nc_core->sub_class->validate_english_name($EnglishName)) {
            nc_print_status(CONTROL_CONTENT_SUBDIVISION_SUBCLASS_ERROR_KEYWORD, 'error');
            nc_class_wizard_class_form($ClassID, $Class_Type);
            break;
        }

        if (!$SubdivisionID) {
            nc_print_status(CONTROL_CONTENT_SUBDIVISION_INDEX_ERROR_THREE_PARENTSUB, 'error');
            nc_class_wizard_class_form($ClassID, $Class_Type);
            break;
        }

        if (($SubClassID = ActionSubClassCompleted(1))) {
            ob_end_clean();
            // дерево будет обновлено со страницы, куда ведет редирект
            // (добавление шаблона), поскольку будет запрошен
            // несуществующий узел в дереве sub-$SubdivisionID
            header("Location: ".$SUB_FOLDER.$HTTP_ROOT_PATH."?inside_admin=1&cc=".$SubClassID);
            exit();
        }
        break;
}
?>