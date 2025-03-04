<?php

$NETCAT_FOLDER = realpath(dirname(__FILE__) . '/..') . DIRECTORY_SEPARATOR;
include_once $NETCAT_FOLDER . "vars.inc.php";
require_once $ROOT_FOLDER . 'connect_io.php';

$nc_core = nc_Core::get_object();

$catalogue = isset($catalogue) ? (int)$catalogue : 0;
$sub = isset($sub) ? (int)$sub : 0;
$cc = isset($cc) ? (int)$cc : 0;
$curPos = isset($curPos) ? (int)$curPos : 0;

if ($curPos < 0) {
    $curPos = 0;
}

if (empty($action)) {
    $action = "index";
    $nc_core->action = "index";
} else {
    $nc_core->action = $action;
}

// подключаем систему и $nc_core
require ($INCLUDE_FOLDER."index.php");

$lsDisplayType = $nc_core->get_display_type();
if (!$nc_core->inside_admin && $lsDisplayType == 'longpage_vertical' && $sub != $current_catalogue['Title_Sub_ID'] && $sub != $current_catalogue['E404_Sub_ID']) {
    $subdivision = $nc_core->subdivision->get_current();
    $parentSubdivisionId = $subdivision['Parent_Sub_ID'];
    $scrollToSubdivision = $sub;

    $template = null;

    if ($parentSubdivisionId) {
        $sub = $parentSubdivisionId;
        $sql = "SELECT `Sub_Class_ID` FROM `Sub_Class` " .
            "WHERE `Subdivision_ID` = {$sub} AND `Checked` = 1 " .
            "ORDER BY `Priority` ASC LIMIT 1";
        $subClass = $nc_core->db->get_row($sql, ARRAY_A);
        $cc = $subClass ? $subClass['Sub_Class_ID'] : 0;

        require ($INCLUDE_FOLDER."index.php");
    } else {
        $sub = $current_catalogue['Title_Sub_ID'];
        $cc = 0;

        require ($INCLUDE_FOLDER."index.php");
        $cc = 0;
        $current_cc = false;
    }
}

if ($nc_core->inside_admin && empty($UI_CONFIG) && $cc) { //без $cc вывалится фатал
    $UI_CONFIG = new ui_config_objects($cc);
}

if (!isset($f_AdminButtons)) {
    $f_AdminButtons = '';
}

if (!isset($f_AdminCommon)) {
    $f_AdminCommon = '';
}

if ($nc_core->admin_mode) {
    $nc_core->page->set_routing_result(array(
        'resource_type' => 'folder',
        'site_id' => $catalogue,
        'folder_id' => $sub,
        'infoblock_id' => null,
        'object_id' => null,
        'action' => 'index',
        'format' => 'html',
        'variables' => $nc_core->input->fetch_get(),
        'date' => null,
        'redirect_to_url' => null,
    ));
}

$template_settings = array();

if (!$templatePreview) {
    if ($nc_core->subdivision->get_current('Template_ID') == $template) {
        $template_settings = $nc_core->subdivision->get_template_settings($sub);
    } else if (isset($nc_use_site_template_settings) && $nc_use_site_template_settings) {
        // используется в скриптах modules/auth
        $template_settings = $nc_core->catalogue->get_template_settings($catalogue);
    } else {
        $template_settings = $nc_core->template->get_settings_default_values($template);
    }

    // подключаем все Settings темплейтов, чтобы шаблоны навигации и пагинации были видны в s_list
    if ($File_Mode) {
        $template_view = $nc_core->template->get_file_template($template);
        $template_view->include_all_required_assets();
        $array_settings_path = $template_view->get_all_settings_path_in_array();
        foreach ($array_settings_path as $path) {
            include_once $path;
        }
    }
}
// для админки
if ($inside_admin && $UI_CONFIG) {
    $UI_CONFIG->locationHash = "object.list({$cc})";
}

// Выводить данные только одного инфоблока
$cc_only = (int)$nc_core->input->fetch_get_post('cc_only');
if ($cc_only) {
    $cc = $cc_only;
    $nc_core->sub_class->set_current_by_id($cc_only);
}

if ($inside_admin) {
    $use_multi_sub_class = 0;
} else {
    $use_multi_sub_class = $nc_core->subdivision->get_current("UseMultiSubClass");

    if ($use_multi_sub_class == 2) {
        $use_multi_sub_class = 0;
    }
}

$nc_main_content = '';
$nc_objects_list_vars = $nc_core->url->get_parsed_url('query') .
    (isset($date) ? "&date=" . $date : "") . "&isMainContent=1";

if ($cc) {
    $nc_area_keyword = $nc_core->sub_class->get_by_id($cc, 'AreaKeyword');
} else {
    $nc_area_keyword = null;
}

// Кнопка добавления блока в режиме редактирования
if ($nc_core->admin_mode && !$nc_core->inside_admin && empty($cc_keyword) && !$cc_array) {
    $nc_main_content .=
        "<div class='nc-infoblock'>" .
        nc_admin_infoblock_insert_toolbar($sub, '', 0, 'first', 0) .
        "</div>";
}

if ($use_multi_sub_class && empty($cc_keyword) && !$nc_area_keyword && !$cc_only) {
    $nc_objects_list_vars .= "&isSubClassArray=1";
    if (count($cc_array)) {
        foreach ($cc_array as $cc) {
            // поскольку компонентов несколько, то current_cc нужно переопределить
            $current_cc = $nc_core->sub_class->set_current_by_id($cc);
            // вывод списка объектов компонента
            $nc_main_content .= nc_objects_list($sub, $cc, $nc_objects_list_vars);
        }
    }
    // current_cc нужно вернуть в первоначальное состояние, чтобы использовать в футере макета
    $current_cc = $nc_core->sub_class->set_current_by_id(nc_array_value($cc_array, 0));
} else if ($cc || $user_table_mode) {
    if ($nc_area_keyword) {
        $current_cc = $nc_core->sub_class->set_current_by_id($cc);
        $nc_main_content .= nc_objects_list($nc_area_keyword, $cc, $nc_objects_list_vars, false, false);
    } else {
        $nc_main_content .= nc_objects_list($sub, $cc, $nc_objects_list_vars);
    }
}

ob_start();

if ($nc_core->inside_admin && !empty($nc_trash_full)) {
    nc_print_status(NETCAT_TRASH_OBJECT_WERE_DELETED_TRASHBIN_FULL, 'info');
}

if ($nc_core->inside_admin && !empty($nc_folder_fail)) {
    nc_print_status(sprintf(NETCAT_TRASH_FOLDER_FAIL, $nc_core->HTTP_TRASH_PATH), 'info');
}

if ($nc_core->inside_admin && !empty($nc_trashed_ids) && is_array($nc_trashed_ids)) {
    $url = http_build_query($_GET, null, '&').'&nc_recovery=1';
    $url = $nc_core->SUB_FOLDER.$nc_core->HTTP_ROOT_PATH.'message.php?'.$url;
    nc_print_status(sprintf(NETCAT_TRASH_OBJECT_IN_TRASHBIN_AND_CANCEL, $nc_core->ADMIN_PATH."trash/", $url), 'info');
    unset($url);
}

$lsDisplayType = $nc_core->get_display_type();

$inputDisplayType = $nc_core->input->fetch_get('lsDisplayType');

if (!$nc_core->inside_admin && !$inputDisplayType && ($lsDisplayType == 'shortpage' || $lsDisplayType == 'longpage_vertical')) {
    echo "<div " . nc_ls_display_container($sub) . ">";
    echo $nc_main_content;
    echo "</div>";
} else {
    $skipTemplate     = $nc_core->input->fetch_get('skipTemplate');

    $subdivisionTemplate = null;

    if ($inputDisplayType == 'shortpage' && (!$skipTemplate))  {
        $catalogue = $nc_core->catalogue->get_current();
        $subdivision = $nc_core->subdivision->get_current();

        $subdivisionTemplate = $nc_core->catalogue->get_current('Template_ID') != $subdivision['Template_ID'] ?
            $nc_core->template->get_by_id($subdivision['Template_ID']) : null;
        // $subdivisionTemplate = $nc_core->template->get_by_id($subdivision['Template_ID']);
    }

    if ($subdivisionTemplate) {
        if ($File_Mode) {
            $shortpageTemplateView = new nc_tpl_template_view($nc_core->TEMPLATE_FOLDER, $nc_core->db);
            $shortpageTemplateView->load_template($subdivision['Template_ID'], $subdivisionTemplate['File_Path']);
            foreach ($shortpageTemplateView->get_all_settings_path_in_array() as $path) {
                include $path;
            }
            $shortpageTemplateView->fill_fields();
        }

        if ($File_Mode) {
            echo $shortpageTemplateView->get_header();
        } else {
            eval(nc_check_eval("echo \"" . $subdivisionTemplate['Header'] . "\";"));
        }
    }

    echo $nc_main_content;
    if ($inputDisplayType === 'shortpage') {
        echo nc_include_quickbar_updates();
    }

    if ($subdivisionTemplate) {
        if ($File_Mode) {
            echo $shortpageTemplateView->get_footer();
        } else {
            eval(nc_check_eval("echo \"" . $subdivisionTemplate['Footer'] . "\";"));
        }
    }
}

$old_current_sub = $current_sub;
$old_current_cc = $current_cc;

//longpage display view
if (!$nc_core->inside_admin && $sub != $current_catalogue['E404_Sub_ID'] && $lsDisplayType == 'longpage_vertical') {
    $catalogue = $nc_core->catalogue->get_current();
    $subdivision = $nc_core->subdivision->get_current();

    $catalogueId = (int)$catalogue['Catalogue_ID'];
    $subdivisionId = (int)$subdivision['Subdivision_ID'];

    if ($catalogue && $subdivision && $subdivision['Catalogue_ID'] == $catalogueId) {

        if ($catalogue['Title_Sub_ID'] == $subdivisionId || $catalogue['E404_Sub_ID'] == $subdivisionId) {
            $parentSubId = 0;
        } else {
            $parentSubId = $subdivisionId;
        }

        $sql = "SELECT `Subdivision_ID`, `Template_ID` " .
            "FROM `Subdivision` " .
            "WHERE `Checked` = 1 AND `Catalogue_ID` = {$catalogueId} AND `Parent_Sub_ID` = {$parentSubId} " .
            "AND `DisplayType` IN ('inherit', 'longpage_vertical') " .
            "ORDER BY `Priority`";

        $innerSubdivisions = (array)$db->get_results($sql, ARRAY_A);

        foreach ($innerSubdivisions as $innerSubdivision) {
            $innerSubdivisionId = (int)$innerSubdivision['Subdivision_ID'];

            if ($subdivisionId == $innerSubdivisionId) {
                continue;
            }

            $sql = "SELECT `Sub_Class_ID` FROM `Sub_Class` " .
                "WHERE `Subdivision_ID` = {$innerSubdivisionId} AND `Checked` = 1 " .
                "ORDER BY `Priority` ASC LIMIT 1";

            $subClass = $db->get_row($sql, ARRAY_A);

            if ($subClass) {
                $subdivisionTemplate = $nc_core->catalogue->get_current('Template_ID') != $innerSubdivision['Template_ID'] ?
                    $nc_core->template->get_by_id($innerSubdivision['Template_ID']) : null;

                echo "<div " . nc_ls_display_pointer($innerSubdivisionId, isset($scrollToSubdivision) && $innerSubdivisionId == $scrollToSubdivision) . "></div>";
                if ($subdivisionTemplate) {
                    if ($File_Mode) {
                        $longpageTemplateView = new nc_tpl_template_view($nc_core->TEMPLATE_FOLDER, $nc_core->db);
                        $longpageTemplateView->load_template($innerSubdivision['Template_ID'], $subdivisionTemplate['File_Path']);
                        foreach ($longpageTemplateView->get_all_settings_path_in_array() as $path) {
                            include $path;
                        }
                        $longpageTemplateView->fill_fields();
                    }

                    if ($File_Mode) {
                        echo $longpageTemplateView->get_header();
                    } else {
                        eval(nc_check_eval("echo \"" . $subdivisionTemplate['Header'] . "\";"));
                    }
                }

                echo "<div " . nc_ls_display_container($innerSubdivisionId) . ">";

                $current_cc = $nc_core->sub_class->get_by_id($subClass['Sub_Class_ID']);
                $current_sub = $nc_core->subdivision->get_by_id($innerSubdivisionId);

                echo nc_objects_list($innerSubdivisionId, $subClass['Sub_Class_ID'], '', false, false);
                echo "</div>";

                if ($subdivisionTemplate) {
                    if ($File_Mode) {
                        echo $longpageTemplateView->get_footer();
                    } else {
                        eval(nc_check_eval("echo \"" . $subdivisionTemplate['Footer'] . "\";"));
                    }
                }
            }
        }
    }
}

$current_sub = $old_current_sub;
$current_cc = $old_current_cc;

$nc_result_msg = ob_get_clean();

$nc_core->page->is_processing_template_now();

if (!empty($_REQUEST['isModal'])) {
    $nc_result_msg = nc_prepare_message_form(
        $nc_result_msg, $action, $admin_mode, $user_table_mode, $sys_table_id, $current_cc,
        $f_Checked, $f_Priority, $f_Keyword,
        $f_ncTitle, $f_ncKeywords, $f_ncDescription, false, false,
        $f_ncSMO_Title, $f_ncSMO_Description, $f_ncSMO_Image, $f_ncDemoContent, $f_ncFullPageMode
    );
}

if ($File_Mode) {
    require_once $INCLUDE_FOLDER . 'index_fs.inc.php';

    if (!$templatePreview) {
        if ($nc_core->inside_admin && $UI_CONFIG) {
            $js_code = $UI_CONFIG->to_json();
            $template_header = nc_insert_in_head($template_header, $js_code, true);
        }
    }
}

if (!$File_Mode || $templatePreview) {
    nc_evaluate_template($template_header, $template_footer, $File_Mode);
}

// выполнить необходимую обработку кода страницы и отдать результат пользователю:
$nc_core->output_page($template_header, $nc_result_msg, $template_footer);
