<?php

/* $Id: admin.php 7302 2012-06-25 21:12:35Z alive $ */

// get settings
$NETCAT_FOLDER = join(strstr(__FILE__, "/") ? "/" : "\\", array_slice(preg_split("/[\/\\\]+/", __FILE__), 0, -4)).( strstr(__FILE__, "/") ? "/" : "\\" );
include_once($NETCAT_FOLDER."vars.inc.php");
require ($ADMIN_FOLDER."function.inc.php");
require_once($ADMIN_FOLDER."widget/function.inc.php"); /**/

$Title2 = WIDGETS;

if (!isset($phase) || !$phase) $phase = 10;

switch ($phase) {
    case 10: // list
        $UI_CONFIG = new ui_config_widgetes();
        $UI_CONFIG->treeMode = 'modules';
        BeginHtml($Title2, $Title2, "http://".$DOC_DOMAIN."/management/class/");
        $perm->ExitIfNotAccess(NC_PERM_WIDGETCLASS, NC_PERM_ACTION_LIST);

        nc_naked_action_header();
        nc_widget_list();
        nc_naked_action_footer();

        break;

    case 20: // add
        $UI_CONFIG = new ui_config_widget('add');
        $UI_CONFIG->treeMode = 'modules';
        BeginHtml($Title2, $Title2, "http://".$DOC_DOMAIN."/management/class/");
        $perm->ExitIfNotAccess(NC_PERM_WIDGETCLASS, NC_PERM_ACTION_ADD);

        if (+$_REQUEST['isNaked']) {
            ob_clean();
            nc_widget_add_form_modal('', intval($widget_id));
            exit;
        }

        nc_widget_add_form('', intval($widget_id));
        break;

    case 21:
        $UI_CONFIG = new ui_config_widget('add');
        $UI_CONFIG->treeMode = 'modules';
        BeginHtml($Title2, $Title2, "http://".$DOC_DOMAIN."/management/class/");
        $perm->ExitIfNotAccess(NC_PERM_WIDGETCLASS, NC_PERM_ACTION_ADD, $widget_id);

        $post = $nc_core->input->fetch_get_post();

        // Валидация
        if (!$post['Name']) {
            nc_print_status(WIDGET_ADD_ERROR_NAME, 'error');
            nc_widget_add_form($post);
            exit;
        }

        if (!$post['Keyword']) {
            nc_print_status(WIDGET_ADD_ERROR_KEYWORD, 'error');
            nc_widget_add_form($post);
            exit;
        }

        // проверка символов для ключевого слова
        if (!$nc_core->widget->validate_keyword($post['Keyword'])) {
            nc_print_status(CONTROL_CONTENT_SUBDIVISION_SUBCLASS_ERROR_KEYWORD_INVALID, 'error');
            nc_widget_add_form($post);
            exit;
        }

        if (is_exist_keyword($post['Keyword'], 0, $widget_id)) {
            nc_print_status(WIDGET_ADD_ERROR_KEYWORD_EXIST, 'error');
            nc_widget_add_form($post);
            exit;
        }

        $widget_class_id = (int) $post['Widget_Class_ID'];

        $res = (array) $db->get_results("SELECT * FROM `Field` WHERE `Widget_Class_ID`='".$widget_class_id."'");
        $fields = array();
        foreach ($res as $row) {
            $fields[$row->Field_Name] = $row;
        }
        foreach ($post as $key => $val) {
            if (nc_substr($key, 0, 5) === 'field') {
                $fields_data[(int)nc_substr($key, 5)] = $val;
            } elseif (nc_substr($key, 0, 2) === 'f_') {
                $fieldId = $fields[nc_substr($key, 2)]->Field_ID;
                if ($fieldId) {
                    $fields_data[$fieldId] = $val;
                }
            }
        }

        $file_mode = $db->get_var("SELECT `File_Mode` FROM `Widget_Class` WHERE `Widget_Class_ID` = " . $widget_class_id);

        // Выполняем действие перед сохранением (insert & update) объекта
        if ($file_mode) {
            $action_data = array(
                'action'          => 'insert',
                'widget_class_id' => $widget_class_id,
                'widget_fields'   => $fields,
                'post'            => $post,
                'widget_id'       => 0,
            );
            extract($action_data);
            $widget_view = new nc_tpl_widget_view($nc_core->WIDGET_TEMPLATE_FOLDER, $db);
            $widget_view->load($widget_class_id);

            $before_action_file = $widget_view->get_field_path('BeforeSaveAction');
            if (file_exists($before_action_file)) {
                include $before_action_file;
            }
        }

        // Добавление виджета
        $add_id = nc_widget_add($post, $fields_data);

        // Выполняем действие после сохранения (insert & update) объекта
        if ($file_mode) {
            $widget_id = $add_id;
            $after_action_file = $widget_view->get_field_path('AfterSaveAction');
            if (file_exists($after_action_file)) {
                include $after_action_file;
            }
        }

        $UI_CONFIG = new ui_config_widgetes();
        nc_print_status(WIDGET_ADD_OK, 'ok');

        nc_naked_action_header();
        nc_widget_list();
        nc_naked_action_footer();
        break;

    case 30: // edit
        $UI_CONFIG = new ui_config_widget('edit', $widget_id);
        $UI_CONFIG->treeMode = 'modules';
        BeginHtml($Title2, $Title2, "http://".$DOC_DOMAIN."/management/class/");
        $perm->ExitIfNotAccess(NC_PERM_WIDGETCLASS, NC_PERM_ACTION_EDIT, $widget_id, null, 0);

        if (+$_REQUEST['isNaked']) {
            ob_clean();
            nc_widget_edit_form_modal('', intval($widget_id));
            exit;
        }

        nc_widget_edit_form('', $widget_id);
        break;

    case 31: // post edit
        $UI_CONFIG = new ui_config_widget('edit', $widget_id);
        $UI_CONFIG->treeMode = 'modules';
        BeginHtml($Title2, $Title2, "http://".$DOC_DOMAIN."/management/class/");
        $perm->ExitIfNotAccess(NC_PERM_WIDGETCLASS, NC_PERM_ACTION_EDIT, $widget_id);

        $post = $nc_core->input->fetch_get_post();
        $widget_class_id = (int) $post['Widget_Class_ID'];
        $params = array(
            'Name'            => $post['Name'],
            'Keyword'         => $post['Keyword'],
            'Widget_Class_ID' => $widget_class_id,
        );

        $widget_id = $post['widget_id'];

        $res = (array) $db->get_results("SELECT `Field_Name`, `Field_ID`, `NotNull`, `Description`, `Format`, `TypeOfData_ID` FROM `Field` WHERE `Widget_Class_ID`={$widget_class_id}");
        $fieldIDs = $fieldNames = $fieldNotNull = $fieldDescriptions = $fieldTypes = $fieldFormats = $widget_fields = array();
        foreach ($res as $res_row) {
            $fieldIDs[$res_row->Field_Name]        = $res_row->Field_ID;
            $fieldNames[$res_row->Field_ID]        = $res_row->Field_Name;
            $fieldNotNull[$res_row->Field_ID]      = $res_row->NotNull;
            $fieldDescriptions[$res_row->Field_ID] = $res_row->Description;
            $fieldTypes[$res_row->Field_ID]        = $res_row->TypeOfData_ID;
            $fieldFormats[$res_row->Field_ID]      = $res_row->Format;
            $widget_fields[$res_row->Field_Name]   = $res_row;
        }

        foreach ($post as $key => $val) {
            if (nc_substr($key, 0, 5) === 'field') {
                $fields[(int)nc_substr($key, 5)] = $val;
            } elseif (nc_substr($key, 0, 2) === 'f_') {
                $field_name = nc_substr($key, 2);
                $fieldId = $fieldIDs[$field_name];
                if ($fieldId) {
                    $fields[$fieldId] = $val;
                }
            }
        }

        // Проверка символов для ключевого слова
        if (!$nc_core->widget->validate_keyword($post['Keyword'])) {
            nc_print_status(CONTROL_CONTENT_SUBDIVISION_SUBCLASS_ERROR_KEYWORD_INVALID, 'error');
            nc_widget_edit_form($post, $widget_id);
            exit;
        }

        if (is_exist_keyword($post['Keyword'], 0, $widget_id)) {
            nc_print_status(WIDGET_ADD_ERROR_KEYWORD_EXIST, 'error');
            nc_widget_edit_form($post, $widget_id);
            exit;
        }

        // Проверка полей виджета
        if (isset($fields)) {
            foreach ($fields as $f_Id => $f_val) {
                if (($f_val == '') && ($fieldNotNull[$f_Id] == '1')) {
                    nc_print_status(
                        str_replace(
                            "%NAME",
                            $fieldDescriptions[$f_Id] ?: $fieldNames[$f_Id],
                            NETCAT_MODERATION_MSG_ONE
                        ),
                        'error'
                    );

                    nc_widget_edit_form($post, $widget_id);
                    exit;
                }

                if ($f_val != '') {
                    switch ($fieldTypes[$f_Id]) {
                        case NC_FIELDTYPE_STRING:
                            if (
                                $fieldFormats[$f_Id] === 'email' &&
                                $f_val &&
                                !nc_preg_match("/^.+@.+\..+$/i", $f_val)
                            ) {
                                $type_err = 1;
                            }

                            if (
                                $fieldFormats[$f_Id] === 'phone' &&
                                $f_val &&
                                !nc_normalize_phone_number(preg_replace('/\s+/', ' ', $f_val))
                            ) {
                                $type_err = 1;
                            }

                            if ($fieldFormats[$f_Id] === 'url' &&
                                ($f_val === 'http://' || $f_val === 'ftp://') &&
                                ($fieldNotNull[$f_Id] == '0')
                            ) {
                                $f_val = "";
                            }

                            if ($fieldFormats[$f_Id] === 'url' && $f_val && !isURL($f_val)) {
                                $type_err = 1;
                            }

                            break;
                        case NC_FIELDTYPE_INT:
                            if ($f_val != "" && $f_val != (string)(int)$f_val) {
                                $type_err = 1;
                            }
                            break;

                        case NC_FIELDTYPE_FLOAT:
                            if ($f_val != "" && !preg_match("/^\-?[0-9]+(\.[0-9]+)?$/is", str_replace(",", ".", $f_val))) {
                                $type_err = 1;
                            }
                            if (preg_match('/,/', $f_val)) {
                                $f_val = str_replace(',', '.', $f_val);
                            }
                            break;
                    }

                    if (!empty($type_err)) {
                        nc_print_status(
                            str_replace("%NAME", $fieldDescriptions[$f_Id] ?: $fieldNames[$f_Id], NETCAT_MODERATION_MSG_TWO),
                            'error'
                        );
                        nc_widget_edit_form($post, $widget_id);
                    }
                }
            }
        }

        $file_mode = $db->get_var("SELECT `File_Mode` FROM `Widget_Class` WHERE `Widget_Class_ID` = " . $widget_class_id);

        // Выполняем действие перед сохранением (insert & update) объекта
        if ($file_mode) {
            $action_data = array(
                'action'          => 'update',
                'widget_class_id' => $widget_class_id,
                // 'widget_fields'   => $widget_fields,
                // 'post'         => $post,
                // 'widget_id'    => $widget_id,
            );
            extract($action_data);
            $widget_view = new nc_tpl_widget_view($nc_core->WIDGET_TEMPLATE_FOLDER, $db);
            $widget_view->load($widget_class_id);

            $before_action_file = $widget_view->get_field_path('BeforeSaveAction');
            if (file_exists($before_action_file)) {
                include $before_action_file;
            }
        }

        nc_widget_edit($widget_id, $params, isset($fields) ? $fields : array());

        // Выполняем действие после сохранения (insert & update) объекта
        if ($file_mode) {
            $after_action_file = $widget_view->get_field_path('AfterSaveAction');
            if (file_exists($after_action_file)) {
                include $after_action_file;
            }
        }

        nc_print_status(WIDGET_EDIT_OK, 'ok');
        nc_widget_edit_form($post, $widget_id);
        break;

    case 60:  // delete
        $UI_CONFIG = new ui_config_widget('delete', $widget_id);
        $UI_CONFIG->treeMode = 'modules';
        BeginHtml($Title6, $Title3.$Delimeter.$Title6, "http://".$DOC_DOMAIN."/management/class/");
        $perm->ExitIfNotAccess(NC_PERM_WIDGETCLASS, NC_PERM_ACTION_DEL, $widget_id);

        nc_widget_delete_warning($widget_id);
        break;

    case 61:
        $UI_CONFIG = new ui_config_widgetes();
        $UI_CONFIG->treeMode = 'modules';
        BeginHtml($Title6, $Title3.$Delimeter.$Title6, "http://".$DOC_DOMAIN."/management/class/");
        $perm->ExitIfNotAccess(NC_PERM_WIDGETCLASS, NC_PERM_ACTION_DEL, $widget_id);

        nc_widget_delete($widget_id);

        nc_naked_action_header();
        nc_widget_list();
        nc_naked_action_footer();
        break;
}

EndHtml();
