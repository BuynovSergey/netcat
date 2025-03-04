<?php

/* $Id: function.inc.php 8366 2012-11-07 16:30:14Z aix $ */

/**
 * Выводит список удаленных объектов
 * @param array поисковый фильтр
 */
function nc_trash_list($options = array()) {
    global $db, $UI_CONFIG, $ADMIN_TEMPLATE, $nc_core, $perm;

    $options = nc_trash_options_validate($options);
    $CartCount = 0;


    // Выбираем все данные чтобы построить пользовательские js-фильтры
    $trash_data = $db->get_results("
    SELECT `t`.`Type`, `t`.`Sub_Class_ID`, `t`.`Class_ID` , `t`.`Subdivision_ID`, t.`XML_Filename`, t.`XML_Filesize`,  UNIX_TIMESTAMP(t.`Created`) as `created`,
                    `sub`.`Subdivision_Name`, `cc`.`Sub_Class_Name`, c.`Class_Name`
    FROM `Trash_Data` as `t`
    LEFT JOIN `Class` as `c` ON `c`.`Class_ID` = `t`.`Class_ID`
    LEFT JOIN `Sub_Class` as `cc` ON `t`.`Sub_Class_ID` = `cc`.`Sub_Class_ID`
    LEFT JOIN `Subdivision` as `sub` ON `sub`.`Subdivision_ID` =  `t`.`Subdivision_ID`
    GROUP BY t.`XML_Filename`
    ORDER BY `sub`.`Subdivision_Name`, `cc`.`Sub_Class_Name`", ARRAY_A);

    if ($db->is_error) {
        throw new nc_Exception_DB_Error($db->last_query, $db->last_error);
    }

    if (!empty($trash_data))
            foreach ($trash_data as $k => $v) {
            $CartActualSummarySize[$v['XML_Filename']] = $v['XML_Filesize'];
            if (!$perm->isSubClass($v['Sub_Class_ID'], MASK_DELETE)) {
                unset($trash_data[$k]);
                continue;
            }
            $CartCount++;
        }

    if (!$nc_core->get_settings('TrashUse')) {
        nc_print_status(NETCAT_TRASH_TRASHBIN_DISABLED." <a href='".$nc_core->ADMIN_PATH."settings.php?phase=1'>".NETCAT_TRASH_EDIT_SETTINGS."</a>", 'info');
    }

    // корзина полностью пуста
    if (empty($trash_data)) {
        nc_print_status(NETCAT_TRASH_NOMESSAGES, 'info');
        return;
    }

    if (!empty($CartActualSummarySize)) {
        echo "<div id='trash_sizeinfo'>
         ".sprintf(NETCAT_TRASH_SIZEINFO, nc_bytes2size(array_sum($CartActualSummarySize)), $nc_core->get_settings('TrashLimit'))."
       </div>";
    }

    $t = nc_trash_load_sub_cc($trash_data);

    $subdivisions = $t['subs'];
    $sub_classes  = $t['ccs'];
    $cc_sub       = $t['cc_sub'];
    $classes      = $t['classes'];

    unset($t);

    echo nc_trash_search_form($subdivisions, $classes, $options);

    // фильтр
    foreach ($trash_data as $k => $v) {
        if ($options['sub'] && $options['sub'] != $v['Subdivision_ID'] ||
                $options['class_id'] && $options['class_id'] != $v['Class_ID'] ||
                $options['date_b'] && $options['date_b'] > $v['created'] ||
                $options['date_e'] && $options['date_e'] < $v['created']) {
            unset($trash_data[$k]);
        }
    }

    if (empty($trash_data)) {
        nc_print_status(NETCAT_TRASH_OBJECT_NOT_FOUND, 'info');
        return;
    }

    $t = nc_trash_load_sub_cc($trash_data);
    $subdivisions = $t['subs'];
    $sub_classes  = $t['ccs'];
    $cc_sub       = $t['cc_sub'];
    $classes      = $t['classes'];
    unset($t);

    echo "<fieldset class='trash_fldst'>
        <legend>".NETCAT_TRASH_TRASHBIN."</legend>";
    echo "<form method='post' action='index.php' name='mainForm' id='mainForm'>";
    echo $nc_core->token->get_input();
    echo "<input type='hidden' id='phase' name='phase' value=''>
    <input type='submit' class='hidden'>
    ".$nc_core->token->get_input();

    foreach ($trash_data as $t) {
        $id = $t['Sub_Class_ID'];
        $v  = $sub_classes[$id];
        $sub_id   = $sub_classes[$id]['sub_id'];
        $sub_name = $subdivisions[$sub_id]['name'];
        if (count($cc_sub[$sub_id]) > 1) {
            $sub_name .= " (".$sub_classes[$id]['name'].")";
        }

        $icon = $t['Type'] ? 'mod-comments' : 'folder-dark';

        echo "<div style='margin-bottom:10px;'>
            <i class='nc-icon nc--".$icon."'></i>
            ".$sub_id.". <a href='#' class='ajax_link' onclick='nc_trash_get_objects(".$id.", ".($options['date_b'] + 0).", ".($options['date_e'] + 0).", ".(int)$t['Type'].");return false;'>
              ".$sub_name."</a>
          <div id='cc_".$id."_".(int)$t['Type']."' style='margin:10px 0 0 20px;'></div>
        </div>";
    }

    echo "</form>";
    echo "</fieldset>";

    echo "<script type='text/javascript'>\n
   function sumbit_form ( phase ) {\n
     document.getElementById('mainForm').phase.value =  phase;\n
     parent.mainView.submitIframeForm('mainForm');\n
     return 0;\n
   }\n
   </script>\n";


    // только супервизор могут очистить всю корзину
    if ($perm->isSupervisor()) {
        $UI_CONFIG->actionButtons[] = array(
            "id" => "clean",
            "caption" => TOOLS_TRASH_CLEAN,
            "action" => "document.getElementById('mainViewIframe').contentWindow.sumbit_form(4)",
            "align" => "right",
            "red_border" => true,
        );
    }

    return true;
}

function trashRecovery($messages, $class_id = false) {
    global $nc_core;
    if ($class_id) {
        return $nc_core->trash->recovery_by_message_and_class($messages, $class_id);
    } else {
        return $nc_core->trash->recovery_by_id($messages);
    }
}

function nc_trash_prerecovery($trash_ids) {
    global $UI_CONFIG;
    $nc_core = nc_Core::get_object();

    if (!is_array($trash_ids)) $trash_ids = array($trash_ids);
    $trash_ids = array_map('intval', $trash_ids);

    $trash_data = $nc_core->db->get_results("
  SELECT `t`.`Sub_Class_ID`, `t`.`Class_ID` , `t`.`Subdivision_ID`, t.`XML_Filename`,
                  `sub`.`Subdivision_Name`, `cc`.`Sub_Class_Name`
  FROM `Trash_Data` as `t`
  LEFT JOIN `Sub_Class` as `cc` ON `t`.`Sub_Class_ID` = `cc`.`Sub_Class_ID`
  LEFT JOIN `Subdivision` as `sub` ON `sub`.`Subdivision_ID` =  `t`.`Subdivision_ID`
  WHERE t.Trash_ID IN (".join(',', $trash_ids).")
  AND `sub`.`Subdivision_ID` IS NULL
  GROUP BY `t`.`Subdivision_ID`", ARRAY_A);

    if (!$trash_data) return false;

    $t = nc_trash_load_sub_cc($trash_data);
    $subdivisions = $t['subs'];
    $sub_classes = $t['ccs'];
    $cc_sub = $t['cc_sub'];
    unset($t);

    foreach ($subdivisions as $sub_id => $row) {
        $f = 1;
        foreach ($trash_data as $v) {
            if ($v['Subdivision_ID'] == $sub_id) $f = 0;
        }
        if ($f) unset($subdivisions[$sub_id]);
    }

    if (!count($subdivisions)) return false;

    echo NETCAT_TRASH_PRERECOVERYSUB_INFO;
    echo "<form action='index.php' method='post' >";
    foreach ($subdivisions as $sub_id => $row) {
        // узнаем номер сайта
        $catalogue = intval($row['catalogue']);
        try {
            $nc_core->catalogue->get_by_id($catalogue);
        } catch (Exception $e) {
            $catalogue = $nc_core->db->get_var("SELECT `Catalogue_ID` FROM `Catalogue` ORDER BY Checked, Priority LIMIT 1");
        }
        echo "<input type='hidden' name='sub_catalogue[".$sub_id."]' value='".$catalogue."' />";
        $subs = $nc_core->db->get_results("SELECT `Subdivision_ID` AS value,
                                    CONCAT(Subdivision_ID, '. ', Subdivision_Name) AS description,
                                    `Parent_Sub_ID` AS parent
                                    FROM `Subdivision`
                                    WHERE `Catalogue_ID` = '".$catalogue."'
                                    ORDER BY `Subdivision_ID`", ARRAY_A);

        echo "<table border='0' cellpadding='6' cellspacing='0' width='100%'>
           <tr><td>".
        nc_admin_checkbox_simple("sub_checked[".$sub_id."]", 1, NETCAT_TRASH_PRERECOVERYSUB_CHECKED, '', ($row['checked'] ? " checked='checked'" : ""))."
           </td></tr>
           <tr><td>
             <font color='gray'>".NETCAT_TRASH_PRERECOVERYSUB_NAME.":</font><br/>".
        nc_admin_input_simple("sub_name[".$sub_id."]", $row['name'], 50, '', "maxlength='255'")."
           </td></tr>
           <tr><td>
             <font color='gray'>".NETCAT_TRASH_PRERECOVERYSUB_KEYWORD.":</font><br/>".
        nc_admin_input_simple("sub_keyword[".$sub_id."]", $row['keyword'], 50, '', "maxlength='255'")."
           </td></tr>
           <tr><td>
           <font color='gray'>".NETCAT_TRASH_PRERECOVERYSUB_PARENT.":</font><br/>
           <select name='sub_parent[".$sub_id."]' ><option value='0'>".NETCAT_TRASH_PRERECOVERYSUB_ROOT."</option>".nc_select_options($subs, $row['parent'])."</select>
           </td></tr>
         </table><br/>";
    }
    foreach ($trash_ids as $id) {
        echo "<input type='hidden' name='trash_ids[]' value='".$id."' />\r\n";
    }
    echo "<input type='hidden' name='phase' value='21' />";
    echo "</form>";


    $UI_CONFIG->actionButtons[] = array("id" => "next",
            "caption" => NETCAT_TRASH_PRERECOVERYSUB_NEXT,
            "action" => "mainView.submitIframeForm()"
    );

    return true;
}

function nc_trash_load_sub_cc($trash_data) {
    $nc_core = nc_Core::get_object();
    $subdivisions = $sub_classes = $cc_sub = $classes = array();
    $err_str = '';
    if (empty($trash_data)) return 0;

    foreach ($trash_data as $row) {
        $classes[$row['Class_ID']] = array('name' => $row['Class_Name']);
        $cc_sub[$row['Subdivision_ID']][] = $row['Sub_Class_ID'];
        $cc_sub[$row['Subdivision_ID']] = array_unique($cc_sub[$row['Subdivision_ID']]);
        // раздел сущетсвует
        if ($row['Subdivision_Name']) {
            $subdivisions[$row['Subdivision_ID']] = array('name' => $row['Subdivision_Name'],
                    'keyword' => $row['EnglishName'],
                    'checked' => $row['Checked'],
                    'parent' => $row['Parent_Sub_ID'],
                    'catalogue' => $row['Catalogue_ID'],
                    'exists' => 1);
        }
        if ($row['Sub_Class_Name']) {
            $sub_classes[$row['Sub_Class_ID']] = array('name' => $row['Sub_Class_Name'],
                    'sub_id' => $row['Subdivision_ID'],
                    'class_id' => $row['Class_ID'],
                    'exists' => 1);
        }
        if (!$row['Sub_Class_Name'] || !$row['Subdivision_Name']) {
            // поиск раздела в корзине
            if (!file_exists($nc_core->TRASH_FOLDER.$row['Class_ID'].'/'.$row['XML_Filename'])) {
                $nc_core->db->query("DELETE FROM `Trash_Data` WHERE `Class_ID` = '".$row['Class_ID']."' AND `XML_Filename` = '".$row['XML_Filename']."' ");
                $err_str .= sprintf(NETCAT_TRASH_FILE_DOEST_EXIST, $TRASH_FOLDER.'/'.$row['Class_ID'].'/'.$row['XML_Filename'])."<br/>";
                continue;
            }
            $doc = new DOMDocument(1.0, 'utf-8');
            $doc->load($nc_core->TRASH_FOLDER.$row['Class_ID'].'/'.$row['XML_Filename']);
            $xpath = new DOMXPath($doc);
            $subs = $xpath->query("/netcatml/subdivisions/subdivision");
            for ($i = 0; $i < $subs->length; $i++) {
                $v = $subs->item($i);
                $sub_id = $v->attributes->getNamedItem('subdivision_id')->value;
                $cat_id = $v->attributes->getNamedItem('catalogue_id')->value;
                $parent = $v->attributes->getNamedItem('parent_sub_id')->value;
                for ($j = 0; $j < $v->childNodes->length; $j++) {
                    $a = $v->childNodes->item($j);
                    if ($a->nodeName == 'Subdivision_Name')
                            $sub_name = $nc_core->trash->encode_to_system($a->nodeValue);
                    if ($a->nodeName == 'EnglishName')
                            $sub_keyword = $nc_core->trash->encode_to_system($a->nodeValue);
                    if ($a->nodeName == 'Checked') $sub_checked = $a->nodeValue;
                }
                if (!$subdivisions[$sub_id])
                        $subdivisions[$sub_id] = array('name' => $sub_name,
                            'keyword' => $sub_keyword,
                            'catalogue' => $cat_id,
                            'parent' => $parent,
                            'checked' => $sub_checked,
                            'exists' => 0);
            }
            // поиск компонентов в разделе
            $ссs = $xpath->query("/netcatml/sub_classes/sub_class");
            for ($i = 0; $i < $ссs->length; $i++) {
                $v = $ссs->item($i);
                $сс_id = $v->attributes->getNamedItem('sub_class_id')->value;
                $sub_id = $v->attributes->getNamedItem('subdivision_id')->value;
                $class_id = $v->attributes->getNamedItem('class_id')->value;
                for ($j = 0; $j < $v->childNodes->length; $j++) {
                    $a = $v->childNodes->item($j);
                    if ($a->nodeName == 'Sub_Class_Name')
                            $сс_name = $nc_core->trash->encode_to_system($a->nodeValue);
                }
                if (!$sub_classes[$сс_id])
                        $sub_classes[$сс_id] = array('name' => $сс_name, 'sub_id' => $sub_id, 'class_id' => $class_id, 'exists' => 0);
            }
        }
    }

    if ($err_str) {
        nc_print_status(NETCAT_TRASH_ERROR_RELOAD_PAGE, 'info');
        nc_print_status($err_str, 'error');
        exit;
    }
    return array('subs' => $subdivisions, 'ccs' => $sub_classes, 'cc_sub' => $cc_sub, 'classes' => $classes);
}

function nc_trash_recovery_sub($trash_ids) {
    $nc_core = nc_Core::get_object();
    $db = $nc_core->db;

    if (!is_array($trash_ids)) $trash_ids = array($trash_ids);
    $trash_ids = array_map('intval', $trash_ids);

    $sub_name = $nc_core->input->fetch_get_post('sub_name');
    $sub_catalogue = $nc_core->input->fetch_get_post('sub_catalogue');
    $sub_checked = $nc_core->input->fetch_get_post('sub_checked');
    $sub_keyword = $nc_core->input->fetch_get_post('sub_keyword');
    $sub_parent = $nc_core->input->fetch_get_post('sub_parent');

    $trash_data = $nc_core->db->get_results("
  SELECT `t`.`Sub_Class_ID`, `t`.`Class_ID` , `t`.`Subdivision_ID`, t.`XML_Filename`,
        `sub`.`Subdivision_Name`, `cc`.`Sub_Class_Name`
  FROM `Trash_Data` as `t`
  LEFT JOIN `Sub_Class` as `cc` ON `t`.`Sub_Class_ID` = `cc`.`Sub_Class_ID`
  LEFT JOIN `Subdivision` as `sub` ON `sub`.`Subdivision_ID` =  `t`.`Subdivision_ID`
  WHERE t.Trash_ID IN (".join(',', $trash_ids).")
  AND `sub`.`Subdivision_ID` IS NULL
  GROUP BY `t`.`Subdivision_ID`", ARRAY_A);

    $t = nc_trash_load_sub_cc($trash_data);
    $subdivisions = $t['subs'];
    $sub_classes = $t['ccs'];
    $cc_sub = $t['cc_sub'];

    if (!empty($sub_name))
            foreach ($sub_name as $id => $name) {
            $db->query("INSERT INTO `Subdivision` SET `Subdivision_ID` = '".intval($id)."',
                                         `Catalogue_ID` = '".intval($sub_catalogue[$id])."',
                                         `Subdivision_Name` = '".$db->escape($name)."',
                                         `Checked` = '".intval($sub_checked[$id])."',
                                         `Parent_Sub_ID` = '".intval($sub_parent[$id])."',
                                         `EnglishName` = '".$sub_keyword[$id]."' ");

            // обновим Hidden_URL
            $hidden_url = GetHiddenURL($sub_parent[$id]);
            UpdateHiddenURL($hidden_url ? $hidden_url : "/", $sub_parent[$id], $sub_catalogue[$id]);

            $ccs = $cc_sub[$id];
            foreach ($ccs as $cc_id) {
                $db->query("INSERT INTO `Sub_Class` SET `Sub_Class_ID` = '".intval($cc_id)."',
                                           `Catalogue_ID` = '".intval($sub_catalogue[$id])."',
                                           `Subdivision_ID` = '".intval($id)."',
                                           `Class_ID` = '".intval($sub_classes[$cc_id]['class_id'])."',
                                           `Sub_Class_Name` = '".$db->escape($sub_classes[$cc_id]['name'])."',
                                           `Checked` = '".intval($sub_checked[$id])."',
                                           `EnglishName` = '".$sub_keyword[$id]."' ");
            }
        }

    return count($sub_name);
}

function nc_trash_search_form($subs = array(), $classes = array(), $options = array()) {
    $nc_core = nc_Core::get_object();
    if ($nc_core->modules->get_by_keyword('calendar', 0)) {
        echo nc_set_calendar(0);
    }

    if ($options['date_b']) {
        $b_d = $options['date_b_dd'];
        if ($b_d < 10) $b_d = '0'.$b_d;
        $b_m = $options['date_b_mm'];
        if ($b_m < 10) $b_m = '0'.$b_m;
        $b_y = $options['date_b_yyyy'];
        $b_h = $options['date_b_hh'];
        if ($b_h < 10) $b_h = '0'.$b_h;
        $b_i = $options['date_b_min'];
        if ($b_i < 10) $b_i = '0'.$b_i;
    }
    if ($options['date_e']) {
        $e_d = $options['date_e_dd'];
        if ($e_d < 10) $e_d = '0'.$e_d;
        $e_m = $options['date_e_mm'];
        if ($e_m < 10) $e_m = '0'.$e_m;
        $e_y = $options['date_e_yyyy'];
        $e_h = $options['date_e_hh'];
        if ($e_h < 10) $e_h = '0'.$e_h;
        $e_i = $options['date_e_min'];
        if ($e_i < 10) $e_i = '0'.$e_i;
    }

    $html .= "

    <div id='RecycleSearchOn'>
      <fieldset class='trash_fldst'>
        <legend>".NETCAT_TRASH_FILTER.":
        </legend>
        <form method='post' action='index.php'>
          <table id='trash_search'  class='admin_table'>
            <tr>
              <td class='left'>".NETCAT_TRASH_FILTER_DATE_FROM." <i>(".NETCAT_TRASH_FILTER_DATE_FORMAT.")</i></td>
              <td class='right'>".
            nc_admin_input_simple('options[date_b_dd]', $b_d, 2, '', "maxlength='2'")."-".
            nc_admin_input_simple('options[date_b_mm]', $b_m, 2, '', "maxlength='2'")."-".
            nc_admin_input_simple('options[date_b_yyyy]', $b_y, 4, '', "maxlength='4'")."&nbsp;&nbsp;".
            nc_admin_input_simple('options[date_b_hh]', $b_h, 2, '', "maxlength='2'").":".
            nc_admin_input_simple('options[date_b_min]', $b_i, 2, '', "maxlength='2'")."
                </td>";
            if ($nc_core->modules->get_by_keyword('calendar', 0)) {
		$html .= "<td class= 'right'>
				<div class='calendar'>
                    <a href='#' id='nc_calendar_popup_img_options[date_b_dd]' onclick='nc_calendar_popup(\"options[date_b_dd]\",\"options[date_b_mm]\", \"options[date_b_yyyy]\", \"0\"); return false'>
                        <i class='nc-icon nc--calendar'></i>
                    </a>
                </div>
                <div class='_cl'  id='nc_calendar_popup_options[date_b_dd]'></div>

              </td>";
            }
            $html .= "</tr><tr>

              <td class='left'>".NETCAT_TRASH_FILTER_DATE_TO." <i>(".NETCAT_TRASH_FILTER_DATE_FORMAT.")</i></td>
              <td class='right'>".
            nc_admin_input_simple('options[date_e_dd]', $e_d, 2, '', "maxlength='2'")."-".
            nc_admin_input_simple('options[date_e_mm]', $e_m, 2, '', "maxlength='2'")."-".
            nc_admin_input_simple('options[date_e_yyyy]', $e_y, 4, '', "maxlength='4'")."&nbsp;&nbsp;".
            nc_admin_input_simple('options[date_e_hh]', $e_h, 2, '', "maxlength='2'").":".
            nc_admin_input_simple('options[date_e_min]', $e_i, 2, '', "maxlength='2'")."
                </div>
                </td>";
            if ($nc_core->modules->get_by_keyword('calendar', 0)) {
		$html .= "<td class= 'right'>
                    <div class='calendar'>
                        <a href='#' id='nc_calendar_popup_img_options[date_e_dd]' onclick='nc_calendar_popup(\"options[date_e_dd]\",\"options[date_e_mm]\", \"options[date_e_yyyy]\", \"0\"); return false'>
                            <i class='nc-icon nc--calendar'></i>
                        </a>
                    </div>
				</td><div class='_cl' id='nc_calendar_popup_options[date_e_dd]'></div>";
            }

            $html .= "</tr><tr>";

    if (!empty($subs)) {
        $html .="
              <td class='left'>".NETCAT_TRASH_FILTER_SUBDIVISION."</td>
              <td class='right' colspan='2'>
              <select  name='options[sub]' class='select'>
              <option value='0'>".NETCAT_TRASH_FILTER_ALL."</option>";
        foreach ($subs as $id => $v) {
            $html .="<option value='".$id."' ".($options['sub'] == $id ? "selected='selected'" : "").">".$id.". ".$v['name']."</option>\r\n";
        }
        $html .="
              </select>
              </td>
              </tr><tr>";
    }

    if (!empty($classes)) {
        $html .="
              <td class='left'>".NETCAT_TRASH_FILTER_COMPONENT."</td>
              <td class='right' colspan='2'>
              <select  name='options[class_id]' class='select'>
              <option value='0'>".NETCAT_TRASH_FILTER_ALL."</option>";
        foreach ($classes as $id => $v) {
            if ($id) {
                $html .="<option value='".$id."' ".($options['class_id'] == $id ? "selected='selected'" : "").">".$id.". ".$v['name']."</option>\r\n";
            }
        }
        $html .="
              </select>
              </td>
              </tr>";
    }
    $html .= "
        </table>
        <div style='text-align:right; padding-right:30px'>
            <input style='background: #EEE; padding: 8px 6px 12px 6px; font-size: 15px; color: #333; border: 2px solid #1A87C2;' type='submit' class='s' value='" . NETCAT_TRASH_FILTER_APPLY . "' title='" . NETCAT_TRASH_FILTER_APPLY . "' />
      </form>
    </fieldset>
  </div>";

    return $html;
}

function nc_trash_options_validate($options = array()) {
    if (!isset($options)) {
        $options = array();
    }

    $options["sub"]      = (int)nc_array_value($options, 'sub', 0);
    $options["class_id"] = (int)nc_array_value($options, 'class_id', 0);

    foreach (array('b', 'e') as $tag) {
        $options["date_{$tag}_dd"]   = (int)nc_array_value($options, 'date_b_dd');
        $options["date_{$tag}_mm"]   = (int)nc_array_value($options, 'date_b_mm');
        $options["date_{$tag}_yyyy"] = (int)nc_array_value($options, 'date_b_yyyy');
        $options["date_{$tag}_hh"]   = (int)nc_array_value($options, 'date_b_hh', 0);
        $options["date_{$tag}_min"]  = (int)nc_array_value($options, 'date_b_min', 0);

        if (
            $options["date_{$tag}_dd"]
            || $options["date_{$tag}_mm"]
            || $options["date_{$tag}_yyyy"]
            || $options["date_{$tag}_hh"]
            || $options["date_{$tag}_min"]
        ) {
            $options["date_{$tag}_dd"] = (int)nc_array_value($options, "date_{$tag}_dd", date("d"));
            $options["date_{$tag}_mm"] = (int)nc_array_value($options, "date_{$tag}_mm", date("m"));
            $options["date_{$tag}_yyyy"] = (int)nc_array_value($options, "date_{$tag}_yyyy", date("Y"));

            if ($options["date_{$tag}_dd"] > 31) {
                $options["date_{$tag}_dd"] = 31;
            }
            if ($options["date_{$tag}_dd"] < 1) {
                $options["date_{$tag}_dd"] = 1;
            }

            if ($options["date_{$tag}_mm"] > 12) {
                $options["date_{$tag}_mm"] = 12;
            }
            if ($options["date_{$tag}_mm"] < 1) {
                $options["date_{$tag}_mm"] = 1;
            }

            if ($options["date_{$tag}_yyyy"] < 1) {
                $options["date_{$tag}_yyyy"] = 1970;
            }

            if ($options["date_{$tag}_hh"] > 23) {
                $options["date_{$tag}_hh"] = 23;
            }
            if ($options["date_{$tag}_hh"] < 0) {
                $options["date_{$tag}_hh"] = 0;
            }

            if ($options["date_{$tag}_min"] > 59) {
                $options["date_{$tag}_min"] = 59;
            }
            if ($options["date_{$tag}_min"] < 0) {
                $options["date_{$tag}_min"] = 0;
            }

            $options["date_{$tag}"] = mktime(
                $options["date_{$tag}_hh"],
                $options["date_{$tag}_min"],
                $tag === 'e' ? 59 : 0,
                $options["date_{$tag}_mm"],
                $options["date_{$tag}_dd"],
                $options["date_{$tag}_yyyy"]
            );
        }
    }

    return array_map('intval', $options);
}
?>
