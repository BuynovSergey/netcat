<?php

$NETCAT_FOLDER = join(strstr(__FILE__, "/") ? "/" : "\\", array_slice(preg_split("/[\/\\\]+/", __FILE__), 0, -4)).( strstr(__FILE__, "/") ? "/" : "\\" );
include_once ($NETCAT_FOLDER."vars.inc.php");
require_once ($ROOT_FOLDER."connect_io.php");
require_once ($ADMIN_FOLDER."function.inc.php");

// проверки
// $removed_cc может прийти из /netcat/admin/subdivision/index.php
$cc      = isset($removed_cc) ? (int)$removed_cc : (int)$nc_core->input->fetch_get_post('cc');
$type_id = intval($nc_core->input->fetch_get_post('type_id'));
$date_b  = intval($nc_core->input->fetch_get_post('date_b'));
$date_e  = intval($nc_core->input->fetch_get_post('date_e'));
if (!$cc) exit;
if (!$perm instanceof Permission || !$perm->isSubClass($cc, MASK_DELETE)) exit;

// удаленные объекты
$nc_trashed_objs = $db->get_results("
SELECT `tr`.`Type`, `tr`.`Trash_ID`, `tr`.`Message_ID`, `tr`.`Class_ID`, `tr`.`Sub_Class_ID`, `tr`.`XML_Filename`, `tr`.`XML_Filesize`, `tr`.`IP`, `tr`.`UserAgent`, `tr`.`User_ID`, `u`.`".$nc_core->AUTHORIZE_BY."` as `Login`,  DATE_FORMAT( `tr`.`Created`,'%d.%m.%Y, %H:%i') as `Created`
FROM `Trash_Data` as `tr`
LEFT JOIN `User` as `u` ON `tr`.`User_ID` = `u`.`User_ID`
WHERE `tr`.`Type` = '".$type_id."'
    AND `tr`.`Sub_Class_ID` = '".$cc."'
".( $date_b ? " AND UNIX_TIMESTAMP(`tr`.`Created`) > ".$date_b : "")
                .( $date_e ? " AND UNIX_TIMESTAMP(`tr`.`Created`) < ".$date_e : ""), ARRAY_A);

// Определяем номер компонента
$class_id = $nc_trashed_objs[0]['Class_ID'];
// $type_id  = $nc_trashed_objs[0]['Type'];
$File_Mode = nc_get_file_mode('Class', $class_id);
// Загрузка корзины для доступа к константам класса
$trash = $nc_core->trash;

if ($type_id == $trash::TYPE_MESSAGE) {
    $trash_class = $db->get_row("
        SELECT `FormPrefix`,
               `FormSuffix`,
               `RecordTemplate`,
               `File_Mode`,
               `File_Path`,
               `File_Hash`,
               'ClassTemplate'
            FROM `Class`
                WHERE  `ClassTemplate` = ".$class_id."
                  AND `Type` = 'trash'
                    LIMIT 1", ARRAY_A);

    if (empty($trash_class)) {
        die(NETCAT_TRASH_TEMPLATE_DOESNT_EXIST.". <br />
      <a href='".$nc_core->ADMIN_PATH."class/index.php?".$nc_core->token->get_url()."&amp;Type=trash&amp;base=auto&amp;phase=141&amp;from_trash=1&amp;ClassID=".$class_id.($File_Mode ? "&amp;fs=1" : "")."'>".CONTROL_CLASS_COMPONENT_TEMPLATE_CREATE_FOR_TRASH."</a>");
    }

    if ($trash_class['File_Mode']) {
        $file_class = new nc_tpl_component_view($nc_core->CLASS_TEMPLATE_FOLDER, $nc_core->db);
        $file_class->load($trash_class['ClassTemplate'], $trash_class['File_Path'], $trash_class['File_Hash']);
        $nc_parent_class_folder_path = nc_get_path_to_main_parent_folder($trash_class['File_Path']);
    }
}

$messages = array();
$fields = array();

if (!empty($nc_trashed_objs)) {
    foreach ($nc_trashed_objs as $k => $obj) {
        $id = $obj['Message_ID'];

        // чтобы не открыать по сто раз один и тот же файл,
        // при открытии файла-корзины в $messages попадают все объекты из этого файла
        if (isset($messages[$id])) continue;

        // в базе объект есть - а файла нет.
        $xml_dir = $obj['Type'] ? 'comments' : $obj['Class_ID'];
        $xml_file = $TRASH_FOLDER.$xml_dir.'/'.$obj['XML_Filename'];
        if (!file_exists($xml_file) || !is_readable($xml_file)) {
            printf(NETCAT_TRASH_FILE_DOEST_EXIST, $xml_file);
            $db->query("DELETE FROM `Trash_Data` WHERE `Trash_ID` = '".$obj['Trash_ID']."'");
            unset($nc_trashed_objs[$k]);
            continue;
        }

        $doc = new DOMDocument('1.0', 'utf-8');
        $doc->load($xml_file);
        $xpath = new DOMXPath($doc);

        // загрузка всех полей,
        $fields_element = $xpath->query("/netcatml/fields/field");
        foreach ($fields_element as $field_element) {
            $field_array = array();
            // на будущее, когда fields будет содеражить больше данных
            foreach ($field_element->childNodes as $data) {
                $field_array[$data->nodeName] = $data->nodeValue;
                if ($data->nodeName == 'Field_Name')
                        $field_name = $data->nodeValue;
            }
            $field_array['id'] = $field_element->getAttribute('field_id');
            $field_array['data_id'] = $field_element->getAttribute('type_of_data_id');

            $fields[$field_name] = $field_array;
        }

        if ($type_id == $trash::TYPE_MESSAGE) {
            $messages_element = $xpath->query("/netcatml/messages/message");

            foreach ($messages_element as $message_element) {
                $data_id = $message_element->getAttribute('message_id');
                $messages[$data_id]['Row_ID'] = $data_id;
                $messages[$data_id]['Subdivision_ID'] = $message_element->getAttribute('subdivision_id');
                $messages[$data_id]['Sub_Class_ID'] = $message_element->getAttribute('sub_class_id');
                // поля объекта
                foreach ($message_element->childNodes as $data) {
                    $field = nc_array_value($fields, $data->nodeName);

                    // поля типа "Файл", "Список", "Множественный список", "Дата и время" требуют отдельной обработки
                    switch (nc_array_value($field, 'data_id')) {
                        case NC_FIELDTYPE_FILE:
                            $messages[$data_id][$data->nodeName] = $data->getAttribute('path');
                            $file_info = explode(':', $data->nodeValue);
                            $messages[$data_id][$data->nodeName.'_name'] = nc_array_value($file_info, 0);
                            $messages[$data_id][$data->nodeName.'_type'] = nc_array_value($file_info, 1);
                            $messages[$data_id][$data->nodeName.'_size'] = nc_array_value($file_info, 2);
                            break;
                        case NC_FIELDTYPE_SELECT:
                            $messages[$data_id][$data->nodeName.'_id'] = $data->nodeValue;
                            $messages[$data_id][$data->nodeName] = nc_trash_get_select_item($field['id'], $data->nodeValue);
                            break;
                        case NC_FIELDTYPE_MULTISELECT:
                            $values = array_unique(explode(',', $data->nodeValue));
                            foreach ($values as $key => $v) {
                                if (!$v) {
                                    unset($values[$key]);
                                }
                            }
                            $messages[$data_id][$data->nodeName.'_id'] = $values;
                            $messages[$data_id][$data->nodeName] = nc_trash_get_select_item($field['id'], $values, 1);
                            break;
                        default:
                            $messages[$data_id][$data->nodeName] = $data->nodeValue;
                            break;
                    }
                }
            }
        }
        elseif ($type_id == $trash::TYPE_COMMENT) {
            $comment_elems = $xpath->query("/netcatml/comments/comment");

            foreach ($comment_elems as $comment) {
                $comment_id = $comment->getAttribute('comment_id');
                $messages[$comment_id]['Row_ID'] = $comment_id;
                // $messages[$comment_id]['Subdivision_ID'] = $comment->getAttribute('subdivision_id');
                $messages[$comment_id]['Sub_Class_ID']   = $comment->getAttribute('sub_class_id');

                foreach ($comment->childNodes as $data) {
                    $messages[$comment_id][$data->nodeName] = $data->nodeValue;
                }
                // print_r($comment[0]);
            }
        }

        unset($doc);
    }
}

$result = '';

if ($type_id == $trash::TYPE_MESSAGE) {
    if ($trash_class['File_Mode']) {
        $nc_parent_field_path = $file_class->get_parent_field_path('FormPrefix');
        $nc_field_path = $file_class->get_field_path('FormPrefix');
        ob_start();
        include $nc_field_path;
        $result .= ob_get_clean();
        $nc_parent_field_path = null;
        $nc_field_path = null;
    } else {
        if ($trash_class['FormPrefix']) {
            eval("\$result.= \"" . $trash_class["FormPrefix"] . "\";");
        }
    }
}

$rowCount = 0;
$cc_env["convert2txt"] = '';
$iteration_RecordTemplate = array();

if (!empty($nc_trashed_objs)) {
    foreach ($nc_trashed_objs as $nc_object) {
        $message = $nc_object['Message_ID'];

        $classID = $nc_object['Class_ID'];
        $parent_message = 0;
        $cc_env = array("Class_Name" => null);
        $no_cache_marks = null;

        $row_data = array();
        $message_keys = array_keys((array)nc_array_value($messages, $message)) ?: array();
        foreach ($message_keys as $v) {
            // ${"f_" . $v} = $nc_core->trash->encode_to_system($messages[$message][$v]);
            $row_data["f_" . $v] = $trash->encode_to_system($messages[$message][$v]);;
        }

        $f_AdminButtons = "
<div class='nc_idtab' style='position:relative; width:300px'>
  <div class='nc_idtab_handler' style='display: none;'>
  <div style='margin:7px 0 0 5px; display:block' title='" . NETCAT_TRASH_IDENTIFICATOR . ": " . $message . "' class='nc_idtab_messageid'>" . $message . "</div></div>
  <div class='nc_idtab_id' style='padding-top: 8px; padding-left: 6px;'>
    <div title='" . NETCAT_TRASH_USER_IDENTIFICATOR . ": " . $nc_object['Login'] . "' class='nc_idtab_adduser' style='background-image:none'>" . $nc_object['Login'] . "</div>
  </div>
  <div style='line-height: 25px; padding-top: 3px; position: absolute; right: 70px; display: inline;'><strong>" . $nc_object['Created'] . "</strong></div>
  <div class='nc_idtab_buttons' style='padding-top: 3px; position: absolute; right: 10px;'>
    <a class='nc-button-restore' href='" . $ADMIN_PATH . "trash/index.php?phase=2&trash_ids=" . $nc_object['Trash_ID'] . "&nc_token=" . $nc_core->token->get() . "'>" . nc_admin_img('type_bool\' style=\'margin:3px 0 0 5px', NETCAT_TRASH_RECOVERY) . "</a>&nbsp;&nbsp;
    <a class='nc-button-remove' href='" . $ADMIN_PATH . "trash/index.php?phase=3&trash_ids=" . $nc_object['Trash_ID'] . "&nc_token=" . $nc_core->token->get() . "'>" . nc_admin_img('delete\' style=\'margin-top:3px', NETCAT_TRASH_DELETE_FROM_TRASH) . "</a>
  </div>
  <div class='ncf_row nc_clear'></div>
</div>";

        if ($trash_class['File_Mode'] || $type_id == $trash::TYPE_COMMENT) {
            $fetch_row[$rowCount]                 = array();
            $fetch_row[$rowCount]['RowID']        = $message;
            $fetch_row[$rowCount]['AdminButtons'] = $f_AdminButtons;
            $iteration_RecordTemplate[$rowCount]  = array($row_data);
            ++$rowCount;
        } else {
            extract($row_data);
            eval("\$result.= \"" . $trash_class["RecordTemplate"] . "\";");
        }
    }
    // echo '<pre>' . htmlspecialchars( print_r($fetch_row, 1) );
    // exit;
    if ($type_id == $trash::TYPE_MESSAGE) {
        if ($trash_class['File_Mode']) {
            $nc_parent_field_path = $file_class->get_parent_field_path('RecordTemplate');
            $nc_field_path        = $file_class->get_field_path('RecordTemplate');
            ob_start();
            include $nc_field_path;
            $result .= ob_get_clean();
            $nc_parent_field_path = null;
            $nc_field_path = null;
        }
    }
    elseif ($type_id == $trash::TYPE_COMMENT) {
        foreach ($fetch_row as $i=>$row) {
            extract($row, EXTR_PREFIX_ALL, 'f');
            foreach ($iteration_RecordTemplate[$i] as $values) {
                extract($values);
            }
            $result .= $f_AdminButtons . $f_Comment . '<br /><br />';
        }
    }
}
echo $result;

/**
 * Функция возвращает элемент классификатора
 *
 * @param int номер поля
 * @param int,array элмент(ы)
 * @param bool мультиселект?
 * @return mixed
 */
function nc_trash_get_select_item($field_id, $value, $multi = false) {
    $nc_core = nc_Core::get_object();
    $db = $nc_core->db;

    static $tables = array();

    // кэширование
    if (!isset($tables[$field_id])) {
        $tables[$field_id] = array();
        $format = $db->get_var("SELECT `Format` FROM `Field` WHERE `Field_ID` = '".intval($field_id)."' ");
        if (!$format) return false;
        $format = $db->escape(strtok($format, ':'));
        $res = $db->get_results("SELECT * FROM Classificator_".$format, ARRAY_A);
        if ($res)
                foreach ($res as $v) {
                $tables[$field_id][$v[$format.'_ID']] = $v[$format.'_Name'];
            }
    }

    if (!$multi) {
        $res = isset($tables[$field_id][$value]) ? $tables[$field_id][$value] : null;
    } else {
        $res = array();
        if ($value)
                foreach ($value as $v) {
                $res[] = $tables[$field_id][$v];
            }
    }

    return $res;
}
