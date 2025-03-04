<?php

if (!class_exists("nc_System")) {
    die("Unable to load file.");
}

if (!isset($nc_core)) {
    $nc_core = nc_Core::get_object();
}

$action = $nc_core->action;

/**
 * @var int $fldCount
 * @var array $fld
 * @var array $fldValue
 * @var array $fldType
 * @var array $fldNotNull
 * @var array $fldFmt
 * @var array $fldTypeOfEdit
 * @var array $fldDefault
 * @var array $fldID
 * @var array $fldFS
 * @var array $fldDisposition
 * @var array $format_string
 * @var nc_core $nc_core
 * @var nc_db $db
 * @var int $classID
 * @var int $sub
 * @var int $cc
 * @var int $message
 * @var int $systemTableID
 * @var int $TemplateID
 * @var string $action
 * @var bool $user_table_mode
 * @var int $AUTH_USER_ID
 * @var array $current_user
 * @var Permission $perm
 */

$updateStrings = array();
$updateString = "";
$fieldString = "";
$valueString = "";

$multiple_changes = +$nc_core->input->fetch_post('multiple_changes');
$nc_multiple_changes = isset($nc_multiple_changes) ? (array)$nc_multiple_changes : array();
reset($nc_multiple_changes);

$updateStrings_tmp = array();
$partial = $nc_core->input->fetch_post_get('partial');

if (!isset($message)) {
    $message = null;
}
$msg_id = $message;

do {
    if ($multiple_changes) {
        $msg_id = key($nc_multiple_changes);
        $multiple_changes_fields = current($nc_multiple_changes);

        if ($msg_id === null && !$multiple_changes_fields) {
            break; // выход из цикла do() если были перебраны все записи в $nc_multiple_changes
        }

        if (!is_array($multiple_changes_fields)) {
            break;
        }

        next($nc_multiple_changes);

        foreach ($multiple_changes_fields as $multiple_changes_key => $multiple_changes_value) {
            $fldValue[array_search($multiple_changes_key, $fld)] = $multiple_changes_value;
        }

        foreach (array('Priority', 'Keyword') as $system_field) {
            if (isset($nc_multiple_changes[$msg_id][$system_field])) {
                $updateStrings_tmp[] = "`$system_field` = '" . $db->escape($nc_multiple_changes[$msg_id][$system_field]) . "'";
            }
        }
    }

    $KeywordDefined = $KeywordNewValue = null;
    for ($i = 0, $j = 0; $i < $fldCount; $i++) {
        $nc_field_value_is_not_set =
            !isset($_REQUEST["f_" . $fld[$i]]) &&
            !isset(${"f_" . $fld[$i]}) &&
            !isset($multiple_changes_fields[$fld[$i]]);

        if (!(
                ($fldType[$i] == NC_FIELDTYPE_BOOLEAN && $fldNotNull[$i]) ||
                 $fldType[$i] == NC_FIELDTYPE_RELATION ||
                 $fldType[$i] == NC_FIELDTYPE_DATETIME ||
                ($fldType[$i] == NC_FIELDTYPE_MULTISELECT && !$multiple_changes))
             && $nc_field_value_is_not_set
        ) {
            $fldValue[$i] = '""';
            continue;
        }

        if (
            $partial &&
            ($fldType[$i] == NC_FIELDTYPE_BOOLEAN || $fldType[$i] == NC_FIELDTYPE_MULTISELECT || $fldType[$i] == NC_FIELDTYPE_RELATION) &&
            $nc_field_value_is_not_set
        ) {
            $fldValue[$i] = '""';
            continue;
        }

        // set zero value for checkbox, if not checked - not in $_REQUEST
        if ($fldType[$i] == NC_FIELDTYPE_BOOLEAN && $fldNotNull[$i] && $nc_field_value_is_not_set) {
            $fldValue[$i] = 0;
            ${"f_" . $fld[$i]} = 0;
        }

        // для даты персонально
        if ($fldType[$i] == NC_FIELDTYPE_DATETIME) {
            $format = nc_field_parse_format($fldFmt[$i], NC_FIELDTYPE_DATETIME);
            // Если передано значение в f_ИмяПоля и не переданы поля _year, _hours, то берем значения из f_ИмяПоля
            if (!empty($_REQUEST['f_' . $fld[$i]]) && empty($_REQUEST['f_' . $fld[$i] . '_year']) && empty($_REQUEST['f_' . $fld[$i] . '_hours'])) {
                $tmp_timestamp = strtotime($_REQUEST['f_' . $fld[$i]]);
                if ($tmp_timestamp) {
                    foreach (array('Y' => 'year', 'm' => 'month', 'd' => 'day', 'H' => 'hours', 'i' => 'minutes', 's' => 'seconds') as $tmp_format => $tmp_key) {
                        $_REQUEST['f_' . $fld[$i] . '_' . $tmp_key] = date($tmp_format, $tmp_timestamp);
                    }
                }
                unset($tmp_timestamp, $tmp_key, $tmp_format);
            }
            switch ($format['type']) {
                case "event":
                    if (!(isset($_REQUEST["f_" . $fld[$i] . "_day"]) && isset($_REQUEST["f_" . $fld[$i] . "_month"]) && isset($_REQUEST["f_" . $fld[$i] . "_year"]) && isset($_REQUEST["f_" . $fld[$i] . "_hours"]) && isset($_REQUEST["f_" . $fld[$i] . "_minutes"]) && isset($_REQUEST["f_" . $fld[$i] . "_seconds"]))) {
                        continue 2;
                    }
                    break;
                case "event_date":
                    if (!(isset($_REQUEST["f_" . $fld[$i] . "_day"]) && isset($_REQUEST["f_" . $fld[$i] . "_month"]) && isset($_REQUEST["f_" . $fld[$i] . "_year"]))) {
                        continue 2;
                    }
                    break;
                case "event_time":
                    if (!(isset($_REQUEST["f_" . $fld[$i] . "_hours"]) && isset($_REQUEST["f_" . $fld[$i] . "_minutes"]) && isset($_REQUEST["f_" . $fld[$i] . "_seconds"]))) {
                        continue 2;
                    }
                    break;
                default: // В общем случае - меняем только если прислали хотя бы одно поле
                    if (!(isset($_REQUEST["f_" . $fld[$i] . "_day"]) || isset($_REQUEST["f_" . $fld[$i] . "_month"]) || isset($_REQUEST["f_" . $fld[$i] . "_year"]) || isset($_REQUEST["f_" . $fld[$i] . "_hours"]) || isset($_REQUEST["f_" . $fld[$i] . "_minutes"]) || isset($_REQUEST["f_" . $fld[$i] . "_seconds"]))) {
                        continue 2;
                    }
                    break;
            }
        }

        if ($fldType[$i] == NC_FIELDTYPE_STRING || $fldType[$i] == NC_FIELDTYPE_TEXT || $fldType[$i] == NC_FIELDTYPE_DATETIME || $fldType[$i] == NC_FIELDTYPE_MULTISELECT) {
            if (NC_FIELDTYPE_TEXT == $fldType[$i]) {
                $format = nc_field_parse_format($fldFmt[$i], NC_FIELDTYPE_TEXT);
            }

            if (NC_FIELDTYPE_STRING == $fldType[$i]) {
                // транслитерация: только если пользователь сам не ввёл значение поля, чтобы позволить ему вводить свои собственные
                if ($format_string[$i]['use_transliteration'] == 1) {
                    $nc_transliteration_target_field_value = $nc_core->input->fetch_post_get('f_' . $format_string[$i]['transliteration_field']);
                    if ((!$partial && $nc_transliteration_target_field_value === null) || $nc_transliteration_target_field_value === "") {
                        $fieldValue = nc_transliterate($fldValue[$i], ($format_string[$i]['use_url_rules'] == 1 ? true : false));
                        if ($format_string[$i]['transliteration_field'] == 'Keyword') {
                            $fieldValue = nc_check_keyword_name($message, $fieldValue, $classID, $nc_core->sub_class->get_by_id($cc, 'Subdivision_ID'));
                        }
                        $updateString .= "`" . $format_string[$i]['transliteration_field'] . "` = \"" . $fieldValue . "\", ";
                        ${$format_string[$i]['transliteration_field'] . 'Defined'} = true;
                        ${$format_string[$i]['transliteration_field'] . 'NewValue'} = "\"" . $fieldValue . "\"";
                    }
                    unset($nc_transliteration_target_field_value);
                }
            }
            $fldValue[$i] = str_replace("\\'", "'", addslashes($fldValue[$i]));
            if ($fldType[$i] == NC_FIELDTYPE_DATETIME && empty($fldValue[$i])) {
                $fldValue[$i] = "NULL";
            } else {
                $fldValue[$i] = "\"" . $fldValue[$i] . "\"";
            }
        }

        if ($fldValue[$i] == "" && ($fldType[$i] == NC_FIELDTYPE_INT || $fldType[$i] == NC_FIELDTYPE_FLOAT || $fldType[$i] == NC_FIELDTYPE_SELECT || $fldType[$i] == NC_FIELDTYPE_RELATION)) {

            if ($fldNotNull[$i]) {
                if ($fldTypeOfEdit[$i] == NC_FIELD_PERMISSION_EVERYONE) {
                    $fldValue[$i] = "NULL";
                }
                if ($fldTypeOfEdit[$i] > NC_FIELD_PERMISSION_EVERYONE && $fldDefault[$i] != "") {
                    $fldValue[$i] = "\"\"";
                }
            } else {
                if ($fldTypeOfEdit[$i] > NC_FIELD_PERMISSION_EVERYONE && $fldDefault[$i] != "") {
                    $fldValue[$i] = "\"\"";
                } // int
                elseif ($fldType[$i] == NC_FIELDTYPE_INT && $fldDefault[$i] != "" && $fldDefault[$i] == strval(intval($fldDefault[$i]))) {
                    $fldValue[$i] = "\"" . $fldDefault[$i] . "\"";
                } // float
                elseif ($fldType[$i] == NC_FIELDTYPE_FLOAT && $fldDefault[$i] != "" && $fldDefault[$i] == strval(str_replace(",", ".", floatval($fldDefault[$i])))) {
                    $fldValue[$i] = "\"" . $fldDefault[$i] . "\"";
                } // list
                elseif ($fldType[$i] == NC_FIELDTYPE_SELECT && $fldValue[$i] !== false) {
                    $fldValue[$i] = 0;
                } else {
                    $fldValue[$i] = "NULL";
                }
            }
        }

        if (NC_FIELDTYPE_MULTIFILE == $fldType[$i]) {
            // файлы и информация о них сохраняются в add.php или message.php
            $fldValue[$i] = '""';
        }


        if ($fldType[$i] == NC_FIELDTYPE_FILE) {
            $fldValue[$i] = null;
            $fldFileArray = null;
            $tmpFileHttpPath = isset($_POST["f_" . $fld[$i] . "_tmp"]) ? $_POST["f_" . $fld[$i] . "_tmp"] : null;
            if (!empty($_FILES["f_" . $fld[$i]]) && $_FILES["f_" . $fld[$i]]['error'] != UPLOAD_ERR_NO_FILE) {
                $fldFileArray = $_FILES["f_" . $fld[$i]];
                $fldValue[$i] = $fldFileArray["tmp_name"];
            }
            // Загружать временные файлы можно только с TMP-папки!
            if ($tmpFileHttpPath) {
                $tmpFileAbsolutePath = realpath($nc_core->DOCUMENT_ROOT . $tmpFileHttpPath);
                if (strpos($tmpFileAbsolutePath, realpath($nc_core->TMP_FOLDER)) !== 0) {
                    $tmpFileHttpPath = null;
                }
            }

            if (($fldValue[$i] && $fldValue[$i] != "none" && is_uploaded_file($fldValue[$i])) || $tmpFileHttpPath) {
                if ($user_table_mode && $action != "add" && !$message) {
                    $message = $AUTH_USER_ID;
                }
                if ($systemTableID == 4) {
                    $message = $TemplateID;
                }

                //перехват альтернативной папки из условий добавления/изменения
                $fldFileArray['folder'] = nc_array_value(${"f_" . $fld[$i]}, 'folder');

                $file_info = $nc_core->files->field_save_file(
                    $systemTableID ? $nc_core->get_system_table_name_by_id($systemTableID) : $classID,
                    $fldID[$i],
                    $message,
                    $tmpFileHttpPath ?: $fldFileArray,
                    false,
                    $action === 'add' ? array('sub' => $cc ? $nc_core->sub_class->get_by_id($cc, 'Subdivision_ID') : $sub, 'cc' => $cc) : false,
                    false,
                    false
                );

                //строка для записи в БД
                $fldValue[$i]           = $file_info['fldValue'];

                // save file path in the $f_Field_url
                ${"f_" . $fld[$i] . "_url"}         = $file_info['url'];
                ${"f_" . $fld[$i] . "_preview_url"} = $file_info['preview_url'];
                ${"f_" . $fld[$i] . "_name"}        = $file_info['name'];
                ${"f_" . $fld[$i] . "_size"}        = $file_info['size'];
                ${"f_" . $fld[$i] . "_type"}        = $file_info['type'];

                $j++;
            } elseif (($fldValue[$i] == '' || $fldValue[$i] == 'none') && empty(${'f_KILL' . $fldID[$i]})) {
                $fldValue[$i] = $fld[$i];
                continue;
            }

            $fldValue[$i] = "\"" . $db->escape($fldValue[$i]) . "\"";

            if ($tmpFileHttpPath) {
                unlink($nc_core->DOCUMENT_ROOT . $tmpFileHttpPath);
            }

        }

        $user_has_permission_to_this_action = (
            $fldTypeOfEdit[$i] == NC_FIELD_PERMISSION_EVERYONE ||
            ($fldTypeOfEdit[$i] == NC_FIELD_PERMISSION_ADMIN && nc_field_check_admin_perm())
        );

        if ($user_has_permission_to_this_action && empty(${$fld[$i] . "Defined"})) {
            $fieldString .= "`" . $fld[$i] . "`,";
            $valueString .= $fldValue[$i] . ",";
            if (
                $action == "change" &&
                !(
                    $user_table_mode &&
                    $fld[$i] == $nc_core->AUTHORIZE_BY &&
                    !(
                        $nc_core->get_settings('allow_change_login', 'auth') ||
                        (isset($current_user) && in_array($current_user['UserType'], array('fb', 'vk', 'twitter', 'openid')))
                    )
                )
            ) {
                $updateString .= "`" . $fld[$i] . "` = " . $fldValue[$i] . ", ";
            }
        }

        if ($multiple_changes) {
            $updateStrings_tmp[] = "`{$fld[$i]}` = {$fldValue[$i]}";
        }
    }

    $updateStrings[$msg_id] = join(', ', $updateStrings_tmp);
    $updateStrings_tmp = array();

} while ($multiple_changes);

if (!$user_table_mode && !empty($cc) && $perm instanceof Permission && $perm->isSubClass($cc, MASK_MODERATE) && !$nc_multiple_changes) {
    $nc_fields_seo = array('ncTitle', 'ncKeywords', 'ncDescription', 'ncSMO_Title', 'ncSMO_Description');
    foreach ($nc_fields_seo as $nc_field) {
        if (!$nc_multiple_changes && isset($_REQUEST["f_$nc_field"])) {
            $nc_field_value = $db->escape(${"f_$nc_field"});
            $updateString .= "`$nc_field` = '$nc_field_value', ";
            $fieldString .= "`$nc_field`,";
            $valueString .= "'$nc_field_value',";
        }
    }
}
