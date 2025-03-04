<?php

$NETCAT_FOLDER = join(strstr(__FILE__, "/") ? "/" : "\\", array_slice(preg_split("/[\/\\\]+/", __FILE__), 0, -4)).( strstr(__FILE__, "/") ? "/" : "\\" );
include_once ($NETCAT_FOLDER."vars.inc.php");
require_once ($INCLUDE_FOLDER."s_common.inc.php");
require ($ADMIN_FOLDER."function.inc.php");
require ($ADMIN_FOLDER."widget/function.inc.php");

/** @var Permission $perm */

$fs_suffix = $_REQUEST['fs'] ? '_fs' : '';

$Delimeter = " &gt ";
$item_id = 9;
$Title2 = TOOLS_WIDGET;
$Title3 = '';
$Title4 = '';

if (!isset($category)) {
    $category = $nc_core->input->fetch_get_post('category');
}
if (!isset($phase)) {
    $phase = 10;
}
switch ($phase) {

    case 10: // list
        $UI_CONFIG = ($category ? new ui_config_widgetclasses('widgetgroup', $category) : new ui_config_widgetclasses());
        BeginHtml($Title2, $Title2, "http://".$DOC_DOMAIN."/management/class/");
        $perm->ExitIfNotAccess(NC_PERM_WIDGETCLASS, NC_PERM_ACTION_LIST);

        nc_widgetclass_list($category);
        break;

    case 20: // add
        $UI_CONFIG = new ui_config_widgetclass('add');
        BeginHtml($Title4, $Title3.$Delimeter.$Title4, "http://".$DOC_DOMAIN."/management/class/");
        $perm->ExitIfNotAccess(NC_PERM_WIDGETCLASS, NC_PERM_ACTION_ADD);

        nc_widgetclass_add_template($widget_group);
        $UI_CONFIG->treeSelectedNode = 'widgetgroup-' . $nc_core->input->fetch_get('widget_group');
        break;

    case 21: // post add
        $UI_CONFIG = new ui_config_widgetclass('add', $widgetclass_id);
        BeginHtml($Title4, $Title3.$Delimeter.$Title4, "http://".$DOC_DOMAIN."/management/class/");

        $nc_core->token->exit_if_invalid();
        $perm->ExitIfNotAccess(NC_PERM_WIDGETCLASS, NC_PERM_ACTION_ADD, $widgetclass_id);

        nc_widgetclass_add_form('', $widgetclass_id, $widget_group);
        break;

    case 22:
        $UI_CONFIG = new ui_config_widgetclass('add');
        BeginHtml($Title4, $Title3.$Delimeter.$Title4, "http://".$DOC_DOMAIN."/management/class/");
        $perm->ExitIfNotAccess(NC_PERM_WIDGETCLASS, NC_PERM_ACTION_ADD);
        $nc_core->token->exit_if_invalid();

        $post = $nc_core->input->fetch_post();
        $category = $post['Category_New'] ? $post['Category_New'] : $post['Category'];
        $params = array(
            'Name'           => $post['Name'],
            'Keyword'        => $post['Keyword'],
            'Category'       => $category,
            'Description'    => $post['Description'],
            'Template'       => $post['Template'],
            'InDevelop'      => nc_array_value($post, 'InDevelop'),
            'WidgetDisallow' => nc_array_value($post, 'WidgetDisallow'),
            'Settings'       => $post['Settings'],
            'Update'         => $post['Update'],
            'IsStatic'       => $post['IsStatic'],
        );

        if (!$post['Name']) {
            nc_print_status(WIDGET_ADD_ERROR_NAME, 'error');
            nc_widgetclass_add_form($params, '', '', $base_widgetclass_id);
            exit;
        }
        if (!$post['Keyword']) {
            nc_print_status(WIDGET_ADD_ERROR_KEYWORD, 'error');
            nc_widgetclass_add_form($params, '', '', $base_widgetclass_id);
            exit;
        }

        // проверка символов для ключевого слова
        if (!$nc_core->widget->validate_keyword($post['Keyword'])) {
            nc_print_status(CONTROL_CONTENT_SUBDIVISION_SUBCLASS_ERROR_KEYWORD_INVALID, 'error');
            nc_widgetclass_add_form($params, '', '', $base_widgetclass_id);
            exit;
        }

        if (is_exist_keyword($post['Keyword'], 1)) {
            nc_print_status(WIDGET_ADD_ERROR_KEYWORD_EXIST, 'error');
            nc_widgetclass_add_form($params, '', '', $base_widgetclass_id);
            exit;
        }
        $add_id = nc_widgetclass_add($params, $base_widgetclass_id);
        $UI_CONFIG = new ui_config_widgetclass('edit', $add_id);
        $isNewCategory = $db->get_var("SELECT COUNT(Category) FROM Widget_Class WHERE Category = '" . $db->escape($category) . "'");
        if ($isNewCategory == 1) {
            $UI_CONFIG->treeChanges['addNode'][] = array(
                    "parentNodeId" => "widgetclass.list",
                    "nodeId" => "widgetgroup-".md5($category),
                    "name" => $category,
                    "href" => "#widgetgroup.edit(".md5($category).")",
                    "image" => 'i_widgetclassgroup.gif',
                    "buttons" => $classgroup_buttons,
                    "hasChildren" => 1,
                    "dragEnabled" => false
            );
        }

        if ($add_id) {
            $hasChildren = $db->get_var("SELECT COUNT(`Field_ID`) FROM `Field` WHERE `Widget_Class_ID` = '{$add_id}'");
            $widgetclass_buttons = array();
            $widgetclass_buttons[] = nc_get_array_2json_button(
                CONTROL_FIELD_LIST_ADD,
                "widgetfield$fs_suffix.add({$add_id})",
                "nc-icon nc--file-add nc--hovered");

            $widgetclass_buttons[] = nc_get_array_2json_button(
                CONTROL_CLASS_DELETE,
                "widgetclass$fs_suffix.drop({$add_id}, 1)",
                "nc-icon nc--remove nc--hovered");

            $sql = "SELECT `Name`, `Category` FROM `Widget_Class` WHERE `Widget_Class_ID` = {$add_id}";
            $row = $db->get_row($sql, ARRAY_A);

            $UI_CONFIG->treeChanges['addNode'][] = array(
                "nodeId" => "widgetclass-{$add_id}",
                "name" => $add_id . ". " . $row["Name"],
                "href" => "#widgetclass.edit({$add_id})",
                "sprite" => 'dev-com-widgets' . ($fs_suffix ? '' : '-v4'),
                "acceptDropFn" => "treeClassAcceptDrop",
                "onDropFn" => "treeClassOnDrop",
                "hasChildren" => $hasChildren,
                "dragEnabled" => true,
                "buttons" => $widgetclass_buttons,
                "parentNodeId" => 'widgetgroup-' . md5($row['Category']),
            );
        }

        nc_widgetclass_edit_form($params, $add_id, $post['phase']);
        break;

    case 30: // edit
        $UI_CONFIG = new ui_config_widgetclass('edit', $widgetclass_id);
        BeginHtml($Title2, $Title2, "http://".$DOC_DOMAIN."/management/class/");
        $perm->ExitIfNotAccess(NC_PERM_WIDGETCLASS, NC_PERM_ACTION_EDIT, $widgetclass_id, null, 0);

        $AJAX_SAVER = true;
        if ($perm->isGuest()) {
            $AJAX_SAVER = false;
        }

        nc_widgetclass_edit_form('', $widgetclass_id, $phase);
        break;

    case 31:
        $UI_CONFIG = new ui_config_widgetclass('edit', $widgetclass_id);
        BeginHtml($Title2, $Title2, "http://".$DOC_DOMAIN."/management/class/");

        $nc_core->token->exit_if_invalid();
        $perm->ExitIfNotAccess(NC_PERM_WIDGETCLASS, NC_PERM_ACTION_EDIT, $widgetclass_id);

        $post = $nc_core->input->fetch_post();
        $params = array(
            'Name'           => nc_array_value($post, 'Name'),
            'Keyword'        => nc_array_value($post, 'Keyword'),
            'Category'       => nc_array_value($post, 'Category_New') ?: nc_array_value($post, 'Category'),
            'Description'    => nc_array_value($post, 'Description'),
            'Template'       => nc_array_value($post, 'Template'),
            'InDevelop'      => nc_array_value($post, 'InDevelop'),
            'WidgetDisallow' => nc_array_value($post, 'WidgetDisallow'),
            'Settings'       => nc_array_value($post, 'Settings'),
            'Update'         => nc_array_value($post, 'Update'),
            'IsStatic'       => nc_array_value($post, 'IsStatic'),
        );

        $before_edit = $nc_core->widget->get_widgetclass(intval($widgetclass_id));

        if (!$post['Keyword']) {
            nc_print_status(WIDGET_ADD_ERROR_KEYWORD, 'error');
            nc_widgetclass_edit_form($params, $post['widgetclass_id'], $post['phase']);
            exit;
        }

        if (!$nc_core->widget->validate_keyword($post['Keyword'])) {
            nc_print_status(CONTROL_CONTENT_SUBDIVISION_SUBCLASS_ERROR_KEYWORD_INVALID, 'error');
            nc_widgetclass_edit_form($params, $post['widgetclass_id'], $post['phase']);
            exit;
        }

        if (is_exist_keyword($post['Keyword'], $post['widgetclass_id'])) {
            nc_print_status(WIDGET_ADD_ERROR_KEYWORD_EXIST, 'error');
            nc_widgetclass_edit_form($params, $post['widgetclass_id'], $post['phase']);
            exit;
        }
        nc_widgetclass_edit($post['widgetclass_id'], $params);

        if (+$_REQUEST['isNaked']) {
            ob_clean();
            echo 'OK';
            exit;
        }

        nc_print_status(WIDGET_EDIT_OK, 'ok');
        if ($before_edit['Name'] != $params['Name']) {
            $UI_CONFIG->headerText = $params['Name'];
            $UI_CONFIG->treeChanges['updateNode'][] = array(
                "nodeId" => "widgetclass-".$widgetclass_id,
                "name" => $widgetclass_id.'. '.$params['Name']
            );
        }

        nc_widgetclass_edit_form($params, $post['widgetclass_id'], $post['phase']);
        break;

    case 40: // info
        $UI_CONFIG = new ui_config_widgetclass('info', $widgetclass_id);
        BeginHtml($Title2, $Title2, "http://".$DOC_DOMAIN."/management/class/");
        $perm->ExitIfNotAccess(NC_PERM_WIDGETCLASS, NC_PERM_ACTION_INFO, $widgetclass_id, null, 0);

        nc_widgetclass_info($widgetclass_id);
        break;

    case 50: // action
        $UI_CONFIG = new ui_config_widgetclass('action', $widgetclass_id);
        BeginHtml($Title2, $Title2, "http://".$DOC_DOMAIN."/management/class/");
        $perm->ExitIfNotAccess(NC_PERM_WIDGETCLASS, NC_PERM_ACTION_ADMIN, $widgetclass_id, null, 0);

        nc_widgetclass_action_form('', $widgetclass_id);
        break;

    case 51:
        $UI_CONFIG = new ui_config_widgetclass('action', $widgetclass_id);
        BeginHtml($Title2, $Title2, "http://".$DOC_DOMAIN."/management/class/");

        $nc_core->token->exit_if_invalid();
        $perm->ExitIfNotAccess(NC_PERM_WIDGETCLASS, NC_PERM_ACTION_ADMIN, $widgetclass_id);

        $post = $nc_core->input->fetch_post();
        $params = array(
            'AddForm'          => $post['AddForm'],
            'EditForm'         => $post['EditForm'],
            'AfterSaveAction'  => $post['AfterSaveAction'],
            'BeforeSaveAction' => $post['BeforeSaveAction'],
        );
        nc_widgetclass_action($widgetclass_id, $params);
        nc_widgetclass_action_form($params, $widgetclass_id);
        break;

    case 60: // delete
        BeginHtml($Title2, $Title3.$Delimeter.$Title6, "http://".$DOC_DOMAIN."/management/class/");

        $nc_core->token->exit_if_invalid();
        $perm->ExitIfNotAccess(NC_PERM_WIDGETCLASS, NC_PERM_ACTION_DEL);

        $post = $nc_core->input->fetch_post();
        if (!$from_tree) {
            foreach ($post as $key => $val) {
                if (nc_substr($key, 0, 6) == "Delete" && $val) {
                    $delete[] = intval($val);
                }
            }

            if (!isset($delete)) {
                $UI_CONFIG = new ui_config_widgetclasses();
                nc_print_status(WIDGET_LIST_ERROR_DELETE, 'error');
                nc_widgetclass_list($category);
                exit;
            }
        } else {
            $delete = $widgetclass_id;
        }
        $UI_CONFIG = new ui_config_widgetclass('delete', $widgetclass_id);
        nc_widgetclass_delete_warning($delete, $from_tree);
        break;

    case 61:
        $UI_CONFIG = new ui_config_widgetclasses();
        BeginHtml($Title6, $Title3.$Delimeter.$Title6, "http://".$DOC_DOMAIN."/management/class/");

        $nc_core->token->exit_if_invalid();
        $perm->ExitIfNotAccess(NC_PERM_WIDGETCLASS, NC_PERM_ACTION_DEL);

        nc_widgetclass_delete($delete, $from_tree);
        $delete = explode(',', $delete);
        foreach ((array) $delete as $d) {
            $UI_CONFIG->treeChanges['deleteNode'][] = "widgetclass-".$d;
        }
        nc_widgetclass_list($category);
        break;

    case 80: // import
        $UI_CONFIG = new ui_config_widgetclass('import');
        BeginHtml($Title2, $Title2, "http://".$DOC_DOMAIN."/management/class/");
        $perm->ExitIfNotAccess(NC_PERM_WIDGETCLASS, NC_PERM_ACTION_ADD);

        nc_widgetclass_import_form();
        break;

    case 81:
        $UI_CONFIG = new ui_config_widgetclass('import');
        BeginHtml($Title2, $Title2, "http://".$DOC_DOMAIN."/management/class/");

        $nc_core->token->exit_if_invalid();
        $perm->ExitIfNotAccess(NC_PERM_WIDGETCLASS, NC_PERM_ACTION_ADD);

        if ($_FILES['import']['error'] != 0) {
            nc_print_status(WIDGET_IMPORT_ERROR, 'error');
            nc_widgetclass_import_form();
        } else {
            $id = nc_widgetclass_import($_FILES['import']);
            if ($id) {
                nc_print_status(WIDGET_IMPORT_OK, 'ok');
                $fs = +$_REQUEST['fs'];
                echo "<a href='index.php?phase=30&amp;widgetclass_id=".$id."&fs=".$fs."'>".WIDGET_TAB_EDIT."</a>";
            } else {
                nc_print_status(WIDGET_IMPORT_ERROR, 'error');
            }
        }
        break;

    case 90: // результаты ajax
        $perm->ExitIfNotAccess(NC_PERM_ITEM_USER, NC_PERM_ACTION_RIGHT, -1, 0, 0);
        $post           = $nc_core->input->fetch_post();
        $widget_class_id = (int) $post['Widget_Class_ID'];
        $widget_id      = (int) $post['Widget_ID'];
        $action = nc_array_value($post, 'action');
        $res            = '';

        $SQL = "SELECT `Field_ID`,
                       `Field_Name`,
                       `Description`,
                       `TypeOfData_ID`,
                       `Format`,
                       `NotNull`,
                       `DefaultState`
                    FROM `Field`
                        WHERE `Widget_Class_ID` = '".$widget_class_id."'
                            ORDER BY `Priority`";

        $result = $db->get_results($SQL, ARRAY_A);


        if (!$result) {
            exit;
        }

        $fields = array();

        foreach ($result as $r) {
            $fields[$r['Field_Name']] = $r;
            $fieldIDs[$r['Field_ID']] = $r['Field_Name'];
        }

        $widget_fields = (array) $db->get_results("SELECT `Field_ID`, `Value`
                                     FROM `Widget_Field`
                                     WHERE `Field_ID` IN (".implode(',', array_keys($fieldIDs)).") AND `Widget_ID`= '".$widget_id."'
                                     ORDER BY `Field_ID`", ARRAY_A);
        $SQL = "SELECT `AddForm`,
                       `EditForm`,
                       `File_Mode`
                    FROM `Widget_Class`
                        WHERE `Widget_Class_ID` = " . $widget_class_id;

        $widget_result   = $db->get_row($SQL);
        $alter_add_form  = $widget_result->AddForm;
        $alter_edit_form = $widget_result->EditForm;
        $File_Mode       = $widget_result->File_Mode;

        if ($File_Mode) {
            $widget_view = new nc_tpl_widget_view($nc_core->WIDGET_TEMPLATE_FOLDER, $db);
            $widget_view->load($widget_class_id);
            $alter_add_form  = file_get_contents($widget_view->get_field_path('AddForm'));
            $alter_edit_form = file_get_contents($widget_view->get_field_path('EditForm'));
        }

        foreach ($widget_fields as $wf) {
            $widget_fields_array[] = array($wf['Field_ID'] => $wf['Value']);
        }

        if ((nc_array_value($post, 'show_old') == '1') && (nc_array_value($post, 'old_widget_class_id') == $widget_class_id)) {
            if ($alter_edit_form) {
                foreach ($post as $post_name => $post_val) {
                    if (($post_name != 'Widget_ID') && ($post_name != 'Widget_Class_ID'))
                            ${$post_name} = $post_val;
                }
            } else {
                $i = 0;
                foreach ((array) $result as $r) {
                    $description = "<font color='gray'>".($r['Description'] != '' ? $r['Description'] : $r['Field_Name']).($r['NotNull'] == '1' ? " (*)" : "").":</font><br />\n";
                    if ($r['TypeOfData_ID'] == NC_FIELDTYPE_TEXT) {
                        $res .= $description;
                        $res .= nc_admin_textarea_simple("f_".$fieldIDs[$r['Field_ID']], $post["f_".$fieldIDs[$r['Field_ID']]], '', 0, 0, '', 'soft')."<br /><br />\n";
                    } else {
                        $res .= $description;
                        $res .= "<input name='f_".$fieldIDs[$r['Field_ID']]."' size='50' value='";
                        $res .= $post["f_".$fieldIDs[$r['Field_ID']]];
                        $res .= "' /><br /><br />\n";
                    }
                    $i++;
                }
            }
            if ($File_Mode) {
                ob_start();
                include $widget_view->get_field_path('EditForm');
                $res .= ob_get_clean();
            } else {
                eval(nc_check_eval("\$res .= \"".$alter_edit_form."\";"));
            }

        } else if ($alter_add_form && !$action && !$widget_id) {
            if ($File_Mode) {
                ob_start();
                include $widget_view->get_field_path('AddForm');
                $res .= ob_get_clean();
            } else {
                eval(nc_check_eval("\$res .= \"".$alter_edit_form."\";"));
            }
        } else if ($alter_edit_form && $widget_id) {
            if (!empty($widget_fields_array)) {
                foreach ($widget_fields_array as $wf) {
                    foreach ($wf as $key => $val) {
                        ${'field'.$key} = $val;
                        ${'f_'.$fieldIDs[$key]} = $val;
                    }
                }
            }

            if ($File_Mode) {
                ob_start();
                include $widget_view->get_field_path('EditForm');
                $res .= ob_get_clean();
            } else {
                eval(nc_check_eval("\$res .= \"".$alter_edit_form."\";"));
            }

        } else {
            $i = 0;
            foreach ((array) $result as $r) {
                $description = "<font color='gray'>".($r['Description'] != '' ? $r['Description'] : $r['Field_Name']).($r['NotNull'] == '1' ? " (*)" : "").":</font><br />\n";
                if ($r['TypeOfData_ID'] == NC_FIELDTYPE_TEXT) {
                    $res .= $description;
                    $res .= nc_admin_textarea_simple("f_".$fieldIDs[$r['Field_ID']], ($action == 'EditForm' ? '$f_'.$fieldIDs[$r['Field_ID']] : (($action == 'AddForm' || !$widget_id) ? $r['DefaultState'] : $widget_fields_array[$i][$r['Field_ID']])), '', 0, 0, '', 'soft')."<br /><br />\n";
                } else {
                    $res .= $description;
                    $res .= "<input name='f_".$fieldIDs[$r['Field_ID']]."' size='50' value='";
                    $res .= ($action == 'EditForm' ? ($File_Mode ? "<?= " : "") . '$f_'.$fieldIDs[$r['Field_ID']] . ($File_Mode ? " ?>" : "") : (($action == 'AddForm' || !$widget_id) ? $r['DefaultState'] : $widget_fields_array[$i][$r['Field_ID']]));
                    $res .= "' /><br /><br />\n";
                }
                $i++;
            }
        }

        echo $res;
        exit;
        break;

    case 91: // ajax превью
        $perm->ExitIfNotAccess(NC_PERM_ITEM_USER, NC_PERM_ACTION_RIGHT, -1, 0, 0);

        $result = $db->get_var("SELECT `Result`
                            FROM `Widget`
                            WHERE `Keyword` = '" . $db->escape($keyword) . "'");
        print $result;
        exit();
        break;

    case 100: // для CKEditor'a
        $perm->ExitIfNotAccess(NC_PERM_ITEM_USER, NC_PERM_ACTION_RIGHT, -1, 0, 0);
        $result = $db->get_results("SELECT REPLACE(w.`Name`, CONCAT(wc.`Category`, ':'), '') as `Name`, w.`Keyword`, w.`Result`, wc.`Category`
                            FROM `Widget` as `w`, `Widget_Class` as `wc`
                            WHERE w.Widget_Class_ID = wc.Widget_Class_ID
                            ORDER BY wc.Category");
        die(json_encode($result));
}

EndHtml();
