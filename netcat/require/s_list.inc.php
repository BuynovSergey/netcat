<?php

/**
 * Выводит список объектов в инфоблоке
 *
 * @param int $sub
 * @param int $cc
 * @param string|array $query_string
 * @param bool $show_in_admin_mode
 * @param bool $get_current_cc
 * @return nc_partial_object_list|nc_partial_container|string
 */
function nc_objects_list($sub, $cc, $query_string = "", $show_in_admin_mode = true, $get_current_cc = true) {
    $cc = (int)$cc;
    if (!$cc) {
        return '';
    }

    $data = array();
    if (is_array($query_string)) {
        $data = $query_string;
    } else {
        parse_str($query_string, $data);
    }

    $result = '';

    try {
        $nc_core = nc_core::get_object();
        switch ($nc_core->sub_class->get_by_id($cc, 'Sub_Class_Type')) {
            case 'main':
                $result = new nc_partial_main($cc);
                break;
            case 'container':
                $result = new nc_partial_container($cc, $data);
                break;
            case 'object_list':
                $result = new nc_partial_object_list($cc, $data);
                $result->show_in_admin_mode($show_in_admin_mode);
                $result->set_longpage_mode(!$get_current_cc);
                break;
            case 'object_partial':
                $result = new nc_partial_object_partial($cc);
                break;
            default:
                throw new UnexpectedValueException("Unsupported block type: " . $nc_core->sub_class->get_by_id($cc, 'Sub_Class_Type'));
        }
    } catch (Exception $e) {
        trigger_error($e->getMessage(), E_USER_WARNING);
        if (nc_array_value($GLOBALS['current_user'], 'InsideAdminAccess')) {
            // NB: nc_print_status() при $GLOBALS['isNaked'] останавливает выполнение скрипта
            $result = "<div class='nc-alert nc--red'><i class='nc-icon-l nc--status-error'></i>{$e->getMessage()}</div>";
        }
    }

    return $result;
}

function s_list_class($sub, $cc, $query_string = "", $show_in_admin_mode = false) {
    return nc_objects_list($sub, $cc, $query_string, $show_in_admin_mode);
}

function nc_widgets_block() {
    return call_user_func_array(array(nc_core('widget'), 'render_widgets_block'), func_get_args());
}

/**
 * Возвращает содержимое области с указанным ключевым словом
 * @param string $area_keyword
 * @param string $additional_css_classes
 * @return string
 */
function nc_area($area_keyword, $additional_css_classes = '') {
    global $current_cc;
    $nc_core = nc_core::get_object();

    $original_cc = $current_cc;
    $blocks_in_area = $nc_core->sub_class->get_by_area_keyword($area_keyword, $nc_core->page->get_routing_result());

    $additional_attributes = '';

    if ($nc_core->admin_mode) {
        $additional_attributes = " data-keyword='$area_keyword'";
    } else {
        $blocks_in_area = array_filter($blocks_in_area, function($block) {
            return (bool)$block['Checked'];
        });
    }

    $area_css_classes = 'nc-area tpl-area-' . nc_camelcase_to_dashcase($area_keyword);
    if ($additional_css_classes) {
        $area_css_classes .= " $additional_css_classes";
    }

    $list_css_classes = 'tpl-block-list';

    if ($area_keyword === 'main') {
        $list_css_classes .= ' tpl-area-main-list'; // см. nc_page::add_page_mixins()
    }

    $result = "<div class='$area_css_classes'" . $additional_attributes . ">";

    if ($blocks_in_area) {
        $result .= "<div class='$list_css_classes'>";
        foreach ($blocks_in_area as $block) {
            $current_cc = $nc_core->sub_class->set_current_by_id($block['Sub_Class_ID']);
            $result .= nc_objects_list($area_keyword, $block['Sub_Class_ID'], '');
        }
        $result .= '</div>';
    } else if ($nc_core->admin_mode) {
        $result .= "<div class='nc-container nc--empty'>";
        $result .= nc_admin_infoblock_insert_toolbar(0, $area_keyword, 0, 'first', 0);
        $result .= "</div>";
    }

    $result .= '</div>';

    $current_cc = $original_cc;

    return $result;
}

function nc_insert_area_blocks($area_keyword) {
    global $current_cc;
    $nc_core = nc_core::get_object();

    $original_cc = $current_cc;
    $blocks_in_area = $nc_core->sub_class->get_by_area_keyword($area_keyword, $nc_core->page->get_routing_result());
    $result = '';

    if ($blocks_in_area) {
        foreach ($blocks_in_area as $block) {
            $current_cc = $nc_core->sub_class->set_current_by_id($block['Sub_Class_ID']);
            $result .= nc_objects_list($area_keyword, $block['Sub_Class_ID'], '');
        }
    } else if ($nc_core->admin_mode) {
        $result .= "<div class='nc-container nc--empty'>";
        $result .= nc_admin_infoblock_insert_toolbar(0, $area_keyword, 0, 'first', 0);
        $result .= "</div>";
    }

    $current_cc = $original_cc;

    return $result;
}

function showSearchForm($fldName, $fldType, $fldDoSearch, $fldFmt, $isAdd = false) {
    global $systemTableID, $db, $srchPat, $srchPatAdd, $nc_core;

    if (!is_array($fldName)) {
        return false;
    }

    $result = '';
    $j = 0;
    $srchPatName = ($isAdd === false) ? "srchPat" : "srchPatAdd";
    $srchPatValues = $$srchPatName;
    for ($i = 0; $i < count($fldName); $i++) {
        $fld_prefix = "<div class='tpl-block-input'>";
        $fld_suffix = "</div>\n";
        $fldNameTempl = "$fld_prefix<span class='tpl-caption'>$fldName[$i]: </span>";

        if (!$fldDoSearch[$i]) {
            continue;
        }
        if (!isset($srchPatValues[$j])) {
            $srchPatValues[$j] = null;
        }
        if (is_scalar($srchPatValues[$j])) {
            $stringValue = htmlspecialchars(stripcslashes($srchPatValues[$j]), ENT_QUOTES);
            $stringValue = addcslashes($stringValue, '$');
        } else {
            $stringValue = '';
        }

        switch ($fldType[$i]) {
            case NC_FIELDTYPE_STRING:
                $result .= $fldNameTempl . "<br><input type='text' name='".$srchPatName."[" . $j . "]' size='50' maxlength='255' value='" . $stringValue . "'>" . $fld_suffix;
                $j++;
                break;
            case NC_FIELDTYPE_INT:
                $result .= $fldNameTempl . "&nbsp;&nbsp;" . NETCAT_MODERATION_MOD_FROM . " <input type='text' name='".$srchPatName."[" . $j . "]' size='10' maxlength='16' value='" . ($srchPatValues[$j] ? (int)$srchPatValues[$j] : "") . "'>";
                $j++;
		if (!isset($srchPatValues[$j])) {
                    $srchPatValues[$j] = null;
                }
                $result .= NETCAT_MODERATION_MOD_DON . "<input type='text' name='".$srchPatName."[" . $j . "]' size='10' maxlength='16' value='" . ($srchPatValues[$j] ? (int)$srchPatValues[$j] : "") . "'>" . $fld_suffix;
                $j++;
                break;
            case NC_FIELDTYPE_TEXT:
                $result .= $fldNameTempl . "<br><input type='text' name='".$srchPatName."[" . $j . "]' size='50' maxlength='255' value='" . $stringValue . "'>" . $fld_suffix;
                $j++;
                break;
            case NC_FIELDTYPE_SELECT:
                if ($fldFmt[$i]) {
                    $result .= $fldNameTempl . "<br><select name='".$srchPatName."[" . $j . "]' size='1'>";
                    $result .= "<option value=''>" . NETCAT_MODERATION_MODA . "</option>";

                    $list_format = explode(":" , $fldFmt[$i]);
                    $fldFmt[$i] = $list_format[0]; //название таблицы

                    $SortType = $SortDirection = 0;
                    $res = $db->get_row("SELECT `Sort_Type`, `Sort_Direction` FROM `Classificator` WHERE `Table_Name` = '" . $db->escape($fldFmt[$i]) . "'", ARRAY_N);
                    if ($db->num_rows != 0) {
                        $row = $res;
                        $SortType = $row[0];
                        $SortDirection = $row[1];
                    }

                    $s = "SELECT * FROM `Classificator_" . $db->escape($fldFmt[$i]) . "` WHERE `Checked` = 1 ORDER BY ";
                    switch ($SortType) {
                        case 1:
                            $s .= "`" . $db->escape($fldFmt[$i]) . "_Name`";
                            break;
                        case 2:
                            $s .= "`" . $db->escape($fldFmt[$i]) . "_Priority`";
                            break;
                        default:
                            $s .= "`" . $db->escape($fldFmt[$i]) . "_ID`";
                    }

                    if ($SortDirection == 1) {
                        $s .= " DESC";
                    }

                    $selected = (int)$srchPatValues[$j];
                    $lstRes = (array)$db->get_results($s, ARRAY_N);
                    foreach ($lstRes as $q) {
                        list($lstID, $lstName) = $q;
                        $lstName = htmlspecialchars($lstName);
                        $result .= "<option value='" . $lstID . "'" . ($selected == $lstID ? "selected" : "") . ">" . $lstName . "</option>";
                    }
                    $result .= '</select>' . $fld_suffix;
                }
                $j++;
                break;
            case NC_FIELDTYPE_BOOLEAN:
                $result .= $fldNameTempl;
                $result .= "&nbsp;&nbsp;<input type='radio' name='".$srchPatName."[" . $j . "]' id='t" . $j . "_1' value='' style='vertical-align:middle'" . (!nc_array_value($srchPatValues, $j) ? " checked" : "") . "><label for='t" . $j . "_1'>" . NETCAT_MODERATION_MOD_NOANSWER . '</label> ';
                $result .= "&nbsp;&nbsp;<input type='radio' name='".$srchPatName."[" . $j . "]' id='t" . $j . "_2' value='1' style='vertical-align:middle'" . (nc_array_value($srchPatValues, $j) == '1' ? " checked" : "") . "><label for='t" . $j . "_2'>" . CONTROL_CONTENT_SUBDIVISION_FUNCS_OBJ_YES . '</label> ';
                $result .= "&nbsp;&nbsp;<input type='radio' name='".$srchPatName."[" . $j . "]' id='t" . $j . "_3' value='0' style='vertical-align:middle'" . (nc_array_value($srchPatValues, $j) == '0' ? " checked" : "") . "><label for='t" . $j . "_3'>" . CONTROL_CONTENT_SUBDIVISION_FUNCS_OBJ_NO . '</label>';
                $result .= $fld_suffix;
                $j++;
                break;
            case NC_FIELDTYPE_FILE:
                $result .= $fldNameTempl . "<br><input type='text' name='".$srchPatName."[" . $j . "]' size='50' maxlength='255' value='" . $stringValue . "'>" . $fld_suffix;
                $j++;
                break;
            case NC_FIELDTYPE_FLOAT:
                $result .= $fldNameTempl . "&nbsp;&nbsp;" . NETCAT_MODERATION_MOD_FROM . " <input type='text' name='".$srchPatName."[" . $j . "]' size='10' maxlength='16' value='" . (nc_array_value($srchPatValues, $j) ? (float)$srchPatValues[$j] : "") . "'>";
                $j++;
                $result .= NETCAT_MODERATION_MOD_DON . "<input name='".$srchPatName."[" . $j . "]' type='text' size='10' maxlength='16' value='" . (nc_array_value($srchPatValues, $j) ? (float)$srchPatValues[$j] : "") . "'>" . $fld_suffix;
                $j++;
                break;
            case NC_FIELDTYPE_DATETIME:
                $format = nc_field_parse_format($fldFmt[$i], NC_FIELDTYPE_DATETIME);
                $result .= $fldNameTempl . "&nbsp;&nbsp;";
                if ($format['calendar'] && nc_module_check_by_keyword('calendar', 0)) {
                    $result .= nc_set_calendar(0) . "<br/>";
                }
                $result .= "<span class='tpl-block-input-row'>";
                $result .= NETCAT_MODERATION_MOD_FROM;


                if ($format['type'] !== 'event_time') {
                    $result .= "<input type='text' class='no_special_style' name='".$srchPatName."[" . $j . "]' size='2' maxlength='2' value='" . (nc_array_value($srchPatValues, $j) ? sprintf("%02d", $srchPatValues[$j]) : "") . "' placeholder='" . NETCAT_HINT_FIELD_D . "' >.";
                    $j++;
                    $result .= "<input type='text' class='no_special_style' name='".$srchPatName."[" . $j . "]' size='2' maxlength='2' value='" . (nc_array_value($srchPatValues, $j) ? sprintf("%02d", $srchPatValues[$j]) : "") . "' placeholder='" . NETCAT_HINT_FIELD_M . "' >.";
                    $j++;
                    $result .= "<input type='text' class='no_special_style' name='".$srchPatName."[" . $j . "]' size='4' maxlength='4' value='" . (nc_array_value($srchPatValues, $j) ? sprintf("%04d", $srchPatValues[$j]) : "") . "' placeholder='" . NETCAT_HINT_FIELD_Y . "' > ";
                    $j++;
                }
                else {
                    $j += 3;
                }
                if ($format['type'] !== 'event_date') {
                    $result .= "<input type='text' class='no_special_style' name='".$srchPatName."[" . $j . "]' size='2' maxlength='2' value='" . (nc_array_value($srchPatValues, $j) ? sprintf("%02d", $srchPatValues[$j]) : "") . "' placeholder='" . NETCAT_HINT_FIELD_H . "' >:";
                    $j++;
                    $result .= "<input type='text' class='no_special_style' name='".$srchPatName."[" . $j . "]' size='2' maxlength='2' value='" . (nc_array_value($srchPatValues, $j) ? sprintf("%02d", $srchPatValues[$j]) : "") . "' placeholder='" . NETCAT_HINT_FIELD_MIN . "' >:";
                    $j++;
                    $result .= "<input type='text' class='no_special_style' name='".$srchPatName."[" . $j . "]' size='2' maxlength='2' value='" . (nc_array_value($srchPatValues, $j) ? sprintf("%02d", $srchPatValues[$j]) : "") . "' placeholder='" . NETCAT_HINT_FIELD_S . "' > ";
                    $j++;
                }
                else {
                    $j += 3;
                }

                if ($format['calendar']&& nc_module_check_by_keyword('calendar', 0) && $format['type'] != 'event_time') {
                    $result .= "<div style='display: inline; position: relative;'>
                         <img  id='nc_calendar_popup_img_".$srchPatName."[" . ($j - 6) . "]' onclick=\\\"nc_calendar_popup('".$srchPatName."[" . ($j - 6) . "]', '".$srchPatName."[" . ($j - 5) . "]', '".$srchPatName."[" . ($j - 4) . "]', '0');\\\" src='" . nc_module_path('calendar') . "images/calendar.jpg' style='cursor: pointer; position: absolute; left: 7px; top: -3px;'/>
                       </div>
                       <div style='display: none; z-index: 10000;' id='nc_calendar_popup_".$srchPatName."[" . ($j - 6) . "]'></div><br/>";
                }

                $result .= "</span><span class='tpl-block-input-row'>";
                $result .= NETCAT_MODERATION_MOD_DON;
                if ($format['type'] !== 'event_time') {
                    $result .= "<input type='text' class='no_special_style' name='".$srchPatName."[" . $j . "]' size='2' maxlength='2' value='" . (nc_array_value($srchPatValues, $j) ? sprintf("%02d", $srchPatValues[$j]) : "") . "' placeholder='" . NETCAT_HINT_FIELD_D . "' >.";
                    $j++;
                    $result .= "<input type='text' class='no_special_style' name='".$srchPatName."[" . $j . "]' size='2' maxlength='2' value='" . (nc_array_value($srchPatValues, $j) ? sprintf("%02d", $srchPatValues[$j]) : "") . "' placeholder='" . NETCAT_HINT_FIELD_M . "' >.";
                    $j++;
                    $result .= "<input type='text' class='no_special_style' name='".$srchPatName."[" . $j . "]' size='4' maxlength='4' value='" . (nc_array_value($srchPatValues, $j) ? sprintf("%04d", $srchPatValues[$j]) : "") . "' placeholder='" . NETCAT_HINT_FIELD_Y . "' > ";
                    $j++;
                }
                else {
                    $j += 3;
                }
                if ($format['type'] !== 'event_date') {
                    $result .= "<input type='text' class='no_special_style' name='".$srchPatName."[" . $j . "]' size='2' maxlength='2' value='" . (nc_array_value($srchPatValues, $j) ? sprintf("%02d", $srchPatValues[$j]) : "") . "' placeholder='" . NETCAT_HINT_FIELD_H . "' >:";
                    $j++;
                    $result .= "<input type='text' class='no_special_style' name='".$srchPatName."[" . $j . "]' size='2' maxlength='2' value='" . (nc_array_value($srchPatValues, $j) ? sprintf("%02d", $srchPatValues[$j]) : "") . "' placeholder='" . NETCAT_HINT_FIELD_MIN . "' >:";
                    $j++;
                    $result .= "<input type='text' class='no_special_style' name='".$srchPatName."[" . $j . "]' size='2' maxlength='2' value='" . (nc_array_value($srchPatValues, $j) ? sprintf("%02d", $srchPatValues[$j]) : "") . "' placeholder='" . NETCAT_HINT_FIELD_S . "' > ";
                    $j++;
                }
                else {
                    $j += 3;
                }

                if ($format['calendar'] && $format['type'] !== 'event_time') {
                    $result .= "<div style='display: inline; position: relative;'>
                         <img  id='nc_calendar_popup_img_".$srchPatName."[" . ($j - 6) . "]' onclick=\\\"nc_calendar_popup('".$srchPatName."[" . ($j - 6) . "]', '".$srchPatName."[" . ($j - 5) . "]', '".$srchPatName."[" . ($j - 4) . "]', '0');\\\" src='" . nc_module_path('calendar') . "images/calendar.jpg' style='cursor: pointer; position: absolute; left: 7px; top: -3px;'/>
                       </div>
                       <div style='display: none; z-index: 10000;' id='nc_calendar_popup_".$srchPatName."[" . ($j - 6) . "]'></div><br/>";
                }

                $result .= '</span>';
                $result .= $fld_suffix;
                break;
            case NC_FIELDTYPE_MULTISELECT:
                if ($fldFmt[$i]) {
                    list($clft_name, $type_element, $type_size) = explode(":", $fldFmt[$i]);

                    if (!$type_element) {
                        $type_element = "select";
                    }
                    if (!$type_size) {
                        $type_size = 3;
                    }

                    $fldFmt[$i] = $clft_name;

                    $SortType = $SortDirection = 0;
                    $res = $db->get_row("SELECT `Sort_Type`, `Sort_Direction` FROM `Classificator` WHERE `Table_Name` = '" . $db->escape($fldFmt[$i]) . "'", ARRAY_N);
                    if ($db->num_rows != 0) {
                        $row = $res;
                        $SortType = $row[0];
                        $SortDirection = $row[1];
                    }

                    $s = "SELECT * FROM Classificator_" . $fldFmt[$i] . " ORDER BY ";
                    switch ($SortType) {
                        case 1:
                            $s .= $fldFmt[$i] . "_Name";
                            break;
                        case 2:
                            $s .= $fldFmt[$i] . "_Priority";
                            break;
                        default:
                            $s .= $fldFmt[$i] . "_ID";
                    }

                    if ($SortDirection == 1) {
                        $s .= " DESC";
                    }

                    if (is_string($srchPatValues[$j])) {
                        $srchPatValues[$j] = explode('-', $srchPatValues[$j]);
                    }
                    $selected = $srchPatValues[$j] ?: array();

                    $lstRes = (array)$db->get_results($s, ARRAY_N);

                    $result .= $fldNameTempl . "<br>";

                    if ($type_element === 'select') {
                        $result .= "<select name='".$srchPatName."[" . $j . "][]' size='" . $type_size . "' multiple>";
                        $result .= "<option value=''>" . NETCAT_MODERATION_MODA . "</option>";
                    }


                    foreach ($lstRes as $q) {
                        list($lstID, $lstName) = $q;
                        $lstName = htmlspecialchars($lstName);
                        $temp_str = '';
                        if (in_array($lstID, $selected)) {
                            $temp_str = ($type_element == "select") ? " selected" : " checked";
                        }

                        if ($type_element === 'select') { #TODO сделать возможность передавать селектед в виде массива
                            $result .= "<option value='" . $lstID . "' " . $temp_str . ">" . $lstName . "</option>";
                        }
                        else {
                            $result .= "<input type='checkbox' value='" . $lstID . "' name='".$srchPatName."[" . $j . "][]' " . $temp_str . "> " . $lstName . "<br>\r\n";
                        }
                    }

                    if ($type_element === 'select') {
                        $result .= '</select><br>';
                    } //.$fld_suffix;

                    $j++;
                    $result .= "<input type='hidden' name='".$srchPatName."[" . $j . "]' value='0'>\n";
                    $result .= $fld_suffix;
                }
                $j++;
                break;
        }
        $result .= "<br>\n";
    }

    if (!$j) {
        return false;
    }

    return $result;
}


/**
 * @param int|string $component_id          ID/тип компонента (например: 'User', 'Template', 123)
 * @param int|int[] $object_id_or_ids       ID или массив с ID объектов
 * @param string|null $returned_field_name  Если указано — вернуть значения только для указанного поля
 * @return array|null|nc_multifield
 *    Если $object_ids — массив, то массив с массивом nc_multifield для каждого объекта (ключ — ID объекта).
 *    Если $object_ids — число, то массив с полями для указанного объекта.
 *    Если $object_ids — число и указано $field_name — объект nc_multifile.
 *    Если у компонента нет полей типа MULTIFILE — NULL.
 */
function nc_get_multifile_field_values($component_id, $object_id_or_ids, $returned_field_name = null) {
    $object_ids = (array)$object_id_or_ids;

    $nc_core = nc_core::get_object();
    $component = $nc_core->get_component($component_id);
    $fields = $component->get_fields(NC_FIELDTYPE_MULTIFILE, false);

    if ($returned_field_name) { // fetch data for a single field only
        $returned_field_id = array_search($returned_field_name, $fields);
        $fields = array($returned_field_id => $fields[$returned_field_id]);
    }

    if (!$fields) { return null; }

    /** @var nc_multifield[][] $results */
    $result = array();
    foreach ($object_ids as $object_id) {
        foreach ($fields as $field_id => $field_name) {
            $multifield = new nc_multifield($field_name, null, null, $field_id, $component_id);
            $result[$object_id][$field_name] = $multifield;
        }
    }

    $rows = (array)$nc_core->db->get_results(
        "SELECT `Field_ID`,
                `Message_ID`,
                `Priority`,
                `Name`,
                `Size`,
                `Path`,
                `Preview`,
                `ID`
           FROM `Multifield`
          WHERE `Message_ID` IN (" . join(",", array_map('intval', $object_ids)) . ")
            AND `Field_ID` IN (" . join(",", array_keys($fields)) . ")
          ORDER BY `Priority`",
        ARRAY_A
    );

    foreach ($rows as $row) {
        $object_id= $row['Message_ID'];
        $field_name = $fields[$row['Field_ID']];
        $result[$object_id][$field_name]->add_record($row);
    }

    if (!is_array($object_id_or_ids)) {
        if ($returned_field_name) {
            return $result[$object_id_or_ids][$returned_field_name];
        }
        else {
            return $result[$object_id_or_ids];
        }
    }
    else {
        return $result;
    }
}


function nc_AdminCommon($sub, $cc, $cc_env, $f_AdminCommon_package, $f_AdminCommon_add, $f_AdminCommon_delete_all, $show_add_record_button) {
    $nc_core = nc_Core::get_object();
    $system_env = $nc_core->get_settings();
    $ADMIN_TEMPLATE = $nc_core->get_variable("ADMIN_TEMPLATE");
    $f_AdminCommon_cc_name = $cc_env['Sub_Class_Name'];
    $f_AdminCommon_cc = $cc;

    if ($system_env['AdminButtonsType']) {
        eval(nc_check_eval("\$f_AdminCommon = \"" . $system_env['AdminCommon'] . "\";"));
    }
    else {
        $f_AdminCommon_buttons = "\n<li><span>" . $cc_env['Sub_Class_ID'] . "</span></li>\n";
        if ($show_add_record_button) {
            $f_AdminCommon_buttons .= "<li><a onClick='nc.load_dialog(this.href); return false' href='$f_AdminCommon_add'>" . NETCAT_MODERATION_BUTTON_ADD . "</a></li>\n";
        }
        $f_AdminCommon_buttons .= nc_get_AdminCommon_multiedit_button($cc_env) . "
    " . ($nc_core->InsideAdminAccess ? "
        <li><a onClick='parent.nc_form(this.href); return false;' href='{$nc_core->SUB_FOLDER}admin/class/index.php?phase=4&ClassID=" . ($cc_env['Class_Template_ID'] ? $cc_env['Class_Template_ID'] : $cc_env['Class_ID']) . "'>
            <i class='nc-icon nc--dev-components' title='" . CONTROL_CLASS_DOEDIT . "'></i>
        </a></li>
    " : "") . "
    <li><a onClick='parent.nc_form(this.href); return false;' href='{$nc_core->ADMIN_PATH}subdivision/SubClass.php?SubdivisionID={$cc_env['Subdivision_ID']}&sub_class_id={$cc_env['Sub_Class_ID']}'>
        <i class='nc-icon nc--settings' title='" . CONTROL_CLASS_CLASS_SETTINGS . "'></i>
    </a></li>
    <li><a href='$f_AdminCommon_delete_all'>
        <i class='nc-icon nc--remove' title='" . NETCAT_MODERATION_REMALL . "'></i>
    </a></li>";


        if ($nc_core->get_settings('PacketOperations')) {
            $f_AdminCommon_buttons .= "<li class='nc-divider'></li>
                <li class='nc--alt'><a href='#' onclick='nc_package_obj.process(\"checkOn\", " . $cc . "); return false;'>
                    <i class='nc-icon nc--selected-on' title='" . NETCAT_MODERATION_SELECTEDON . "'></i>
                </a></li>
                <li class='nc--alt'><a href='#' onclick='nc_package_obj.process(\"checkOff\", " . $cc . "); return false;'>
                    <i class='nc-icon nc--selected-off' title='" . NETCAT_MODERATION_SELECTEDOFF . "'></i>
                </a></li>
                <li class='nc--alt'><a href='#' onclick='nc_package_obj.process(\"delete\", " . $cc . "); return false;'>
                    <i class='nc-icon nc--selected-remove' title='" . NETCAT_MODERATION_DELETESELECTED . "'></i>
                </a></li>
            ";
        }

        $f_AdminCommon = "<div class='nc_idtab nc_admincommon'>";
        if (CheckUserRights($cc, 'add', 1) == 1) {
            $f_AdminCommon = "<ul class='nc-toolbar nc--right main-toolbar'>" . $f_AdminCommon_buttons . "</ul>
              <div class='nc--clearfix'></div>";
            $f_AdminCommon .= $f_AdminCommon_package;
        }
        else {
            $f_AdminCommon .= "<div class='nc_idtab_id'>
                                  <div class='nc_idtab_messageid error' title='" . NETCAT_MODERATION_ERROR_NORIGHT . "'>
                                      " . NETCAT_MODERATION_ERROR_NORIGHT . "
                                  </div>
                              </div>
                              <div class='ncf_row nc_clear'></div>";
        }
        $f_AdminCommon .= "<div class='nc--clearfix'></div>";
    }
    return $f_AdminCommon;
}

function nc_get_AdminCommon_multiedit_button($cc_env) {
    $nc_core = nc_Core::get_object();
    $result = '';
    $multi_edit_template_id = nc_get_AdminCommon_multiedit_button_template_id($cc_env['Class_ID']);
    if ($multi_edit_template_id) {
        $href = $nc_core->SUB_FOLDER . $nc_core->HTTP_ROOT_PATH . "index.php?" .
            "isModal=1" .
            "&amp;catalogue={$cc_env['Catalogue_ID']}" .
            "&amp;sub={$cc_env['Subdivision_ID']}" .
            "&amp;cc={$cc_env['Sub_Class_ID']}" .
            "&amp;nc_ctpl=$multi_edit_template_id" .
            ($nc_core->inside_admin ? "&amp;inside_admin=1" : "");
        $result = "
            <li><a onClick='nc.load_dialog(this.href); return false;' href='$href'>
                <i class='nc-icon nc--edit' title='" . CONTROL_CLASS_COMPONENT_TEMPLATE_TYPE_MULTI_EDIT . "'></i>
            </a></li>";
    }

    return $result;
}

function nc_get_AdminCommon_multiedit_button_template_id($class_id) {
    static $data = array();

    if (!isset($data[$class_id])) {
        $data[$class_id] = +nc_Core::get_object()->db->get_var("SELECT Class_ID FROM Class WHERE (Class_ID = $class_id OR ClassTemplate = $class_id) AND Type = 'multi_edit'");
    }

    return $data[$class_id];
}

function nc_get_fullLink($admin_url_prefix, $catalogue, $sub, $cc, $f_RowID, $inside_admin = 0) {
    return $admin_url_prefix . "full.php?inside_admin=" . $inside_admin . "&amp;sub=" . $sub . "&amp;cc=" . $cc . "&amp;message=" . $f_RowID;
}

function nc_get_fullDateLink($fullLink, $dateLink) {
    return $fullLink . $dateLink;
}

function nc_get_AdminButtons_user_change($f_LastUserID) {
    return $f_LastUserID ? $f_LastUserID : "";
}

function nc_get_AdminButtons_copy($ADMIN_PATH, $catalogue, $sub, $cc, $classID, $f_RowID) {
    return $ADMIN_PATH . "objects/copy_message.php?catalogue=" . $catalogue . "&amp;sub=" . $sub . "&amp;cc=" . $cc . "&amp;classID=" . $classID . "&amp;message=" . $f_RowID;
}

function nc_get_AdminButtons_change($SUB_FOLDER, $HTTP_ROOT_PATH, $catalogue, $sub, $cc, $f_RowID, $curPos, $inside_admin = 0) {
    return $SUB_FOLDER . $HTTP_ROOT_PATH . "message.php?" . ($inside_admin ? 'inside_admin=1&amp;' : '') . "catalogue=" . $catalogue . "&amp;sub=" . $sub . "&amp;cc=" . $cc . "&amp;message=" . $f_RowID . ($curPos ? "&amp;curPos=" . $curPos : "");
}

function nc_get_AdminButtons_version($SUB_FOLDER, $HTTP_ROOT_PATH, $catalogue, $sub, $cc, $f_RowID, $curPos, $inside_admin = 0) {
    return $SUB_FOLDER . $HTTP_ROOT_PATH . "message.php?" . ($inside_admin ? 'inside_admin=1&amp;' : '') . "isVersion=1&amp;restore=1&amp;catalogue=" . $catalogue . "&amp;sub=" . $sub . "&amp;cc=" . $cc . "&amp;message=" . $f_RowID . ($curPos ? "&amp;curPos=" . $curPos : "");
}

function nc_get_AdminButtons_delete($SUB_FOLDER, $HTTP_ROOT_PATH, $catalogue, $sub, $cc, $f_RowID, $curPos, $inside_admin = 0) {
    return $SUB_FOLDER . $HTTP_ROOT_PATH . "message.php?" . ($inside_admin ? 'inside_admin=1&amp;' : '') . "catalogue=" . $catalogue . "&amp;sub=" . $sub . "&amp;cc=" . $cc . "&amp;message=" . $f_RowID . "&amp;delete=1" . ($curPos ? "&amp;curPos=" . $curPos : "");
}

function nc_get_dropLink($deleteLink, $nc_core) {
    return $deleteLink . "&posting=1" . ($nc_core->token->is_use('drop') ? "&" . $nc_core->token->get_url() : "");
}

function nc_get_AdminButtons_check($f_Checked, $SUB_FOLDER, $HTTP_ROOT_PATH, $catalogue, $sub, $cc, $classID, $f_RowID, $curPos, $admin_mode, $admin_url_prefix, $nc_core) {
    return $admin_url_prefix .
           "message.php?catalogue=" . $catalogue .
           "&amp;sub=" . $sub .
           "&amp;cc=" . $cc .
           "&amp;classID=" . $classID .
           "&amp;message=" . $f_RowID .
           "&amp;checked=" . ($f_Checked ? 1 : 2) .
           "&amp;posting=1" .
           ($curPos ? "&amp;curPos=" . $curPos : "") .
           ($admin_mode ? "&amp;admin_mode=1" : "") .
           ($nc_core->inside_admin ? "&amp;inside_admin=1" : "") .
           ($nc_core->token->is_use('edit') ? "&amp;" . $nc_core->token->get_url() : "");
}

function nc_get_AdminButtons_select($f_AdminButtons_id) {
    return "top.selectItem(" . $f_AdminButtons_id . "); return false;";
}

function nc_get_list_mode_select_AdminButtons_buttons($f_AdminButtons_select, $ADMIN_TEMPLATE) {
    return "<a href='#' onclick='" . $f_AdminButtons_select . "' title='" . NETCAT_MODERATION_SELECT_RELATED . "' > " . NETCAT_MODERATION_SELECT_RELATED . " </a>";
}

function nc_get_list_mode_select($f_Checked, $classID, $f_RowID, $f_AdminButtons_id, $f_AdminButtons_buttons) {
    $f_AdminButtons = "<ul class='nc-toolbar nc--left'>";
    $f_AdminButtons .= "<li><b># " . $f_AdminButtons_id . "</b></li>";
    $f_AdminButtons .= "<li>" . $f_AdminButtons_buttons . "</li>";

    $f_AdminButtons .= "</ul><div class='nc--clearfix'></div>";
    return $f_AdminButtons;
}

function nc_get_AdminButtons_buttons($f_RowID, $f_Checked, $f_AdminButtons_check, $f_AdminButtons_uncheck, $f_AdminButtons_copy, $f_AdminButtons_change, $f_AdminButtons_delete, $f_AdminButtons_version='', $cc, $subdivision_id = '', $class_id = '', $show_landing_buttons = '') {
    $nc_core = nc_Core::get_object();

    $result = "";
    if ($nc_core->get_settings('PacketOperations')) {
        $result .= "
        <li><label>
            <input class='nc_multi_check' type='checkbox' onchange='nc_package_obj.select(" . $f_RowID . ", " . $cc . ");' > " . $f_RowID . "
        </label></li>";
    }

    if ($f_AdminButtons_check != '') {
        $result .= "<li><a onClick='parent.nc_action_message(this.href); return false;' href='" . $f_AdminButtons_check . "'>
	        <span class='nc-text-" . ($f_Checked ? 'green' : 'red') . "'>" . ($f_Checked ? NETCAT_MODERATION_OBJ_ON : NETCAT_MODERATION_OBJ_OFF) . "</span>
	    </a></li>";
    }

    if ($f_AdminButtons_copy != '') {
        $result .= "<li><a href='#' onclick=\"window.open('" . $f_AdminButtons_copy . "', 'nc_popup_test1', 'width=800,height=500,menubar=no,resizable=no,scrollbars=no,toolbar=no,resizable=yes'); return false;\">
        	<i class='nc-icon nc--copy' title='" . NETCAT_MODERATION_COPY_OBJECT . "'></i>
    	</a></li>";
    }

    if ($show_landing_buttons) {
        $site_id = $nc_core->sub_class->get_by_id($cc, 'Catalogue_ID');
        $result .= nc_get_AdminButtons_landing_button($site_id, $class_id, $f_RowID);
    }

    if ($f_AdminButtons_change != '') {
        $result .= "<li><a onClick='nc.save_inline_then_load_dialog(this); return false;' href='" . $f_AdminButtons_change . "'>
	        <i class='nc-icon nc--edit' title='" . NETCAT_MODERATION_CHANGE . "'></i>
	    </a></li>";
    }

    if ($nc_core->get_settings('AutosaveUse') == 1) {
        $revision = nc_Core::get_object()->revision;
        $revision->set_indexes(array('Subdivision_ID' => $subdivision_id, 'Class_ID' => $class_id, 'Sub_Class_ID' => $cc), $f_RowID);

        if ($revision->check_draft_exists() == true) {
            $result .= "<li><a onClick='parent.nc_form(this.href); return false;' href='" . $f_AdminButtons_version . "'>
                <i class='nc-icon nc--clock' title='" . NETCAT_MODERATION_VERSION . "'></i>
            </a></li>";
        } else {
            $result .= "<li><a href=# style='cursor: default;' onclick='return false;'><i class='nc-icon nc--clock nc--dark' title='" . NETCAT_MODERATION_VERSION_NOT_FOUND . "'></i></a></li>";
        }
    }
    if ($f_AdminButtons_delete != '') {
        $result .= "<li><a" . ($nc_core->admin_mode ? " onClick='parent.nc_action_message(this.href); return false;'" : "") . " href='" . $f_AdminButtons_delete . "'>
            <i class='nc-icon nc--remove' title='" . NETCAT_MODERATION_DELETE . "'></i>
        </a></li>";
    }

    return $result;
}

/**
 * Временная функция, генерирует кнопку создания лендинг-страницы в «старом» стиле
 * @param $site_id
 * @param $component_id
 * @param $object_id
 * @return string
 */
function nc_get_AdminButtons_landing_button($site_id, $component_id, $object_id) {
    $create_landing_link = nc_landing::get_instance($site_id)->get_object_landing_create_dialog_url($component_id, $object_id);
    return "<li><a href='" . htmlspecialchars($create_landing_link, ENT_QUOTES) . "' target='_blank' onclick='nc.load_dialog(this.href); return false'>
	        <i class='nc-icon nc--mod-landing' title='" . NETCAT_MODULE_LANDING_CONSTRUCTOR_BUTTON_TITLE . "'></i>
	        </a></li>";
}

function nc_get_AdminButtonsMultiBlock(
    $f_RowID,
    $f_Checked,
    $f_AdminButtons_check,
    $f_AdminButtons_copy,
    $f_AdminButtons_change,
    $f_AdminButtons_delete,
    $f_AdminButtons_version,
    $cc,
    $subdivision_id,
    $class_id,
    $show_landing_buttons,
    $show_drag_button
) {
    $nc_core = nc_Core::get_object();

    $result = "<ul class='nc6-toolbar nc-object-toolbar'>";
//    if ($nc_core->get_settings('PacketOperations')) {
//        $result .= "
//        <li><label>
//            <input class='nc_multi_check' type='checkbox' onchange='nc_package_obj.select(" . $f_RowID . ", " . $cc . ");' > " . $f_RowID . "
//        </label></li>";
//    }

    $expanded_menu_items = array();
    $compact_menu_items = array();

    // Перетаскивание
    if ($show_drag_button) {
        $result .=
            "<li class='nc-move-place nc--expanded-only' id='message" . $class_id . "-" . $f_RowID . "_handler'>" .
            "<i class='nc-icon-drag'></i></li>";
    }

    // Редактирование
    if ($f_AdminButtons_change != '') {
        $result .= "<li class='nc--expanded-only'><a onClick='nc.save_inline_then_load_dialog(this); return false;' href='" . $f_AdminButtons_change . "'>
	        <i class='nc-icon-edit' title='" . NETCAT_MODERATION_CHANGE . "'></i>
	    </a></li>";
        $compact_menu_items[] = "<li class='nc--compact-only'><a onClick='nc.save_inline_then_load_dialog(this); return false;' href='" . $f_AdminButtons_change . "'>
	        <i class='nc-icon-edit'></i><span class='nc--caption'>" . NETCAT_MODERATION_CHANGE . "</span>
	    </a></li>";
    }

    // Вкл-выкл
    if ($f_AdminButtons_check != '') {
        $result .= "<li class='nc--expanded-only'><a href='$f_AdminButtons_check' onClick='parent.nc_action_message(this.href); return false;'>";

        if ($f_Checked) {
            $result .= "<span class='nc--on'>" . NETCAT_MODERATION_OBJ_ON . "</span>";
        }
        else {
            $result .= "<span class='nc--off'>" . NETCAT_MODERATION_OBJ_OFF . "</span>";
        }

        $result .= "</a></li>";

        $compact_menu_items[] = "<li class='nc--compact-only'><a href='$f_AdminButtons_check' onClick='parent.nc_action_message(this.href); return false;'>
	        <i class='nc-icon-show'></i><span class='nc--caption'>" . ($f_Checked ? NETCAT_MODERATION_TURNTOOFF : NETCAT_MODERATION_TURNTOON) . "</span>
	    </a></li>";
    }

    // Черновик
    if ($nc_core->get_settings('AutosaveUse') == 1) {
        $revision = nc_Core::get_object()->revision;
        $revision->set_indexes(array('Subdivision_ID' => $subdivision_id, 'Class_ID' => $class_id, 'Sub_Class_ID' => $cc), $f_RowID);

        if ($revision->check_draft_exists() == true) {
            $result .= "<li class='nc--expanded-only'><a onClick='parent.nc_form(this.href); return false;' href='" . $f_AdminButtons_version . "'>
                <i class='nc-icon-object-version' title='" . NETCAT_MODERATION_VERSION . "'></i>
            </a></li>";

            $compact_menu_items[] = "<li class='nc--compact-only'><a onClick='parent.nc_form(this.href); return false;' href='" . $f_AdminButtons_version . "'>
                <i class='nc-icon-object-version'></i><span class='nc--caption'>" . NETCAT_MODERATION_VERSION . "'></span>
            </a></li>";
        }
    }

    // все остальные пункты идут в подменю ···

    // Лендинг
    if ($show_landing_buttons) {
        $site_id = $nc_core->subdivision->get_by_id($subdivision_id, 'Catalogue_ID');
        $create_landing_link = nc_landing::get_instance($site_id)->get_object_landing_create_dialog_url($class_id, $f_RowID);
        $expanded_menu_items[] = "<li><a href='" . htmlspecialchars($create_landing_link, ENT_QUOTES) . "' target='_blank' onclick='nc.load_dialog(this.href); return false'>
        	<i class='nc-icon-landing'></i><span class='nc--caption nc--dropdown-only'>" . NETCAT_MODULE_LANDING_CONSTRUCTOR_BUTTON_TITLE . "</span>
    	</a></li>";
    }

    // Копировать/перенести
    if ($f_AdminButtons_copy != '') {
        $expanded_menu_items[] = "<li><a href='#' onclick=\"window.open('" . $f_AdminButtons_copy . "', '', 'width=800,height=500,menubar=no,resizable=no,scrollbars=no,toolbar=no,resizable=yes'); return false;\">
        	<i class='nc-icon-copy'></i><span class='nc--caption nc--dropdown-only'>" . NETCAT_MODERATION_COPY_OBJECT . "</span>
    	</a></li>";
    }

    // Удалить
    if ($f_AdminButtons_delete != '') {
        $expanded_menu_items[] = "<li class='nc-object-delete'><a onclick='parent.nc_action_message(this.href); return false'" .
                " href='$f_AdminButtons_delete'>" .
                "<i class='nc-icon-trash'></i><span class='nc--caption nc--dropdown-only'>" .
                NETCAT_MODERATION_DELETE . "</span></a></li>";
    }

    $more_caption = htmlspecialchars(NETCAT_MODERATION_MORE_OBJECT, ENT_QUOTES);

    // Не показываем меню «ещё», если пунктов в нём меньше 2
    $show_expanded_menu = count($expanded_menu_items) > 1;
    if (!$show_expanded_menu) {
        $result .= implode('', $expanded_menu_items);
    }

    $result .= "<li class='nc--dropdown nc-object-toolbar-more" . ($show_expanded_menu ? '' : ' nc--compact-only') . "'><a>" .
        "<i class='nc-icon-more-object nc--expanded-only' title='$more_caption'></i>" .
        "<i class='nc-icon-edit nc--compact-only' title='$more_caption'></i>" .
        "</a><ul>";

    $result .= implode('', $compact_menu_items);

    if ($show_expanded_menu) {
        $result .= implode('', $expanded_menu_items);
    }

    $result .= "</ul></li>";
    $result .= "<div class='nc-object-toolbar-bridge'></div>";
    $result .= "</ul>";
    return $result;
}

function nc_AdminCommonAddObject($infoblock_id, $subdivision_id) {
    $nc_core = nc_Core::get_object();
    $add_object_path = $nc_core->SUB_FOLDER . $nc_core->HTTP_ROOT_PATH . "add.php?sub=$subdivision_id&cc=$infoblock_id";
    $accusative = $nc_core->sub_class->get_by_id($infoblock_id, 'ObjectNameSingular');
    $caption = htmlspecialchars(NETCAT_MODERATION_ADD_OBJECT . ' ' . (!empty($accusative) ? $accusative : NETCAT_MODERATION_ADD_OBJECT_DEFAULT), ENT_QUOTES);

    return
        "<ul class='nc6-toolbar nc--right nc-infoblock-toolbar'>" .
        "<li class='nc--expanded-only'>" .
        "<a onclick='parent.nc_form(this.href); return false;' href='$add_object_path'>" .
        "<i class='nc-icon-add'></i><span class='nc--caption'>$caption</span></a>" .
        "</li>" .
        "<li class='nc--compact-only'>" .
        "<a onclick='parent.nc_form(this.href); return false;' href='$add_object_path' title='$caption'>" .
        "<i class='nc-icon-add' title='" . htmlspecialchars($caption, ENT_QUOTES) . "'></i></a>" .
        "</li>" .
        "</ul>";
}

function nc_AdminCommonMultiBlock($infoblock_id, $subdivision_id, $show_add_record_button) {
    $nc_core = nc_Core::get_object();

    $path = $nc_core->SUB_FOLDER . $nc_core->HTTP_ROOT_PATH;
    $controller_path = $path . 'action.php?ctrl=admin.infoblock&infoblock_id=' . $infoblock_id;

    $infoblock_data = $nc_core->sub_class->get_by_id($infoblock_id);
    $component_id = $infoblock_data['Class_ID'];

    $toggle_infoblock_path = $controller_path . '&action=toggle';
    $add_object_path = $path . "add.php?sub=$subdivision_id&cc=$infoblock_id";
    $accusative = $component_id ? $nc_core->component->get_by_id($component_id, 'ObjectNameSingular') : null;
    $is_enabled = $infoblock_data['Checked'];
    $is_disallow_move_and_delete = $nc_core->sub_class->disallow_move_and_delete($infoblock_id);
    $area_keyword = $infoblock_data['AreaKeyword'];
    $container_id = $infoblock_data['Parent_Sub_Class_ID'];

    $type = $infoblock_data['Sub_Class_Type'];
    $is_container = !$component_id;
    $is_main_container = $type === 'main';

    $hash = '#' . nc_transliterate($infoblock_data['EnglishName'], true);

    $result = "<ul class='nc6-toolbar nc--right nc-infoblock-toolbar" . ($is_enabled ? "" : " nc--disabled") . "'" .
              " data-infoblock-id='$infoblock_id'>";

    // Добавление элемента
    if ($show_add_record_button) {
        $add_record_button = "<li class='nc--space-after %s'>" .
            "<a onclick='parent.nc_form(this.href); return false;' href='" . $add_object_path . "'>" .
            "<i class='nc-icon-add'></i><span class='nc--caption'>" .
            NETCAT_MODERATION_ADD_OBJECT . " " .
            (!empty($accusative) ? $accusative : NETCAT_MODERATION_ADD_OBJECT_DEFAULT) . "</a>" .
            "</span></li>";
        $result .= sprintf($add_record_button, 'nc--expanded-only');
        $add_record_menu = sprintf($add_record_button, 'nc--compact-only');
    } else {
        $add_record_menu = '';
    }

    // Шаблоны компонента
    $template_menu = '';
    if ($type === 'object_list' || $type === 'object_partial') {
        $block_templates = array();
        $default_template_can_be_used = $nc_core->component->get_by_id($component_id, 'IsOptimizedForMultipleMode');

        if ($type === 'object_list') {
            foreach ($nc_core->component->get_component_templates($component_id, 'useful') as $template) {
                if ($template['IsOptimizedForMultipleMode']) {
                    $block_templates[] = $template;
                }
            }
        } else if ($type === 'object_partial') {
            $block_templates = $nc_core->component->get_full_page_templates_for_area($area_keyword, true);
        }

        $component_template_id = $infoblock_data['Class_Template_ID'];
        if (count($block_templates) + $default_template_can_be_used > 1) {

            $template_menu = "<li class='nc--dropdown %s'>" .
                "<a onclick='return false;' href='" . $hash . "'>" .
                "<i class='nc-icon-component-template'></i>" .
                "<span class='nc--caption'>" . NETCAT_MODERATION_COMPONENT_TEMPLATES . "</span>" .
                "</a>" .
                "<ul class='nc-infoblock-template-list'>";

            if ($default_template_can_be_used) {
                $template_menu .= "<li>" .
                    (!$component_template_id
                        ? "<a onclick='return false' class='nc--current'>" . NETCAT_MODERATION_COMPONENT_NO_TEMPLATE . "</a>"
                        : "<a onclick='return nc_infoblock_set_template($subdivision_id,$infoblock_id,0)' href='$hash'>" .
                        NETCAT_MODERATION_COMPONENT_NO_TEMPLATE . "</a>"
                    ) .
                    "</li>";
            }

            foreach ($block_templates as $template) {
                if ($template['Class_ID'] == $component_template_id) {
                    $return = "false";
                    $class = " class='nc--current'";
                } else {
                    $return = "nc_infoblock_set_template($subdivision_id,$infoblock_id,$template[Class_ID])";
                    $class = "";
                }

                $template_menu .= "<li$class><a onclick='return $return' href='$hash'>" .
                    htmlspecialchars(nc_preg_replace('/(?<!%)%(?!%)/', '%%', $template['Class_Name'])) .
                    "</a></li>";
            }

            $template_menu .= "</ul></li>";

            $result .= sprintf($template_menu, 'nc--expanded-only');
            $template_menu = sprintf($template_menu, 'nc--compact-only');
        }
    }

    // Настройки инфоблока

    $settings_caption = htmlspecialchars(($is_container ? NETCAT_INFOBLOCK_SETTINGS_CONTAINER : NETCAT_MODERATION_BLOCK_SETTINGS), ENT_QUOTES);
    $settings_menu_item = "<li class='%s'><a onClick='nc.save_inline_then_load_dialog(this); return false'" .
            " href='$controller_path&action=show_settings_dialog'>" .
            "<i class='nc-icon-settings'%s></i>" .
            "%s" .
            "</a></li>";
    $result .= sprintf($settings_menu_item, 'nc--expanded-only', " title='$settings_caption'", '');
    $settings_menu_item = sprintf($settings_menu_item, 'nc--compact-only', '', "<span class='nc--caption'>$settings_caption</span>");

    // Вкл-выкл

    $result .= "<li class='nc--expanded-only'>" .
        "<a onClick='" . ($is_disallow_move_and_delete ? "nc.load_dialog(this.href); return false" : "return nc_infoblock_toggle(this)") . "'" .
        "href='" . ($is_disallow_move_and_delete ? "$controller_path&action=show_error_infoblock&type_action=hide" : $toggle_infoblock_path) . "'>" .
        "<span class='nc--on'>" . NETCAT_MODERATION_OBJ_ON . "</span>" .
        "<span class='nc--off'>" . NETCAT_MODERATION_OBJ_OFF . "</span>" .
        "</a></li>";

    $toggle_menu_item = "<li class='nc--compact-only'>" .
        "<a onClick='" . ($is_disallow_move_and_delete ? "nc.load_dialog(this.href); return false" : "return nc_infoblock_toggle(this)") . "' href='" . ($is_disallow_move_and_delete ? "$controller_path&action=show_error_infoblock&type_action=hide" : $toggle_infoblock_path) . "'>" .
        "<i class='nc-icon-show" . ($is_enabled ? ' nc-icon-show-strike' : '') . "'></i><span class='nc--caption'>" .
        "<span class='nc--on'>" . htmlspecialchars(NETCAT_MODERATION_TURNTOOFF) . '</span>' .
        "<span class='nc--off'>" . htmlspecialchars(NETCAT_MODERATION_TURNTOON) . '</span>' .
        "</span></a></li>";

    // ···

    $more_button_caption = $is_container ? NETCAT_MODERATION_MORE_CONTAINER : NETCAT_MODERATION_MORE_BLOCK;

    $result .= "<li class='nc--dropdown nc-infoblock-toolbar-more'>" .
        "<a href='$hash'><i class='nc-icon-more-block' title='" . htmlspecialchars($more_button_caption, ENT_QUOTES) . "'></i></a>" .
        "<ul>";

    $result .= $add_record_menu;
    $result .= $template_menu;
    $result .= $settings_menu_item;

    // Вырезать
    $result .= "<li><a onclick='nc_infoblock_buffer_add($infoblock_id, true); return false'" .
            " href='$hash'>" .
            "<i class='nc-icon-cut'></i><span class='nc--caption'>" .
            NETCAT_MODERATION_CUT_BLOCK . "</span></a></li>";

    // Скопировать
    if (!$is_main_container) {
        $result .= "<li><a onclick='nc_infoblock_buffer_add($infoblock_id); return false'" .
                " href='$hash'>" .
                "<i class='nc-icon-copy'></i><span class='nc--caption'>" .
                NETCAT_MODERATION_COPY_BLOCK . "</span></a></li>";
    }

    // Выключить (для схлопнутого меню)
    $result .= $toggle_menu_item;

    // Добавить блок до, после
    $infoblock_add_params = array(
        'action' => 'show_new_infoblock_simple_dialog',
        'subdivision_id' => $subdivision_id ?: null,
        'container_id' => $container_id,
        'area_keyword' => $area_keyword ?: null,
    );
    $add_infoblock_path = $controller_path . '&amp;' . http_build_query($infoblock_add_params, null, '&amp;');

    $result .= "<li class='nc--dropdown'><a><i class='nc-icon-add'></i><span class='nc--caption'>" .
            NETCAT_MODERATION_ADD_BLOCK . "...</span></a>" .
            "<ul>" .
            "<li><a href='$add_infoblock_path&amp;position=before' onclick='nc.load_dialog(this.href); return false;'><span class='nc--caption'>" .
            NETCAT_MODERATION_ADD_BLOCK_BEFORE . ' ' .
            ($is_container ? NETCAT_MODERATION_ADD_BLOCK_RELATIVE_TO_THIS_CONTAINER : NETCAT_MODERATION_ADD_BLOCK_RELATIVE_TO_THIS_BLOCK) .
            "</span></a></li>" .
            "<li><a href='$add_infoblock_path&amp;position=after' onclick='nc.load_dialog(this.href); return false;'><span class='nc--caption'>" .
            NETCAT_MODERATION_ADD_BLOCK_AFTER . ' ' .
            ($is_container ? NETCAT_MODERATION_ADD_BLOCK_RELATIVE_TO_THIS_CONTAINER : NETCAT_MODERATION_ADD_BLOCK_RELATIVE_TO_THIS_BLOCK) .
            "</span></a></li>" .
            "</ul>" .
            "</li>";

    // Удалить
    $result .= "<li class='nc-infoblock-delete'><a onclick='nc.load_dialog(this.href); return false'" .
            " href='" . ($is_disallow_move_and_delete ? "$controller_path&action=show_error_infoblock&type_action=delete" : "$controller_path&action=show_delete_confirm_dialog") . "'>" .
            "<i class='nc-icon-trash'></i><span class='nc--caption'>" .
            ($is_container ? NETCAT_INFOBLOCK_DELETE_CONTAINER : NETCAT_MODERATION_DELETE_BLOCK) .
            "</span></a></li>";

    $result .= "</ul></li></ul><div class='nc--clearfix'></div>";

    return $result;
}

/**
 * Возвращает тулбар для добавления инфоблока.
 * Не является частью публичного API.
 * @param int $subdivision_id
 * @param string $area_keyword (пустая строка, если блок находится в основной контентной области)
 * @param $container_id
 * @param string $position 'before', 'after'
 * @param int $relative_to_infoblock_id
 * @param null|bool $cross_axis кнопка для перпендикулярной оси — обёртывание в контейнер (если null, добавляется автоматически)
 * @return string
 */
function nc_admin_infoblock_insert_toolbar($subdivision_id, $area_keyword, $container_id, $position, $relative_to_infoblock_id, $cross_axis = null) {
    $nc_core = nc_core::get_object();
    $controller_path = $nc_core->SUB_FOLDER . $nc_core->HTTP_ROOT_PATH . 'action.php?';
    $controller_params = array(
        'ctrl' => 'admin.infoblock',
        'site_id' => $nc_core->catalogue->get_current('Catalogue_ID'),
        'subdivision_id' => $subdivision_id ?: null,
        'container_id' => $container_id,
        'infoblock_id' => $relative_to_infoblock_id,
        'area_keyword' => $area_keyword ?: '',
        'position' => ($cross_axis ? 'wrap_' : '') . $position,
    );

    try {
        $reference_infoblock_is_container = !$nc_core->sub_class->get_by_id($relative_to_infoblock_id, 'Class_ID');
        $reference_infoblock_name = $nc_core->sub_class->get_by_id($relative_to_infoblock_id, 'Sub_Class_Name');
    } catch (Exception $e) {
        $reference_infoblock_is_container = false;
        $reference_infoblock_name = false;
    }

    if ($reference_infoblock_is_container || !$relative_to_infoblock_id) {
        $relative_to_block_description = $reference_infoblock_name
            ? sprintf(NETCAT_MODERATION_ADD_BLOCK_RELATIVE_TO_CONTAINER, $reference_infoblock_name)
            : NETCAT_MODERATION_ADD_BLOCK_RELATIVE_TO_THIS_CONTAINER;
    } else {
        $relative_to_block_description = $reference_infoblock_name
            ? sprintf(NETCAT_MODERATION_ADD_BLOCK_RELATIVE_TO_BLOCK, $reference_infoblock_name)
            : NETCAT_MODERATION_ADD_BLOCK_RELATIVE_TO_THIS_BLOCK;
    }

    if ($position === 'before') {
        $position_description = NETCAT_MODERATION_ADD_BLOCK_BEFORE;
    } else if ($position === 'after') {
        $position_description = NETCAT_MODERATION_ADD_BLOCK_AFTER;
    } else {
        $position_description = NETCAT_MODERATION_ADD_BLOCK_INSIDE;
    }

    $position_description .= ' ' . $relative_to_block_description;

    $add_button_title   = htmlspecialchars(NETCAT_MODERATION_ADD_BLOCK .   ' ' . $position_description, ENT_QUOTES);
    $paste_button_title = htmlspecialchars(NETCAT_MODERATION_PASTE_BLOCK . ' ' . $position_description, ENT_QUOTES);

    $add_params = array('action' => 'show_new_infoblock_simple_dialog') + $controller_params;
    $add_infoblock_path = $controller_path . http_build_query($add_params, null, '&amp;');

    $paste_params = array('action' => 'paste') + $controller_params;
    $paste_infoblock_path = $controller_path . http_build_query($paste_params, null, '&amp;');

    $axis_classes = $cross_axis
        ? 'nc-infoblock-insert-transverse nc--vertical nc--half nc--inside'
        : 'nc-infoblock-insert-between nc--horizontal';

    $result = "<div class='nc-infoblock-insert nc-infoblock-insert-$position $axis_classes'>" .
                  "<div class='nc-infoblock-insert-overlay'></div>" .
                  "<div class='nc-infoblock-insert-line'></div>" .
                  "<div class='nc-infoblock-insert-buttons'>" .
                      "<div class='nc-infoblock-insert-buttons-inner-wrapper'>" .
                          "<a class='nc-infoblock-insert-button-new' href='$add_infoblock_path'" .
                          " title='$add_button_title'" .
                          " onclick='return nc_infoblock_show_add_dialog(this);'>" .
                              "<i class='nc-icon-add'></i>" .
                          "</a>" .
                          "<a class='nc-infoblock-insert-button-paste' href='$paste_infoblock_path'" .
                          " title='$paste_button_title'" .
                          " onclick='return nc_infoblock_buffer_paste(this);'>" .
                              "<i class='nc-icon-paste-here'></i>" .
                          "</a>" .
                      "</div>" .
                  "</div>" .
              "</div>";

    if ($cross_axis === null && $position !== 'first') {
        $cross_axis_toolbar = nc_admin_infoblock_insert_toolbar($subdivision_id, $area_keyword, $container_id, $position, $relative_to_infoblock_id, true);
        if ($position === 'before') {
            $result .= $cross_axis_toolbar;
        } else {
            $result = $cross_axis_toolbar . $result;
        }
    }

    return $result;
}

function nc_get_AdminButtons_modPerm($classID, $f_RowID, $f_AdminButtons_id, $f_AdminButtons_priority, $f_AdminInterface_user_add, $f_AdminButtons_user_add, $f_AdminInterface_user_change, $f_AdminButtons_user_change, $f_AdminButtons_buttons, $cc, $query_order) {
    $f_AdminButtons = "<li><span class='nc-move-place' " . (nc_Core::get_object()->inside_admin && nc_show_drag_handler($cc, $query_order) ? '' : "style='display: none;'") . " id='message" . $classID . "-" . $f_RowID . "_handler'>
    <i class='nc-icon nc--move'></i></span></li>";
    // $f_AdminButtons.= "<div class='nc_idtab_buttons'>" . $f_AdminButtons_buttons . "</div>";
    $f_AdminButtons .= $f_AdminButtons_buttons;
    // $f_AdminButtons.= "<div class='ncf_row nc_clear'></div>";
    return $f_AdminButtons;
}

function nc_get_AdminButtons_suffix() {
    return "</ul><div class='nc--clearfix'></div>";
}

function nc_get_AdminButtons_prefix($f_Checked, $cc) {
    return "<ul class='nc-toolbar nc--left" . ($f_Checked ? "" : " nc--disabled") . "'>";
}


function nc_show_drag_handler($cc, $query_order) {
    $SortBy = nc_Core::get_object()->sub_class->get_by_id($cc, 'SortBy');
    return !$query_order && (!$SortBy || preg_match('/^\s*(a\.)?`?Priority`?(\s+(desc|asc))?\s*$/i', $SortBy));
}

function nc_add_column_aliases($query_select) {
    $columns = array();

    $column_start = 0;
    $bracket_count = 0;
    $opened_quote = false;
    $escape = false;

    for ($i = 0, $strlen = strlen($query_select); $i < $strlen; $i++) {
        $char = $query_select[$i];
        $next_char = ($i === $strlen-1) ? null : $query_select[$i+1];

        if (!$escape && ($char === '"' || $char === "'") && $next_char !== $char) { // unescaped quote symbol
            if (!$opened_quote) {  // opening quote
                $opened_quote = $char;
            }
            elseif ($opened_quote === $char) {  // closing quote
                $opened_quote = false;
            }
        }
        elseif (!$opened_quote) {
            if ($char === "(") { $bracket_count++; }
            elseif ($char === ")") { $bracket_count--; }
            elseif ($char === "," && !$bracket_count) {
                $columns[] = trim(substr($query_select, $column_start, $i - $column_start));
                $column_start = $i + 1;
            }
        }

        $escape = ($char === "\\");
    }

    $columns[] = trim(substr($query_select, $column_start, $i - $column_start));

    foreach ($columns as $i => $column) {
        if (!preg_match('/\S(?:\s+AS)?\s+[`\'"]?\w+[`\'"]?$/i', $column)) {
            $columns[$i] .= " AS `user_column_$i`";
        }

    }

    $result = join(", ", $columns);
    return $result;
}
