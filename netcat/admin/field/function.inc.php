<?php

/**
 * Init filed types, field name
 */
function InitVars() {
    global $nc_core, $field_types, $field_types_sprites, $field_type_name, $type_of_edit, $type_of_edit_name, $type_of_error;

    $field_types = array(
        1 => 'icon_type_string',
        2 => 'icon_type_int',
        3 => 'icon_type_text',
        4 => 'icon_type_classificator',
        5 => 'icon_type_bool',
        6 => 'icon_type_file',
        7 => 'icon_type_float',
        8 => 'icon_type_date',
        9 => 'icon_type_link',
        10 => 'icon_type_multiclassificator',
        11 => 'icon_type_m_file'
    );

    $field_types_sprites = array(
        1 => 'field-string',
        2 => 'field-int',
        3 => 'field-text',
        4 => 'field-select',
        5 => 'field-bool',
        6 => 'field-file',
        7 => 'field-float',
        8 => 'field-date',
        9 => 'field-link',
        10 => 'field-multiselect',
        11 => 'field-multifile'
    );

    $field_type_name = array(
        1 => CLASSIFICATOR_TYPEOFDATA_STRING,
        2 => CLASSIFICATOR_TYPEOFDATA_INTEGER,
        3 => CLASSIFICATOR_TYPEOFDATA_TEXTBOX,
        4 => CLASSIFICATOR_TYPEOFDATA_LIST,
        5 => CLASSIFICATOR_TYPEOFDATA_BOOLEAN,
        6 => CLASSIFICATOR_TYPEOFDATA_FILE,
        7 => CLASSIFICATOR_TYPEOFDATA_FLOAT,
        8 => CLASSIFICATOR_TYPEOFDATA_DATETIME,
        9 => CLASSIFICATOR_TYPEOFDATA_RELATION,
        10 => CLASSIFICATOR_TYPEOFDATA_MULTILIST,
        11 => CLASSIFICATOR_TYPEOFDATA_MULTIFILE
    );

    $type_of_edit = array(
        1 => 'field_access_all',
        2 => 'field_access_admin',
        3 => 'field_access_no'
    );

    $type_of_edit_name = array(
        1 => CLASSIFICATOR_TYPEOFEDIT_ALL,
        2 => CLASSIFICATOR_TYPEOFEDIT_ADMINS,
        3 => CLASSIFICATOR_TYPEOFEDIT_NOONE
    );

    $type_of_error = array(
        0 => CONTROL_FIELD_DB_ERROR,
        1 => CONTROL_FIELD_NAME_ERROR,
        2 => CONTROL_FIELD_ONE_RESERVED,
        3 => CONTROL_FIELD_EXITS_ERROR,
        4 => CONTROL_FIELD_FORMAT_ERROR,
        5 => CONTROL_FIELD_DIGIT_ERROR
    );

    return;
}

/**
 * Insert(modify, drop) into Table (Message or Subdivision or Template or.. see SystemTable) field
 * @param int $FieldID
 * @param int $type operation: 1 - add, 2 - modify, 3 - drop
 * @param object $db
 * @param string|bool $NewFieldName
 * @return bool
 */
function ColumnInMessage($FieldID, $type, $db, $NewFieldName = false) {
    /** @var nc_Db $db */
    global $db;

    $FieldID = (int)$FieldID;

    $SelectField = $db->get_row("SELECT `Class_ID`, `Widget_Class_ID`, `Field_Name`, `TypeOfData_ID`, `Extension`, `NotNull`, `DoSearch`, `DefaultState`, `System_Table_ID` FROM `Field` WHERE `Field_ID`= '" . $FieldID . "'");

    if ($SelectField->Widget_Class_ID) {
        return true;
    }

    if ($NewFieldName) {
        $NewFieldName = $db->escape($NewFieldName);
        $db->query("UPDATE `Field` SET `Field_Name` = '{$NewFieldName}' WHERE `Field_ID` = {$FieldID}");
    }

    $isSys = $SelectField->System_Table_ID; #for short, ==0 - if Component Field
    $TableName = $isSys ? GetSystemTableName($SelectField->System_Table_ID) : "Message" . $SelectField->Class_ID;

    switch ($type) {
        case 1:
            $oper = "ADD";
            break;
        case 2:
            $oper = $NewFieldName ? "CHANGE" : "MODIFY";
            break;
        case 3:
            $oper = "DROP";
            break;
    }

    if ($type == 2 && !$isSys) {
        $arr_indexes = $db->get_results("SHOW INDEX FROM `Message{$SelectField->Class_ID}`", ARRAY_A);
        if (!empty($arr_indexes)) {
            foreach ($arr_indexes as $arr_indexes_row) {
                if ($arr_indexes_row['Key_name'] == $SelectField->Field_Name) {
                    $db->query("ALTER TABLE `Message" . $SelectField->Class_ID . "` DROP INDEX `" . $SelectField->Field_Name . "`");
                }
            }
        }
    }


    $alter = "ALTER TABLE `" . $TableName . "` " . $oper . " `" . $SelectField->Field_Name . "`";

    if ($type == 3) {
        $db->query($alter);
        return true;
    }

    if ($NewFieldName && $type == 2) {
        $alter .= " `{$NewFieldName}`";
    }

    $alter .= " ";

    switch ($SelectField->TypeOfData_ID) {
        case NC_FIELDTYPE_STRING:
            $alter .= 'CHAR(255)';
            break;
        case NC_FIELDTYPE_INT:
            $alter .= 'INT';
            break;
        case NC_FIELDTYPE_TEXT:
            $alter .= 'LONGTEXT';
            break;
        case NC_FIELDTYPE_SELECT:
            $alter .= 'INT';
            break;
        case NC_FIELDTYPE_BOOLEAN:
            $alter .= 'TINYINT';
            break;
        case NC_FIELDTYPE_FILE:
            $alter .= 'TEXT';
            break;
        case NC_FIELDTYPE_FLOAT:
            $alter .= 'DOUBLE';
            break;
        case NC_FIELDTYPE_DATETIME:
            $alter .= 'DATETIME';
            break;
        case NC_FIELDTYPE_RELATION:
            $alter .= 'INT';
            break;
        case NC_FIELDTYPE_MULTISELECT:
            $alter .= 'TEXT';
            break;
        case NC_FIELDTYPE_MULTIFILE:
            $alter .= 'CHAR(255)';
            break;
    }

    switch (true) {
        case $SelectField->DefaultState != NULL &&
            !in_array($SelectField->TypeOfData_ID, array(NC_FIELDTYPE_TEXT, NC_FIELDTYPE_FILE, NC_FIELDTYPE_DATETIME)):
            $alter .= " NOT NULL DEFAULT '" . $db->escape($SelectField->DefaultState) . "'";
            break;
        case $SelectField->NotNull :
            $alter .= " NOT NULL";
            break;
        default:
            $alter .= " NULL";
    }

    $db->query($alter);

    if ($isSys) {
        return true;
    }

    if ($SelectField->DoSearch && $SelectField->TypeOfData_ID != NC_FIELDTYPE_TEXT) {
        $FieldName = $NewFieldName && $type == 2 ? $NewFieldName : $SelectField->Field_Name;
        $db->query("ALTER TABLE `" . $TableName . "` ADD INDEX (`" . $FieldName . "`)");
    }

    if ($NewFieldName) {
        nc_Core::get_object()->component->update_cache_for_multipurpose_templates();
    }

    return true;
}

/**
 * Show field list
 *
 * @param int $Id - ClassID or SystemTableID
 * @param bool is field in system table
 * @param bool show for component wizard
 * @return bool  true - if success
 */
function FieldList($Id, $isSys = 0, $isWizard = 0, $isWidget = 0) {
    global $UI_CONFIG, $ADMIN_PATH, $ADMIN_TEMPLATE;
    global $field_types, $field_type_name, $type_of_edit, $type_of_edit_name;

    $nc_core = nc_Core::get_object();
    $db = $nc_core->db;

    $Id += 0;
    if (!$Id)
        return false;

    $where = $isSys ? "`System_Table_ID`" : ($isWidget ? "`Widget_Class_ID`" : "`Class_ID`");

    if (isset($_REQUEST['fs'])) {
        $File_Mode = +$_REQUEST['fs'];
    } else {
        $queries = array(
            '`System_Table_ID`' => 'select File_Mode from Class where `System_Table_ID` = 3 and `ClassTemplate` = 0',
            '`Widget_Class_ID`' => 'select File_Mode from Widget_Class where Widget_Class_ID = ' . $Id,
            '`Class_ID`' => 'select File_Mode from Class where Class_ID = ' . $Id);
        $File_Mode = +$db->get_var($queries[$where]);
    }
    $where .= "='" . $Id . "'";

    $Result = $db->get_results("SELECT `Field_ID`, `Field_Name`, `Description`, `TypeOfData_ID`, `DoSearch`, `Extension`, `NotNull`, `TypeOfEdit_ID`, `Priority`
    FROM `Field`
    WHERE " . $where . "
    ORDER BY `Priority`");

    if (($countFields = $db->num_rows)) {

        if (!$isWizard)
            print "<form method='post' action='index.php'>\n";
        ?>
        <table border='0' cellpadding='0' cellspacing='0' width='100%'>
            <tr>
                <td>
                    <table class='nc-table nc--small nc--hovered nc--striped' width='100%'>
                        <tr>
                            <th>ID</th>
                            <th width='20%'><?=
                                CONTROL_FIELD_LIST_NAME
                                ?></th>
                            <th width='50%'><?=
                                CONTROL_FIELD_LIST_DESCRIPTION
                                ?></th>
                            <th colspan='4' class='align-center'
                                width='10%'><?= CONTROL_CLASS_CLASS_OBJECTSLIST_SHOWOBJ_DOPL ?></th>
                            <th class="nc-text-center">
                                <div class='icons icon_prior'
                                     title='<?= CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_PRIORITY ?>'></div>
                            </th>
                            <? if (!$isWizard): ?>
                                <th class="nc-text-center">
                                    <div class='icons icon_delete'
                                         title='<?= CONTROL_CONTENT_CATALOUGE_FUNCS_SHOWCATALOGUELIST_DELETE ?>'></div>
                                </th>
                            <? endif; ?>
                        </tr>

                        <?php
                        foreach ($Result as $Row) {
                            print "<tr>\n";
                            print "  <td>" . $Row->Field_ID . "</td>\n";
                            if ($isWizard) {
                                print "  <td>" . $Row->Field_Name . "</td>\n";
                            } else {
                                print "  <td><a href='index.php?fs=$File_Mode&phase=4&amp;FieldID={$Row->Field_ID}&amp;isSys={$isSys}&isWidget=$isWidget'>" . $Row->Field_Name . "</a></td>\n";
                            }
                            print "  <td>" . $Row->Description . "</td>\n";
                            print "  <td><div class='icons " . $field_types[$Row->TypeOfData_ID] . "' title='" . $field_type_name[$Row->TypeOfData_ID] . "'></div></td>\n";
                            print "  <td><div class='icons icon_search" . ($Row->DoSearch ? "" : "_disabled") . "' title='" . CONTROL_FIELD_ONE_INDEX . "'></div></td>\n";
                            print "  <td><div class='icons icon_notnull" . ($Row->NotNull ? "" : "_disabled") . "' title='" . CONTROL_FIELD_ONE_MUSTBE . "'></div></td>\n";
                            print "  <td><div class='icons icon_" . $type_of_edit[$Row->TypeOfEdit_ID] . "' title='" . $type_of_edit_name[$Row->TypeOfEdit_ID] . "'></div></td>\n";
                            print "  <td align='center'>" .
                                nc_admin_input_simple("priority[" . $Row->Field_ID . "]", ($Row->Priority ? $Row->Priority : 0), 3, '', "class='s' maxlength='5'" . ($isWizard ? " disabled" : "")) . "</td>\n";
                            if (!$isWizard)
                                print "  <td align='center'>" . nc_admin_checkbox_simple('Delete[]', $Row->Field_ID) . "</td>\n";
                            print "</tr>\n";
                        }

                        $name = $isSys ? "SystemTableID" : ($isWidget ? "widgetclass_id" : "ClassID");
                        print "<input type='hidden' name='" . $name . "' value='" . $Id . "'>\n";
                        print "<input type='hidden' name='isSys' value='" . $isSys . "'>\n";
                        print "<input type='hidden' name='fs' value='" . $File_Mode . "'>\n";
                        print $nc_core->token->get_input();
                        ?>
                    </table>
                </td>
            </tr>
        </table>
        <br>
    <?php
    } else {
        nc_print_status(CONTROL_FIELD_LIST_NONE, 'info');
    }
    if (!$isWizard) {
        if ($countFields) {
            $UI_CONFIG->actionButtons[] = array("id" => "submit",
                "caption" => CONTROL_FIELD_LIST_CHANGE,
                "action" => "mainView.submitIframeForm()");
        }
        $location = $isSys ? "systemfield" : ($isWidget ? "widgetfield" : "field");
        $UI_CONFIG->actionButtons[] = array("id" => "addClass",
            "caption" => CONTROL_FIELD_LIST_ADD,
            "action" => "urlDispatcher.load('" . $location . (+$_REQUEST['fs'] ? '_fs' : '') . ".add(" . $Id . ")')",
            "align" => "left");
        if ($countFields) {
            ?>
            <input type='hidden' name='phase' value='6'>
            <input type='hidden' name='fs' value='<?= $File_Mode ?>'>
            <input type='submit' class='hidden'>
            </form>
        <?php
        }
    }
    return true;
}

#   --- End function FieldList ---
#########################################
# Редактирование свойств отдельного поля ИЛИ СОЗДАНИЕ НОВОГО СВОЙСТВА
#########################################

/**
 * Show form to edit field or create new
 *
 * @param int Field id, 0 - if new
 * @param int ClassId or SystemTableId, if 0 - edit field
 * @param bool is field in system table
 * @param string action
 * @param string form name
 * @param string form id
 * @param string Advanced elements
 * @return bool true
 */
function FieldForm($FieldID, $Id, $isSys = 0, $action = "index.php", $FormName = '', $FormID = '', $Additional = '', $isWidget = 0) {
    global $db, $nc_core;
    global $UI_CONFIG;
    global $field_type_name, $type_of_edit_name;
    $FieldID = intval($FieldID);
    $Id = intval($Id);
    $systemTableId = null;
    $file_attach = null; // закачиваемый или нет?
    $file_download = null;
    $file_icon = null;
    $file_onlyicon = null;

    $no_multifile = false;
    if ($isSys) {
        if (!$Id && $FieldID) {
            $sql = "SELECT `System_Table_ID` FROM `Field` WHERE `Field_ID` = {$FieldID}";
            $systemTableId = $db->get_var($sql);
            $no_multifile = $systemTableId != 3;
        } else if ($Id != 3) {
            $no_multifile = true;
        }
    }

    if ($FieldID) {
        $Array = $db->get_row("SELECT `Field_Name`, `Description`, `TypeOfData_ID`, `Format`, `Extension`, `NotNull`, `Priority`, `DoSearch`,
      `Inheritance`, `DefaultState`, `TypeOfEdit_ID`, `Widget_Class_ID`, `InTableView`
      FROM `Field`
      WHERE `Field_ID`='" . $FieldID . "' AND `Checked` = 1", ARRAY_A);
        if ($Array["Widget_Class_ID"]) {
            $isWidget = 1;
        }
    } else {
        $Array = $_POST;
        $where = $isSys ? "`System_Table_ID`" : ($isWidget ? "`Widget_Class_ID`" : "`Class_ID`");
        $where .= "='" . $Id . "'";

        if (empty($Array["Priority"])) {
            $res = $db->get_var("SELECT (Priority+1) FROM `Field` WHERE " . $where . " ORDER BY `Priority` DESC LIMIT 1");
            if (!is_null($res))
                $Array["Priority"] = $res;
        }
    }
    // js для визуального заполнения "Формата"
    $js = "<script type='text/javascript' language='JavaScript'>
    " . (!empty($Array["TypeOfData_ID"]) || 1 ? "nc_field_additional_format(" . intval(!empty($Array["TypeOfData_ID"]) ? $Array["TypeOfData_ID"] : 1) . ");" : "") . "

    function nc_field_additional_format(selected_field) {

     //settings for text
     document.getElementById('div_upload_limits').style.display = ((selected_field == 6 || selected_field == 11 ) ? 'block' : 'none');

    // select FS for file field
     document.getElementById('div_field_file').style.display = ((selected_field == 6) ? 'block' : 'none');

     //don't show Format field
     document.getElementById('div_format').style.display = ((selected_field == 3 || selected_field == 1) ? 'none' : 'block');

     //settings for text
     document.getElementById('div_field_text').style.display = ((selected_field == 3 ) ? 'block' : 'none');

    //settings for string
     document.getElementById('div_field_string').style.display = ((selected_field == 1 ) ? 'block' : 'none');

     //settings for date
     document.getElementById('div_field_date').style.display = ((selected_field == 8 ) ? 'block' : 'none');

    document.getElementById('div_file_resize').style.display = ((selected_field == 11 || selected_field == 6) ? 'block' : 'none');
    document.getElementById('div_field_multifile').style.display = (selected_field == 11 ? 'block' : 'none');";

    if (!$isSys) {
        $js .= "//settings for search
		document.getElementById('dosr').disabled = ((selected_field == 9 || selected_field == 11) ? true : false);
		document.getElementById('dosr').style.background = ( (selected_field == 9 || selected_field == 11) ? '#eee' : '' );";
    }
    if (!isset($Array["DefaultState"])) {
        $Array["DefaultState"] = null;
    }
    $js .= "// default value field
     var defaultField = document.getElementById('DefaultState');

    if (defaultField) {
         // default value field disabled
         defaultField.disabled = ( (selected_field == 11 || selected_field==3 || selected_field==6 || selected_field==8 || selected_field==10) ? true : false );
         defaultField.style.background = ( (selected_field == 11 || selected_field==3 || selected_field==6 || selected_field==8) ? '#eee' : '' );

         // default value field data
         defaultField.value = (selected_field==" . (int)nc_array_value($Array, "TypeOfData_ID") . " ? '" . htmlspecialchars($Array["DefaultState"], ENT_QUOTES, $nc_core->NC_CHARSET, false) . "' : '');
    }
     return false;
    }

    function nc_select_field() {
     var selected_field = document.getElementById('TypeOfData_ID').selectedIndex + 1 ;
     nc_field_additional_format (selected_field);
     nc_field_select_fs();
     return false;
    }

    function nc_field_select_fs() {
      var selected_fs = document.getElementById('Format_FS').selectedIndex + 1;
      document.getElementById('attachment').disabled = ( selected_fs != 3 );
      document.getElementById('download').disabled = ( selected_fs != 3 );
      return false;
    }

    function nc_field_select_string_format() {
        document.getElementById('div_field_string_protect_email').style.display = document.getElementById('Format_String').value == 'email' ? 'block' : 'none';
    }

    function nc_field_select_fck(element) {
        var \$this = \$nc(element);
        var \$panel_block = \$nc('#format-panel-block');
        if (\$this.val() == 2) {
            \$panel_block.hide();
        } else {
            \$panel_block.show();
        }
    }
    function nc_field_select_transliteration(element) {
        \$nc('#transliteration_field').change(function() {
          if (\$nc('#transliteration_field').val() == 'Keyword') {
            \$nc('#use_url_rules').prop('checked', true);
          }
        });
        var \$this = \$nc(element);
        var \$panel_block = \$nc('#transliteration-fields-panel-block');
        if (\$this.is(':checked')) {
            \$panel_block.show();
            if (\$nc('#transliteration_field').val() == 'Keyword') {
              \$nc('#use_url_rules').prop('checked', true);
            }
        } else {
            \$panel_block.hide();
        }
    }
    nc_field_select_fs();
    nc_field_select_string_format();
  </script>\n";

    print "<fieldset>";
    if ($FieldID)
        print "<legend>" . ($Array["Description"] ? $Array["Description"] : $Array["Field_Name"]) . "</legend>";

    print "<br/><form method='post' action='" . $action . "' name='" . $FormName . "' id='" . $FormID . "'>";

    $Format_FS = NC_FS_PROTECTED; // тип по ФС по умолчанию
    $format_text = nc_field_parse_format("", NC_FIELDTYPE_TEXT); // для textarea значения по умолчанию

    if ($FieldID) {
        if (!$isWidget) {
            print CONTROL_FIELD_LIST_NAMELAT . ":<br>" . nc_admin_input_simple('FieldName', $Array["Field_Name"], 50, '', "maxlength='64'") . "<br><br>";
        } else {
            print CONTROL_FIELD_LIST_NAME . ": " . $Array["Field_Name"] . "<br><br>";
        }
        $format_string = array();
        $format_date = array();
        $format_file_resize = array("multifile_min"=> null, "multifile_max" => null, 
                                    "use_resize" => null,"resize_width" => null,
                                    "resize_height" => null,"use_crop" => null,
                                    "crop_mode" => null,"crop_width" => null,
                                    "crop_height" => null,"crop_x0" => null,
                                    "crop_y0" => null,"crop_x1" => null,
                                    "crop_y1" => null,"crop_ignore" => null,
                                    "crop_ignore_width" => null,"crop_ignore_height" => null,
                                    "use_preview" => null,"preview_use_resize" => null,
                                    "preview_width" => null,"preview_height" => null,
                                    "preview_use_crop" => null,"preview_crop_mode" => null,
                                    "preview_crop_width" => null,"preview_crop_height" => null,
                                    "preview_crop_x0" => null,"preview_crop_y0" => null,
                                    "preview_crop_x1" => null,"preview_crop_y1" => null,
                                    "preview_crop_ignore" => null,"preview_crop_ignore_width" => null,
                                    "preview_crop_ignore_height" => null,

                                );
        switch ($Array['TypeOfData_ID']) {
            case NC_FIELDTYPE_FILE: // определим тип ФС
                $format_file_resize = nc_field_parse_resize_options($Array['Format']);
                $Array['Format'] = array_shift(explode(';', $Array['Format']));
                $format_file = nc_field_parse_format($Array['Format'], $Array['TypeOfData_ID']);
                $Format_FS = $format_file['fs'];
                $file_attach = $format_file['disposition']; // закачиваемый или нет?
                $file_download = $format_file['download'];
                $file_icon = $format_file['icon'];
                $file_onlyicon = $format_file['onlyicon'];
                $Array['Format'] = nc_preg_replace('/(:?)(fs)(\d+)/', '', $Array['Format']); // уберем из Format тип ФС
                $Array['Format'] = nc_preg_replace('/(:?)(download)/', '', $Array['Format']); // уберем download
                $Array['Format'] = nc_preg_replace('/(:?)(onlyicon)/', '', $Array['Format']); // уберем onlyicon
                $Array['Format'] = nc_preg_replace('/(:?)(icon)/', '', $Array['Format']); // уберем icon
                $Array['Format'] = nc_preg_replace('/(:?)((attachment)|(inline))/', '', $Array['Format']); // уберем attachment
                break;
            case NC_FIELDTYPE_TEXT:
                $format_text = nc_field_parse_format($Array['Format'], $Array['TypeOfData_ID']);
                break;
            case NC_FIELDTYPE_DATETIME:
                $format_date = nc_field_parse_format($Array['Format'], $Array['TypeOfData_ID']);
                $Array['Format'] = $format_date['type'];
                break;
            case NC_FIELDTYPE_STRING:
                $format_string = nc_field_parse_format($Array['Format'], $Array['TypeOfData_ID']);
                $Array['Format'] = $format_string['format'];
                $Array['UseTransliteration'] = $format_string['use_transliteration'];
                $Array['TransliterationField'] = $format_string['transliteration_field'];
                $Array['UseUrlRules'] = $format_string['use_url_rules'];
                break;
            case NC_FIELDTYPE_MULTIFILE:
                $format_file_resize = nc_field_parse_resize_options($Array['Format']);
                $Array['Format'] = array_shift(explode(';', $Array['Format']));
                break;
        }
    } else {
        print CONTROL_FIELD_LIST_NAMELAT . ":<br>" . nc_admin_input_simple('FieldName', nc_array_value($Array, "Field_Name"), 50, '', "maxlength='64'") . "<br><br>";
    }

    print CONTROL_FIELD_LIST_DESCRIPTION . ":<br>" . nc_admin_input_simple('Description', htmlspecialchars_decode(nc_array_value($Array, "Description")), 50, '', "maxlength='255'") . "<br><br>";

    print CONTROL_FIELD_ONE_FTYPE . ":<br><select name='TypeOfData_ID' id='TypeOfData_ID' onchange='nc_select_field(); return false;'>";

    for ($i = 1; $i <= count($field_type_name); $i++) {
        // Поле типа "Связь с другими.." для системных полей не нужно
        if (($isSys || $isWidget) && $i === NC_FIELDTYPE_RELATION) {
            continue;
        }

        if ($i == NC_FIELDTYPE_MULTIFILE && $no_multifile) {
            continue;
        }

        print "<option " . ($Array["TypeOfData_ID"] == $i ? "selected" : "") . " value='" . $i . "'>" . $i . ": " . $field_type_name[$i] . "</option>\n";
    }
    print "</select><br><br>";

    $html = "<div id='div_format'>" . CONTROL_FIELD_ONE_FORMAT . ":<br>" . nc_admin_input_simple('Format', (nc_array_value($Array, "TypeOfData_ID") != NC_FIELDTYPE_TEXT ? nc_array_value($Array, "Format") : ""), 50, '', "maxlength='255'") . "<br><br></div>";

    $html .= "<div id='div_field_string'>" . nc_admin_select_simple(CONTROL_FIELD_ONE_FORMAT . ":<br/>", 'Format_String', array(
                '' => CONTROL_FIELD_ONE_FORMAT_NONE,
                'email' => CONTROL_FIELD_ONE_FORMAT_EMAIL,
                'url' => CONTROL_FIELD_ONE_FORMAT_URL,
                'html' => CONTROL_FIELD_ONE_FORMAT_HTML,
                'password' => CONTROL_FIELD_ONE_FORMAT_PASSWORD,
                'phone' => CONTROL_FIELD_ONE_FORMAT_PHONE,
                'tags' => CONTROL_FIELD_ONE_FORMAT_TAGS
            ), nc_array_value($Array, "Format"), "id='Format_String' onchange='nc_field_select_string_format();'") . "<br>";
    $html .= "<div id='div_field_string_protect_email'>";
    if (!isset($format_string['protect_email'])) {
        $format_string['protect_email'] = null;
    } 
    $html .= nc_admin_checkbox_simple('protect_email', '', CONTROL_FIELD_ONE_PROTECT_EMAIL, $format_string['protect_email']);
    $html .= "</div>";

    $options = GetTransliterateOptions(($isSys ? "system" : ($isWidget ? "widget" : "class")), $FieldID , $Id , $systemTableId);

    if (count($options) > 0) {
      if (!isset($Array['UseTransliteration'])) {
        $Array['UseTransliteration'] = 0;
      }
      $html .= "<div id='div_field_string_use_transliteration'>";
      $html .= nc_admin_checkbox_simple('use_transliteration', '1', CONTROL_FIELD_USE_TRANSLITERATION, $Array['UseTransliteration'], '', "onchange='nc_field_select_transliteration(this); return true;'");
      $html .= "</div>";

      $html .= "<div id='transliteration-fields-panel-block' style='" . ($Array['UseTransliteration'] == 1 ? '' : 'display: none;') .  "'>";
      if (!isset($Array['TransliterationField'])) {
        $Array['TransliterationField'] = '';
      }
      $html .= nc_admin_select_simple(CONTROL_FIELD_TRANSLITERATION_FIELD . ":<br/>", 'transliteration_field', $options, $Array["TransliterationField"], "id='transliteration_field'", "onchange='alert('here'); return true;'") . "<br>";
      if (!isset($Array['UseUrlRules'])) {
        $Array['UseUrlRules'] = '';
      }
      $html .= nc_admin_checkbox_simple('use_url_rules', '1', CONTROL_FIELD_USE_URL_RULES, $Array['UseUrlRules'], '');
      $html .= "</div>";
    }
    $html .= "</div>";

    // Вывод ограничений на загрузку файлов для тип поля Файл и множественный выбор файлов
    $html .= "<div id='div_upload_limits' style='display: none;'>" . CONTROL_FIELD_FILE_UPLOADS_LIMITS . "<br />
            post_max_size (" . CONTROL_FIELD_FILE_POSTMAXSIZE . "): " . ini_get('post_max_size') . " <br />
            upload_max_filesize (" . CONTROL_FIELD_FILE_UPLOADMAXFILESIZE . "): " . ini_get('upload_max_filesize') . "<br />
            max_file_uploads (" . CONTROL_FIELD_FILE_MAXFILEUPLOADS . "): " . ini_get('max_file_uploads') . "<br />
            <br /></div>";

    // расширение формата для Файлов
    $html .= "<div id='div_field_file' style='display: none;'>\r\n"
        . CLASSIFICATOR_TYPEOFFILESYSTEM . ":
            <select name='Format_FS' id='Format_FS' onchange='nc_field_select_fs(); return false;' >\r\n
               <option value='" . NC_FS_SIMPLE . "' " . (NC_FS_SIMPLE == $Format_FS ? 'selected' : '') . ">" . CONTROL_FS_NAME_SIMPLE . "</option>\r\n
               <option value='" . NC_FS_ORIGINAL . "' " . (NC_FS_ORIGINAL == $Format_FS ? 'selected' : '') . ">" . CONTROL_FS_NAME_ORIGINAL . "</option>\r\n
               <option value='" . NC_FS_PROTECTED . "' " . (NC_FS_PROTECTED == $Format_FS ? 'selected' : '') . ">" . CONTROL_FS_NAME_PROTECTED . "</option>\r\n
             </select>
             <br />" . nc_admin_checkbox_simple('attachment', '', CONTROL_FIELD_ATTACHMENT, $file_attach, '', "disabled") . "
             <br />" . nc_admin_checkbox_simple('download', '', CONTROL_FIELD_DOWNLOAD_COUNT, $file_download, '', "disabled") . "
             <div>
              <span>" . nc_admin_checkbox_simple('icon', '', CONTROL_FIELD_CAN_BE_AN_ICON, $file_icon, '') . "</span>
              <span>" . nc_admin_checkbox_simple('onlyicon', '', CONTROL_FIELD_CAN_BE_ONLY_ICON, $file_onlyicon, '') . "</span>
             </div>
            </div>";

    // расширение формата для мультиФайлов
    $html .= "<div id='div_field_multifile' style='display: none;'><br>\r\n"
        . CONTROL_FIELD_MULTIFIELD_MINMAX . ":";
        $html .= "<div  id='multifile_minmax'  style='padding-left: 15px;'>";
            $html .= CONTROL_FIELD_MULTIFIELD_MIN . ": " . nc_admin_input_simple('format_multifile_min', isset($format_file_resize) ? nc_array_value($format_file_resize, 'multifile_min') : null, 10) . " ";
            $html .= CONTROL_FIELD_MULTIFIELD_MAX . ": " . nc_admin_input_simple('format_multifile_max', isset($format_file_resize) ? nc_array_value($format_file_resize,'multifile_max') : null, 10);
        $html .= "</div>";
    $html .= "</div>";

    // расширение формата для текста
    $html .= "<div id='div_field_text' style='display: none;'>
  <table class='admin_table' width='40%' >
  <col width='25%'/><col width='25%'/><col width='25%'/><col width='25%'/>
  <tr align='center'>
  <th></th>
  <th>" . CONTROL_CONTENT_SUBDIVISION_FUNCS_ACCESS_INHERIT . "</th>
  <th>" . CONTROL_CONTENT_SUBDIVISION_FUNCS_OBJ_YES . "</th>
  <th>" . CONTROL_CONTENT_SUBDIVISION_FUNCS_OBJ_NO . "</th>
  </tr>
  <tr align='center'>
  <td>" . CONTROL_CLASS_CLASS_OBJECTSLIST_SHOWOBJ_HTML . "</td>";
    // разрешать HTML-теги
    for ($i = 0; $i <= 2; $i++) {
        $html .= "<td bgcolor='" . ($format_text['html'] == $i ? "#E7E7E7" : "#FFFFFF") . "'>" .
            nc_admin_radio_simple('format_html', $i, '', (isset($Array['format_html']) && $Array['format_html'] == $i) || $format_text['html'] == $i, '', "class='w'") . "
      </td>";
    }
    $html .= "</tr><tr align='center'>
  <td>" . CONTROL_CLASS_CLASS_OBJECTSLIST_SHOWOBJ_BR . "</td>";
    // перенос строки
    for ($i = 0; $i <= 2; $i++) {
        $html .= "<td bgcolor='" . ($format_text['br'] == $i ? "#E7E7E7" : "#FFFFFF") . "'>" .
            nc_admin_radio_simple('format_br', $i, '', (isset($Array['format_br']) && $Array['format_br'] == $i) || $format_text['br'] == $i, '', "class='w'") . "
      </td>";
    }

    $html .= "
  </tr><tr align='center'>
  <td>" . CONTROL_FIELD__EDITOR_EMBED_TO_FIELD . "</td>";
    // встроить редактор
    for ($i = 0; $i <= 2; $i++) {
        $html .= "<td bgcolor='" . ($format_text['fck'] == $i ? "#E7E7E7" : "#FFFFFF") . "'>" .
            nc_admin_radio_simple('format_fck', $i, '', (isset($Array['format_fck']) && $Array['format_fck'] == $i) || $format_text['fck'] == $i, '', "class='w' onchange='nc_field_select_fck(this); return true;'") . "
      </td>";
    }

    $html .= "</tr></table><br/>";


    $html .= CONTROL_FIELD__TEXTAREA_SIZE . ":<br/>
  <table class='admin_table' width='40%' >
  <col width='50%'/><col width='50%'/>
    <tr align='center'>
      <td>" . CONTROL_FIELD_HEIGHT . "</td>
      <td>" . nc_admin_input_simple('format_height', isset($Array['format_height']) ? $Array['format_height'] : $format_text['rows'], 0, 'width:100%') . "</td></tr>
    <tr align='center'>
      <td>" . CONTROL_FIELD_WIDTH . "</td>
      <td>" . nc_admin_input_simple('format_width', isset($Array['format_height']) ? $Array['format_width'] : $format_text['cols'], 0, 'width:100%') . "</td>
    </tr>
  </table>
  <br />";

    $panels = array(
        0 => CONTROL_FIELD_PANELS_DEFAULT,
    );
    $sql = "SELECT `Wysiwyg_Panel_ID`, `Name` FROM `Wysiwyg_Panel` " .
        "WHERE `Editor` = 'ckeditor' " .
        "ORDER BY `Wysiwyg_Panel_ID` ASC";
    foreach ((array)$db->get_results($sql, ARRAY_A) as $panel) {
        $panels[$panel['Wysiwyg_Panel_ID']] = $panel['Name'];
    }

    $html .= "<div id='format-panel-block' style='" . ($format_text['fck'] == 2 ? 'display: none;' : '') . "'>" .
        CONTROL_FIELD_PANELS . ":<br><div class='nc-select'>" .
        nc_admin_select_simple('', 'format_panel', $panels, isset($Array['format_panel']) ? $Array['format_panel'] : $format_text['panel']) .
        "<i class='nc-caret'></i></div><br><br /></div>";

    $html .= nc_admin_checkbox_simple('format_typo', 1, CONTROL_FIELD_TYPO, isset($Array['format_typo']) ? $Array['format_typo'] : $format_text['typo']) . "
  <br />" . nc_admin_checkbox_simple('format_bbcode', 1, CONTROL_FIELD_BBCODE_ENABLED, isset($Array['format_bbcode']) ? $Array['format_bbcode'] : $format_text['bbcode']) . "
  <br/><br/>
  </div>";
    if (!isset($format_date['calendar'])) {
        $format_date['calendar'] = null;
    } 
    $html .= "<div id='div_field_date' style='display: none'>" . nc_admin_checkbox_simple('use_calendar', 1, CONTROL_FIELD_USE_CALENDAR, false, 'format_use_calendar', ($format_date['calendar'] ? "checked='checked'" : "")) . "
            </div><br/>";

    $html .= "<div id='div_file_resize' style='display: none'>";
        $gd_version = extension_loaded('gd') ? gd_info() : 0;
        preg_match('/\d/', $gd_version['GD Version'], $match);
        if (!(isset($match[0]) && $match[0] >= 2 )) {
            $html .= nc_print_status(CONTROL_FIELD_FILE_WRONG_GD, 'info', null, 1);
        }

        $html .= nc_admin_checkbox_simple('format_use_resize', 1, CONTROL_FIELD_MULTIFIELD_USE_IMAGE_RESIZE, isset($format_file_resize) ? $format_file_resize['use_resize'] : null);
        $html .= "<div  id='use_resize'  style='padding-left: 15px;'>";
            $html .= CONTROL_FIELD_MULTIFIELD_IMAGE_WIDTH . ": " . nc_admin_input_simple('format_resize_width', isset($format_file_resize) ?  $format_file_resize['resize_width'] : null, 10) . " ";
            $html .= CONTROL_FIELD_MULTIFIELD_IMAGE_HEIGHT . ": " . nc_admin_input_simple('format_resize_height', isset($format_file_resize) ?   $format_file_resize['resize_height'] : null, 10);
        $html .= "</div><br>";

        $html .= nc_admin_checkbox_simple('format_use_crop', 1, CONTROL_FIELD_MULTIFIELD_USE_IMAGE_CROP, isset($format_file_resize) ?  $format_file_resize['use_crop'] : null, 'format_use_crop'). "<br>";
        $html .= "<div id='use_crop'  style='padding-left: 15px;'>";
            $html .= nc_admin_radio_simple('format_crop_mode', 1, CONTROL_FIELD_MULTIFIELD_CROP_CENTER, isset($format_file_resize) ?   $format_file_resize['crop_mode'] == 1 : null, 'crop_mode_1');
            $html .= nc_admin_radio_simple('format_crop_mode', 0, CONTROL_FIELD_MULTIFIELD_CROP_COORD, isset($format_file_resize) ?  $format_file_resize['crop_mode'] == 0 : null, 'crop_mode_2');
            $html .= "<div id='crop_center'>";
                $html .= CONTROL_FIELD_MULTIFIELD_IMAGE_WIDTH . ": " . nc_admin_input_simple('format_crop_width', isset($format_file_resize) ?  $format_file_resize['crop_width'] : null, 10) . " ";
                $html .= CONTROL_FIELD_MULTIFIELD_IMAGE_HEIGHT . ": " . nc_admin_input_simple('format_crop_height', isset($format_file_resize) ?  $format_file_resize['crop_height'] : null, 10);
            $html .= "</div><br>";
            $html .= "<div id='crop_coord'>";
                $html .= "X0: " . nc_admin_input_simple('format_crop_x0',  isset($format_file_resize) ?  $format_file_resize['crop_x0'] : null, 10) . " ";
                $html .= "Y0: " . nc_admin_input_simple('format_crop_y0',  isset($format_file_resize) ?  $format_file_resize['crop_y0'] : null, 10) . "<br>";
                $html .= "X1: " . nc_admin_input_simple('format_crop_x1',  isset($format_file_resize) ?  $format_file_resize['crop_x1'] : null, 10) . " ";
                $html .= "Y1: " . nc_admin_input_simple('format_crop_y1',  isset($format_file_resize) ?  $format_file_resize['crop_y1'] : null, 10);
            $html .= "</div>";
            $html .= nc_admin_checkbox_simple('format_crop_ignore', 1, CONTROL_FIELD_MULTIFIELD_CROP_IGNORE, isset($format_file_resize) ?  $format_file_resize['crop_ignore'] : null);
            $html .= "<div>";
                $html .= CONTROL_FIELD_MULTIFIELD_IMAGE_WIDTH . ": " . nc_admin_input_simple('format_crop_ignore_width', isset($format_file_resize) ?  $format_file_resize['crop_ignore_width'] : null, 10) . " ";
                $html .= CONTROL_FIELD_MULTIFIELD_IMAGE_HEIGHT . ": " . nc_admin_input_simple('format_crop_ignore_height', isset($format_file_resize) ?  $format_file_resize['crop_ignore_height'] : null, 10);
            $html .= "</div>";
        $html .= "</div><br/>";

        $html .= nc_admin_checkbox_simple('format_use_preview', 1, CONTROL_FIELD_MULTIFIELD_USE_IMAGE_PREVIEW, isset($format_file_resize) ?  $format_file_resize['use_preview'] : null, 'format_use_preview'). "<br>";
        $html .= "<div id='use_preview' style='padding-left: 15px;'>";
            $html .= nc_admin_checkbox_simple('format_preview_use_resize', 1, CONTROL_FIELD_MULTIFIELD_USE_PREVIEW_RESIZE, (isset($format_file_resize) ?  $format_file_resize['preview_use_resize']: null || ((isset($format_file_resize) ?  $format_file_resize['preview_width'] : null) && (isset($format_file_resize) ?  $format_file_resize['preview_height'] : null))));
            $html .= "<div id='preview_use_resize'  style='padding-left: 15px;'>";
                $html .= CONTROL_FIELD_MULTIFIELD_IMAGE_WIDTH . ": " . nc_admin_input_simple('format_preview_width', isset($format_file_resize) ?  $format_file_resize['preview_width'] : null, 10) . " ";
                $html .= CONTROL_FIELD_MULTIFIELD_IMAGE_HEIGHT . ": " . nc_admin_input_simple('format_preview_height', isset($format_file_resize) ?  $format_file_resize['preview_height'] : null, 10);
            $html .= "</div><br>";

            $html .= nc_admin_checkbox_simple('format_preview_use_crop', 1, CONTROL_FIELD_MULTIFIELD_PREVIEW_USE_IMAGE_CROP, isset($format_file_resize) ?  $format_file_resize['preview_use_crop'] : null, 'format_preview_use_crop'). "<br>";
            $html .= "<div id='preview_use_crop'  style='padding-left: 15px;'>";
                $html .= nc_admin_radio_simple('format_preview_crop_mode', 1, CONTROL_FIELD_MULTIFIELD_CROP_CENTER, isset($format_file_resize) ?  $format_file_resize['preview_crop_mode'] == 1 : null, 'preview_crop_mode_1');
                $html .= nc_admin_radio_simple('format_preview_crop_mode', 0, CONTROL_FIELD_MULTIFIELD_CROP_COORD, isset($format_file_resize) ?  $format_file_resize['preview_crop_mode'] == 0 : null, 'preview_crop_mode_2');
                $html .= "<div id='preview_crop_center'>";
                    $html .= CONTROL_FIELD_MULTIFIELD_IMAGE_WIDTH . ": " . nc_admin_input_simple('format_preview_crop_width', isset($format_file_resize) ?  $format_file_resize['preview_crop_width'] : null, 10) . " ";
                    $html .= CONTROL_FIELD_MULTIFIELD_IMAGE_HEIGHT . ": " . nc_admin_input_simple('format_preview_crop_height', isset($format_file_resize) ?  $format_file_resize['preview_crop_height'] : null, 10);
                $html .= "</div><br>";
                $html .= "<div id='preview_crop_coord'>";
                    $html .= "X0: " . nc_admin_input_simple('format_preview_crop_x0', isset($format_file_resize) ?  $format_file_resize['preview_crop_x0'] : null, 10) . " ";
                    $html .= "Y0: " . nc_admin_input_simple('format_preview_crop_y0', isset($format_file_resize) ?  $format_file_resize['preview_crop_y0'] : null, 10) . "<br>";
                    $html .= "X1: " . nc_admin_input_simple('format_preview_crop_x1', isset($format_file_resize) ?  $format_file_resize['preview_crop_x1'] : null, 10) . " ";
                    $html .= "Y1: " . nc_admin_input_simple('format_preview_crop_y1', isset($format_file_resize) ?  $format_file_resize['preview_crop_y1'] : null, 10);
                $html .= "</div><br>";
                $html .= nc_admin_checkbox_simple('format_preview_crop_ignore', 1, CONTROL_FIELD_MULTIFIELD_PREVIEW_CROP_IGNORE, isset($format_file_resize) ?  $format_file_resize['preview_crop_ignore'] : null);
                $html .= "<div>";
                    $html .= CONTROL_FIELD_MULTIFIELD_IMAGE_WIDTH . ": " . nc_admin_input_simple('format_preview_crop_ignore_width', isset($format_file_resize) ?  $format_file_resize['preview_crop_ignore_width'] : null, 10) . " ";
                    $html .= CONTROL_FIELD_MULTIFIELD_IMAGE_HEIGHT . ": " . nc_admin_input_simple('format_preview_crop_ignore_height', isset($format_file_resize) ?  $format_file_resize['preview_crop_ignore_height'] : null, 10);
                $html .= "</div>";
            $html .= "</div><br/>";
        $html .= "</div><br/>";
    $html .= "</div><br/>";

    echo $html;

    echo nc_admin_textarea_simple('Extension', nc_array_value($Array, 'Extension'), CONTROL_FIELD_ONE_EXTENSION . ":<br>", 7, 0, '', 'soft');
    echo "<br><br>";

    print nc_admin_checkbox_simple('NotNull', 1, CONTROL_FIELD_ONE_MUSTBE, nc_array_value($Array, "NotNull"), 'notn') . "<br>";
    if ($isSys) {
        print nc_admin_checkbox_simple('Inheritance', 1, CONTROL_FIELD_ONE_INHERITANCE, nc_array_value($Array, "Inheritance"), 'inhr') . "<br>";
    } else {
        print nc_admin_checkbox_simple('DoSearch', 1, CONTROL_FIELD_ONE_INDEX, nc_array_value($Array, "DoSearch") && nc_array_value($Array, 'TypeOfData_ID') != NC_FIELDTYPE_RELATION, 'dosr', (nc_array_value($Array, 'TypeOfData_ID') != NC_FIELDTYPE_RELATION ? " disabled='disabled'" : "")) . "<br>";
        print nc_admin_checkbox_simple('InTableView', 1, CONTROL_FIELD_ONE_IN_TABLE_VIEW, nc_array_value($Array, "InTableView")) . "<br>";
    }

    print '<br>';

    print CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_PRIORITY . ": " . nc_admin_input_simple('Priority', (int)nc_array_value($Array, 'Priority'), 3, '', "maxlength='5'") . "<br><br>";

    print CONTROL_FIELD_ONE_DEFAULT . "*:<br>";
    print nc_admin_input_simple('DefaultState', $Array["DefaultState"], 50, '', "id='DefaultState' maxlength='255'") . "\n";
    print "<br>* " . CONTROL_FIELD_ONE_DEFAULT_NOTE . ".<br><br>\n";

    print "<table class='admin_table' width='60%'>" .
        "<col width='25%'/><col width='25%'/><col width='25%'/><col width='25%'/>" .
        "<tr><td></td>";

    if (!nc_array_value($Array, "TypeOfEdit_ID"))
        $Array["TypeOfEdit_ID"] = 1;

    for ($i = 1; $i <= count($type_of_edit_name); $i++) {
        print "<td style='text-align:center'><label for='mod" . $i . "'>" . $type_of_edit_name[$i] . "</label></td>";
    }

    print "</tr><tr><td style='text-align:center'>" . CONTROL_FIELD_ONE_ACCESS . "</td>";

    for ($i = 1; $i <= count($type_of_edit_name); $i++) {
        print "<td style='text-align:center'>" .
            nc_admin_radio_simple('TypeOfEdit_ID', $i, '', $Array["TypeOfEdit_ID"] == $i, "mod" . $i, "class='w'") . "</td>";
    }

    print "</tr>" .
        "</table>";

    // js для визуальной настройки формата
    print $js;

    ?>
     <script type='text/javascript' language='JavaScript'>
         $nc("#icon").click(function() {
             if ($nc(this).is(':checked')) { $nc("#onlyicon").parent("span").show(); }
             else { $nc("#onlyicon").parent("span").hide(); }
         }).triggerHandler('click');

        $nc('#format_use_resize').click(function() {
            $nc('#use_resize').toggle(this.checked);
        });
        $nc('#format_use_resize').triggerHandler('click');

        $nc('#format_use_crop').click(function() {
            $nc('#use_crop').toggle(this.checked);
        });
        $nc('#format_use_crop').triggerHandler('click');

        $nc('#crop_mode_1').click(function() {
            $nc('#crop_center').toggle(this.checked);
            $nc('#crop_coord').toggle(!this.checked);
        });
        $nc('#crop_mode_1').triggerHandler('click');

        $nc('#crop_mode_2').click(function() {
            $nc('#crop_coord').toggle(this.checked);
            $nc('#crop_center').toggle(!this.checked);
        });
        $nc('#crop_mode_2').triggerHandler('click');

        $nc('#format_use_preview').click(function() {
            $nc('#use_preview').toggle(this.checked);
        });
        $nc('#format_use_preview').triggerHandler('click');

        $nc('#format_preview_use_resize').click(function() {
            $nc('#preview_use_resize').toggle(this.checked);
        });
        $nc('#format_preview_use_resize').triggerHandler('click');

        $nc('#format_preview_use_crop').click(function() {
            $nc('#preview_use_crop').toggle(this.checked);
        });
        $nc('#format_preview_use_crop').triggerHandler('click');

        $nc('#preview_crop_mode_1').click(function() {
            $nc('#preview_crop_center').toggle(this.checked);
            $nc('#preview_crop_coord').toggle(!this.checked);
        });
        $nc('#preview_crop_mode_1').triggerHandler('click');

        $nc('#preview_crop_mode_2').click(function() {
            $nc('#preview_crop_coord').toggle(this.checked);
            $nc('#preview_crop_center').toggle(!this.checked);
        });
        $nc('#preview_crop_mode_2').triggerHandler('click');
    </script>
    <?

    if (!$FieldID) {
        $UI_CONFIG->actionButtons[] = array("id" => "submit",
            "caption" => CONTROL_FIELD_LIST_ADD,
            "action" => "mainView.submitIframeForm('" . $FormID . "')");
    } else {
        $UI_CONFIG->actionButtons[] = array("id" => "submit",
            "caption" => CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_SAVE,
            "action" => "mainView.submitIframeForm('" . $FormID . "')");
    }

    $name = $isSys ? "SystemTableID" : ($isWidget ? "widgetclass_id" : "ClassID");

    if ($Id) {
        print "<input type='hidden' name='" . $name . "' value='" . $Id . "'>\n";
        print "<input type='hidden' name='phase' value='3'>\n";
    } else {
        if ($isSys) {
            print "<input type='hidden' name='SystemTableID' value='" . $systemTableId . "'>\n";
        }
        print "<input type='hidden' name='FieldID' value='" . $FieldID . "'>\n";
        print "<input type='hidden' name='phase' value='5'>\n";
    }
    print $Additional;
    print $nc_core->token->get_input();
    print "<input type='hidden' name='isSys' value='" . $isSys . "'>\n";
    print "<input type='hidden' name='fs' value='" . +$_REQUEST['fs'] . "'>";
    print "<input type='submit' class='hidden'>\n";
    print "<input type='hidden' name='isWidget' value='$isWidget' />";
    print "</form>\n";

    print "</fieldset>";

    return true;
}

/**
 * Insert into DB properties of field
 *
 * @param bool is field in system table
 * @return int field id: 0 - unknown error, -1 = incorrect field name,
 *                      -2 = field name is a mysql_keyword or is the same as a global variable name,
 *                      -3 = field already exits, -4 = incorrect field format
 */
function FieldCompleted() {
    global $db;
    global $ClassID, $SystemTableID, $widgetclass_id;
    global $Inheritance, $DoSearch, $InTableView;
    global $FieldID, $FieldName, $Description, $TypeOfData_ID, $TypeOfEdit_ID, $Format, $Format_String, $Format_FS, $Extension, $NotNull, $Priority, $DefaultState, $attachment, $download, $icon, $onlyicon;
    global $format_height, $format_width, $format_html, $format_br, $format_fck, $format_panel, $format_typo, $format_bbcode, $use_calendar, $protect_email, $use_transliteration, $transliteration_field, $use_url_rules;
    global $isSys;

    $nc_core = nc_Core::get_object();
    $input = $nc_core->input;

    $ClassID = (int)$ClassID;
    $FieldID = (int)$FieldID;

    $Format = stripslashes($Format);

    if ($TypeOfData_ID == NC_FIELDTYPE_STRING)
        $Format = $Format_String;
    if ($FieldID) {
        $TableName = $isSys ? GetSystemTableName(GetSystemTableIDByFieldID($FieldID)) : "Message" . GetClassIDByFieldID($FieldID);
    } else {
        $TableName = $isSys ? GetSystemTableName($SystemTableID) : "Message" . $ClassID;
    }

    // widgets
    $widgetclass_id = !empty($widgetclass_id) ? (int)$widgetclass_id : 0;
    if ($FieldID) {
        $widgetclass_id = $db->get_var("SELECT `Widget_Class_ID` FROM `Field` WHERE `Field_ID`= '" . $FieldID . "'");
    }

    $NotNull += 0;
    $DoSearch += 0;
    $InTableView += 0;
    $Inheritance += 0;
    $Priority += 0;

    $format_height += 0;
    $format_width += 0;
    $format_html += 0;
    $format_br += 0;
    $format_fck += 0;
    $format_panel += 0;
    $format_typo += 0;
    $format_bbcode += 0;

    $use_calendar += 0;

    // float field
    if ($TypeOfData_ID == NC_FIELDTYPE_FLOAT) {
        $DefaultState = str_replace(",", ".", $DefaultState);
    }

    $FieldName = str_replace(' ', '', $FieldName);
    if (!isCorrectFormat($Format, $TypeOfData_ID)) {
        return -4;
    }

    if (nc_is_mysql_keyword($FieldName) || nc_is_global_variable($FieldName)) {
        return -2;
    }

    // Поля, название которых начинается с 'nc', являются зарезервированными
    // «на всякий случай» как системные. Исключение составляет ncDuration,
    // которое используется для серийных рассылок.
    if (substr($FieldName, 0, 2) === 'nc' && $FieldName !== 'ncDuration') {
        return -2;
    }

    $post = $input->fetch_post();
    //для совместимости со старой схемой
    //$post['format_preview_width'] && $post['format_preview_height'] === $post['format_preview_use_resize']
    if (empty($post['format_preview_use_resize'])) {
        $post['format_preview_width'] = $post['format_preview_height'] = 0;
    }

    // Добавим к Формату тип файловой системы\ настройки textarea
    if ($TypeOfData_ID == NC_FIELDTYPE_FILE) {
        if (!$Format_FS) {
            $Format_FS = NC_FS_SIMPLE;
        }
        $Format .= ":fs" . $Format_FS;
        $Format .= ":" . ($attachment ? 'attachment' : 'inline');
        $Format .= ($download ? ':download' : '');
        $Format .= ($icon ? ':icon' : '');
        $Format .= ($onlyicon ? ':onlyicon' : '');

        if (nc_substr($Format, 0, 1) === ':') {
            $Format = nc_substr($Format, 1);
        }

        $Format .= ";";
        foreach (nc_field_parse_resize_options('') as $key => $value) {
            $Format .= "$key:" . (int)nc_array_value($post, 'format_' . $key) . ";";
        }

    } else if ($TypeOfData_ID == NC_FIELDTYPE_TEXT) {
        $Format = ($format_height ? $format_height : 5) . ":" . ($format_width ? $format_width : 60) . ";";
        $Format .= "html:" . $format_html . ";br:" . $format_br . ";fck:" . $format_fck . ";";
        $Format .= "panel:" . $format_panel . ";typo:" . $format_typo . ";bbcode:" . $format_bbcode . ";";
    } else if ($TypeOfData_ID == NC_FIELDTYPE_DATETIME && $use_calendar) {
        $Format .= ($Format ? ";" : "") . 'calendar';
    } else if ($TypeOfData_ID == NC_FIELDTYPE_STRING) {
        if ($Format == 'email' && $protect_email) {
            $Format .= ':protect';
        } else {
          $Format .= ':';
        }
        if ($use_transliteration) {
            $Format .= ':'.$use_transliteration;
            $Format .= ':'.$transliteration_field;
            $Format .= ':'.$use_url_rules;
        }
    } else if ($TypeOfData_ID == NC_FIELDTYPE_MULTIFILE) {
        $Format .= ";";
        foreach (nc_field_parse_resize_options('') as $key => $value) {
            $Format .= "$key:" . (int)nc_array_value($post, 'format_' . $key) . ";";
        }
    }

    $data = array(
        'Class_ID' => $ClassID,
        'Field_Name' => $FieldName,
        'Description' => htmlspecialchars(stripslashes($Description), ENT_QUOTES, $nc_core->NC_CHARSET, false),
        'TypeOfData_ID' => (int)$TypeOfData_ID,
        'Format' => $Format,
        'Extension' => stripslashes($Extension),
        'NotNull' => $NotNull,
        'Priority' => $Priority,
        'DoSearch' => $DoSearch,
        'DefaultState' => stripslashes($DefaultState),
        'Inheritance' => $Inheritance,
        'System_Table_ID' => (int)$SystemTableID,
        'Widget_Class_ID' => (int)$widgetclass_id,
        'TypeOfEdit_ID' => (int)$TypeOfEdit_ID,
        'Checked' => 1,
        'InTableView' => $InTableView,
    );
    
    // Add new field
    if (!$FieldID) {
        nc_Core::get_object()->event->execute(nc_Event::BEFORE_FIELD_CREATED, 0, array(0 => $data));

        if ($db->get_results(
                $widgetclass_id
                    ? "SELECT 1 FROM `Field` WHERE `Widget_Class_ID` = '$widgetclass_id' AND `Field_Name` = '" . $db->escape($FieldName) . "'"
                    : "SHOW FIELDS FROM `$TableName` LIKE '" . $db->escape($FieldName) . "'"
                )
        ) {
            return -3;
        }

        if (!$FieldName || nc_preg_match("/[^a-zA-Z0-9_]/", $FieldName)) {
            return -1;
        }
        if (!$FieldName || nc_preg_match("/^[\d]+/", $FieldName)) {
            return -5;
        }
        // check if field name is already exists
        if (!$widgetclass_id) {
            $table = $ClassID ? "`Message" . $ClassID . "`" : $db->get_var("select `System_Table_Name` from `System_Table` where `System_Table_ID`=" . $SystemTableID);
            if ($db->column_exists($table, $FieldName)) {
                return -3;
            }
        }

        $CurrentFieldID = nc_db_table::make('Field')->insert($data);

        if ($CurrentFieldID) {
            if (!$widgetclass_id) {
                ColumnInMessage($CurrentFieldID, 1, $db);
            }

            $nc_core->component->update_cache_for_multipurpose_templates();

            nc_Core::get_object()->event->execute(nc_Event::AFTER_FIELD_CREATED, $CurrentFieldID, array($CurrentFieldID => $data));
        }

        return $CurrentFieldID;
    } else {
        $data_before_change = $db->get_row("SELECT * FROM `Field` WHERE `Field_ID` = $FieldID", ARRAY_A);
        $data['Class_ID'] = $data_before_change['Class_ID'];
        $data['Checked'] = $data_before_change['Checked'];

        // Update field
        nc_Core::get_object()->event->execute(nc_Event::BEFORE_FIELD_UPDATED, $FieldID, array($FieldID => $data));

        $NewFieldName = false;
        if (!$widgetclass_id) {
            if (!$FieldName || nc_preg_match("/[^a-zA-Z0-9_]/", $FieldName)) {
                return -1;
            }

            if (!$FieldName || nc_preg_match("/^[\d]+/", $FieldName)) {
                return -5;
            }

            $sql = "SELECT `Field_Name` FROM `Field` WHERE `Field_ID` = {$FieldID} LIMIT 1";
            $OldFieldName = $db->get_var($sql);

            if (($FieldName) != ($OldFieldName)) {
                if ($db->column_exists($TableName, $FieldName)) {
                    return -3;
                }

                $NewFieldName = $FieldName;
            }
        }

        unset($data['Field_Name']); // будет обновлено в ColumnInMessage()
        $result = nc_db_table::make('Field')->where_id($FieldID)->update($data);

        if (($result || $NewFieldName) && !$widgetclass_id) {
            ColumnInMessage($FieldID, 2, $db, $NewFieldName);
        }

        nc_Core::get_object()->event->execute(nc_Event::AFTER_FIELD_UPDATED, $FieldID, array($FieldID => $data));

        return $FieldID;
    }

    return 0;
}

/**
 * Update priority
 *
 * @param array priority
 * @return bool
 */
function UpdateFieldPriority($priority) {
    global $db, $nc_core;

    if (is_array($priority) && !empty($priority)) {
        $field_priorities = array();
        $field_ids = array();
        foreach ($priority as $k => $v) {
            $field_priorities[(int)$k] = (int)$v;
            $field_ids[(int)$k] = (int)$k;
        }
        unset($priority);

        $field_data = $db->get_results(
            "SELECT * FROM `Field` WHERE `Field_ID` IN (" . join(', ', $field_ids) . ")",
            ARRAY_A,
            'Field_ID'
        );

        foreach ($field_priorities as $field_id => $field_priority) {
            $field_priorities[$field_id] = $field_priority;
        }

        $nc_core->event->execute(nc_Event::BEFORE_FIELD_UPDATED, array_keys($field_ids), $field_data);
        foreach ($field_priorities as $field_id => $field_priority) {
            $updated = $db->query("UPDATE `Field` SET `Priority` = $field_priority WHERE `Field_ID` = $field_id");
            if (!$updated) {
                unset($field_ids[$field_id], $field_data[$field_id]);
            }
        }
        if ($field_ids) {
            $nc_core->event->execute(nc_Event::AFTER_FIELD_UPDATED, array_keys($field_ids), $field_data);
        }
        
    }
    return true;
}

/**
 * Confirm to field delete
 *
 * @param array fields
 * @param int Id - ClassId or SystemTableID
 * @param bool is field in system table
 * @return empty
 */
function ConfirmFieldsRemoval($fields, $Id, $isSys = 0, $widgetclass_id = '') {

    global $db, $UI_CONFIG;
    global $priority;

    $nc_core = nc_Core::get_object();
    $fields = array_map('intval', $fields);
    $field_array = $db->get_results("SELECT `Field_ID`, `Field_Name`, `Description`, `TypeOfData_ID` FROM `Field` WHERE Field_ID IN (" . join(',', $fields) . ")", ARRAY_A);

    if ($db->num_rows > 1) {
        nc_print_status(CONTROL_FIELD_MSG_CONFIRM_REMOVAL_MANY, 'info');
    } else {
        nc_print_status(CONTROL_FIELD_MSG_CONFIRM_REMOVAL_ONE, 'info');
    }

    $name = $isSys ? "SystemTableID" : "ClassID";

    print "<form method='post' action='index.php'>\n";
    print "<input type='hidden' name='phase' value='7'>\n";
    print "<input type='hidden' name='widgetclass_id' value='" . $widgetclass_id . "'>\n";
    print "<input type='hidden' name='" . $name . "' value='" . $Id . "'>\n";
    print "<input type='hidden' name='isSys' value='" . $isSys . "'>\n";
    print "<input type='hidden' name='fs' value='" . $_REQUEST['fs'] . "'>";
    print $nc_core->token->get_input();

    print "<ul>\n";
    foreach ((array)$field_array as $field) {
        print "  <li>" . $field['Field_Name'] . " (" . $field['Description'] . ")<input type='hidden' name='Delete[]' value='" . $field['Field_ID'] . "'>";
    }
    print "</ul>\n";

    if ($priority)
        foreach ($priority as $key => $val)
            echo "<input type='hidden' name='priority[" . $key . "]' value='" . $val . "'>\n";
    print "</form>\n";

    $UI_CONFIG->actionButtons[] = array(
        "id" => "submit",
        "caption" => CONTROL_FIELD_CONFIRM_REMOVAL,
        "action" => "mainView.submitIframeForm()",
        "red_border" => true,
    );
    return;
}

/**
 * Delete fields
 *
 * @param array $fields
 * @return int count deleted fields
 */
function DeleteFields($fields) {
    global $db, $UI_CONFIG;
    // start up value
    $fields_to_delete = array();
    // if no array
    if (empty($fields))
        return 0;
    //
    $fields = array_map('intval', $fields);
    $fields_data = $db->get_results("SELECT * FROM `Field`
    WHERE `Field_ID` IN (" . join(", ", $fields) . ")", ARRAY_A, 'Field_ID');
    // walk
    foreach ($fields_data as $field) {
        if (ColumnInMessage($field['Field_ID'], 3, $db)) {
            $field_id = intval($field['Field_ID']);
            $class_id = (int)$field['Class_ID'];
            $sys_table_id = (int)$field['System_Table_ID'];
            $fields_to_delete[] = $field_id;
            $UI_CONFIG->treeChanges['deleteNode'][] = ($sys_table_id ? "system" : "") . "field-" . $field_id;
            $entity = nc_image_generator::get_entity_by_class_id_or_system_table($class_id, $sys_table_id);
            nc_image_generator::remove_generated_images($entity, $field_id);
        }
    }
    // delete fields from base
    if (!empty($fields_to_delete)) {
        nc_Core::get_object()->event->execute(nc_Event::BEFORE_FIELD_DELETED, $fields_to_delete, $fields_data);

        // delete fields files
        DeleteFieldsFiles($fields_to_delete);
        // delete fields from base
        $db->query("DELETE FROM `Field` WHERE `Field_ID` IN (" . join(", ", $fields_to_delete) . ")");
        nc_Core::get_object()->component->update_cache_for_multipurpose_templates();

        nc_Core::get_object()->event->execute(nc_Event::AFTER_FIELD_DELETED, $fields_to_delete, $fields_data);

        return $db->rows_affected;
    }
    // return 0 if no fields deleted from base
    return 0;
}

function DeleteFieldsFiles($fields) {
    global $db, $FILES_FOLDER;
    // set as array
    $fields = (array)$fields;
    $file_fields_to_delete = array();
    // validate for security reason
    $fields = array_map("intval", $fields);
    // return if array is empty
    if (empty($fields))
        return 0;

    $deleted = 0;

    //поле "Файл"
    // get files info to delete
    $files_to_delete = (array)$db->get_results("SELECT ft.`Field_ID`, ft.`Virt_Name`, ft.`File_Path`, fl.`System_Table_ID` FROM `Filetable` AS ft
    LEFT JOIN `Field` as fl ON ft.`Field_ID` = fl.`Field_ID`
    WHERE ft.`Field_ID` IN (" . join(", ", $fields) . ") AND fl.`TypeOfData_ID` = " . NC_FIELDTYPE_FILE, ARRAY_A);

    foreach ($files_to_delete AS $_file_to_delete) {
        // set full path to file
        $file_to_delete_path = $FILES_FOLDER . $_file_to_delete['File_Path'] . $_file_to_delete['Virt_Name'];
        // try to delete file from disk
        if (is_writable($file_to_delete_path)) {
            @unlink($file_to_delete_path);
            $file_fields_to_delete[] = $_file_to_delete['Field_ID'];
        }
    }
    // drop rows form `Filetable` table
    if (!empty($file_fields_to_delete)) {
        $db->query("DELETE FROM `Filetable` WHERE `Field_ID` IN (" . join(", ", $file_fields_to_delete) . ")");
        $deleted += $db->rows_affected;
    }

    //поле "Мультифайл"
    $multifield_delete = array();

    $nc_core = nc_Core::get_object();

    $sql = "SELECT m.`Field_ID`, m.`Path`, m.`Preview` FROM `Multifield` AS m " .
        "LEFT JOIN `Field` as f ON m.`Field_ID` = f.`Field_ID` " .
        "WHERE m.`Field_ID` IN (" . join(", ", $fields) . ") AND f.`TypeOfData_ID` = " . NC_FIELDTYPE_MULTIFILE;

    $multifields = (array)$db->get_results($sql, ARRAY_A);
    foreach ($multifields as $multifield) {
        $field_id = $multifield['Field_ID'];

        $settings_http_path = nc_standardize_path_to_folder($nc_core->HTTP_FILES_PATH . "/multifile/{$field_id}/");
        $settings_path = nc_standardize_path_to_folder($nc_core->DOCUMENT_ROOT . '/' . $nc_core->SUB_FOLDER . '/' . $settings_http_path);

        foreach (array('Path', 'Preview') as $path) {
            $file_path = $multifield[$path];

            if ($file_path) {
                $parts = explode('/', nc_standardize_path_to_file($file_path));
                $file_name = array_pop($parts);

                @unlink($settings_path . $file_name);
            }
        }

        if (!in_array($field_id, $multifield_delete)) {
            $multifield_delete[] = $field_id;
        }
    }

    if (count($multifield_delete) > 0) {
        $db->query("DELETE FROM `Multifield` WHERE `Field_ID` IN (" . join(", ", $multifield_delete) . ")");
        $deleted += $db->rows_affected;
    }

    return $deleted;
}

/** Checking - correct field format
 * @param Format
 * @param Date type
 * @return bool, true - if correct
 */
function isCorrectFormat($format, $id) {
    switch ($id) {
        case NC_FIELDTYPE_SELECT:
            return (bool)$format; // формат не должен быть пустой
        case NC_FIELDTYPE_MULTISELECT:
            return nc_preg_match('/^[_a-z0-9]+((:)((select(:[0-9]+)?)|(checkbox)))?$/i', $format);
        case NC_FIELDTYPE_RELATION: // связь с объектами
            return (bool)$format; // формат не должен быть пустой
        default: //Для остальных типов данных проверки нет
            return true;
    }
}

###############################################################################

/**
 * Check, is param is mysql keyword
 *
 * http://dev.mysql.com/doc/refman/4.1/en/reserved-words.html
 * http://dev.mysql.com/doc/refman/5.0/en/reserved-words.html
 *
 * @param string Field Name
 * @return bool true - if keyword
 */
function nc_is_mysql_keyword($FieldName) {
	// old values (removed): accessible connection goto label linear range read_only read_write title upgrade x509
    return in_array(strtolower($FieldName), nc_preg_split("/\s+/", "add all alter analyze and as asc asensitive
                  before between bigint binary blob both by call cascade
                  case change char character check collate column condition
                  constraint continue convert create cross current_date
                  cume_dist current_time current_timestamp current_user
                  cursor database databases day_hour day_microsecond day_minute
                  day_second dec decimal declare default delayed delete dense_rank desc describe
                  deterministic distinct distinctrow div double drop dual each else
                  elseif empty enclosed escaped except exists exit explain false fetch first_value
                  float float4 float8 for force foreign from fulltext grant
                  group grouping groups having high_priority hour_microsecond hour_minute hour_second
                  if ignore in index infile inner inout insensitive insert
                  int int1 int2 int3 int4 int8 integer interval into is iterate join json_table
                  key keys kill lag last_value lateral lead leading leave left like limit lines load
                  localtime localtimestamp lock long longblob longtext
                  loop low_priority match mediumblob mediumint mediumtext
                  middleint minute_microsecond minute_second mod modifies natural
                  not no_write_to_binlog nth_value ntile null numeric of on optimize
                  option optionally or order out outer outfile over percent_rank precision primary
                  procedure purge rank read reads real recursive references
                  regexp release rename repeat replace require restrict return revoke
                  right rlike row_number schema schemas second_microsecond select sensitive separator set
                  show smallint soname spatial specific sql sqlexception sqlstate sqlwarning sql_big_result
                  sql_calc_found_rows sql_small_result ssl starting straight_join system table
                  terminated then tinyblob tinyint tinytext to trailing trigger true
                  undo union unique unlock unsigned update usage use
                  using utc_date utc_time utc_timestamp values varbinary
                  varchar varcharacter varying when where while with window write
                  xor year_month zerofill"));
}

/**
 * Проверяет, совпадает ли имя поля с одной из автоматически создаваемых переменных $f_ИмяПеременной
 * Вызывается при добавлении полей в системные таблицы, компоненты и виджет-компоненты
 *
 * @param string $field_name имя проверяемого поля
 * @return bool возвращает true, если имя поля совпадает с глобальной переменной
 */
function nc_is_global_variable($field_name) {
    $reserved_variable_names = array (
        // Заголовок страницы, задаваемый в параметрах компонента
        'title',
        // Buttons, Common, Interface и все их варианты
        'AdminButtons*',
        'AdminCommon*',
        'AdminInterface*',
        // Переменные, значение которых будет присвоено в случае компонента-агрегатора
        'db_Subdivision_ID',
        'db_Sub_Class_ID',
        'db_Keyword',
        // Переменные, которые создаются при запросе из таблиц Message* (параметры инфоблока и раздела)
        'EnglishName',
        'Hidden_URL',
        // Переменные, которые создаются при запросе из компонентов v4
        'UserID',
        'LastUserID',
        'UserGroup',
        // Переменные, которые создаются при запросе из таблицы User
        'PermissionGroup',
        'PermissionGroup_ID',
        // Номер (ID) объекта в таблице БД (MessageXX)
        'RowID',
        // Порядковый номер объекта на странице
        'RowNum',
        // Флаг наследования в системных таблицах
        '_nc_final',
    );

    foreach ($reserved_variable_names as $reserved_variable_name) {
        if (fnmatch($reserved_variable_name, $field_name)) {
            return true;
        }
    }

    return false;
}

/**
 *
 * @global type $db
 * @global type $nc_core
 * @param string $type - Тип поля - поле системной таблицы, поле виджета, поле компонента
 * @param type $FieldID
 * @param type $Id
 * @param type $systemTableId
 * @return array $options - Массив значения для выпадающего списка
 */
function GetTransliterateOptions($type = '', $FieldID = 0, $Id = 0, $systemTableId = 0)
{
  global $db, $nc_core;

  $options = array();
  switch ($type) {
    case "system":
      if ($FieldID == 0) {
        //add new field to system table
        $systemTableId = $Id;
      }
      $component = new nc_component(0, $systemTableId);
      foreach ($component->get_fields() as $field) {
        if ($field['type'] == NC_FIELDTYPE_STRING && $field['id'] != $FieldID) {
          $options[$field['name']] = $field['description'] != '' ? $field['description'] : $field['name'];
        }
      }
      break;
    case "widget":

      //для виджетов пока отключена транслитерация, код ниже полностью рабочий

      /*
      if ($FieldID == 0) {
        $Widget_Class_ID = $Id;
      } else {
        $sql = "SELECT `Widget_Class_ID` FROM `Field` WHERE `Field_ID` = {$FieldID} LIMIT 1";
        $Widget_Class_ID = $db->get_var($sql);
      }
      $results = $db->get_results(
              "SELECT `Field_ID` as `id`,
                            `Field_Name` as `name`,
                            `TypeOfData_ID` as `type`,
                            `Description` AS `description`
                       FROM `Field`
                      WHERE `Checked` = 1  AND `Widget_Class_ID` = '" . $Widget_Class_ID . "' ORDER BY `Priority`", ARRAY_A);

      foreach ($results as $res) {
        if ($res['type'] == NC_FIELDTYPE_STRING && $res['id'] != $FieldID) {
          $options[$res['name']] = $res['description'] != '' ? $res['description'] : $res['name'];
        }
      }
       */

      break;
    default:
      $options = array('Keyword' => CONTROL_CONTENT_SUBDIVISION_FUNCS_MAINDATA_KEYWORD);
      if ($FieldID > 0) {
        $sql = "SELECT `Class_ID` FROM `Field` WHERE Field_ID='" . $FieldID . "' LIMIT 1";
        $result = $db->get_row($sql, ARRAY_A);
        $classId = $result['Class_ID'];
      } else {
        //add new field to component
        $classId = $Id;
      }
      $component = $nc_core->get_component($classId);
      foreach ($component->get_fields() as $field) {
        if ($field['type'] == NC_FIELDTYPE_STRING && $field['id'] != $FieldID) {
          $options[$field['name']] = $field['description'] != '' ? $field['description'] : $field['name'];
        }
      }
      break;
  }
  return $options;
}

class ui_config_field extends ui_config {

    private $_location;

    function __construct($active_tab = 'edit', $field_id, $id, $is_sys, $isWidget = false) {
        $db = nc_Core::get_object()->db;
        $fs_suffix = +$_REQUEST['fs'] ? '_fs' : '';
        $id = intval($id);
        $field_id = intval($field_id);

        $this->_location = $is_sys ? "systemfield" : "field";

        switch ($active_tab) {
            case 'add':
                $this->tabs = array(
                    array('id' => 'add',
                        'caption' => CONTROL_FIELD_ADDING,
                        'location' => $this->_location . $fs_suffix . ".add(" . $id . ")")
                );
                $this->headerText = $is_sys ? constant($db->get_var("SELECT `System_Table_Rus_Name` FROM `System_Table` WHERE `System_Table_ID` = '" . $id . "'")) :
                    $db->get_var("SELECT `Class_Name` FROM `Class` WHERE `Class_ID` = '" . $id . "'");

                break;
            case 'edit':
                $this->tabs = array(
                    array('id' => 'edit',
                        'caption' => CONTROL_FIELD_EDITING,
                        'location' => $this->_location . $fs_suffix . ".edit(" . $field_id . ")")
                );
                $this->headerText = $db->get_var("SELECT `Description` FROM `Field` WHERE `Field_ID` = '" . $field_id . "'");
                $this->treeSelectedNode = ($is_sys ? "system" : "") . "field-{$field_id}";
                break;
            case 'delete':
                $this->tabs = array(
                    array('id' => 'delete',
                        'caption' => CONTROL_FIELD_DELETING,
                        'location' => $this->_location . $fs_suffix . ".delete(" . $id . ")")
                );
                $this->headerText = $is_sys ? constant($db->get_var("SELECT `System_Table_Rus_Name` FROM `System_Table` WHERE `System_Table_ID` = '" . $id . "'")) :
                    $db->get_var("SELECT `Class_Name` FROM `Class` WHERE `Class_ID` = '" . $id . "'");
                $this->treeSelectedNode = $is_sys ? "systemclass-{" . (isset($system_class_id) ? $system_class_id : null). "}" : "dataclass-{" . (isset($class_id) ? $class_id : null). "}";

                break;
        }

        $this->activeTab = $active_tab;
        switch (true) {
            case $isWidget:
                $this->treeMode = 'widgetclass';
                break;

            case $is_sys:
                $this->treeMode = 'systemclass';
                break;

            default:
                $this->treeMode = 'dataclass';
                break;
        }
        $this->treeMode .= $fs_suffix;
    }

    function updateTreeFieldNode($field_id, $field_type, $field_name) {
        global $field_types_sprites;

        $sql = "SELECT `NotNull`, Description FROM `Field` WHERE `Field_ID` = {$field_id}";
        $res = nc_core('db')->get_row($sql);

        $this->treeChanges['updateNode'][] = array(
            "nodeId" => $this->_location . "-" . $field_id,
            "name" => $field_name ? "{$field_id}. {$field_name}" : null,
            "sprite" => $field_types_sprites[$field_type] . ($res->NotNull ? ' nc--required' : ''),
            "description" => !empty($res->Description) ? $res->Description : ''
        );
    }

}
