<?php

if (!isset($_REQUEST['fs'])) {
    $_REQUEST['fs'] = nc_Core::get_object()->input->fetch_post_get('fs');
}


/*
 * Вывод списка виджетов
 */
function get_fs() {
    return +$_REQUEST['fs'];
}

function nc_widget_list() {
    global $db, $ADMIN_PATH, $ADMIN_TEMPLATE, $UI_CONFIG, $SUB_FOLDER;

    $nc_core = nc_Core::get_object();

    $widgets = $db->get_results("SELECT `Widget_Class_ID`, `Widget_ID`, `Name`, `Keyword` from `Widget` ORDER BY `Name`");

    $cat_result = $db->get_results("SELECT `Category`, `Widget_Class_ID` FROM `Widget_Class` ORDER BY `Category`", ARRAY_A);

    if ( ! $cat_result) {
        nc_print_status(CONTROL_WIDGET_NONE, 'info');
        return;
    }

    $categories = array();
    foreach ($cat_result as $c) {
        $widget_categories[$c['Widget_Class_ID']] = $c['Category'];

        if ( ! in_array($c['Category'], $categories)) {
            $categories[$c['Category']] = $c['Category'];
        }
    }

    $UI_CONFIG->actionButtons[] = array("id" => "addClass",
        "caption" => WIDGETS_LIST_ADD,
        "action"  => "parent.nc_form('{$ADMIN_PATH}widget/admin.php?phase=20'); return false;",
        "align"   => "left");

    $view = $nc_core->ui->view( dirname(__FILE__) . '/views/widget_list' );

    $view->with('widget_categories', $widget_categories);
    $view->with('categories',        $categories);
    $view->with('widgets',           $widgets);

    echo (string)$view;
}

/*
 * Вывод формы добавления виджета
 */

function nc_widget_add_form($params = '', $widget_id = '') {
    global $UI_CONFIG, $nc_core, $ADMIN_PATH;

    if ($widget_id > 0) {
        $params = $nc_core->widget->get_widget(intval($widget_id));
    }

    echo_widget_form($params);
    echo "<input type='hidden' name='phase' value='21'>".
    "<input type='hidden' name='widget_id' id ='widget_id' value='".$widget_id."'>".
    "<br/>".NETCAT_MODERATION_INFO_REQFIELDS."<br/><br/>\r\n".
    "<input type='submit' class='hidden'>".
    "</form>".
    "<script type='text/javascript' src='" . nc_add_revision_to_url($ADMIN_PATH . 'js/widget.js') . "'></script>" .
    "<script type='text/javascript'>nc_widget_obj = new nc_widget();</script>";

    $UI_CONFIG->actionButtons[] = array(
            "id" => "submit",
            "caption" => WIDGET_ADD_ADD,
            "action" => "mainView.submitIframeForm()"
    );
}

function nc_widget_add_form_modal($params = '', $widget_id = '') {
    global $ADMIN_PATH;
    $nc_core = nc_Core::get_object();

    if ($widget_id > 0) {
        $params = $nc_core->widget->get_widget(intval($widget_id));
    }

    echo nc_get_simple_modal_header(CONTROL_WIDGET_ADD_ACTION);

    echo_widget_form($params, 0, 'widget/admin.php');

    echo "<input type='hidden' name='phase' value='21'>".
    "<input type='hidden' name='widget_id' id ='widget_id' value='".$widget_id."'>".
    "<br/>".NETCAT_MODERATION_INFO_REQFIELDS."<br/><br/>\r\n".
    "</form>".
    "<script type='text/javascript' src='" . nc_add_revision_to_url($ADMIN_PATH . 'js/widget.js') . "'></script>" .
    "<script type='text/javascript'>nc_widget_obj = new nc_widget();</script>";

    echo nc_get_simple_modal_footer();
}

/*
 * Добавление виджета
 */

function nc_widget_add($params, $fields) {
    global $nc_core;
    $add = $nc_core->widget->add_widget($params, $fields);
    return $add;
}

/*
 * Вывод формы редактирования виджета
 */

function nc_widget_edit_form($params='', $widget_id='') {
    global $nc_core, $UI_CONFIG, $ADMIN_PATH;

    if ($params == '' && $widget_id > 0) {
        $params = $nc_core->widget->get_widget(intval($widget_id));
    }

    echo_widget_form($params);
    echo "<input type='hidden' name='phase' value='31'>".
    "<input type='hidden' name='widget_id' id='widget_id' value='".$widget_id."'>".
    "<br/>".NETCAT_MODERATION_INFO_REQFIELDS."<br/><br/>\r\n".
    "<input type='submit' class='hidden'>".
    "</form>".
    "<script type='text/javascript' src='" . nc_add_revision_to_url($ADMIN_PATH . 'js/widget.js') . "'></script>" .
    "<script type='text/javascript'>nc_widget_obj = new nc_widget();</script>";

    $UI_CONFIG->actionButtons[] = array(
            "id" => "submit",
            "caption" => WIDGET_EDIT_SAVE,
            "action" => "mainView.submitIframeForm()"
    );
}

function nc_widget_edit_form_modal($params = '', $widget_id = '') {
    global $ADMIN_PATH;
    $nc_core = nc_Core::get_object();

    if ($params == '' && $widget_id > 0) {
        $params = $nc_core->widget->get_widget(intval($widget_id));
    }

    echo nc_get_simple_modal_header(WIDGET_LIST_EDIT);

    echo_widget_form($params, 0, 'widget/admin.php');

    echo "<input type='hidden' name='phase' value='31'>".
    "<input type='hidden' name='widget_id' id='widget_id' value='".$widget_id."'>".
    "<br/>".NETCAT_MODERATION_INFO_REQFIELDS."<br/><br/>\r\n".
    "</form>".
    "<script type='text/javascript' src='" . nc_add_revision_to_url($ADMIN_PATH . 'js/widget.js') . "'></script>" .
    "<script type='text/javascript'>
        setTimeout(function() {
            nc_widget_obj = new nc_widget();
        }, 500);
     </script>";

    echo nc_get_simple_modal_footer();
}

/*
 * Редактирование виджета
 */

function nc_widget_edit($widget_id, $params, $fields) {
    global $nc_core;
    $nc_core->widget->edit_widget(intval($widget_id), $params, $fields);
}

/*
 * Вывод формы удаления виджета
 */

function nc_widget_delete_warning($widget_id) {
    global $db, $UI_CONFIG;

    nc_print_status(WIDGET_DELETE, 'info');
    echo "<form method='post' action='admin.php'>" .
        "<input type='hidden' name='widget_id' value='" . $widget_id . "'>" .
        "<input type='hidden' name='phase' value='61'>" .
        nc_core::get_object()->token->get_input() .
        "</form>";

    $UI_CONFIG->actionButtons[] = array(
        "id" => "submit",
        "caption" => WIDGET_DELETE_CONFIRMDELETE,
        "action" => "mainView.submitIframeForm()",
        "red_border" => true,
    );
}

/*
 * Удаление виджета
 */

function nc_widget_delete($id) {
    global $nc_core;
    $nc_core->widget->drop_widget(intval($id));
}

/*
 * Вывод списка виджет-компонентов
 */

function nc_widgetclass_list($category) {
    global $nc_core, $db, $ADMIN_PATH, $ADMIN_TEMPLATE, $UI_CONFIG;

    $result = $nc_core->widget->list_widgetclass();
    if ($result) {
        echo "<form method='post' action='index.php'>".
        "<table border='0' cellpadding='0' cellspacing='0' width='100%'><tr><td>".
        "<table class='nc-table nc--striped nc--hovered' width='100%'>".
        "<tr>".
        "<th>ID</th>".
        "<th width='45%'>".WIDGET_LIST_NAME."</th>".
        "<th width='45%>'".WIDGET_LIST_CATEGORY."</th>".
        "<th class='nc-text-center'>".WIDGET_LIST_GO."</th>".
        "<th width='10%'>".WIDGET_LIST_FIELDS."</th>".
        "<th class='nc-text-center'><i class='nc-icon nc--remove' title='".WIDGET_LIST_DELETE."'></i></th>".
        "</tr>";
        foreach ($result as $key => $val) {
            if (md5($val['Category']) == $category || !isset($category)) {
                echo "<tr>".
                "<td >".$key."</td>".
                "<td ><a href='index.php?fs=".get_fs()."&phase=30&widgetclass_id=".$key."'>".$val['Name']."</a></td>".
                "<td ><a href='index.php?fs=".get_fs()."&phase=10&category=".md5($val['Category'])."'>".$val['Category']."</a></td>".
                "<td class='nc-text-center'><nobr>".
                "<a href='index.php?fs=".get_fs()."&phase=30&widgetclass_id=".$key."'>".WIDGET_LIST_EDIT."</a>&nbsp;&nbsp;".
                "<a href='index.php?fs=".get_fs()."&phase=50&widgetclass_id=".$key."'>".WIDGET_LIST_AT."</a></nobr></td>".
                "<td><a class='nc-label nc--blue' href='".$ADMIN_PATH."field/index.php?fs=".get_fs()."&widgetclass_id=".$key."&fs=".get_fs()."'>".$val['Fields']." ".mb_strtolower( $nc_core->lang->get_numerical_inclination($val['Fields'], array(CONTROL_CLASS_FIELD, CONTROL_CLASS_FIELDS, CONTROL_CLASS_FIELDS_COUNT)) )."</a></td>".
                "<td class='nc-text-center'>".nc_admin_checkbox_simple("Delete".$key, $key)."</td>".
                "</tr>";
            }
        }
        echo "</table></td></tr></table>".
        "<input type='hidden' name='phase' value='60'>".
        "<input type='hidden' name='fs' value='".get_fs()."'>".
        $nc_core->token->get_input() .
        "<input type='submit' class='hidden'></form>";
    } else {
		nc_print_status(CONTROL_WIDGET_NONE, "info");
	}

    if (isset($key)) {
        $UI_CONFIG->actionButtons[] = array(
            "id" => "submit",
            "caption" => WIDGET_LIST_DELETE_SELECTED,
            "action" => "mainView.submitIframeForm()",
            "red_border" => true,
        );
    }

    $suf = get_fs() ? '_fs' : '';

    $UI_CONFIG->actionButtons[] = array("id" => "addClass",
            "caption" => WIDGET_LIST_ADDWIDGET,
            "action" => "urlDispatcher.load('widgetclass$suf.add()')",
            "align" => "left");
    $UI_CONFIG->actionButtons[] = array("id" => "submit",
            "caption" => WIDGETS_LIST_IMPORT,
            "action" => "urlDispatcher.load('widgetclass$suf.import()')",
            "align" => "left");
}

/*
 * Вывод списка шаблонов при создании нового виджет-компонента
 */

function nc_widgetclass_add_template($widget_group='') {
    global $db, $UI_CONFIG;
    $SQL = "SELECT `Widget_Class_ID` AS value,
                   CONCAT(`Widget_Class_ID`, '. ', `Name`) AS description,
                   `Category` AS optgroup
                FROM `Widget_Class`
                    WHERE File_Mode = ".get_fs()."
                        ORDER BY `Category`, `Widget_Class_ID`";
    $widgets = $db->get_results($SQL, ARRAY_A);
    echo "<fieldset>".
    "<legend>".WIDGET_ADD_CREATENEW_BASICOLD."</legend>".
    "<form method='post' style='padding:15px 20px 10px'>".
    "<table border='0' cellpadding='0' cellspacing='0'>".
    "<tr>".
    "<td width='80%'>".
    "<select name='widgetclass_id'>".
    "<option value='0'>".WIDGET_ADD_CREATENEW."</option>";
    if (!empty($widgets)) echo nc_select_options($widgets);
    echo "</select>".
    "</td>".
    "<td>&nbsp;</td>".
    "</tr>".
    "</table>".
    "<input type='hidden' name='phase' value='21'>".
    "<input type='hidden' name='fs' value='".get_fs()."'>".
    "<input type='hidden' name='widget_group' value='".$widget_group."'>".
    nc_core::get_object()->token->get_input() .
    "<input type='submit' class='hidden'>".
    "</form>".
    "</fieldset>";

    $UI_CONFIG->actionButtons[] = array(
            "id" => "submit",
            "caption" => WIDGET_ADD_CONTINUE,
            "action" => "mainView.submitIframeForm()"
    );
}

/*
 * Вывод формы добавления виджет-компонента
 */

function nc_widgetclass_add_form($params = '', $widgetclass_id = '', $widget_group = '', $base_widgetclass_id = '') {
    global $UI_CONFIG, $nc_core;

    if ($widgetclass_id > 0) {
        $params = $nc_core->widget->get_widgetclass(intval($widgetclass_id));
        $base_widgetclass_id = $widgetclass_id;
    }

    echo_widgetclass_form($params, '', $widget_group);
    echo "<input type='hidden' name='phase' value='22' />".
    "<input type='hidden' name='base_widgetclass_id' value='".$base_widgetclass_id."' />".
    "<input type=hidden name=fs value=".get_fs().">".
    "<input type='submit' class='hidden' />".
    "</form>";

    $UI_CONFIG->actionButtons[] = array(
            "id" => "submit",
            "caption" => WIDGETCLASS_ADD_ADD,
            "action" => "mainView.submitIframeForm()"
    );
}

/*
 * Добавление виджет-компонента
 */

function nc_widgetclass_add($params, $base_widgetclass_id='') {
    global $nc_core;
    $add = $nc_core->widget->add_widgetclass($params, 0, intval($base_widgetclass_id));
    return $add;
}

/*
 * Вывод формы редактирование виджет-компонента
 */

function nc_widgetclass_edit_form($params='', $widgetclass_id='', $phase) {
    global $nc_core, $UI_CONFIG;

    if ($params == '' && $widgetclass_id > 0) {
        $params = $nc_core->widget->get_widgetclass(intval($widgetclass_id));
    }

    if ($widgetclass_id) {
        $params['Widget_Class_ID'] = (int)$widgetclass_id;
    }

    echo_widgetclass_form($params, $phase);
    echo "<input type='hidden' name='phase' value='31'>".
    "<input type='hidden' name='widgetclass_id' value='".$widgetclass_id."'>".
    "<input type='hidden' value='".get_fs()."' name='fs'>".
    "<input type='submit' class='hidden'>".
    "</form>";

    if ($nc_core->input->fetch_post('Category_New') && $widgetclass_id) {
        ?>
        <script>
            parent.window.frames[0].window.location.href += '&selected_node=widgetclass-<?= $widgetclass_id; ?>';
        </script>
    <?
    }

    $UI_CONFIG->actionButtons[] = array(
            "id" => "submit",
            "caption" => WIDGET_EDIT_SAVE,
            "action" => "mainView.submitIframeForm()"
    );
    if ($nc_core->get_settings('TextareaResize')) {
        echo '<script type="text/javascript">bindTextareaResizeButtons();</script>';
    }
//    nc_print_admin_save_script('nc_widgetclass_form');
}

/*
 * Редактирование виджет-компонента
 */

function nc_widgetclass_edit($widgetclass_id, $params) {
    global $nc_core;
    $nc_core->widget->edit_widgetclass(intval($widgetclass_id), $params);
}

/*
 * Вывод информации виджет-компонента
 */

function nc_widgetclass_info($widgetclass_id) {
    $nc_core = nc_Core::get_object();
    $info = $nc_core->widget->get_widgetclass(intval($widgetclass_id));
    $fields = $nc_core->db->get_results("SELECT `Field_Name`, `DefaultState`, `Description`
                              FROM `Field`
                              WHERE `Widget_Class_ID` = " . (int)$widgetclass_id, ARRAY_A);
    $fields_desc = '';
    if ($fields)
            foreach ($fields as $f) {
            $fields_desc .= "<tr><td>".$f['Field_Name']." - ".$f['Description']."</td></tr>";
            $fields_func[] = "'".$f['Field_Name']."' => ".($f['DefaultState'] ? "'".$f['DefaultState']."'" : "'...'");
        }

    echo "<table border='0' cellpadding='6' cellspacing='1' width='100%'>".
    "<tr><td>".
    "<table border='0' cellpadding='0' cellspacing='0' width='100%'>".
    "<tr><td colspan='2'><b>".WIDGET_INFO_DESCRIPTION."</td></tr>".
    "<tr><td>".($info['Description'] == '' ? WIDGET_INFO_DESCRIPTION_NONE : $info['Description'])."</td></tr><tr><td>&nbsp;&nbsp;</font></td></tr>";
    if (intval($info['InDevelop']) == 0) {
        echo "<tr><td colspan='2'><b>".WIDGET_INFO_PREVIEW."</td></tr>".
        "<tr><td>".$nc_core->widget->generate($info['Keyword'])."</td></tr><td>&nbsp;&nbsp;</font></td></tr>".
        "<tr><td colspan='2'><b>".WIDGET_INFO_INSERT."</td></tr>".
        "<tr><td>\$nc_core->widget->show('".$info['Keyword']."')</td></tr><td>&nbsp;&nbsp;</font></td></tr>";
        if ($info['WidgetDisallow'] != 1) {
            echo "<tr><td colspan='2'><b>".WIDGET_INFO_INSERT_TEXT."</td></tr>".
            "<tr><td>%NC_WIDGET_SHOW('".$info['Keyword']."')%</td></tr><td>&nbsp;&nbsp;</td></tr>";
        }
        echo "<tr><td colspan='2'><b>".WIDGET_INFO_GENERATE."</td></tr>".
        "<tr><td>\$nc_core->widget->generate('".$info['Keyword']."', array(".($fields_func ? implode(', ', $fields_func) : "")."))</td></tr><td>&nbsp;&nbsp;</td></tr>";

        echo $fields_desc;
    }
    echo "</table>".
    "</table>";
}

/*
 * Вывод шаблонов действия виджет-компонента
 */

function nc_widgetclass_action_form($params='', $widgetclass_id='') {
    global $ROOT_FOLDER, $ClassGroup, $ADMIN_PATH, $UI_CONFIG, $db, $nc_core;
    if ($params == '' && $widgetclass_id > 0) {
        $params = $nc_core->widget->get_widgetclass(intval($widgetclass_id));
    }
    echo "<form method='post' action='index.php'>" .
        $nc_core->token->get_input() .
        "<input type='hidden' name='widgetclass_id' value='" . $widgetclass_id . "'>" .
        "<input type='hidden' name='fs' value='" . get_fs() . "'>" .
        "<input type='hidden' name='phase' value='51'><br />";
    echo WIDGET_ACTION_ADDFORM." (<a href='#' onclick=\"generate_widget_form(".$widgetclass_id.", 'AddForm'); return false;\">".CONTROL_CLASS_CLASS_FORMS_ADDFORM_GEN."</a>)".":<br />".nc_admin_textarea_simple('AddForm', $params['AddForm'], '', 10, 60, "id='AddForm'")."<br /><br />";
    echo WIDGET_ACTION_EDITFORM." (<a href='#' onclick=\"generate_widget_form(".$widgetclass_id.", 'EditForm'); return false;\">".CONTROL_CLASS_CLASS_FORMS_EDITFORM_GEN."</a>)".":<br />".nc_admin_textarea_simple('EditForm', $params['EditForm'], '', 10, 60, "id='EditForm'")."<br /><br />";
    echo WIDGET_ACTION_BEFORE_SAVE.":<br />".nc_admin_textarea_simple('BeforeSaveAction', $params['BeforeSaveAction'], '', 10, 60, "id='BeforeSaveAction'")."<br /><br />";
    echo WIDGET_ACTION_AFTER_SAVE.":<br />".nc_admin_textarea_simple('AfterSaveAction', $params['AfterSaveAction'], '', 10, 60, "id='AfterSaveAction'")."<br /><br />";
    echo "</form>";

    $UI_CONFIG->actionButtons[] = array(
            "id" => "submit",
            "caption" => WIDGET_EDIT_SAVE,
            "action" => "mainView.submitIframeForm()"
    );
}

/*
 * Сохрание шаблонов действия виджет-компонента
 */

function nc_widgetclass_action($widgetclass_id, $params) {
    global $nc_core;
    $nc_core->widget->edit_widgetclass(intval($widgetclass_id), $params);
}

/*
 * Вывод формы подтверждения удаления виджет-компонента
 */

function nc_widgetclass_delete_warning($delete, $from_tree) {
    global $db, $UI_CONFIG;

    nc_print_status(sprintf(WIDGET_DELETE_WARNING, CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_WARNING_SITEDELETE_I, CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_WARNING_SITEDELETE_U), 'info');
    echo "<form method='post' action='index.php'>" .
        "<input type='hidden' name='delete' value='" . ($from_tree == 1 ? $delete : implode(',', $delete)) . "'>" .
        "<input type='hidden' name='from_tree' value='" . $from_tree . "'>" .
        "<input type='hidden' name='phase' value='61'>" .
        "<input type='hidden' name='fs' value='" . get_fs() . "'>" .
        nc_core::get_object()->token->get_input() .
        "</form>";

    $delete = (array)$delete;
    $delete = array_map('intval', $delete);
    $widgetclass = $db->get_results("SELECT * FROM `Widget_Class` WHERE `Widget_Class_ID` IN (" . implode(',', $delete) . ")", ARRAY_A);
    $widget = $db->get_results("SELECT * FROM `Widget`", ARRAY_A);
    foreach ($widgetclass as $wc) {
        echo "<span class='widgetclass-name'><strong>".WIDGET_LIST_DELETE_WIDGETCLASS."</strong> <a href='index.php?phase=30&widgetclass_id=".$wc['Widget_Class_ID']."'>".$wc['Name']."</a></span><br />";
        foreach ($widget as $w) {
            if ($w['Widget_Class_ID'] == $wc['Widget_Class_ID']) {
                $widget_html .= "<span class='widget-name'> <a href='admin.php?phase=30&widget_id=".$w['Widget_ID']."'>".$w['Name']."</a></span>";
            }
        }
        if ($widget_html) {
            echo "<strong>".WIDGET_LIST_DELETE_WIDGET."</strong> ";
            echo $widget_html;
        }
        $widget_html = '';
        echo "<br /><br />";
    }

    $UI_CONFIG->actionButtons[] = array(
        "id" => "submit",
        "caption" => WIDGET_DELETE_CONFIRMDELETE,
        "action" => "mainView.submitIframeForm()",
        "red_border" => true,
    );
}

/*
 * Удаление виджет-компонента
 */

function nc_widgetclass_delete($delete, $from_tree) {
    global $nc_core;
    if ($from_tree == 1) {
        $nc_core->widget->drop_widgetclass(intval($delete));
        $nc_core->db->query("DELETE FROM `Field` WHERE `Widget_Class_ID`='".intval($delete)."'");
    } else {
        foreach (explode(',', $delete) as $d) {
            $nc_core->widget->drop_widgetclass(intval($d));
            $nc_core->db->query("DELETE FROM `Field` WHERE `Widget_Class_ID`='".intval($d)."'");
        }
    }
}


/*
 * Экспорт виджет-компонента
 */

function nc_widgetclass_export($widget_class_id) {
    global $db, $nc_core;

    $widget_class_id = (int)$widget_class_id;
    $select = "SELECT * from `Widget_Class`
             WHERE `Widget_Class_ID` = '".$widget_class_id."'";
    if ($result = $db->get_results($select, ARRAY_A)) {

        $File_Mode = $result[0]['File_Mode'];
        if ($File_Mode) {
            $File_Path = $result[0]['File_Path'];
            $file_class = new nc_tpl_widget_editor($nc_core->WIDGET_TEMPLATE_FOLDER, $nc_core->db);
            $file_class->load($widget_class_id, $File_Path);
            $file_class->fill_fields();

            include ($nc_core->DOCUMENT_ROOT.$nc_core->ADMIN_PATH."tar.inc.php");
        }

        $fields = $db->get_results("SELECT `Field_Name`, `Description`, `TypeOfData_ID`, `Format`, `NotNull`, `Priority`, `DoSearch`, `DefaultState`, `Inheritance`, `Widget_Class_ID`, `TypeOfEdit_ID` from `Field`
                                WHERE `Widget_Class_ID` = '".$widget_class_id."'", ARRAY_A);
        $ret = "<?xml version='1.0' encoding='utf-8'?>\n".
                "<netcatml>\n".
                "  <widgetclasses>\n".
                "    <widgetclass>\n".
                "      <ID>".$widget_class_id."</ID>\n".
                "      <Name>".to_uni($result[0]['Name'])."</Name>\n".
                "      <Keyword>".$result[0]['Keyword']."</Keyword>\n".
                "      <Description><![CDATA[".to_uni(strip_new_line($result[0]['Description']))."]]></Description>\n".
                "      <Category>".to_uni($result[0]['Category'])."</Category>\n".
                "      <InDevelop>".$result[0]['InDevelop']."</InDevelop>\n".
                "      <Template><![CDATA[". ($File_Mode ? '' : to_uni($result[0]['Template']))."]]></Template>\n".
                "      <Settings><![CDATA[". ($File_Mode ? '' : to_uni($result[0]['Settings']))."]]></Settings>\n".
                "      <AddForm><![CDATA[". ($File_Mode ? '' : to_uni($result[0]['AddForm']))."]]></AddForm>\n".
                "      <EditForm><![CDATA[". ($File_Mode ? '' : to_uni($result[0]['EditForm']))."]]></EditForm>\n".
                "      <WidgetDisallow>".$result[0]['WidgetDisallow']."</WidgetDisallow>\n".
                "      <Update>".$result[0]['Update']."</Update>\n".
                "      <File_Mode>".$result[0]['File_Mode']."</File_Mode>\n".
                "    </widgetclass>\n".
                "  </widgetclasses>\n".
                "  <fields>\n";

        if (!empty($fields)) {
            foreach ($fields as $f) {
                $ret .= "    <field type_of_data_id='".$f['TypeOfData_ID']."'>\n";
                $ret .= "      <Field_Name>".$f['Field_Name']."</Field_Name>\n";
                $ret .= "      <Description><![CDATA[".to_uni(strip_new_line($f['Description']))."]]></Description>\n";
                $ret .= "      <Format>".$f['Format']."</Format>\n";
                $ret .= "      <NotNull>".$f['NotNull']."</NotNull>\n";
                $ret .= "      <Priority>".$f['Priority']."</Priority>\n";
                $ret .= "      <DoSearch>".$f['DoSearch']."</DoSearch>\n";
                $ret .= "      <DefaultState>".$f['DefaultState']."</DefaultState>\n";
                $ret .= "      <Inheritance>".$f['Inheritance']."</Inheritance>\n";
                $ret .= "      <TypeOfEdit_ID>".$f['TypeOfEdit_ID']."</TypeOfEdit_ID>\n";
                $ret .= "    </field>\n";
            }
        }
        $ret .= "  </fields>\n";

        if ($File_Mode) {
            $tmp_file_name = $nc_core->TMP_FOLDER . "netcat_widget_$widget_class_id.tgz";
            $dump_file = nc_tgz_create($tmp_file_name, $widget_class_id, $nc_core->HTTP_TEMPLATE_PATH . 'widget/');
            $tar_contents = file_get_contents($tmp_file_name);
            $ret .= "  <tar_data>".base64_encode($tar_contents)."\n  </tar_data>\n";
        }

        $ret .= "</netcatml>\n";
    }

    return $ret;
}

function nc_widgetclass_import_form() {
    global $UI_CONFIG;
    echo "<form method='post' enctype='multipart/form-data'>" .
        nc_core::get_object()->token->get_input() .
        "<fieldset>" .
        "<legend>" . WIDGET_IMPORT_CHOICE . "</legend>" .
        "<input size='40' type='file' name='import' />" .
        "<input type='hidden' name='fs' value='" . get_fs() . "'/>" .
        "</fieldset><br/>" .
        "<input type='hidden' name='phase' value='81' />" .
        "</form>";

    $UI_CONFIG->actionButtons[] = array(
            "id" => "submit",
            "caption" => WIDGET_IMPORT,
            "action" => "mainView.submitIframeForm()",
    );
}

function nc_widgetclass_import($import) {
    global $db, $nc_core;

    $file = file_get_contents($import['tmp_name']);
    $xml = new SimpleXmlElement($file, LIBXML_NOCDATA);
    $widgetclass = array();
    $widgetclass['Name'] = (string) $xml->widgetclasses->widgetclass->Name;
    $widgetclass['Keyword'] = (string) $xml->widgetclasses->widgetclass->Keyword;
    $widgetclass['Description'] = (string) $xml->widgetclasses->widgetclass->Description;
    $widgetclass['Category'] = (string) $xml->widgetclasses->widgetclass->Category;
    $widgetclass['InDevelop'] = (string) $xml->widgetclasses->widgetclass->InDevelop;
    $widgetclass['Template'] = (string) $xml->widgetclasses->widgetclass->Template;
    $widgetclass['Settings'] = (string) $xml->widgetclasses->widgetclass->Settings;
    $widgetclass['AddForm'] = (string) $xml->widgetclasses->widgetclass->AddForm;
    $widgetclass['EditForm'] = (string) $xml->widgetclasses->widgetclass->EditForm;
    $widgetclass['WidgetDisallow'] = (string) $xml->widgetclasses->widgetclass->WidgetDisallow;
    $widgetclass['Update'] = (string) $xml->widgetclasses->widgetclass->Update;
    $widgetclass['File_Mode'] = (string) $xml->widgetclasses->widgetclass->File_Mode;

    if ($xml->widgetclasses->widgetclass->File_Mode) {
        $_REQUEST['fs'] = 1;
    }

    if (!$nc_core->NC_UNICODE) {
        $widgetclass = $nc_core->utf8->array_utf2win($widgetclass);
    }

    $id = $nc_core->widget->add_widgetclass($widgetclass, 1);

    if (!empty($xml->fields->field)) {
        $query = "INSERT INTO `Field` (`Field_Name`, `Description`, `Format`, `NotNull`, `Priority`, `DoSearch`, `DefaultState`, `Inheritance`, `Widget_Class_ID`, `TypeOfEdit_ID`, `TypeOfData_ID`) VALUES ";
        foreach ($xml->fields->field as $field) {
            $query_arr[] = "('".(string) $field->Field_Name."', '".(string) $field->Description."', '".(string) $field->Format."', ".(string) $field->NotNull.", ".(string) $field->Priority.", ".(string) $field->DoSearch.", '".(string) $field->DefaultState."', ".(string) $field->Inheritance.", ".$id.", ".(string) $field->TypeOfEdit_ID.", ".(string) $field->attributes()->type_of_data_id.")";
        }
        $query .= implode(',', $query_arr);
        if (!$nc_core->NC_UNICODE) {
            $query = $nc_core->utf8->utf2win($query);
        }
        $db->query($query);
    }

    $tar = (string)$xml->tar_data;
    $ID = (string)$xml->widgetclasses->widgetclass->ID;

    if (isset($tar)) {
        include_once ($nc_core->DOCUMENT_ROOT . $nc_core->ADMIN_PATH . "tar.inc.php");

        $tar_name = $nc_core->TMP_FOLDER . 'widget_import_' . $ID . '_temp.tgz';
        file_put_contents($tar_name, base64_decode(trim($tar)));
        nc_tgz_extract($tar_name, $nc_core->TMP_FOLDER);
        if (is_writable($nc_core->WIDGET_TEMPLATE_FOLDER.$id)) {
			if ($handle = opendir($nc_core->TMP_FOLDER.$ID)) {
				while (false !== ($entry = readdir($handle))) {
					if ($entry != "." && $entry != "..") {
						rename($nc_core->TMP_FOLDER . $ID . '/' . $entry, $nc_core->WIDGET_TEMPLATE_FOLDER . $id . '/' . $entry);
					}
				}
				closedir($handle);
			}

            @rmdir($nc_core->TMP_FOLDER . $ID);
        }
        else {
            return false;
        }
        unlink($tar_name);
    }
    return $id;
}

/*
 *  Вспомогательные функции
 */

function to_uni($string) {
    global $nc_core;
    return ($nc_core->NC_UNICODE ? $string : $nc_core->utf8->win2utf($string));
}

function strip_new_line($string) {
    return str_replace(array("\r\n", "\n", "\r"), '', $string);
}

function echo_resizeblock($textarea_id) {
    return null;
}

function echo_remind_script() {
    global $UI_CONFIG;

    $UI_CONFIG->remind[] = 'remind_widgetclass';
}

function echo_resize_script() {
    return null;
}

function echo_widgetclass_form($params, $phase='', $widget_group='') {
    global $ADMIN_PATH, $db, $nc_core;

    $params = (array) $params;

    $nc_core = nc_Core::get_object();
    $db = $nc_core->db;

    if ( get_fs() ) {
        $widget_editor = new nc_tpl_widget_editor($nc_core->WIDGET_TEMPLATE_FOLDER, $nc_core->db);
        if (nc_array_value($params, 'Widget_Class_ID')) {
            $widget_editor->load($params['Widget_Class_ID'], null);
        }
    }

    echo "<form method='post' id='nc_widgetclass_form' action='index.php'>";
    echo $nc_core->token->get_input();

    if ( get_fs() && $phase != 3 ):
		$widget_absolute_path = $widget_editor->get_absolute_path();
        $widget_filemanager_link = nc_module_path('filemanager') . 'admin.php?page=manager&phase=1&dir=' . $nc_core->SUB_FOLDER . $nc_core->HTTP_TEMPLATE_PATH . 'widget' . $widget_editor->get_relative_path();
   ?>
        <br />
        <div><?= sprintf(CONTROL_WIDGETCLASS_FILES_PATH, $widget_filemanager_link, $widget_absolute_path) ?></div>
        <br />
	<? endif;

    echo "<input type='hidden' name='fs' value='".get_fs()."' /><fieldset>".
    "".WIDGET_ADD_NAME.":<br />".
    nc_admin_input_simple('Name', $params['Name'], 50).
    "<br /><br />".
    "".WIDGET_ADD_KEYWORD.":<br />".
    nc_admin_input_simple('Keyword', $params['Keyword'], 50).
    "<br /><br />";

    $widgetCategory = $db->get_col("SELECT DISTINCT `Category` FROM `Widget_Class`");
    echo CONTROL_USER_GROUP.":<br /><select name='Category' style='width:auto;'>\n";
    foreach ($widgetCategory as $wc) {
        if ($params['Category'] == $wc) {
            echo("\t<option value='".$wc."' selected='selected'>".$wc."</option>\n");
        } else {
            echo("\t<option value='".$wc."'>".$wc."</option>\n");
        }
    }

    echo "</select>&nbsp;&nbsp;&nbsp;".
    "".WIDGET_ADD_NEWGROUP."&nbsp;&nbsp;&nbsp;".nc_admin_input_simple('Category_New', null, 25, '', "maxlength='64'").
    "</fieldset>".
    "<table border='0' cellpadding='6' cellspacing='0' width='100%' ><tr style='display: none;'><td colspan='2'>".
    "<div id='DescriptionOn' style='display: none'>".
    "<p style='cursor: pointer;' onclick='document.getElementById(\"DescriptionOn\").style.display=\"none\";document.getElementById(\"DescriptionOff\").style.display=\"\";'> &#x25BC; ".WIDGET_ADD_DESCRIPTION.":</p>".
    "<br />".nc_admin_textarea_simple('Description', $params['Description'], '', 8, 60, "id='Description'")."</div></td></tr></table><br />".
    "<legend>".WIDGET_ADD_OBJECTVIEW."</legend>".
    "<table border='0' cellpadding='6' cellspacing='0' width='100%' >".
    "<tr>".
    "<td>".
    nc_admin_textarea_resize('Template', $params['Template'], "".WIDGET_ADD_PAGEBODY.":", 10, 60, 'PageBody').
    "</td>".
    "</tr>".
    "</table>".
    "<br />".
    "<legend>".WIDGET_ADD_DOPL."</legend>".
    "<table border='0' cellpadding='0' cellspacing='0' width='100%' >".
    "<tr>".
    "<td colspan='2'>".
    "<font>".nc_admin_checkbox_simple('InDevelop', 1, WIDGET_ADD_DEVELOP, $params['InDevelop']).
    "</td>".
    "</tr>".
    "<tr>".
        "<td colspan='2'>".
            "<font>".nc_admin_checkbox_simple('WidgetDisallow', 1, WIDGET_ADD_DISALLOW, $params['WidgetDisallow']).
        "</td>".
    "</tr>".
    "<tr>".
        "<td colspan='2'>".
            "<input type='hidden' name='IsStatic' value='0'>" .
            "<font>".nc_admin_checkbox_simple('IsStatic', 1, WIDGET_IS_STATIC, isset($params['IsStatic']) ? $params['IsStatic'] : 1).
        "</td>".
    "</tr>".
    "<tr>".
    "<td colspan='2'>".
    "".WIDGET_ADD_UPDATE.":<br />".
    nc_admin_input_simple('Update', $params['Update'], 5)."
        <br />".
    "</td>".
    "</tr>".
    "<tr>".
    "<td colspan='2'>".
    nc_admin_textarea_resize('Settings', $params['Settings'], "<br>".WIDGET_ADD_SYSTEM.":", 8, 60).
    "</td>".
    "</tr>".
    // ($phase == 30 ? "<tr><td colspan='2'><a href='ExportToFile.php?widget_class_id=".$params['Widget_Class_ID']."&amp;".$nc_core->token->get_url()."'>".WIDGET_LIST_EXPORT."</a></td></tr>" : "").
    ($phase == 30 ? "<tr><td colspan='2'><a href='" . $nc_core->SUB_FOLDER . $nc_core->HTTP_ROOT_PATH . "action.php?ctrl=admin.backup&amp;action=export_run&amp;raw=1&amp;type=widget_class&amp;id=" . $params['Widget_Class_ID'] . "&amp;" . $nc_core->token->get_url() . "'>".WIDGET_LIST_EXPORT."</a></td></tr>" : "").
    "</table>";
    echo_remind_script();
}

function echo_widget_form($params, $alter = 0, $action = '') {
    global $ADMIN_PATH, $db;
    if ($action) {
         $action = "action='{$ADMIN_PATH}{$action}'";
    }
	
    $html = "";
	
    if (!$alter) {
        $html .= "
            <legend>".WIDGETS_PARAMS."</legend>".
                "<form id='adminForm' class='nc-form' method='post' $action><br />".
                nc_core::get_object()->token->get_input() .
                "".WIDGET_ADD_NAME.":<br />".
                nc_admin_input_simple('Name', isset($params['Name']) ? $params['Name'] : '', 50)."<br /><br />".
                "".WIDGET_ADD_KEYWORD.":<br />".
                nc_admin_input_simple('Keyword', isset($params['Keyword']) ? $params['Keyword'] : '', 50)."<br /><br />";
    }
    $widgets = $db->get_results("SELECT `Widget_Class_ID` AS value, CONCAT(`Widget_Class_ID`, '. ', `Name`) AS description, `Category` AS optgroup FROM `Widget_Class` WHERE `InDevelop`='0' ORDER BY `Category`, `Widget_Class_ID`", ARRAY_A);
    $html .= "".WIDGET_ADD_WK.":<br />".
            "<select name='Widget_Class_ID' id='Widget_Class_ID' onchange='nc_widget_obj.change();return false;'>";
    if (!empty($widgets))
            $html .= nc_select_options($widgets, isset($params['Widget_Class_ID']) ? $params['Widget_Class_ID'] : '');
    $html .= "</select><br /><br /><div id='widget_fields'></div>";
            
    if (!isset($old_values)) {
        $old_values = null;
    }

    if ($params) {
        foreach ($params as $param_name => $param_value) {
            if (nc_substr($param_name, 0, 1) == 'f') {  // и в fieldNNN и в f_XXXXXX первая буква f
                $old_values .= "&".urlencode($param_name)."=".urlencode($param_value);
            }
        }
        if ($old_values) {
            $html .= "<input type='hidden' id='__old_values' value='".$old_values."&old_widget_class_id=".urlencode($params['Widget_Class_ID'])."'>";
        }
    }
    if (!$alter) {
        echo $html;
    } else {
        return $html;
    }
}

function is_exist_keyword($keyword, $widget_class_id=0, $widget_id=0) {
    global $db;
    $keyword = $db->escape($keyword);
    $widget_class_id = (int)$widget_class_id;
    $widget_id = (int)$widget_id;
    $query =
        ($widget_class_id
            ? "SELECT * FROM `Widget_Class` WHERE `Keyword` = '" . $keyword . "' AND `Widget_Class_ID` != $widget_class_id"
            : "SELECT * FROM `Widget` WHERE `Keyword` = '" . $keyword . "'AND `Widget_ID` != $widget_id"
        );
    $result = $db->get_results($query);
    if (!empty($result)) {
        return 1;
    } else {
        return 0;
    }
}

/*
 * UI
 */

class ui_config_widgetclass extends ui_config {

    function __construct($active_tab = 'edit', $class_id = 0, $class_group = '') {

        global $MODULE_VARS;

        $nc_core = nc_Core::get_object();
        $db = $nc_core->db;

        $suffix = +$_REQUEST['fs'] ? '_fs' : '';

        $class_id = (int)$class_id;

        $active_toolbar = '';

        if ($class_id) {
            $this->headerText = $db->get_var("SELECT Name FROM Widget_Class WHERE Widget_Class_ID = $class_id");
        } elseif ($class_group) {
            $this->headerText = $db->get_var("SELECT Class_Group FROM Class WHERE md5(Class_Group) = '" . $db->escape($class_group) . "' GROUP BY Class_Group");
        } else {
            $this->headerText = SECTION_CONTROL_WIDGETCLASS;
        }

        if (in_array($active_tab, array('customadd', 'customedit', 'customsearch', 'customsubscribe', 'customdelete'))) {
            $active_toolbar = $active_tab;
            $active_tab = 'classaction';
        }

        if ($active_tab == 'add') {
            $this->tabs = array(
                    array('id' => 'add',
                            'caption' => CONTROL_WIDGETCLASS_ADD_ACTION,
                            'location' => "widgetclass$suffix.add($class_group)"));
        } elseif ($active_tab == 'addtemplate') {
            $this->tabs = array(
                    array('id' => 'addtemplate',
                            'caption' => CONTROL_CLASS_CLASS_TEMPLATE_ADD,
                            'location' => "widgetclass$suffix.addtemplate(".$class_id.")"));
        } elseif ($active_tab == 'delete') {
            $this->tabs = array(
                    array('id' => 'delete',
                            'caption' => CONTROL_CLASS_DELETE,
                            'location' => "widgetclass$suffix.delete($class_group)"));
        } elseif ($active_tab == 'import') {
            $this->tabs = array(
                    array('id' => 'import',
                            'caption' => WIDGET_IMPORT_TAB,
                            'location' => "widgetclass$suffix.import()"));
        } else {
            $this->tabs = array(
                    array('id' => 'info',
                            'caption' => WIDGET_TAB_INFO,
                            'location' => "widgetclass$suffix.info($class_id)"),
                    array('id' => 'edit',
                            'caption' => WIDGET_TAB_EDIT,
                            'location' => "widgetclass$suffix.edit($class_id)"),
                    array('id' => 'action',
                            'caption' => WIDGET_TAB_CUSTOM_ACTION,
                            'location' => "widgetclass$suffix.action($class_id)"),
                    array('id' => 'fields',
                            'caption' => CONTROL_CLASS_FIELDS,
                            'location' => "widgetclass$suffix.fields($class_id)"));
        }

        $this->activeTab = $active_tab;
        $this->activeToolbarButtons[] = $active_toolbar;

        if ($active_tab == 'add') {
            $this->locationHash = "#widgetclass.$active_tab($class_group)";
        } elseif ($active_tab == 'delete') {
        } else {
            if ($active_tab == 'classaction') {
                $this->locationHash = "#widgetclass.$active_toolbar($class_id)";
            } else if ($active_tab == 'import') {
                $this->locationHash = "#widgetclass.$active_tab()";
            } else {
                $this->locationHash = "#widgetclass.$active_tab($class_id)";
            }
            $this->treeSelectedNode = "widgetclass-{$class_id}";
        }

        $this->treeMode = 'widgetclass' . $suffix;
    }

    function updateTreeClassNode($class_id, $class_name) {

        $this->treeChanges['updateNode'][] = array("nodeId" => "sub-$node_id",
                "name" => "$node_id. $node_name");
    }

}

class ui_config_widgetclasses extends ui_config {

    function __construct($active_tab = 'widgetclass.list', $category='') {
        $this->headerText = SECTION_CONTROL_WIDGETCLASS;
        $this->tabs = array(
                array(
                        'id' => 'widgetclass.list',
                        'caption' => SECTION_CONTROL_WIDGETCLASS,
                        'location' => "widgetclass.list"));
        $this->activeTab = $active_tab;
        $this->locationHash = ($category != "" ? "#widgetgroup.edit({$category})" : "#widgetclass.list");
        $this->treeMode = 'widgetclass' . (+$_REQUEST['fs'] ? '_fs' : '');
        $this->treeSelectedNode = ($category != "" ? "widgetgroup-".$category."" : "widgetclass.list");
    }

}

class ui_config_widgetes extends ui_config {

    function __construct($active_tab = 'widgets') {
        $this->headerText = SECTION_CONTROL_WIDGET;
        $this->tabs = array(
                array('id' => 'widgets',
                        'caption' => SECTION_CONTROL_WIDGET_LIST,
                        'location' => "widgets")
        );
        $this->activeTab = $active_tab;
        $this->locationHash = "#widgets";
        $this->treeMode = 'widgetclass' . (+$_REQUEST['fs'] ? '_fs' : '');
        $this->treeSelectedNode = "widgets";
    }

}

class ui_config_widget extends ui_config {

    function __construct($active_tab = 'edit', $class_id = 0, $class_group = '') {

        global $db, $nc_core, $MODULE_VARS;
        $this->headerText = SECTION_INDEX_DEV_WIDGETS;

        if (!isset($active_toolbar)) {
            $active_toolbar = null;
        }

        if (in_array($active_tab, array('customadd', 'customedit', 'customsearch', 'customsubscribe', 'customdelete'))) {
            $active_toolbar = $active_tab;
            $active_tab = 'classaction';
        }

        if ($active_tab == 'add') {
            $this->tabs = array(
                    array('id' => 'add',
                            'caption' => CONTROL_WIDGET_ADD_ACTION,
                            'location' => "widgets.add($class_group)"));
        } elseif ($active_tab == 'addtemplate') {
            $this->tabs = array(
                    array('id' => 'addtemplate',
                            'caption' => CONTROL_CLASS_CLASS_TEMPLATE_ADD,
                            'location' => "widgets.addtemplate(".$class_id.")"));
        } elseif ($active_tab == 'delete') {
            $this->tabs = array(
                    array('id' => 'delete',
                            'caption' => CONTROL_CLASS_DELETE,
                            'location' => "widgets.delete($class_group)"));
        } elseif ($active_tab == 'edit') {
            $this->tabs = array(
                    array('id' => 'edit',
                            'caption' => CONTROL_WIDGET_ACTIONS_EDIT,
                            'location' => "widgets.edit($class_group)"));
        }
        $this->activeTab = $active_tab;
        $this->activeToolbarButtons[] = $active_toolbar;

        if ($active_tab == 'add') {
            $this->locationHash = "#widgets.$active_tab($class_group)";
//     $this->treeSelectedNode = "group-$class_group";
        } elseif ($active_tab == 'delete') {
            // иначе сбрасывается
            //$this->locationHash = "#dataclass.".$active_tab."(".$class_id.")";
        } else {
            if ($active_tab == 'classaction') {
                $this->locationHash = "#widgets.$active_toolbar($class_id)";
            } else {
                $this->locationHash = "#widgets.$active_tab($class_id)";
            }
            $this->treeSelectedNode = "widgets-{$class_id}";
        }

        $this->treeMode = 'widgetclass' . (+$_REQUEST['fs'] ? '_fs' : '');
    }

    function updateTreeClassNode($class_id, $class_name) {

        $this->treeChanges['updateNode'][] = array("nodeId" => "sub-$node_id",
                "name" => "$node_id. $node_name");
    }

}
