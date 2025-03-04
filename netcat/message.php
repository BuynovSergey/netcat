<?php

$action = "change";

$NETCAT_FOLDER = join(strstr(__FILE__, "/") ? "/" : "\\", array_slice(preg_split("/[\/\\\]+/", __FILE__), 0, -2)) . (strstr(__FILE__, "/") ? "/" : "\\");
@include_once($NETCAT_FOLDER . "vars.inc.php");
require_once $ROOT_FOLDER . 'connect_io.php';

$nc_core = nc_Core::get_object();
$nc_core->action = 'change';

require($INCLUDE_FOLDER . "index.php");
require_once($ADMIN_FOLDER . "admin.inc.php");

/** @var nc_core $nc_core */

$curPos = isset($curPos) ? (int)$curPos : 0;

//загрузка окружения заданного в require/index.php
$cc_env = isset($current_cc) ? $current_cc : null;
$multiple_changes = isset($multiple_changes) && $multiple_changes;

if (!isset($admin_mode)) {
    $admin_mode = null;
}

if (!isset($user_table_mode)) {
    $user_table_mode = null;
}

if (!isset($sys_table_id)) {
    $sys_table_id = null;
}

if (!isset($f_Checked)) {
    $f_Checked = null;
}

if (!isset($f_Priority)) {
    $f_Priority = null;
}

if (!isset($f_Keyword)) {
    $f_Keyword = null;
}

if (!isset($f_ncTitle)) {
    $f_ncTitle = null;
}

if (!isset($f_ncKeywords)) {
    $f_ncKeywords = null;
}

if (!isset($f_ncDescription)) {
    $f_ncDescription = null;
}

if (!isset($f_ncSMO_Title)) {
    $f_ncSMO_Title = null;
}

if (!isset($f_ncSMO_Description)) {
    $f_ncSMO_Description = null;
}

if (!isset($f_ncSMO_Image)) {
    $f_ncSMO_Image = null;
}

if (!isset($f_ncDemoContent)) {
    $f_ncDemoContent = null;
}

if (!isset($systemTableID)) {
    $systemTableID = null;
}

// $cc not found
if (empty($cc_env) && $nc_core->inside_admin) {
    require_once($ADMIN_FOLDER . "function.inc.php");
    require_once($ADMIN_FOLDER . "catalogue/function.inc.php");
    // make UI
    $UI_CONFIG = new ui_config_catalogue('map', $catalogue);
    // show admin template
    BeginHtml();
    nc_print_status(CONTROL_CONTENT_CATALOUGE_FUNCS_ERROR_NOSUBCLASS, 'error');
    EndHtml();
    // halt
    exit;
}

$Class_Template_ID = null;
if (!empty($nc_ctpl) && $cc_env['Class_ID'] == $nc_core->component->get_component_template_by_keyword($cc_env['Class_ID'], $nc_ctpl, 'ClassTemplate')) {
    $Class_Template_ID = $nc_ctpl;
}
else if ($multiple_changes) {
    $Class_Template_ID = $nc_core->sub_class->get_by_id($cc, 'Real_Class_ID', 0, 0, 'multi_edit');
}
else {
    if ($admin_mode) {
        $Class_Template_ID = $nc_core->sub_class->get_by_id($cc, 'Edit_Class_Template') ?:
                             $nc_core->sub_class->get_by_id($cc, 'Real_Class_ID', 0, 0, 'admin_mode');
    }
    if ($inside_admin) {
        $Class_Template_ID = $nc_core->sub_class->get_by_id($cc, 'Admin_Class_Template') ?:
                             $nc_core->sub_class->get_by_id($cc, 'Real_Class_ID', 0, 0, 'inside_admin');
    }
}

if (!$Class_Template_ID) {
    $Class_Template_ID = $nc_core->sub_class->get_by_id($cc, 'Class_Template_ID');
}

if ($Class_Template_ID != $classID) {
    $cc_env = $nc_core->sub_class->get_by_id($cc, null, $Class_Template_ID);
}

if (!isset($cc_env['File_Mode'])) {
    if (is_array($cc_env)) {
        $cc_env = array_merge($cc_env, nc_get_file_mode_and_file_path($Class_Template_ID ? $Class_Template_ID : $classID));
    }
    else {
        $cc_env = nc_get_file_mode_and_file_path($Class_Template_ID ? $Class_Template_ID : $classID);
    }
}

if ($cc_env['File_Mode']) {
    $file_class = new nc_tpl_component_view($CLASS_TEMPLATE_FOLDER, $db);
    $file_class->load($cc_env['Real_Class_ID'], $cc_env['File_Path'], $Class_Template_ID ? null : $cc_env['File_Hash']);
    $file_class->include_all_required_assets();

    require $INCLUDE_FOLDER . "classes/nc_class_aggregator_editor.class.php";
    $nc_class_aggregator = nc_class_aggregator_editor::init($file_class);
}

//revisions
if (isset($isVersion) && $isVersion == 1) {
    $revision = nc_Core::get_object()->revision;
    $revision->set_indexes($current_cc, $message);
    if (isset($restore) && $restore == 1) {
        $restoredFields = $revision->restore_draft();
        print "<script>"
            . "var restoredFields=".  json_encode($restoredFields).";"
            . "</script>";
    } else {
        $revision->save_draft();
        exit;
    }
}

ob_start();

do {
    //эта проверка делается в файлах index.php index_fs.php и завершает работу скрипта
    //но в первом файле поверка только для v4, а второй файл в конце этого скрипта
    //поэтому отправляем сразу в конец скрипта
    if (!$check_auth && !defined('NC_AUTH_IN_PROGRESS')) {
        break;
    }

    if (!$user_table_mode && !$message && !$delete && !$export && !$import && !$nc_recovery && !$multiple_changes) {
        nc_print_status(NETCAT_MODERATION_ERROR_NORIGHTS, "error");
        break;
    }
    if (!isset($posting)) {
        $posting = null;
    }
    if ($posting && $nc_core->token->is_use($delete ? "delete" : "change")) {
        if (!$nc_core->token->verify()) {
            echo NETCAT_TOKEN_INVALID;
            break;
        }
    }

    $is_there_any_files = $user_table_mode ? getFileCount(0, $systemTableID) : getFileCount($classID, 0);

    # права модератора
    $modRights = CheckUserRights($current_cc['Sub_Class_ID'], "moderate", $posting);

    # формирование обратной ссылки
    $alter_goBackLink = "";
    $alter_goBackLink_true = false;

    if (isset($_REQUEST['goBackLink'])) {
        $alter_goBackLink = $_REQUEST['goBackLink'];
        if ($admin_mode && preg_match("/^[\/a-z0-9_-]+\?catalogue=[[:digit:]]+&sub=[[:digit:]]+&cc=[[:digit:]]+(&curPos=[[:digit:]]{0,12})?$/i", $alter_goBackLink)) {
            $alter_goBackLink_true = true;
        }
        if (!$admin_mode && preg_match("/^[\/a-z0-9_-]+(\.html)?(\?curPos=[[:digit:]]{0,12})?$/i", $alter_goBackLink)) {
            $alter_goBackLink_true = true;
        }
    }

    # если путь не задан в форме
    if (!$alter_goBackLink_true) {
        if ($admin_mode) {
            $goBackLink = $admin_url_prefix . "?catalogue=" . $catalogue . "&sub=" . $sub . "&cc=" . $cc . "&curPos=" . $curPos;
        }
        else {
            $goBackLink = ($user_table_mode
                    ? nc_folder_url($current_sub['Subdivision_ID'])
                    : nc_infoblock_url($current_cc['Sub_Class_ID'])
                ) .
                ($curPos ? "?curPos=" . $curPos : "");
        }
    }
    else {
        $goBackLink = $alter_goBackLink;
    }

    $goBack = "<a href=" . $goBackLink . ">" . NETCAT_MODERATION_BACKTOSECTION . "</a>";

    // визуальные настройки
    $cc_settings = nc_array_value($cc_env, 'Sub_Class_Settings');

    // удаление или включение/выключение одного объекта
    // нужно загрузить все данные полей
    if ((!empty($delete) || !empty($checked)) && !empty($message) && !is_array($message) && !empty($posting)) {
        $component = new nc_Component($classID, $user_table_mode ? 3 : 0);
        $component->make_query();

        $field_names = $component->get_fields_query();
        $field_vars = $component->get_fields_vars();
        //$date_field = $component->get_date_field(); #not used

        $message_select = "
            SELECT  " . $field_names . "
            FROM (" . ($user_table_mode ? "`User`" : "`Message" . $classID . "`") . " AS a )" .
            $component->get_joins() . "
            WHERE  " . ($user_table_mode ? "a.`User_ID`" : "a.`Message_ID`") . " = '" . (int)$message . "'";

        eval("list(" . $field_vars . ") = \$db->get_row(\$message_select, ARRAY_N);");

        // List (надо обработать custom_settings)
        $nc_list_fields = $component->get_fields(NC_FIELDTYPE_SELECT);
        if (!empty($nc_list_fields)) {
            // просмотр каждого поля типа select и обработка CustomSettings
            foreach ($nc_list_fields as $list_field) {
                ${"f_" . $list_field['name'] . "_settings"} = $nc_core->list->evaluate_item_settings($list_field['table'], ${"f_" . $list_field['name'] . "_settings"});
            }
        }

        // Multiselect
        $multilist_fileds = $component->get_fields(NC_FIELDTYPE_MULTISELECT);
        if (!empty($multilist_fileds)) {
            // просмотр каждого поля типа multiselect
            foreach ($multilist_fileds as $multilist_filed) {
                // таблицу с элементами можно взять их кэша, если ее там нет - то добавить
                if (!$_cache['classificator'][$multilist_filed['table']]) {
                    $db_res = $db->get_results("SELECT `" . $multilist_filed['table'] . "_ID` AS ID, `" . $multilist_filed['table'] . "_Name` AS Name, `Value`, `CustomSettings`
                                     FROM `Classificator_" . $multilist_filed['table'] . "`", ARRAY_A);
                    if (!empty($db_res)) {
                        foreach ($db_res as $v) { // запись в кэш
                            $_cache['classificator'][$multilist_filed['table']][$v['ID']] = array($v['Name'], $v['Value'], $v['CustomSettings']);
                        }
                    }
                    unset($db_res);
                }

                ${"f_" . $multilist_filed['name'] . "_id"} = array();
                ${"f_" . $multilist_filed['name'] . "_value"} = array();
                ${"f_" . $multilist_filed['name'] . "_settings"} = array();
                if (($value = ${"f_" . $multilist_filed['name']})) { // значение из базы
                    ${"f_" . $multilist_filed['name']} = array();
                    $ids = explode(',', $value);
                    if (!empty($ids)) {
                        foreach ($ids as $id) { // для каждого элемента по id определяем имя
                            if ($id) {
                                array_push(${"f_" . $multilist_filed['name']}, $_cache['classificator'][$multilist_filed['table']][$id][0]);
                                array_push(${"f_" . $multilist_filed['name'] . "_value"}, $_cache['classificator'][$multilist_filed['table']][$id][1]);
                                array_push(
                                    ${"f_" . $multilist_filed['name'] . "_settings"},
                                    $nc_core->list->evaluate_item_settings($multilist_filed['table'], $_cache['classificator'][$multilist_filed['table']][$id][2])
                                );
                                array_push(${"f_" . $multilist_filed['name'] . "_id"}, $id);
                            }
                        }
                    }
                }
                // default value
                if (!is_array(${"f_" . $multilist_filed['name']})) {
                    ${"f_" . $multilist_filed['name']} = array();
                }
            }
            unset($ids);
            unset($id);
            unset($value);
        }

        // 'left join' used to provide compatibility with old fs ($f_File_url)
        $res = $db->get_results("SELECT fd.`Field_ID` AS field_id, fd.`Field_Name` AS field, ft.`File_Path` AS path, ft.`Virt_Name` AS name
                    FROM `Field` AS fd
                    LEFT JOIN `Filetable` AS ft
                    ON (fd.`Field_ID` = ft.`Field_ID` AND ft.`Message_ID` = '" . (int)$message . "')
                    WHERE fd.`Class_ID` = '" . (int)$classID . "'
                    AND fd.`TypeOfData_ID` = " . NC_FIELDTYPE_FILE, ARRAY_A);

        foreach ((array)$res AS $row) {
            $field_value = ${"f_" . $row['field']}; // то, что хранится в базе
            // возможен случай, что файла нет.
            if (!$field_value) {
                continue;
            }
            $field_value = explode(':', $field_value);

            // оригинальное имя, тип, размер
            ${"f_" . $row['field'] . "_name"} = $field_value[0];
            ${"f_" . $row['field'] . "_type"} = $field_value[1];
            ${"f_" . $row['field'] . "_size"} = $field_value[2];


            if ($row['name']) { // Protected FileSystem
                ${"f_" . $row['field']} = $SUB_FOLDER . rtrim($HTTP_FILES_PATH, '/') . $row['path'] . "h_" . $row['name'];
                ${"f_" . $row['field'] . "_url"} = $SUB_FOLDER . rtrim($HTTP_FILES_PATH, '/') . $row['path'] . $row['name'];
            }
            else if ($field_value[3]) { // Original FileSystem
                ${"f_" . $row['field']} = ${"f_" . $row['field'] . "_url"} = $SUB_FOLDER . $HTTP_FILES_PATH . $field_value[3];
            }
            else { // Simple FileSystem
                $ext = substr($field_value[0], strrpos($field_value[0], "."));
                ${"f_" . $row['field'] . "_url"} = ${"f_" . $row['field']} = $SUB_FOLDER . $HTTP_FILES_PATH . $row['field_id'] . "_" . $f_RowID . $ext;
            }
        }
        // free memory
        unset($field_value);
        unset($ext);
        unset($res);

        $f_Created_year = substr($f_Created, 0, 4);
        $f_Created_month = substr($f_Created, 5, 2);
        $f_Created_day = substr($f_Created, 8, 2);
        $f_Created_hours = substr($f_Created, 11, 2);
        $f_Created_minutes = substr($f_Created, 14, 2);
        $f_Created_seconds = substr($f_Created, 17, 2);

        $f_LastUpdated_year = substr($f_LastUpdated, 0, 4);
        $f_LastUpdated_month = substr($f_LastUpdated, 4, 2);
        $f_LastUpdated_day = substr($f_LastUpdated, 6, 2);
        $f_LastUpdated_hours = substr($f_LastUpdated, 8, 2);
        $f_LastUpdated_minutes = substr($f_LastUpdated, 10, 2);
        $f_LastUpdated_seconds = substr($f_LastUpdated, 12, 2);
    }

    if (!empty($nc_recovery) && is_array($nc_trashed_ids)) {
        $nc_core->trash->recovery($nc_trashed_ids);
        ob_end_clean();
        header("Location: " . $goBackLink . "&inside_admin=1");
        exit;
    }

    // DELETE: удаление объекта(ов)
    if (!empty($delete)) {
        // подтверждение удаления
        if (!empty($_REQUEST['isNaked']) && empty($_REQUEST['force_delete']) && !$nc_core->get_settings('TrashUse')) {
            ob_clean();
            echo "trashbin_disabled";
            exit;
        }

        if (!$posting) {
            if ($message && !is_array($message)) {
                if ($inside_admin) {
                    echo $nc_core->trash->delete_preform();
                }

                $action_loaded = false;
                if ($cc_env['File_Mode']) {
                    $nc_parent_field_path = $file_class->get_parent_field_path('DeleteTemplate');
                    $nc_field_path = $file_class->get_field_path('DeleteTemplate');

                    if (filesize($nc_field_path)) {
                        try {
                            if (PHP_MAJOR_VERSION === 5) {
                                nc_check_php_file($nc_field_path);
                            }
                            if ((include $nc_field_path) === false) {
                                throw new nc_tpl_exception_include($nc_field_path);
                            }
                            $action_loaded = true;
                        } catch (Throwable $e) { // PHP 7
                            $file_class->print_error_message($nc_field_path, $e);
                        } catch (Exception $e) { // PHP 5
                            $file_class->print_error_message($nc_field_path, $e);
                        }
                    }
                    $nc_parent_field_path = null;
                    $nc_field_path = null;
                }

                if (!$cc_env['File_Mode'] || !$action_loaded) {
                    eval(nc_check_eval("\$result = \"" . ($DeleteTemplate ? $DeleteTemplate : nc_fields_form("message")) . "\";"));
                    echo $result;
                }
            }
            else {
                $url = $admin_url_prefix . "message.php?" . ($nc_core->inside_admin ? "inside_admin=1&" : "") . "catalogue=" . $catalogue . "&sub=" . $sub . "&cc=" . $cc . "&delete=1&posting=1" . ($admin_mode ? "&admin_mode=1" : "");
                $url .= $nc_core->token->is_use('drop') ? "&" . $nc_core->token->get_url() : "";
                if (!empty($message)) {
                    foreach ($message as $v)
                        $url .= "&message[" . $v . "]=" . $v;
                }

                if (!empty($isNaked)) {
                    echo "kill_all$url";
                    exit;
                }
                else {
                    $confirmation = sprintf(NETCAT_MODERATION_WARN_COMMITDELETIONINCLASS, $cc) . "<br><a href='" . $url . "'>" . NETCAT_MODERATION_COMMON_KILLALL . "</a> | " . $goBack;
                    nc_print_status($confirmation, 'info');
                }
            }

            break;
        }

        // выясним, какие объекты удлаять
        $action_field = $user_table_mode ? "`User_ID`" : "`Message_ID`";
        $message_ids = $db->get_col("
            SELECT " . $action_field . "
            FROM " . ($user_table_mode ? "`User`" : "`Message" . $classID . "`") . "
            WHERE " . (!$message ? "`Sub_Class_ID` = '" . $cc . "' " : $action_field . "
                IN ( " . (is_array($message) ? join(',', $message) : $message) . ")") . "
			" . ($modRights ? "" : " AND `User_ID` = '" . $AUTH_USER_ID . "' "));

        if (!$message_ids) {
            echo NETCAT_MODERATION_ERROR_NORIGHTS . "<br/><br/>" . $goBack;
            break;
        }

        // если идет пакетное удаление или удаление всех, то в
        // $message должен быть массив реально удаляемых объектов
        // при удаление одного объекта $message должен быть числом
        if (!$message || is_array($message)) {
            $message = $message_ids;
        }

        // условие удаления
        $action_loaded = false;
        if ($cc_env['File_Mode']) {
            $nc_parent_field_path = $file_class->get_parent_field_path('DeleteCond');
            $nc_field_path = $file_class->get_field_path('DeleteCond');

            try {
                if (PHP_MAJOR_VERSION === 5) {
                    nc_check_php_file($nc_field_path);
                }
                if ((include $nc_field_path) === false) {
                    throw new nc_tpl_exception_include($nc_field_path);
                }
                $action_loaded = true;
            } catch (Throwable $e) { // PHP 7
                $file_class->print_error_message($nc_field_path, $e);
            } catch (Exception $e) { // PHP 5
                $file_class->print_error_message($nc_field_path, $e);
            }

            $nc_parent_field_path = null;
            $nc_field_path = null;
        }

        if (!$cc_env['File_Mode'] || !$action_loaded) {
            eval(nc_check_eval($DeleteCond));
        }

        // posting может обнулиться в условие удаления
        if (!$posting) {
            if (isset($warnText)) {
                eval(nc_check_eval("echo \"" . $warnText . "\";"));
            }
            break;
        }

        // удаление
        if ($user_table_mode && !empty($systemTableID)) {
            $nc_core->user->delete_by_id($message);
        }
        else {
            $trash = $nc_core->get_settings('TrashUse');
            $nc_core->message->delete_by_id($message, $classID, $trash);
        }

        if (!empty($isNaked)) {
            echo 'deleted';
        }

        // при перемещении объектов в корзину добавим в $goBackLink нужные айди
        if ($trash) {
            if ($nc_core->trash->is_full()) {
                $goBackLink .= strpos($goBackLink, '?') === false ? '?' : '&';
                $goBackLink .= 'nc_trash_full=1';
            }
            if ($nc_core->trash->folder_fail()) {
                $goBackLink .= strpos($goBackLink, '?') === false ? '?' : '&';
                $goBackLink .= 'nc_folder_fail=1';
            }
            if (($deleted_ids = $nc_core->trash->get_deleted_ids()) && !empty($deleted_ids)) {
                $added_ar = array();
                $goBackLink .= strpos($goBackLink, '?') === false ? '?' : '&';
                foreach ($deleted_ids as $v)
                    $added_ar[] = "nc_trashed_ids[]=" . $v;
                $goBackLink .= join('&', $added_ar);
            }
        }

        // действие после удаления
        //если нет действия после удаления выводить стандартную форму
        $action_loaded = false;
        if ($cc_env['File_Mode']) {
            $nc_parent_field_path = $file_class->get_parent_field_path('DeleteActionTemplate');
            $nc_field_path = $file_class->get_field_path('DeleteActionTemplate');

            if (filesize($nc_field_path)) {
                try {
                    if (PHP_MAJOR_VERSION === 5) {
                        nc_check_php_file($nc_field_path);
                    }
                    if ((include $nc_field_path) === false) {
                        throw new nc_tpl_exception_include($nc_field_path);
                    }
                    $action_loaded = true;
                } catch (Throwable $e) { // PHP 7
                    $file_class->print_error_message($nc_field_path, $e);
                } catch (Exception $e) { // PHP 5
                    $file_class->print_error_message($nc_field_path, $e);
                }
            }
            else {
                eval(nc_check_eval("echo \"" . nc_fields_action_code("deleteaction") . "\";"));
                $action_loaded = true;
            }

            $nc_parent_field_path = null;
            $nc_field_path = null;
        }

        if (!$cc_env['File_Mode'] || !$action_loaded) {
            eval(nc_check_eval("echo \"" . ($DeleteActionTemplate ? $DeleteActionTemplate : nc_fields_action_code("deleteaction")) . "\";"));
        }

        if (!empty($isNaked)) {
            // выполнить необходимую обработку кода страницы и отдать результат пользователю:
            $nc_core->output_page($template_header, $nc_result_msg, $template_footer);
            exit;
        }

        break;
    }
    else {
        if (!empty($checked) && !empty($message) && !empty($posting)) {
            if (!(
                $cc_env['Checked_Access_ID'] == 1 ||
                ($cc_env['Checked_Access_ID'] == 2 && $AUTH_USER_ID) ||
                ($perm instanceof Permission && $perm->isSubClass($cc, MASK_CHECKED))
            )
            ) {
                nc_print_status(NETCAT_MODERATION_ERROR_NORIGHTS, 'error');
                break;
            }
            $checked = (int)$checked;

            // все объекты, которые нужно включить \ выключить
            $action_field = $user_table_mode ? "`User_ID`" : "`Message_ID`";
            $action_table = $user_table_mode ? "`User`" : "`Message" . $classID . "`";
            $messages = $db->get_col("SELECT " . $action_field . " FROM " . $action_table . "
						WHERE " . ($modRights ? "1" : "`User_ID` = '" . $AUTH_USER_ID . "'") . "
						AND " . $action_field . " IN (" . join(',', (array)$message) . ")");
            if (empty($messages)) {
                nc_print_status(NETCAT_MODERATION_ERROR_NORIGHTS, 'error');
                break;
            }

            if (!empty($passed_thru_404)) { // Изменение видимости не в режиме admin_mode - поменять состояние на противоположное
                // в БД хранится 0 или 1
                // Если $checked = 2 - объекты включается, если $checked = 1 - объект выключается
                $checked = 2 - $db->get_var("SELECT `Checked` FROM " . $action_table . " WHERE " . $action_field . " = '" . $message . "'");
            }

            // execute core action
            $nc_core->event->execute(($checked == 2 ? nc_Event::BEFORE_OBJECT_ENABLED : nc_Event::BEFORE_OBJECT_DISABLED), $catalogue, $sub, $cc, $classID, $messages);

            $res = $db->query("UPDATE " . $action_table . "
                            SET `Checked` = " . ($checked - 1) . " " .
                (isset($_GET['priority']) ? (', `Priority` = ' . (int)$_GET['priority'] . " ") : '') .
                "WHERE " . $action_field . " IN (" . join(',', $messages) . ")");

            // execute core action
            $nc_core->event->execute(($checked == 2 ? nc_Event::AFTER_OBJECT_ENABLED : nc_Event::AFTER_OBJECT_DISABLED), $catalogue, $sub, $cc, $classID, $messages);

            $action_loaded = false;
            if ($cc_env['File_Mode']) {
                $nc_parent_field_path = $file_class->get_parent_field_path('CheckActionTemplate');
                $nc_field_path = $file_class->get_field_path('CheckActionTemplate');

                if (filesize($nc_field_path)) {
                    try {
                        if (PHP_MAJOR_VERSION === 5) {
                            nc_check_php_file($nc_field_path);
                        }
                        if ((include $nc_field_path) === false) {
                            throw new nc_tpl_exception_include($nc_field_path);
                        }
                        $action_loaded = true;
                    } catch (Throwable $e) { // PHP 7
                        $file_class->print_error_message($nc_field_path, $e);
                    } catch (Exception $e) { // PHP 5
                        $file_class->print_error_message($nc_field_path, $e);
                    }
                }
                else {
                    if ($inside_admin) {
                        ob_end_clean();
                        header("Location: " . $goBackLink . "&inside_admin=1");
                        exit;
                    }
                    else {
                        echo($checked - 1 ? (count($messages) == 1 ? "<div class='obj_is_on'>".NETCAT_MODERATION_OBJISON."</div>" : NETCAT_MODERATION_OBJSAREON) :
                            (count($messages) == 1 ? "<div class='obj_is_off'>".NETCAT_MODERATION_OBJISOFF."</div>" : NETCAT_MODERATION_OBJSAREOFF));
                        echo "<br /><br />" . $goBack;
                    }
                    $action_loaded = true;
                }

                $nc_parent_field_path = null;
                $nc_field_path = null;
            }

            if (!$cc_env['File_Mode'] || !$action_loaded) {
                eval(nc_check_eval("echo \"" . ($CheckActionTemplate ? $CheckActionTemplate : nc_fields_action_code('checkaction')) . "\";"));
            }

            break;
        }

        if ($classPreview && $classPreview == ($current_cc["Class_Template_ID"] ? $current_cc["Class_Template_ID"] : $current_cc["Class_ID"])) {
            $magic_gpc = function_exists('get_magic_quotes_gpc') && get_magic_quotes_gpc();
            $editTemplate = $magic_gpc ? stripslashes($_SESSION["PreviewClass"][$classPreview]["EditTemplate"]) : $_SESSION["PreviewClass"][$classPreview]["EditTemplate"];
            $editCond = $magic_gpc ? stripslashes($_SESSION["PreviewClass"][$classPreview]["EditCond"]) : $_SESSION["PreviewClass"][$classPreview]["EditCond"];
            $editActionTemplate = $magic_gpc ? stripslashes($_SESSION["PreviewClass"][$classPreview]["EditActionTemplate"]) : $_SESSION["PreviewClass"][$classPreview]["EditActionTemplate"];
        }

        if ($posting) {
            if ($cc_env['File_Mode']) {
                $nc_parent_field_path = $file_class->get_parent_field_path('EditCond');
                $nc_field_path = $file_class->get_field_path('EditCond');

                try {
                    if (PHP_MAJOR_VERSION === 5) {
                        nc_check_php_file($nc_field_path);
                    }
                    if ((include $nc_field_path) === false) {
                        throw new nc_tpl_exception_include($nc_field_path);
                    }
                } catch (Throwable $e) { // PHP 7
                    $file_class->print_error_message($nc_field_path, $e);
                } catch (Exception $e) { // PHP 5
                    $file_class->print_error_message($nc_field_path, $e);
                }

                $nc_parent_field_path = null;
                $nc_field_path = null;
            }
            else {
                eval(nc_check_eval($editCond));
            }
        }

        $fld = array();
        require $ROOT_FOLDER . "message_fields.php";

        $query_str = "SELECT
                        DATE_FORMAT(a.`Created`,'%d.%m.%Y, %H:%i'),
                        DATE_FORMAT(a.LastUpdated,'%d.%m.%Y, %H:%i')" .
            (!$user_table_mode ? ",
                        a.`User_ID`,
                        a.`IP`,
                        a.`LastUser_ID`,
                        a.`LastIP`,
                        a.`Priority`" : null
            );

        if (!$user_table_mode && $admin_mode && $AUTHORIZE_BY !== 'User_ID') {
            $query_str .= ", uNewAdminInterface.`" . $AUTHORIZE_BY . "` AS f_newAdminInterface_user_add, uNewAdminInterface2.`" . $AUTHORIZE_BY . "` AS f_newAdminInterface_user_change ";
        }

        $query_str .= " FROM " . ($user_table_mode ? "`User`" : "`Message" . $classID . "`") . " AS a ";

        if (!$user_table_mode && $admin_mode && $AUTHORIZE_BY !== 'User_ID') {
            $query_str .= " LEFT JOIN `User` AS uNewAdminInterface ON a.`User_ID` = uNewAdminInterface.`User_ID` LEFT JOIN `User` AS uNewAdminInterface2 ON a.`LastUser_ID` = uNewAdminInterface2.`User_ID` ";
        }

        $query_str .= "WHERE a.`" . ($user_table_mode ? "User" : "Message") . "_ID` = " . $message;

        $res = $db->get_row($query_str, ARRAY_N);

        if (!$user_table_mode) {
            if ($AUTHORIZE_BY === 'User_ID') {
                list($f_Created, $f_LastUpdated, $f_UserID, $f_IP, $f_LastUserID, $f_LastIP, $tmp_f_Priority) = $res;
                $f_newAdminInterface_user_add = $f_UserID;
                $f_newAdminInterface_user_change = $f_LastUserID;
            }
            else {
                list($f_Created, $f_LastUpdated, $f_UserID, $f_IP, $f_LastUserID, $f_LastIP, $tmp_f_Priority) = $res;
                $f_newAdminInterface_user_add = nc_array_value($res, 7);
                $f_newAdminInterface_user_change = nc_array_value($res, 8);
            }
            if (!$posting) {
                $f_Priority = $res[6];
            }
        }
        else {
            list($f_Created, $f_LastUpdated) = $res;
        }


        // редактируем пользователя, нужно проверить права
        if ($user_table_mode) {
            if (!$message) {
                $message = $AUTH_USER_ID;
            }
            //пользователь должен быть авторизованным и
            // иметь доступ к изменению пользователя (если это не он сам)
            if (!$perm instanceof Permission || ($AUTH_USER_ID != $message && !$perm->isAccess(NC_PERM_ITEM_USER, NC_PERM_ACTION_EDIT, $message, $posting))) {
                nc_print_status(NETCAT_MODERATION_ERROR_NORIGHTS, "error");
                break;
            }
        }

        if ($posting == 0) {
            if (!isset($posting) && count($fld)) {
                $fieldQuery = "`" . implode("`, `", $fld) . "`";

                if ($user_table_mode) {
                    $select = "SELECT " . $fieldQuery . ", `Checked`, `Keyword`
                              FROM `User`
                              WHERE `User_ID` = '" . $message . "'";
                }
                else {
                    $select = "SELECT " . $fieldQuery . ", 
                                  `Checked`, 
                                  `Keyword`, 
                                  `ncTitle`, 
                                  `ncKeywords`, 
                                  `ncDescription`, 
                                  `ncSMO_Title`, 
                                  `ncSMO_Description`, 
                                  `ncSMO_Image`, 
                                  `Parent_Message_ID`, 
                                  `ncDemoContent`,
                                  `ncFullPageMode`
                              FROM `Message" . $classID . "`
                              WHERE `Message_ID` = '" . $message . "'";
                }

                $fldValueVars = $db->get_row($select, ARRAY_A);

                if ($fldValueVars) {
                    $fldValue = array_values($fldValueVars);
                }

                for ($n = 0, $end = count($fldType); $n < $end; ++$n) {
                    if ($fldType[$n] == NC_FIELDTYPE_MULTIFILE) {
                        $fldValueVars[$fld[$n]] = ${'f_' . $fld[$n]};
                    }
                }

                $nc_core->page->update_last_modified_if_timestamp_is_newer(strtotime($f_Created));
                $nc_core->page->update_last_modified_if_timestamp_is_newer(strtotime($f_LastUpdated));

                if ($fldValueVars) {
                    extract($fldValueVars, EXTR_PREFIX_ALL, "f");

                    // Prepare and extract file fields variables
                    $hybrid_component_id = ($user_table_mode ? 'User' : $classID);
                    $nc_core->file_info->cache_object_data($hybrid_component_id, $fldValueVars);
                    extract($nc_core->file_info->get_all_object_file_variables($hybrid_component_id, $message));
                }

                //if ($user_table_mode) $message = $AUTH_USER_ID;

                for ($i = 0; $i < $fldCount; $i++) {
                    if ($fldType[$i] == NC_FIELDTYPE_DATETIME) {
                        ${'f_' . $fld[$i] . '_year'} = substr(${'f_' . $fld[$i]}, 0, 4);
                        ${'f_' . $fld[$i] . '_month'} = substr(${'f_' . $fld[$i]}, 5, 2);
                        ${'f_' . $fld[$i] . '_day'} = substr(${'f_' . $fld[$i]}, 8, 2);
                        ${'f_' . $fld[$i] . '_hours'} = substr(${'f_' . $fld[$i]}, 11, 2);
                        ${'f_' . $fld[$i] . '_minutes'} = substr(${'f_' . $fld[$i]}, 14, 2);
                        ${'f_' . $fld[$i] . '_seconds'} = substr(${'f_' . $fld[$i]}, 17, 2);
                    }
                    else if ($fldType[$i] == NC_FIELDTYPE_FILE && $fldValue[$i]) {
                        ${"f_" . $fld[$i] . "_old"} = $fldValue[$i];
                    }
                }

                $f_ncSMO_Image_old = nc_array_value($fldValueVars, 'ncSMO_Image');
            }


            if (!$modRights && $f_UserID != $AUTH_USER_ID && !$user_table_mode) {
                nc_print_status(NETCAT_MODERATION_ERROR_NORIGHTS, 'error');
                break;
            }

            if ($editTemplate || ($cc_env['File_Mode'] && filesize($file_class->get_field_path('EditTemplate')))) {
                if ($cc_env['File_Mode']) {
                    $nc_parent_field_path = $file_class->get_parent_field_path('EditTemplate');
                    $nc_field_path = $file_class->get_field_path('EditTemplate');

                    // обертка для вывода ошибки в админке
                    if (!empty($warnText) && ($nc_core->inside_admin || $isNaked)) {
                        ob_start();
                        nc_print_status($warnText, 'error');
                        $warnText = ob_get_clean();
                    }

                    ob_start();
                    try {
                        if (PHP_MAJOR_VERSION === 5) {
                            nc_check_php_file($nc_field_path);
                        }
                        if ((include $nc_field_path) === false) {
                            throw new nc_tpl_exception_include($nc_field_path);
                        }
                    } catch (Throwable $e) { // PHP 7
                        $file_class->print_error_message($nc_field_path, $e);
                    } catch (Exception $e) { // PHP 5
                        $file_class->print_error_message($nc_field_path, $e);
                    }
                    if (!isset($sys_table_id)) {
                        $sys_table_id = null;
                    }
                    echo nc_prepare_message_form(ob_get_clean(), $action, $admin_mode, $user_table_mode, $sys_table_id, $current_cc, $f_Checked, $f_Priority, $f_Keyword, $f_ncTitle, $f_ncKeywords, $f_ncDescription, 1, 0, $f_ncSMO_Title, $f_ncSMO_Description, $f_ncSMO_Image, $f_ncDemoContent, $f_ncFullPageMode);

                    $nc_parent_field_path = null;
                    $nc_field_path = null;
                }
                else if (!$systemTableID || ($user_table_mode && (int)$systemTableID === 3)) {
                    ob_start();
                    eval(nc_check_eval("echo \"" . $editTemplate . "\";"));
                    echo nc_prepare_message_form(ob_get_clean(), $action, $admin_mode, $user_table_mode, $sys_table_id, $current_cc, $f_Checked, $f_Priority, $f_Keyword, $f_ncTitle, $f_ncKeywords, $f_ncDescription, 1, 0, $f_ncSMO_Title, $f_ncSMO_Description, $f_ncSMO_Image, $f_ncDemoContent, $f_ncFullPageMode);
                }
                else {
                    eval(nc_check_eval("echo \"" . $editTemplate . "\";"));
                }
            }
            else if ($multiple_changes) {
                echo "<script type='text/javascript'>history.go(-1);</script>";
                exit;
            }
            else {
                require $ROOT_FOLDER . "message_edit.php";
            }

            if ($inside_admin && !empty($UI_CONFIG) && $goBackLink) {
                $UI_CONFIG->actionButtons[] = array("id" => "goback",
                    "caption" => CONTROL_AUTH_HTML_BACK,
                    "align" => 'left',
                    "action" => "mainView.loadIframe('" . $goBackLink . "&inside_admin=1')");
            }

            break;
        }
        else if ($posting == 1) {
            // check permission
            if (!(
                $cc_env['Edit_Access_ID'] == 1 ||
                ($cc_env['Edit_Access_ID'] == 2 && $AUTH_USER_ID) ||
                ($perm instanceof Permission && $perm->isSubClass($cc, MASK_EDIT))
            )
            ) {
                nc_print_status(NETCAT_MODERATION_ERROR_NORIGHTS, 'error');
            }
            else {
                $partial = $nc_core->input->fetch_post_get('partial');

                require $ROOT_FOLDER . "message_put.php";

                $component_id = $nc_core->get_system_table_name_by_id((int)$systemTableID) ?: $classID;
                $nc_multifield_field_names = $nc_core
                    ->get_component($component_id)
                    ->get_fields(NC_FIELDTYPE_MULTIFILE, false);

                foreach ($nc_multifield_field_names as $nc_multifield_field_name) {
                    if ($partial && empty($_FILES["f_{$nc_multifield_field_name}"])) {
                        continue;
                    }
                    nc_multifield_saver::save_from_post_data($component_id, $message, ${"f_{$nc_multifield_field_name}"}, false);
                }

                unset($nc_multifield_field_names);

                if ($multiple_changes) {
                    foreach ($updateStrings as $multiple_changes_msg_id => $update_string) {
                        if ($user_table_mode) {
                            $resMsg = $db->query("UPDATE `User` SET $update_string WHERE `User_ID` = " . $multiple_changes_msg_id);
                        }
                        else {
                            $SQL = "UPDATE `Message$classID`
                                    SET $update_string,
                                    `LastUser_ID` = $AUTH_USER_ID,
                                    `LastIP` = '" . $db->escape($REMOTE_ADDR) . "',
                                    `LastUserAgent` = '" . $db->escape($HTTP_USER_AGENT) . "'
                                     WHERE `Message_ID` = " . $multiple_changes_msg_id;
                            $resMsg = $db->query($SQL);
                        }
                    }
                }
                else {
                    if ($user_table_mode) {
                        $nc_core->event->execute(nc_Event::BEFORE_USER_UPDATED, $message);
                        $resMsg = $db->query("UPDATE `User` SET " . $updateString . " `Checked` = `Checked`" . ($admin_mode ? ", `Keyword` = '" . $f_Keyword . "'" : "") . " " . ($Password ? ", `Password` = " . $nc_core->MYSQL_ENCRYPT . "('" . $db->escape($Password) . "'), `UserType` = 'normal' " : "") . " WHERE `User_ID` = '" . $message . "'");
                    }
                    else {
                        $nc_event_subdivision_id = $nc_core->sub_class->get_by_id($cc, 'Subdivision_ID');
                        $nc_core->event->execute(nc_Event::BEFORE_OBJECT_UPDATED, $catalogue, $nc_event_subdivision_id, $cc, $classID, $message);

                        $set_keyword = '';
                        if ((!$partial || isset($f_Keyword)) && !$KeywordDefined) {
                            $set_keyword = "`Keyword` = '" . nc_check_keyword_name($message, $f_Keyword, $classID, $sub) . "', ";
                        }

                        $set_checked = '';
                        if (!$partial || isset($f_Checked)) {
                            $set_checked = " `Checked` = '" . (int)$f_Checked . "', ";
                        }

                        $nc_mode = $nc_core->catalogue->get_by_id((int)$catalogue, 'ncMode');
                        $set_ncDemoContent = '';
                        if ($perm->isSupervisor() && ($nc_mode == 'template_development' || $nc_mode == 'demo')) {
                            if ($admin_mode) {
                                $set_ncDemoContent = " `ncDemoContent` = '" . (int)$f_ncDemoContent . "', ";
                            }
                        } else {
                            $set_ncDemoContent = " `ncDemoContent` = '0', ";
                        }

                        if (isset($f_ncFullPageMode)) {
                            $set_full_page = " `ncFullPageMode` = '" . $db->escape($f_ncFullPageMode) . "', ";
                        } else {
                            $set_full_page = '';
                        }

                        $SQL = "UPDATE `Message" . $classID . "` SET " . $updateString .
                            ($admin_mode ? $set_checked . $set_keyword : '') .
                            $set_ncDemoContent .
                            $set_full_page .
                            "`LastUser_ID` = '" . $AUTH_USER_ID . "', `LastIP` = '" . $db->escape($REMOTE_ADDR) .
                            "', `LastUserAgent` = '" . $db->escape($HTTP_USER_AGENT) .
                            "' WHERE `Message_ID` = '" . $message . "'" .
                            (!$modRights ? " AND `User_ID` = '" . $AUTH_USER_ID . "'" : "");

                        $resMsg = $db->query($SQL);
                    }
                }

                if ($db->is_error) {
                    $resMsg = 0;
                }
                else {
                    $resMsg = 1;
                    // execute core action
                    if ($user_table_mode) {
                        $nc_core->event->execute(nc_Event::AFTER_USER_UPDATED, $message);
                    }
                    else {
                        $nc_core->event->execute(nc_Event::AFTER_OBJECT_UPDATED, $catalogue, $nc_event_subdivision_id, $cc, $classID, $message);
                    }
                }

                if (nc_module_check_by_keyword("comments")) {
                    // get rule id
                    $CommentData = nc_comments::getRuleData($db, array($catalogue, $sub, $cc, $message));
                    if ($CommentData) {
                        $CommentRelationID = $CommentData['ID'];
                        $comm_env = array($catalogue, $sub, $cc, $message);
                        // do something
                        switch (true) {
                            case $CommentAccessID > 0 && $CommentRelationID:
                                // update comment rules
                                nc_comments::updateRule($db, $comm_env, $CommentAccessID, $CommentsEditRules, $CommentsDeleteRules);
                                break;
                            case $CommentAccessID > 0 && !$CommentRelationID:
                                // add comment relation
                                $CommentRelationID = nc_comments::addRule($db, $comm_env, $CommentAccessID, $CommentsEditRules, $CommentsDeleteRules);
                                break;
                            case $CommentAccessID <= 0 && $CommentRelationID:
                                // delete comment rules
                                nc_comments::dropRule($db, $comm_env);
                                $CommentRelationID = 0;
                                break;
                        }
                    }
                }

                if ($admin_mode && !$user_table_mode && isset($f_Priority)) {
                    $f_Priority = (int)$f_Priority;
                    $res = $db->query(
                        "UPDATE `Message" . $classID . "` 
                        SET `Priority` = " . ($f_Priority) . ", `LastUpdated` = `LastUpdated`
			WHERE `Message_ID` = '" . $message . "'"
                    );
                }

                if ($resMsg) {
                    $action_loaded = false;
                    if ($cc_env['File_Mode']) {
                        $nc_parent_field_path = $file_class->get_parent_field_path('EditActionTemplate');
                        $nc_field_path = $file_class->get_field_path('EditActionTemplate');
                        if (filesize($nc_field_path)) {
                            try {
                                if (PHP_MAJOR_VERSION === 5) {
                                    nc_check_php_file($nc_field_path);
                                }
                                if ((include $nc_field_path) === false) {
                                    throw new nc_tpl_exception_include($nc_field_path);
                                }
                                $action_loaded = true;
                            } catch (Throwable $e) { // PHP 7
                                $file_class->print_error_message($nc_field_path, $e);
                            } catch (Exception $e) { // PHP 5
                                $file_class->print_error_message($nc_field_path, $e);
                            }
                        }
                        $nc_parent_field_path = null;
                        $nc_field_path = null;
                    }

                    if (!$cc_env['File_Mode'] || !$action_loaded) {
                        echo '<div>';
                        if ($editActionTemplate) {
                            eval(nc_check_eval("echo \"" . $editActionTemplate . "\";"));
                        }
                        else {
                            if ($inside_admin) {
                                ob_end_clean();
                                header('Location: ' . $goBackLink . '&inside_admin=1');
                                exit;
                            }
                            else {
                                echo "<div class='obj_is_changed'>".NETCAT_MODERATION_MSG_OBJCHANGED."</div>";
                                echo "<br /><br />" . $goBack;
                            }
                        }
                        echo '</div>';
                    }
                }
                else {
                    echo NETCAT_MODERATION_ERROR_NOOBJCHANGE . "<br/><br/>" . $goBack;
                }
            }

            if (!empty($admin_modal)) {
                echo "\nReloadPageIfActionIsFull=1\n";
            }
        }
    }

} while (false);

$nc_result_msg = ob_get_clean();
$nc_core->page->is_processing_template_now();

if ($File_Mode) {
    require_once $INCLUDE_FOLDER . 'index_fs.inc.php';
}

if (!$File_Mode || $templatePreview) {
    nc_evaluate_template($template_header, $template_footer, $File_Mode);
}

// выполнить необходимую обработку кода страницы и отдать результат пользователю:
$nc_core->output_page($template_header, $nc_result_msg, $template_footer);
