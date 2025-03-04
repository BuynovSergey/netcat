<?php

// вывод полной информации об объекте
$action = 'full';
$NETCAT_FOLDER = realpath(__DIR__ . '/..') . '/';
require_once $NETCAT_FOLDER . 'vars.inc.php';
require_once $ROOT_FOLDER . 'connect_io.php';

$nc_core = nc_Core::get_object();
$nc_core->action = 'full';
/** @var string $INCLUDE_FOLDER */
require $INCLUDE_FOLDER . 'index.php';

/** @var nc_core $nc_core */
/** @var ui_config_objects $UI_CONFIG */
/** @var array $template_env */
/** @var int $catalogue */
/** @var int $sub */
/** @var int $cc */
/** @var int $message */
/** @var int $template */
/** @var int $File_Mode */
/** @var int $templatePreview */

$routing_module_enabled = nc_module_check_by_keyword('routing');

if (empty($passed_thru_404) && !$nc_core->page->get_routing_result()) {
    $nc_core->page->set_routing_result(array(
        'resource_type' => 'object',
        'site_id' => $catalogue,
        'folder_id' => $sub,
        'infoblock_id' => $cc,
        'object_id' => $message,
        'object_keyword' => null,
        'action' => $action,
        'format' => 'html',
        'variables' => array(),
        'date' => null,
        'redirect_to_url' => null,
    ));
}

if ($nc_core->inside_admin && empty($UI_CONFIG)) {
    $UI_CONFIG = new ui_config_objects($cc);
}

$f_AdminButtons = '';
$f_AdminCommon = '';

if ($File_Mode && !$templatePreview) {
    if ($nc_core->subdivision->get_current('Template_ID') == $template) {
        $template_settings = $nc_core->subdivision->get_template_settings($sub);
    } else if (isset($nc_use_site_template_settings) && $nc_use_site_template_settings) {
        // используется в скриптах modules/auth
        $template_settings = $nc_core->catalogue->get_template_settings($catalogue);
    } else {
        $template_settings = $nc_core->template->get_settings_default_values($template);
    }

    $template_view = new nc_tpl_template_view($nc_core->TEMPLATE_FOLDER, $nc_core->db);
    $template_view->load_template($template, $template_env['File_Path']);
    $template_view->include_all_required_assets();
    $array_settings_path = $template_view->get_all_settings_path_in_array();
    foreach ($array_settings_path as $path) {
        include_once $path;
    }
}

do {
    ob_start();

    $ignore_array = array('cond_user', 'cond_mod', 'cond_date', 'cond_where',
        'query_select', 'query_from', 'query_group', 'query_join', 'query_where', 'query_order', 'query_having',
        'ignore_all', 'ignore_catalogue', 'ignore_sub', 'ignore_cc', 'ignore_check', 'ignore_parent', 'ignore_link',
        'result_vars', 'f_ncTitle', 'f_ncKeywords', 'f_ncDescription', 'f_ncSMO_Title', 'f_ncSMO_Description', 'f_ncSMO_Image');

    foreach ($ignore_array as $v) {
        $$v = null;
    }

    if (empty($_db_cc)) {
        $_db_cc = $cc;
    }

    $nc_ctpl = !empty($nc_ctpl) ? $nc_ctpl : 0;
    $cc_env = $nc_core->sub_class->get_by_id($_db_cc, null, $nc_ctpl);

    try {
        if ($nc_ctpl && $nc_ctpl != 'title' && $nc_ctpl != $cc_env['Class_ID'] && ($cc_env['Class_ID'] != $nc_core->component->get_by_id($nc_ctpl, 'ClassTemplate'))) {
            throw new Exception();
        }
    } catch (Exception $e) {
        if ($perm instanceof Permission && $perm->isSupervisor()) {
            echo "<div style='color: red;'>" .
                sprintf(CONTROL_CLASS_CLASS_OBJECTFULL_WRONG_NC_CTPL, $nc_ctpl) .
                $e->getMessage() .
                "</div>";
        }
        $nc_ctpl = 0;
        $cc_env = $nc_core->sub_class->get_by_id($_db_cc);
    }

    if (!$nc_ctpl) {
        if ($admin_mode && $cc_env['Edit_Class_Template']) {
            $cc_env = $nc_core->sub_class->get_by_id($cc, null, $cc_env['Edit_Class_Template']);
        }

        if ($inside_admin && $cc_env['Admin_Class_Template']) {
            $cc_env = $nc_core->sub_class->get_by_id($cc, null, $cc_env['Admin_Class_Template']);
        }
    }

    $sub = $cc_env['Subdivision_ID'];

    $mirror_cc = $cc_env['SrcMirror'];

    if ($mirror_cc) {
        $mirror_data = $nc_core->sub_class->get_by_id($mirror_cc);
        $cc = $mirror_data['Sub_Class_ID'];
        $sub = $mirror_data['Subdivision_ID'];
        $catalogue = $mirror_data['Catalogue_ID'];
    }

    $classPreview = nc_array_value($_GET, 'classPreview') + 0;
    // Если режим предпросмотра то заменим $current_cc данными из сессии.
    if ($classPreview == ($cc_env["Class_Template_ID"] ? $cc_env["Class_Template_ID"] : $cc_env["Class_ID"]) && (isset($_SESSION["PreviewClass"][$classPreview])) && ($_SESSION["PreviewClass"][$classPreview])) {
        $magic_gpc = function_exists('get_magic_quotes_gpc') && get_magic_quotes_gpc();
        foreach ($_SESSION["PreviewClass"][$classPreview] as $tkey => $tvalue) {
            $cc_env[$tkey] = $magic_gpc ? stripslashes($tvalue) : $tvalue;
        }
        // Отключим кеширование в режиме предпросмотра.
        $cc_env['Cache_Access_ID'] = 2;
    }


    // cache section
    if (nc_module_check_by_keyword("cache") && $current_cc['Cache_Access_ID'] == 1) {
        // startup values
        $cached_data = "";
        $cached_eval = false;

        try {
            $nc_cache_full = nc_cache_full::getObject();
            // cache auth addon string
            $cache_for_user = $nc_cache_full->authAddonString($cc_env['CacheForUser'], $current_user);
            // check cached data
            $cached_result = $nc_cache_full->read($classID, $message, $REQUEST_URI . $cache_for_user, $current_cc['Cache_Lifetime']);
            if ($cached_result != -1) {
                // get cached parameters
                list ($cached_data, $cached_eval, $cache_vars) = $cached_result;
                // debug info
                $cache_debug_info = "Read, sub[" . $sub . "], cc[" . $cc . "], Access_ID[" . $current_cc['Cache_Access_ID'] . "], Lifetime[" . $current_cc['Cache_Lifetime'] . "], bytes[" . strlen($cached_data) . "], eval[" . (int)$cached_eval . "]";
                $nc_cache_full->debugMessage($cache_debug_info, __FILE__, __LINE__);

                // extract cached object variables
                if (!empty($cache_vars)) {
                    extract($cache_vars);
                    if ($f_ncTitle) {
                        $nc_core->page->set_metatags('title', $f_ncTitle);
                    } else {
                        $nc_core->page->set_metatags('title', $f_title);
                    }
                    if ($f_ncKeywords) {
                        $nc_core->page->set_metatags('keywords', $f_ncKeywords);
                    }
                    if ($f_ncDescription) {
                        $nc_core->page->set_metatags('description', $f_ncDescription);
                    }
                    if ($f_ncSMO_Title) {
                        $nc_core->page->set_metatags('smo_title', $f_ncSMO_Title);
                    }
                    if ($f_ncSMO_Description) {
                        $nc_core->page->set_metatags('smo_description', $f_ncSMO_Description);
                    }
                    if ($f_ncSMO_Image) {
                        $nc_core->page->set_metatags('smo_image', $f_ncSMO_Image);
                    }
                }

                // return cache if cache data without "nocache" blocks
                if (!$cached_eval) {
                    echo $cached_data;
                    break;
                }
            }
            // set marks into the fields
            $no_cache_marks = $nc_cache_full->nocacheStore($cc_env);
        } catch (Exception $e) {
            // for debug
            $nc_cache_full->errorMessage($e);
        }
    }

    // component custom settings
    $cc_settings = &$cc_env["Sub_Class_Settings"];

    $ignore_user = true;
    $user_table_mode = !empty($user_table_mode);

    $message_level_count = 0;
    $parent_message_tree[$message_level_count] = $message;

    if (!$user_table_mode) {
        while ($parent_message_tree[$message_level_count]) {
            $parent_mess_res = $db->get_var("SELECT `Parent_Message_ID` FROM `Message" . $classID . "` WHERE `Message_ID` = '" . (int)$parent_message_tree[$message_level_count] . "'");
            if ($db->num_rows) {
                $message_level_count++;
                $parent_message_tree[$message_level_count] = $parent_mess_res;
            } else {
                break;
            }
        }
    }

    if ($cc_env['File_Mode']) {
        $file_class = new nc_tpl_component_view($nc_core->CLASS_TEMPLATE_FOLDER, $nc_core->db);
        $file_class->load($cc_env['Real_Class_ID'], $cc_env['File_Path'], $cc_env['File_Hash']);
        $file_class->include_all_required_assets();
        $is_multipurpose = $nc_core->component->get_by_id($cc_env['Real_Class_ID'], 'IsMultipurpose');

        if ($is_multipurpose) {
            $parent_file_class = new nc_tpl_component_view($nc_core->CLASS_TEMPLATE_FOLDER, $nc_core->db);
            $parent_class = $nc_core->component->get_by_id($cc_env['Class_ID']);
            $parent_file_class->load($cc_env['Class_ID'], $parent_class['File_Path'], $parent_class['File_Hash']);
        } else {
            $parent_file_class = $file_class;
        }

        if ($is_multipurpose) {
            $nc_parent_field_path = $parent_file_class->get_field_path('Settings');
        } else {
            $nc_parent_field_path = $file_class->get_parent_field_path('Settings');
        }

        $nc_field_path = $file_class->get_field_path('Settings');

        try {
            if (PHP_MAJOR_VERSION === 5) {
                nc_check_php_file($nc_field_path);
            }
            if ((include $nc_field_path) === false) {
                throw new nc_tpl_exception_include($nc_field_path);
            }
        } catch (Throwable $e) { // PHP 7
            $file_class->print_error_message($nc_field_path, $e);
            // Если возникла ошибка в системных настройках, запрос производиться не будет, т. к. из-за этого
            // стандартным запросом могут быть выведены данные, не предназначенные для глаз пользователя
            break; // выход из do()
        } catch (Exception $e) { // PHP 5
            $file_class->print_error_message($nc_field_path, $e);
            break;
        }

        $nc_parent_field_path = null;
        $nc_field_path = null;
    } else {
        if ($cc_env["Settings"]) {
            eval(nc_check_eval($cc_env["Settings"]));
        }
    }


    // cache eval section
    if (nc_module_check_by_keyword("cache") && $current_cc['Cache_Access_ID'] == 1 && is_object($nc_cache_full) && $cached_eval && $cached_result != -1) {
        eval(nc_check_eval("echo \"" . $cached_data . "\";"));
        break;
    }

    $component = $nc_core->get_component($cc_env['System_Table_ID'] ? 'User' : $classID);
    $field_vars = $component->get_fields_vars();
    $date_field = $component->get_date_field();
    $nc_path_has_keyword = $nc_core->page->get_routing_result('object_keyword') !== null;

    $cond_date = false;
    if (!$ignore_user) {
        $cond_user = " AND a.`User_ID` = '" . (int)$AUTH_USER_ID . "'";
    }
    if (!$admin_mode && !$ignore_check) {
        $cond_mod = " AND a.`Checked` = 1";
    }
    if (!empty($date)) {
        $cond_date = " AND a.`" . $date_field . "` LIKE '" . $db->escape($date) . "%'";
    }

    // При запросе по ключевому слову мы не можем быть уверены, соответствуют ли объекту определённые в ObjectExists()
    // $cc (может отличаться при наличии нескольких блоков одного компонента в разделе — может быть равен ID первого инфоблока
    // этого типа; а также для зеркальных инфоблоков), $sub и $catalogue (могут отличаться для зеркальных инфоблоков)
    if ($nc_path_has_keyword) {
        $nc_possible_infoblock_ids = $nc_possible_subdivision_ids = $nc_possible_site_ids = array();
        foreach ($nc_core->sub_class->get_all_by_subdivision_id($sub) as $nc_infoblock_data) {
            if ($nc_infoblock_data['Class_ID'] == $classID) {
                $nc_possible_infoblock_ids[] = $nc_infoblock_data['SrcMirror'] ?: $nc_infoblock_data['Sub_Class_ID'];

                $nc_possible_subdivision_id = $nc_infoblock_data['SrcMirror'] ? $nc_core->sub_class->get_by_id($nc_infoblock_data['SrcMirror'], 'Subdivision_ID') : $nc_infoblock_data['Subdivision_ID'];
                $nc_possible_subdivision_ids[$nc_possible_subdivision_id] = $nc_possible_subdivision_id;

                $nc_possible_site_id = $nc_infoblock_data['SrcMirror'] ? $nc_core->sub_class->get_by_id($nc_infoblock_data['SrcMirror'], 'Catalogue_ID') : $nc_infoblock_data['Catalogue_ID'];
                $nc_possible_site_ids[$nc_possible_site_id] = $nc_possible_site_id;
            }
        }
        unset($nc_possible_subdivision_id, $nc_infoblock_site_id);
    } else {
        $nc_possible_infoblock_ids = array((int)$cc);
        $nc_possible_subdivision_ids = array((int)$sub);
        $nc_possible_site_ids = array((int)$catalogue);
    }

    if (!$ignore_catalogue && !$user_table_mode) {
        $cond_where .= ' AND sub.`Catalogue_ID` IN (' . (implode(', ', $nc_possible_site_ids) ?: '0') . ')';
    }
    if (!$ignore_sub && !$user_table_mode) {
        $cond_where .= ' AND a.`Subdivision_ID` IN (' . (implode(', ', $nc_possible_subdivision_ids) ?: '0') . ')';
    }
    if (!$ignore_cc && !$user_table_mode) {
        $cond_where .= ' AND a.`Sub_Class_ID` IN (' . (implode(', ', $nc_possible_infoblock_ids) ?: '0') . ')';
    }

    unset($nc_possible_infoblock_ids, $nc_possible_subdivision_ids, $nc_possible_site_ids);

    // query_where
    if ($query_where) {
        $cond_where .= " AND " . $query_where;
    }

    if (!$ignore_all) {
        $message_select =
            "SELECT " . $component->get_fields_query() . ($query_select ? ", " . nc_add_column_aliases($query_select) : "") . "
             FROM (`" . ($user_table_mode ? "User" : "Message" . $classID) . "` AS a
             " . ($query_from ? ", " . $query_from : "") . ")
             " . $component->get_joins() . " " . ($query_join ? " " . $query_join : "") . " 
             WHERE 1=1 " . $cond_where . $cond_user . $cond_mod . $cond_date . "
               AND a.`" . ($user_table_mode ? "User" : "Message") . "_ID` = '" . (int)$message . "'";
    } else {
        $message_select =
            "SELECT " . $query_select . " FROM " . $query_from .
            ($query_join ? " " . $query_join : "") .
            ($query_where ? " WHERE " . $query_where : "");
    }

    $message_select .= $query_group ? " GROUP BY " . $query_group : "";
    $message_select .= $query_having ? " HAVING " . $query_having : "";

    $db->num_rows = 0;

    $resMsg = $db->get_row($message_select, ARRAY_A);

    if (!$db->num_rows) {
        $nc_is_error = $db->is_error;

        if ($nc_is_error) {
            echo NETCAT_FUNCTION_FULL_SQL_ERROR_USER;
        } else {
            nc_send_page_404();
        }

        break;
    }

    // Если объект найден по ключевому слову, $cc будет равен ID первого инфоблока этого типа в разделе, что при наличии
    // нескольких инфоблоков может быть неправильным
    if ($nc_path_has_keyword && !$ignore_cc && $cc != $resMsg['Sub_Class_ID']) {
        $cc = $resMsg['Sub_Class_ID'];
        $current_cc = $nc_core->sub_class->set_current_by_id($cc);
        $nc_core->page->set_routing_result(array('infoblock_id' => $cc) + $nc_core->page->get_routing_result());
    }

    $fetch_row = "";

    /*
     * в списке объектов при fs переменные экстрактятся, и соответственно не работает $result_vars
     * нужно здесь тоже сделать экстракт переменных при fs
     */
    if ($cc_env['File_Mode']) {

        if (is_object($resMsg) && method_exists($resMsg, 'to_array')) {
            extract($resMsg->to_array(), EXTR_PREFIX_ALL, 'f');
        } else {
            extract($resMsg, EXTR_PREFIX_ALL, 'f');
            //добываем старые переменные
            extract($component->get_old_vars($resMsg), EXTR_PREFIX_ALL, 'f');
        }
    } else {
        if (!$ignore_all) {
            $fetch_row = "list(" . $field_vars . ($result_vars ? ", " . $result_vars : "") . ") = array_values(\$resMsg);";
        } else {
            $fetch_row = "list(" . $result_vars . ") = array_values(\$resMsg);";
        }

        eval($fetch_row);
        if ($ignore_link || $cc_env['SrcMirror']) {
            $subLink = nc_folder_path($cc_env['Subdivision_ID']);
            $cc_keyword = $cc_env['EnglishName'];
        }
    }

    // Режим отображения страницы (стандартный, из блоков, либо нет страницы полного вывода)
    if ($ignore_all) {
        list($f_ncFullPageMode, $f_ncFullPageCreated) = $db->get_row(
            "SELECT `ncFullPageMode`, `ncFullPageCreated`
             FROM `Message$classID`
             WHERE `Message_ID` = $message",
            ARRAY_N
        );
    }
    
    if ($f_ncFullPageMode === 'inherit') {
//@todo get from Sub_Class (can be inherited from Class)
        $f_ncFullPageMode = 'template';
    }

    if ($f_ncFullPageMode === 'none') {
        nc_send_page_404();
    }
    
    if (!$user_table_mode && !$ignore_cc && $cc != $f_Sub_Class_ID) {
        nc_send_page_404();
    }

    // Прежние названия переменных в fetch_row:
    $f_RowID = $resMsg[$user_table_mode ? 'User_ID' : 'Message_ID'];
    $f_UserID = $f_User_ID;
    $f_LastUserID = isset($f_LastUser_ID) ? $f_LastUser_ID : null;
    $f_UserGroup = isset($f_PermissionGroup_ID) ? $f_PermissionGroup_ID : null;
    $Hidden_URL = isset($f_Hidden_URL) ? $f_Hidden_URL : null;

    $cc_env['convert2txt'] = $component->get_convert2txt_code($cc_env);
    eval($cc_env['convert2txt']);


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
            if (empty($_cache['classificator'][$multilist_filed['table']])) {
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

    // Prepare and extract file fields variables
    $hybrid_component_id = ($user_table_mode ? 'User' : $classID);
    $nc_core->file_info->cache_object_data($hybrid_component_id, $resMsg);
    extract($nc_core->file_info->get_all_object_file_variables($hybrid_component_id, $message));

    // Prepare and extract 'multifile' fields variables
    $multifile_fields = nc_get_multifile_field_values($hybrid_component_id, $message);

    if ($multifile_fields) {
        foreach ($multifile_fields as $field_name => $field_value) {
            /** @var nc_multifield $field_value */
            if (!isset(${'f_' . $field_name . '_tpl'})) {
                ${'f_' . $field_name . '_tpl'} = null;
            }
            ${'f_' . $field_name} = $field_value->set_template(${'f_' . $field_name . '_tpl'});;
        }
    }

    // user group
    if ($user_table_mode) {
        $nc_user_group = $db->get_results("SELECT ug.`User_ID`, ug.`PermissionGroup_ID`, g.`PermissionGroup_Name`
                                     FROM `User_Group` AS ug,`PermissionGroup` AS g
                                     WHERE User_ID = '" . intval($message) . "'
                                     AND g.`PermissionGroup_ID` = ug.`PermissionGroup_ID` ", ARRAY_A);
        if (!empty($nc_user_group)) {
            foreach ($nc_user_group as $v) {
                $f_PermissionGroup[$v['PermissionGroup_ID']] = $v['PermissionGroup_Name'];
            }
        }
        unset($nc_user_group);
    }

    $nc_core->page->update_last_modified_if_timestamp_is_newer(strtotime($f_Created));
    $nc_core->page->update_last_modified_if_timestamp_is_newer(strtotime($f_LastUpdated));

    // date values
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

    if ($date_field) {
        if (!$admin_mode) {
            $date_link =
                ${'f_' . $date_field . '_year'} . '/' .
                ${'f_' . $date_field . '_month'} . '/' .
                ${'f_' . $date_field . '_day'} . '/';
        } else {
            $date_link =
                ${'f_' . $date_field . '_year'} . '-' .
                ${'f_' . $date_field . '_month'} . '-' .
                ${'f_' . $date_field . '_day'};
        }
    }

    // title и метатеги
    if ($cc_env['TitleTemplate']) {
        eval(nc_check_eval("\$f_title = \"" . nc_array_value($cc_env, 'TitleTemplate') . "\";"));
    }
    $nc_core->page->set_metatags('title', ($f_ncTitle ?: $f_title));
    if ($cc_env['UseAltTitle'] && $f_title) {
        $nc_core->page->set_metatags('title', $f_title);
    }
    if ($f_ncKeywords) {
        $nc_core->page->set_metatags('keywords', $f_ncKeywords);
    }
    if ($f_ncDescription) {
        $nc_core->page->set_metatags('description', $f_ncDescription);
    }
    if ($f_ncSMO_Title) {
        $nc_core->page->set_metatags('smo_title', $f_ncSMO_Title);
    }
    if ($f_ncSMO_Description) {
        $nc_core->page->set_metatags('smo_description', $f_ncSMO_Description);
    }
    if ($f_ncSMO_Image) {
        $nc_core->page->set_metatags('smo_image', $f_ncSMO_Image);
    }

    $nc_core->page->set_h1($f_title);

    if (!empty($no_cache_marks) || $f_title || $f_ncTitle || $f_ncKeywords || $f_ncDescription || $f_ncSMO_Title || $f_ncSMO_Description || $f_ncSMO_Image) {
        // caching variables array
        $cache_vars = array();
        $cache_vars['f_title'] = $f_title;
        if ($f_ncTitle) {
            $cache_vars['f_ncTitle'] = $f_ncTitle;
        }
        if ($f_ncKeywords) {
            $cache_vars['f_ncKeywords'] = $f_ncKeywords;
        }
        if ($f_ncDescription) {
            $cache_vars['f_ncDescription'] = $f_ncDescription;
        }
        if ($f_ncSMO_Title) {
            $cache_vars['f_ncSMO_Title'] = $f_ncSMO_Title;
        }
        if ($f_ncSMO_Description) {
            $cache_vars['f_ncSMO_Description'] = $f_ncSMO_Description;
        }
        if ($f_ncSMO_Image) {
            $cache_vars['f_ncSMO_Image'] = $f_ncSMO_Image;
        }

        // get variables names string
        preg_match("/^list\((.*?)\).*?$/", $fetch_row, $matches);
        if (nc_array_value($matches, 1)) {
            // variables by name array
            $cache_vars_name = explode(",", $matches[1]);
            if (!empty($cache_vars_name)) {
                // correcting
                foreach ($cache_vars_name as $k => $v) {
                    $_variable_name = trim(str_replace('$', "", $v));
                    $cache_vars[$_variable_name] = $$_variable_name;
                    // clear
                    unset($_variable_name);
                }
                // clear
                unset($cache_vars_name);
            }
        }
    }

    $subLink = nc_folder_path($cc_env['Subdivision_ID']);
    $ccLink = nc_infoblock_path($cc_env['Sub_Class_ID']);

    $isNaked = isset($isNaked) ? $isNaked : $cc_env['isNaked'];

    $nc_add_block_markup =
        (!$isNaked || $admin_mode) && // не добавлять разметку в режиме просмотра, если есть isNaked
        $nc_core->component->can_add_block_markup($cc_env['Class_Template_ID'] ?: $cc_env['Class_ID']);

    if ($nc_add_block_markup) {
        $nc_core->page->register_component_usage($cc_env['Class_ID'], $cc_env['Class_Template_ID']);
        $nc_component_css_class = $nc_core->component->get_css_class_name($cc_env['Class_Template_ID'] ?: $cc_env['Class_ID'], $current_cc['Class_ID']);
        $nc_component_css_selector = '.' . str_replace(' ', '.', $nc_component_css_class);
        $nc_block_id = nc_make_block_id("full");
    } else {
        $nc_component_css_class = $nc_component_css_selector = $nc_block_id = null;
    }

    if (!$cc_env['File_Mode']) {
        // get component body
        $component_body = $cc_env['RecordTemplateFull'] . $cc_env['Settings'];
        // other forms
        $cc_env["AddTemplate"] = $cc_env["AddTemplate"] ? $cc_env["AddTemplate"] : $component->add_form($catalogue, $sub, $cc);
        $cc_env["FullSearchTemplate"] = $cc_env["FullSearchTemplate"] ? $cc_env["FullSearchTemplate"] : $component->search_form(1);
    } else {
        // get component body
        $component_body = nc_check_file($file_class->get_field_path('RecordTemplateFull')) ? nc_get_file($file_class->get_field_path('RecordTemplateFull')) : null;
        $component_body .= nc_check_file($file_class->get_field_path('Settings')) ? nc_get_file($file_class->get_field_path('Settings')) : null;
    }

    // FIXME: Сломано (не определены переменные-аргументы showSearchForm):
    $nc_search_form = null;

    if ($routing_module_enabled) {
        $routing_object_parameters = array(
            'site_id' => $catalogue,
            'folder' => substr($f_Hidden_URL, strlen($SUB_FOLDER)), // $f_Hidden_URL включает SUB_FOLDER
            'folder_id' => $f_Subdivision_ID,
            'date' => $date_field ? nc_filter_date_for_path(${"f_$date_field"}) : null,
            'infoblock_id' => $f_Sub_Class_ID,
            'infoblock_keyword' => $cc_env['EnglishName'],
            'object_id' => $f_RowID,
            'object_keyword' => $f_Keyword,
            'action' => 'full',
            'format' => 'html',
        );
    } else {
        $routing_object_parameters = null;
    }

    if ($admin_mode && $check_auth) {
        if ($nc_core->inside_admin) {
            $fullLink = $routing_module_enabled
                ? nc_routing::get_object_path($classID, $routing_object_parameters)
                : $subLink . ($f_Keyword ? $f_Keyword : $cc_env['EnglishName'] . "_" . $f_RowID) . ".html";

            $UI_CONFIG->replace_view_link_url($fullLink);
        }
        $addLink = $admin_url_prefix . "add.php?" . ($nc_core->inside_admin ? "inside_admin=1&amp;" : "") . "catalogue=" . $catalogue . "&amp;sub=" . $sub . "&amp;cc=" . $cc;
        // full link section
        $fullLink = $admin_url_prefix . "full.php?" . ($nc_core->inside_admin ? "inside_admin=1&" : "") . "catalogue=" . $catalogue . "&sub=" . $sub . "&cc=" . $cc . "&message=" . $f_RowID;

        if (!isset($dateLink)) {
            $dateLink = null;
        }
        $fullDateLink = $fullLink . $dateLink;
        // ID объекта в шаблоне
        $f_AdminButtons_id = $f_RowID;

        if (!isset($f_Priority)) {
            $f_Priority = null;
        }
        // Приоритет объекта
        $f_AdminButtons_priority = $f_Priority;
        // ID добавившего пользователя
        $f_AdminButtons_user_add = $f_UserID;
        // ID изменившего пользователя
        $f_AdminButtons_user_change = ($f_LastUserID ? $f_LastUserID : "");
        // копировать объект
        $f_AdminButtons_copy = $ADMIN_PATH . "objects/copy_message.php?catalogue=" . $catalogue . "&sub=" . $sub . "&cc=" . $cc . "&classID=" . $classID . "&message=" . $f_RowID;
        // изменить
        if (!isset($curPos)) {
            $curPos = null;
        }
        $f_AdminButtons_change = $SUB_FOLDER . $HTTP_ROOT_PATH . "message.php?" . ($nc_core->inside_admin ? "inside_admin=1&" : "") . "catalogue=" . $catalogue . "&sub=" . $sub . "&cc=" . $cc . "&message=" . $f_RowID . "&curPos=" . $curPos;
        $editLink = $f_AdminButtons_change;
        // черновики
        if ($nc_core->get_settings('AutosaveUse') == 1) {
            $f_AdminButtons_version = $SUB_FOLDER . $HTTP_ROOT_PATH . "message.php?" . ($nc_core->inside_admin ? "inside_admin=1&" : "") . "isVersion=1&restore=1&catalogue=" . $catalogue . "&sub=" . $sub . "&cc=" . $cc . "&message=" . $f_RowID . "&curPos=" . $curPos;
            $versionLink = $f_AdminButtons_version;
        } else {
            $f_AdminButtons_version = $versionLink = null;
        }
        // удалить
        $f_AdminButtons_delete = $SUB_FOLDER . $HTTP_ROOT_PATH . "message.php?" . ($nc_core->inside_admin ? "inside_admin=1&" : "") . "catalogue=" . $catalogue . "&sub=" . $sub . "&cc=" . $cc . "&message=" . $f_RowID . "&delete=1&curPos=" . $curPos;
        $deleteLink = $f_AdminButtons_delete;
        $dropLink = $deleteLink . "&posting=1";
        if (!isset($any_url_prefix)) {
            $any_url_prefix = null;
        }
        // включить-выключить
        $f_AdminButtons_check = $any_url_prefix . $SUB_FOLDER . $HTTP_ROOT_PATH . "message.php?" . ($nc_core->inside_admin ? "inside_admin=1&" : "") . "catalogue=" . $catalogue . "&sub=" . $sub . "&cc=" . $cc . "&classID=" . $classID . "&message=" . $f_RowID . "&checked=" . ($f_Checked ? 1 : 2) . "&posting=1&curPos=" . $curPos . ($admin_mode ? "&admin_mode=1" : "");
        $checkedLink = $f_AdminButtons_check;
        $f_AdminButtons = "";
        if (!$user_table_mode) {
            if ($system_env['AdminButtonsType']) {
                eval(nc_check_eval("\$f_AdminButtons = \"" . $system_env['AdminButtons'] . "\";"));
            } else if ($nc_add_block_markup) {
                $nc_show_landing_buttons = $admin_mode && nc_module_check_by_keyword('landing') && nc_landing::get_instance()->has_presets_for_component($classID);
                $f_AdminButtons = nc_get_AdminButtonsMultiBlock(
                    $f_RowID,
                    $f_Checked,
                    $f_ncFullPageMode === 'custom' ? '' : $f_AdminButtons_check,
                    // $f_AdminButtons_uncheck,
                    $f_ncFullPageMode === 'custom' ? '' : $f_AdminButtons_copy,
                    $f_AdminButtons_change,
                    $f_ncFullPageMode === 'custom' ? '' : $f_AdminButtons_delete,
                    $f_AdminButtons_version,
                    $cc,
                    $sub,
                    $classID,
                    $f_ncFullPageMode !== 'custom',
                    false
                );
            } else {
                $f_AdminButtons_buttons = "
                    <li><span>" . $f_RowID . "</span></li>
                    <li><a onClick='parent.nc_action_message(this.href); return false;' href='" . $f_AdminButtons_check . "'>
                        <span class='nc-text-" . ($f_Checked ? 'green' : 'red') . "'>" . ($f_Checked ? NETCAT_MODERATION_OBJ_ON : NETCAT_MODERATION_OBJ_OFF) . "</span>
                    </a></li>
                    <li><a href='#' onclick=\"window.open('" . $f_AdminButtons_copy . "', 'nc_popup_test1', 'width=800,height=500,menubar=no,resizable=no,scrollbars=no,toolbar=no,resizable=yes'); return false;\">
                        <i class='nc-icon nc--copy' title='" . NETCAT_MODERATION_COPY_OBJECT . "'></i>
                    </a></li>";

                if (nc_module_check_by_keyword('landing') && nc_landing::get_instance()->has_presets_for_component($classID)) {
                    $f_AdminButtons_buttons .= nc_get_AdminButtons_landing_button($catalogue, $classID, $f_RowID);
                }

                $f_AdminButtons_buttons .= "
                        <li><a onClick='nc.load_dialog(this.href); return false;' href='" . $f_AdminButtons_change . "'>
                            <i class='nc-icon nc--edit' title='" . NETCAT_MODERATION_CHANGE . "'></i>
                        </a></li>";
                if ($nc_core->get_settings('AutosaveUse') == 1) {
                    $f_AdminButtons_buttons .= "<li><a onClick='parent.nc_form(this.href); return false;' href='" . $f_AdminButtons_version . "'>
                            <i class='nc-icon nc--clock' title='" . NETCAT_MODERATION_VERSION . "'></i>
                            </a></li>";
                }
                $f_AdminButtons_buttons .= "<li><a onClick='parent.nc_action_message(this.href); return false;' href='" . $f_AdminButtons_delete . "'>
                     <i class='nc-icon nc--remove' title='" . NETCAT_MODERATION_DELETE . "'></i>
                    </a></li>";

                $f_AdminButtons = "<ul class='nc-toolbar nc--left" . ($f_Checked ? "" : " nc--disabled") . "'>";
                #$f_AdminButtons.= "<div class='nc_idtab_handler' ".(nc_Core::get_object()->inside_admin ? '' : "style='display: none;' ")."id='message" . $classID . "-" . $f_RowID . "_handler'></div>";
                $f_AdminButtons .= $f_AdminButtons_buttons;
                $f_AdminButtons .= "</ul>";
                $f_AdminButtons .= "<div class='nc--clearfix'></div>";
            }
        }
    } else {
        // модуль маршрутизации: нет аналога для $msgLink
        $msgLink = $f_Keyword ?: $cc_env['EnglishName'] . "_" . $f_RowID;

        if ($routing_module_enabled) {
            $fullLink = nc_routing::get_object_path($classID, $routing_object_parameters);
            $fullRSSLink = $cc_env['AllowRSS']
                ? nc_routing::get_object_path($classID, $routing_object_parameters, 'full', 'rss')
                : "";
            $fullDateLink = nc_routing::get_object_path($classID, $routing_object_parameters, 'full', 'html', true, null);
            $editLink = nc_routing::get_object_path($classID, $routing_object_parameters, 'edit');
            if ($nc_core->get_settings('AutosaveUse') == 1) {
                $versionLink = nc_routing::get_object_path($classID, $routing_object_parameters, 'version');
            }
            $deleteLink = nc_routing::get_object_path($classID, $routing_object_parameters, 'delete');
            $dropLink = nc_routing::get_object_path($classID, $routing_object_parameters, 'drop') .
                ($nc_core->token->is_use('drop') ? "?" . $nc_core->token->get_url() : "");
            $checkedLink = nc_routing::get_object_path($classID, $routing_object_parameters, 'checked');
            $subscribeMessageLink = nc_routing::get_object_path($classID, $routing_object_parameters, 'subscribe');
            // действия с компонентом
            $subscribeLink = nc_routing::get_infoblock_path($cc, 'subscribe');
            $searchLink = nc_routing::get_infoblock_path($cc, 'search');
            $addLink = nc_routing::get_infoblock_path($cc, 'add');
        } else {
            $fullLink = $subLink . $msgLink . ".html";
            $fullRSSLink = $cc_env['AllowRSS'] ? $subLink . $msgLink . ".rss" : "";

            $fullDateLink = $subLink . (isset($dateLink) ? $dateLink : '') . $msgLink . ".html";
            $editLink = $subLink . "edit_" . $msgLink . ".html"; // ссылка для редактирования
            if ($nc_core->get_settings('AutosaveUse') == 1) {
                $versionLink = $subLink . "version_" . $msgLink . ".html";
            }
            $deleteLink = $subLink . "delete_" . $msgLink . ".html"; // ссылка для удаления
            $dropLink = $subLink . "drop_" . $msgLink . ".html" .
                ($nc_core->token->is_use('drop') ? "?" . $nc_core->token->get_url() : ""); //            удаления без подтверждения
            $checkedLink = $subLink . "checked_" . $msgLink . ".html"; // ссылка для  включения/выключения
            $subscribeMessageLink = $subLink . "subscribe_" . $msgLink . ".html"; // подписка на объект
            // действия с компонентом
            $subscribeLink = $SUB_FOLDER . $current_cc['Hidden_URL'] . "subscribe_" . $current_cc['EnglishName'] . ".html";
            $searchLink = $SUB_FOLDER . $current_cc['Hidden_URL'] . "search_" . $current_cc['EnglishName'] . ".html";
            $addLink = $SUB_FOLDER . $current_cc['Hidden_URL'] . "add_" . $current_cc['EnglishName'] . ".html";
        }
    }


    /* Следующий и предыдущий объект */
    $nc_next_object = "";
    $nc_prev_object = "";

    if (strpos($component_body, '$nc_next_object') !== false || strpos($component_body, '$nc_prev_object') !== false) {
        // сортировка и запрос
        $sort_by = $query_order ? $query_order : ($cc_env['SortBy'] ? $cc_env['SortBy'] : "a." . ($user_table_mode ? "`" . $AUTHORIZE_BY . "`" : "`Priority` DESC") . ", a.`LastUpdated` DESC");
        $nc_res = $db->get_results(
            "SELECT a.`Message_ID`, a.`Keyword`, a.`Sub_Class_ID`
            " . ($date_field ? ", DATE_FORMAT(a.`$date_field`, '%Y-%m-%d') AS Date" : "") . "
            FROM `" . ($user_table_mode ? "User" : "Message" . $classID) . "` AS a
            " . $component->get_joins() . "
            WHERE 1=1 " . $cond_where . $cond_mod .
            (empty($ignore_parent) ? ' AND a.`Parent_Message_ID` = 0' : '') .
            " ORDER BY " . $sort_by,
            ARRAY_A
        );

        // предыдущий и следующий объект находятся рядом с текущим
        for ($i = 0; $i < $db->num_rows; $i++) {
            if ($nc_res[$i]['Message_ID'] == $message || $nc_res[$i]['Message_ID'] == $resMsg['Parent_Message_ID']) {
                // предыдущий объект
                if ($i > 0) {
                    if ($admin_mode) {
                        $nc_prev_object = $admin_url_prefix .
                            "full.php?" .
                            ($nc_core->inside_admin ? "inside_admin=1&amp;" : "") .
                            "catalogue=" . $catalogue .
                            "&amp;sub=" . $sub .
                            "&amp;cc=" . $cc .
                            "&amp;message=" . $nc_res[$i - 1]['Message_ID'] .
                            ($date ? "&amp;date=" . $nc_res[$i - 1]['Date'] : "");
                    } else if ($routing_module_enabled) {
                        $nc_prev_object = nc_routing::get_object_path(
                            $classID,
                            array(
                                'site_id' => $catalogue,
                                'folder' => $cc_env['Hidden_URL'],
                                'folder_id' => $sub,
                                'infoblock_id' => $nc_res[$i - 1]['Sub_Class_ID'],
                                'object_id' => $nc_res[$i - 1]['Message_ID'],
                                'object_keyword' => $nc_res[$i - 1]['Keyword'],
                                'date' => $nc_res[$i - 1]['Date'],
                            ),
                            'full',
                            'html',
                            (bool)$date
                        );
                    } else {
                        $nc_prev_object = $cc_env['Hidden_URL'] .
                            ($date ? implode('/', explode('-', $nc_res[$i - 1]['Date'])) . "/" : "") .
                            ($nc_res[$i - 1]['Keyword'] ?: $cc_env['EnglishName'] . "_" . $nc_res[$i - 1]['Message_ID']) .
                            ".html";
                    }
                }

                // следующий объект
                if ($i < $db->num_rows - 1) {
                    if ($admin_mode) {
                        $nc_next_object = $admin_url_prefix .
                            "full.php?" .
                            ($nc_core->inside_admin ? "inside_admin=1&amp;" : "") .
                            "catalogue=" . $catalogue .
                            "&amp;sub=" . $sub .
                            "&amp;cc=" . $cc .
                            "&amp;message=" . $nc_res[$i + 1]['Message_ID'] .
                            ($date ? "&amp;date=" . $nc_res[$i + 1]['Date'] : "");
                    } else if ($routing_module_enabled) {
                        $nc_next_object = nc_routing::get_object_path(
                            $classID,
                            array(
                                'site_id' => $catalogue,
                                'folder' => $cc_env['Hidden_URL'],
                                'folder_id' => $sub,
                                'infoblock_id' => $nc_res[$i + 1]['Sub_Class_ID'],
                                'object_id' => $nc_res[$i + 1]['Message_ID'],
                                'object_keyword' => $nc_res[$i + 1]['Keyword'],
                                'date' => $nc_res[$i + 1]['Date'],
                            ),
                            'full',
                            'html',
                            (bool)$date
                        );
                    } else {
                        $nc_next_object = $cc_env['Hidden_URL'] .
                            ($date ? implode('/', explode('-', $nc_res[$i + 1]['Date'])) . "/" : "") .
                            ($nc_res[$i + 1]['Keyword'] ?: $cc_env['EnglishName'] . "_" . $nc_res[$i + 1]['Message_ID']) .
                            ".html";
                    }
                }
                break;
            }
        }
        unset($nc_res);
    }

    // add form from the AddTemplate
    if (nc_strpos($component_body, '$addForm') !== false) {
        if ($cc_env['File_Mode']) {
            if ($is_multipurpose) {
                $nc_parent_field_path = $parent_file_class->get_field_path('AddTemplate');
            } else {
                $nc_parent_field_path = $file_class->get_parent_field_path('AddTemplate');
            }
            $nc_field_path = $file_class->get_field_path('AddTemplate');

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
            $addForm = ob_get_clean();
            if ($nc_add_block_markup && $addForm) {
                $addForm = "<div class='tpl-block-add-form $nc_component_css_class'>$addForm</div>";
            }

            $nc_parent_field_path = null;
            $nc_field_path = null;
        } else {
            eval(nc_check_eval("\$addForm = \"" . $cc_env["AddTemplate"] . "\";"));
        }
    }

    // search form from the FullSearchTemplate
    if (nc_strpos($component_body, '$searchForm') !== false) {
        if ($cc_env['File_Mode']) {
            if ($is_multipurpose) {
                $nc_parent_field_path = $parent_file_class->get_field_path('FullSearchTemplate');
            } else {
                $nc_parent_field_path = $file_class->get_parent_field_path('FullSearchTemplate');
            }
            $nc_field_path = $file_class->get_field_path('FullSearchTemplate');

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
            $searchForm = ob_get_clean();
            if ($nc_add_block_markup && $searchForm) {
                $searchForm = "<div class='tpl-block-search-form $nc_component_css_class'>$searchForm</div>";
            }

            $nc_parent_field_path = null;
            $nc_field_path = null;
        } else {
            eval(nc_check_eval("\$searchForm = \"" . $cc_env["FullSearchTemplate"] . "\";"));
        }
    }

    // exterminate
    unset($component_body);

    // save changed $current_cc["RecordTemplateFull"]
    // this array is updated in $template_header
    $cache_record_template_full = $cc_env['RecordTemplateFull'];

    if (nc_module_check_by_keyword("cache") && $current_cc['Cache_Access_ID'] != 2 && !$cc_env['File_Mode']) {
        ob_start("nc_full_message_parse_buffer");
        eval(nc_check_eval("echo \"" . $cache_record_template_full . "\";"));
        ob_end_flush();
    } else {
        if ($cc_env['File_Mode']) {
            if ($f_ncFullPageMode === 'custom') {
                if (empty($f_ncFullPageCreated)) {
                    // Ещё не были созданы блоки для страницы полного вывода — создаём их
                    $component->create_default_full_page_block(
                        $f_Message_ID,
                        array(
                            'Class_Template_ID' => $cc_env['Class_Template_ID'],
                        )
                    );
                }
                // Выводим блоки страницы полного вывода
                nc_partial_object_partial::set_object_variables(get_defined_vars());
                $nc_core->page->include_custom_full_page_mixin_styles();

                if (!empty($isNaked) && !empty($cc_only)) {
                    // Частичная перезагрузка для режима редактирования
                    echo nc_objects_list(0, $cc_only);
                } else {
                    echo nc_insert_area_blocks($component->get_full_page_area_keyword($f_Message_ID), "nc-area-full-page-custom");
                }
            } else {
                if ($admin_mode && !$GLOBALS['isNaked']) {
                    echo "<div id='nc_admin_mode_content{$cc}' class='nc_admin_mode_content'>";
                }

                // «Обычная» страница полного вывода
                if ($nc_add_block_markup) {
                    echo "<div class='tpl-block-full " . ($admin_mode ? 'nc-infoblock-object ' : '') . $nc_component_css_class . "'" .
                         " id='" . $nc_block_id . "'>";
                }

                if ($is_multipurpose) {
                    $nc_parent_field_path = $parent_file_class->get_field_path('RecordTemplateFull');
                } else {
                    $nc_parent_field_path = $file_class->get_parent_field_path('RecordTemplateFull');
                }
                $nc_field_path = $file_class->get_field_path('RecordTemplateFull');

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

                if ($nc_add_block_markup) {
                    echo "</div>";
                }

                if ($admin_mode && !$GLOBALS['isNaked']) {
                    echo "</div>";
                }
            }
        } else {
            eval(nc_check_eval("echo \"" . $cc_env['RecordTemplateFull'] . "\";"));
        }
    }

} while (false);

$nc_result_msg = ob_get_clean();
$nc_core->page->is_processing_template_now();

$lsDisplayType = $nc_core->get_display_type();
if (!$nc_core->inside_admin && !$isNaked && $lsDisplayType == 'shortpage') {
    $nc_result_msg = "<div " . nc_ls_display_container() . ">" . $nc_result_msg . "</div>";
}

if (!$nc_core->inside_admin && $nc_core->input->fetch_get('lsDisplayType') && $lsDisplayType == 'shortpage') {
    $nc_result_msg = $nc_result_msg . nc_include_quickbar_updates();
}


if ($File_Mode) {
    require_once $INCLUDE_FOLDER . 'index_fs.inc.php';

    if (!$templatePreview) {
        if ($nc_core->inside_admin && $UI_CONFIG) {
            // для админки
            $UI_CONFIG->locationHash = "object.view($cc,$message)";
            // edit button
            if (!$UI_CONFIG->actionButtons) {
                $UI_CONFIG->actionButtons = array();
                $UI_CONFIG->actionButtons[] = array(
                    "id" => "editObject",
                    "caption" => NETCAT_MODERATION_CHANGE,
                    "action" => "nc.load_dialog('{$SUB_FOLDER}{$nc_core->HTTP_ROOT_PATH}message.php?inside_admin=1&cc=$cc&message=$message')",
                    "align" => "left",
                );
                /*$UI_CONFIG->actionButtons[] = array(
                    "id" => "deleteObject",
                    "caption" => NETCAT_MODERATION_DELETE,
                    "style" => "delete",
                    "action" => "urlDispatcher.load('object.delete(" . $cc . ", " . $message . ")')"
                );*/
            }

            $template_header = nc_insert_in_head($template_header, $UI_CONFIG->to_json(), true);
        }
    }
}

if (!$File_Mode || $templatePreview) {
    nc_evaluate_template($template_header, $template_footer, $File_Mode);
}

if (!$nc_core->inside_admin && !$isNaked && $sub != $current_catalogue['E404_Sub_ID'] && $lsDisplayType == 'longpage_vertical') {

    $template_header = $template_header . $nc_result_msg . $template_footer;
    $template_footer = $nc_result_msg = '';

    ob_start();

    $subdivision = $nc_core->subdivision->get_current();
    $parentSubdivisionId = $subdivision['Parent_Sub_ID'];

    if (!$parentSubdivisionId) {
        $parentSubdivisionId = $current_catalogue['Title_Sub_ID'];
    }

    $catalogue = $nc_core->catalogue->get_current();
    $subdivision = $nc_core->subdivision->get_by_id($parentSubdivisionId);

    $catalogueId = (int)$catalogue['Catalogue_ID'];
    $subdivisionId = (int)$subdivision['Subdivision_ID'];

    if ($catalogue && $subdivision && $subdivision['Catalogue_ID'] == $catalogueId) {

        if ($catalogue['Title_Sub_ID'] == $subdivisionId || $catalogue['E404_Sub_ID'] == $subdivisionId) {
            $parentSubId = 0;
        } else {
            $parentSubId = $subdivisionId;
        }

        $title_sub_id = (int)$catalogue['Title_Sub_ID'];

        $sql = "SELECT `Subdivision_ID`, `Template_ID` " .
            "FROM `Subdivision` " .
            "WHERE `Catalogue_ID` = {$catalogueId} AND `Parent_Sub_ID` = {$parentSubId} " .
            "AND `DisplayType` IN ('inherit', 'longpage_vertical') " .
            "AND (`Checked` = 1 OR  `Subdivision_ID` = {$title_sub_id})" .
            "ORDER BY `Priority`";

        $innerSubdivisions = (array)$db->get_results($sql, ARRAY_A);

        $i = 0;
        foreach ($innerSubdivisions as $innerSubdivision) {
            $innerSubdivisionId = (int)$innerSubdivision['Subdivision_ID'];

            $sql = "SELECT `Sub_Class_ID` FROM `Sub_Class` " .
                "WHERE `Subdivision_ID` = {$innerSubdivisionId} AND `Checked` = 1 " .
                "ORDER BY `Priority` ASC LIMIT 1";

            $subClass = $db->get_row($sql, ARRAY_A);

            $subdivisionTemplate = $nc_core->catalogue->get_current('Template_ID') != $innerSubdivision['Template_ID'] ?
                $nc_core->template->get_by_id($innerSubdivision['Template_ID']) : null;

            if ($i > 0) {
                echo "<div " . nc_ls_display_pointer($innerSubdivisionId, $sub == $innerSubdivisionId) . "></div>";
            }
            if ($subdivisionTemplate) {
                if ($File_Mode) {
                    $longpageTemplateView = new nc_tpl_template_view($nc_core->TEMPLATE_FOLDER, $nc_core->db);
                    $longpageTemplateView->load_template($innerSubdivision['Template_ID'], $subdivisionTemplate['File_Path']);
                    foreach ($longpageTemplateView->get_all_settings_path_in_array() as $path) {
                        include $path;
                    }
                    $longpageTemplateView->include_all_required_assets();
                    $longpageTemplateView->fill_fields();
                }

                if ($File_Mode) {
                    echo $longpageTemplateView->get_header();
                } else {
                    eval(nc_check_eval("echo \"" . $subdivisionTemplate['Header'] . "\";"));
                }
            }

            echo "<div " . nc_ls_display_container($innerSubdivisionId) . ">";
            if ($sub == $innerSubdivisionId) {
                echo $nc_result_msg;
            } else {
                if ($subClass) {
                    echo nc_objects_list($innerSubdivisionId, $subClass['Sub_Class_ID']);
                }
            }
            echo "</div>";

            if ($subdivisionTemplate) {
                if ($File_Mode) {
                    echo $longpageTemplateView->get_footer();
                } else {
                    eval(nc_check_eval("echo \"" . $subdivisionTemplate['Footer'] . "\";"));
                }
            }
            $i++;
        }
    }

    $nc_result_msg = ob_get_clean();
}

// выполнить необходимую обработку кода страницы и отдать результат пользователю:
$nc_core->output_page($template_header, $nc_result_msg, $template_footer);

function nc_full_message_parse_buffer($buffer) {
    global $REQUEST_URI, $classID, $message, $cache_vars;
    global $MODULE_VARS, $nc_cache_full, $current_sub, $current_cc, $cache_for_user;

    // cache section
    if (nc_module_check_by_keyword("cache") && $current_cc['Cache_Access_ID'] == 1 && is_object($nc_cache_full)) {
        try {
            $bytes = $nc_cache_full->add($classID, $message, $REQUEST_URI . $cache_for_user, $buffer, $cache_vars);
            // debug info
            if ($bytes) {
                $cache_debug_info = "Written, sub[" . $current_sub['Subdivision_ID'] . "], cc[" . $current_cc['Sub_Class_ID'] . "], Access_ID[" . $current_cc['Cache_Access_ID'] . "], Lifetime[" . $current_cc['Cache_Lifetime'] . "], bytes[" . $bytes . "]";
                $nc_cache_full->debugMessage($cache_debug_info, __FILE__, __LINE__, "ok");
            }
        } catch (Exception $e) {
            // for debug
            $nc_cache_full->errorMessage($e);
        }
    }

    return $buffer;
}
