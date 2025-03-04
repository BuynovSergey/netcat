<?php

/* $Id: nc_trash.class.php 7931 2012-08-09 12:56:31Z ewind $ */
if (!class_exists("nc_System")) die("Unable to load file.");

class nc_Trash extends nc_Essence {

    const TYPE_MESSAGE = 0;
    const TYPE_COMMENT = 1;

    protected $core, $db;
    protected $comments_table;
    // номера записей в таблице Trash_Data
    protected $deleted_ids;
    // размер файла, при достижении которого в него не будет производиться запись
    protected $max_file_size;
    // флаг переполности корзины
    protected $trash_full;
    // флаг статуса директории
    protected $folder_fail;

    /**
     * Constructor function
     */
    public function __construct() {
        // load parent constructor
        parent::__construct();

        // system superior object
        $this->core = nc_Core::get_object();
        $this->db = $this->core->db;

        $this->deleted_ids = array();
        $this->trash_full = 0;
        $this->folder_fail = 0;
        $this->max_file_size = 10 * 1024 * 1024;

        if(!class_exists('DOMDocument')) {
            $this->db->query("UPDATE `Settings` SET `Value` = '0' WHERE `Key` = 'TrashUse'");
        }
    }

    /**
     * Функция для помещения в корзину объектов компонентов
     * @param mixed `message` - идентификаторы объектов подлежащих удалению в виде массива или id
     * @param int `class_id` номер компонента, в котором производится удаление
     */
    public function add($messages, $class_id) {
        global $AUTH_USER_ID;
        $db = $this->db;

        // проверка лимита корзины
        $r = $db->get_col("SELECT `XML_Filesize` FROM `Trash_Data` GROUP BY `XML_Filename`");
        if ($r && array_sum($r) > $this->core->get_settings('TrashLimit') * 1024 * 1024) {
            $this->trash_full = 1;
            throw new nc_Exception_Trash_Full('Trashbin full');
        }

        if (!is_dir($this->core->TRASH_FOLDER) || !is_writable($this->core->TRASH_FOLDER)) {
            throw new nc_Exception_Trash_Folder_Fail('Folder');
        }

        if(!class_exists('DOMDocument')) {
            nc_print_status(NETCAT_ADMIN_DOMDocument_NOT_FOUND, 'error');
            $db->query("UPDATE `Settings` SET `Value` = '0' WHERE `Key` = 'TrashUse'");
            exit;
        }

        $doc = new DOMDocument();
        $doc->preserveWhiteSpace = false;
        $doc->formatOutput = true;
        $doc->encoding = 'utf-8';


        // validate params
        $class_id = intval($class_id);
        if (!$class_id )throw new Exception("Inccorrect param \$class_id");
        if (!is_array($messages)) $messages = array($messages);
        $messages = array_unique(array_map("intval", $messages));

        // Проверяем, чтобы ключи не повторялись
        $already_in_trash = $db->get_col("SELECT `Message_ID`
                                      FROM `Trash_Data`
                                      WHERE `Class_ID` = ".$class_id."
                                      AND `Message_ID` IN (".join(',', $messages).")");
        if (!empty($already_in_trash)) {
            throw new nc_Exception_Trash_Already_Exists($class_id, $already_in_trash);
        }

        // Выбираем данные о пользовательских полях компонента
        $component = new nc_Component($class_id);
        $fields = $component->get_fields();

        if ($fields)
                foreach ($fields as &$v)
                foreach ($v as &$d)
                    $d = $this->encode_to_file($d);

        // выбираем все данные удаляемых объектов из таблицы
        $data = $db->get_results("
    SELECT `m`.*, `sub`.`Subdivision_Name`, `sub`.`Parent_Sub_ID`, `sub`.`Catalogue_ID`,
    `sub`.`EnglishName`, `sub`.`Checked` AS `subChecked`,
    `cc`.`Sub_Class_Name`, `cc`.`EnglishName` AS `ccEnglishName`, `cc`.`Checked` AS `ccChecked`, `m`.`Checked`
    FROM `Message".$class_id."` as `m`
    LEFT JOIN `Subdivision` as `sub` ON `sub`.`Subdivision_ID` = `m`.`Subdivision_ID`
    LEFT JOIN `Sub_Class` as `cc` ON `cc`.`Sub_Class_ID` = `m`.`Sub_Class_ID`
    WHERE `Message_ID` IN ( ".join(',', $messages)." ) ", ARRAY_A);


        if ($data)
                foreach ($data as &$v)
                foreach ($v as &$d)
                    $d = $this->encode_to_file($d);

        // массив значений объекта, номеров объекта, разделов и компонентов
        $values = $message_ids = $subdivisions = $sub_classes = array();

        if (!empty($data))
                foreach ($data as $value) {

                $values[$value['Message_ID']] = $value;
                $subdivisions[$value['Subdivision_ID']] = array('Subdivision_Name' => $value['Subdivision_Name'],
                        'Parent_Sub_ID' => $value['Parent_Sub_ID'],
                        'Catalogue_ID' => $value['Catalogue_ID'],
                        'EnglishName' => $value['EnglishName'],
                        'Checked' => $value['subChecked']);
                $sub_classes[$value['Sub_Class_ID']] = array('Sub_Class_Name' => $value['Sub_Class_Name'],
                        'Subdivision_ID' => $value['Subdivision_ID'],
                        'EnglishName' => $value['ccEnglishName'],
                        'Checked' => $value['ccChecked']);
                $sub_class_ids[] = $value['Sub_Class_ID'];
                $message_ids[] = $value['Message_ID'];
            }

        // комментарии
        if (nc_module_check_by_keyword('comments')) {
            $comments_data = $db->get_results("SELECT * FROM
                                 `Comments_Text`
                                 WHERE `Sub_Class_ID` IN (".join(',', $sub_class_ids).")
                                 AND `Message_ID` IN (".join(',', $message_ids).")", ARRAY_A);
            if ($comments_data)
                    foreach ($comments_data as &$v)
                    foreach ($v as &$d)
                        $d = $this->encode_to_file($d);
        }

        // создаем новый файл или пишем в существующий
        // берем последний файл из корзины данного компонента, смотрим размер и соответствие полей
        $new_file = 1;
        $ex_trash = $db->get_row("SELECT `Trash_ID`, `XML_Filename`, `XML_Filesize`
                              FROM `Trash_Data`
                              WHERE `Type` = " . self::TYPE_MESSAGE . "
                                    AND `Class_ID` = '".$class_id."'
                              ORDER BY UNIX_TIMESTAMP(`Created`) DESC
                              LIMIT 1", ARRAY_A);
        if ($db->num_rows && $ex_trash['XML_Filesize'] < $this->max_file_size) {
            $doc->load($this->core->TRASH_FOLDER.$class_id.'/'.$ex_trash['XML_Filename']);
            $xpath = new DOMXPath($doc);
            $ex_fields = $xpath->query("/netcatml/fields/field");
            // проверка соответствия полей
            $num_check_fields = 0;
            foreach ($ex_fields as $v) {
                foreach ($fields as $val) {
                    if ($val['id'] == $v->attributes->getNamedItem('field_id')->value &&
                            $val['type'] == $v->attributes->getNamedItem('type_of_data_id')->value) {
                        $num_check_fields++;
                    }
                }
            }
            // новый файл нужен в случае несоответствия полей
            $new_file = ($num_check_fields != count($fields));
        }

        if ($new_file) {
            $root_element = $doc->createElement('netcatml');
            $doc->appendChild($root_element);
            $subs_element = $doc->createElement('subdivisions');
            $root_element->appendChild($subs_element);
            $ccs_element = $doc->createElement('sub_classes');
            $root_element->appendChild($ccs_element);
            $fields_element = $doc->createElement('fields');
            $root_element->appendChild($fields_element);
            $messages_element = $doc->createElement('messages');
            $root_element->appendChild($messages_element);
            $comments_element = $doc->createElement('comments');
            $root_element->appendChild($comments_element);
        } else {
            $subs_element = $xpath->query("/netcatml/subdivisions")->item(0);
            $ccs_element = $xpath->query("/netcatml/sub_classes")->item(0);
            $fields_element = $xpath->query("/netcatml/fields")->item(0);
            $fields_element = $xpath->query("/netcatml/fields")->item(0);
            $messages_element = $xpath->query("/netcatml/messages")->item(0);
            $comments_element = $xpath->query("/netcatml/comments")->item(0);
        }

        foreach ($subdivisions as $sub_id => $v) {
            if (!$new_file && ($t = $xpath->query("/netcatml/subdivisions/subdivision[@subdivision_id='".$sub_id."']")) && $t->length) {
                continue;
            }
            $element = $doc->createElement('subdivision');
            $element->setAttribute('subdivision_id', $sub_id);
            $element->setAttribute('catalogue_id', $v['Catalogue_ID']);
            $element->setAttribute('parent_sub_id', $v['Parent_Sub_ID']);
            $element->appendChild($doc->createElement('Subdivision_Name', $v['Subdivision_Name']));
            $element->appendChild($doc->createElement('EnglishName', $v['EnglishName']));
            $element->appendChild($doc->createElement('Checked', $v['Checked']));
            $subs_element->appendChild($element);
        }

        foreach ($sub_classes as $cc_id => $v) {
            if (!$new_file && ($t = $xpath->query("/netcatml/sub_classes/sub_class[@sub_class_id='".$cc_id."']")) && $t->length) {
                continue;
            }
            $element = $doc->createElement('sub_class');
            $element->setAttribute('sub_class_id', $cc_id);
            $element->setAttribute('subdivision_id', $v['Subdivision_ID']);
            $element->setAttribute('class_id', $class_id);
            $element->appendChild($doc->createElement('Sub_Class_Name', $v['Sub_Class_Name']));
            $element->appendChild($doc->createElement('EnglishName', $v['EnglishName']));
            $element->appendChild($doc->createElement('Checked', $v['Checked']));
            $ccs_element->appendChild($element);
        }

        if ($new_file)
                foreach ($fields as $v) {
                $element = $doc->createElement('field');
                $element->setAttribute('field_id', $v['id']);
                $element->setAttribute('type_of_data_id', $v['type']);
                $element->appendChild($doc->createElement('Field_Name', $v['name']));
                $element->appendChild($doc->createElement('Description', $v['description']));
                $fields_element->appendChild($element);
            }

        if (!empty($comments_data)) {
            $v_ar = array('User_ID', 'Date', 'Updated', 'Comment');
            foreach ($comments_data as $v) {
                $element = $doc->createElement('comment');
                $element->setAttribute('comment_id', $v['id']);
                $element->setAttribute('parent_comment_id', $v['Parent_Comment_ID']);
                $element->setAttribute('parent_comment_id', $v['Parent_Comment_ID']);
                $element->setAttribute('sub_class_id', $v['Sub_Class_ID']);
                $element->setAttribute('message_id', $v['Message_ID']);
                foreach ($v_ar as $pr) {
                    $element->appendChild($doc->createElement($pr, $v[$pr]));
                }
                $comments_element->appendChild($element);
            }
        }

        $v_ar = array('User_ID', 'Priority', 'Checked',
                'IP', 'UserAgent', 'Parent_Message_ID', 'Created', 'LastUpdated', 'LastUser_ID',
                'LastIP', 'LastUserAgent', 'Keyword');

        foreach ($values as $message_id => $v) {
            $element = $doc->createElement('message');
            $element->setAttribute('message_id', $message_id);
            $element->setAttribute('class_id', $class_id);
            $element->setAttribute('subdivision_id', $v['Subdivision_ID']);
            $element->setAttribute('sub_class_id', $v['Sub_Class_ID']);

            foreach ($v_ar as $pr) {
                $ch_element = $doc->createElement($pr, $v[$pr]);
                $element->appendChild($ch_element);
            }

            foreach ($fields as $field) {
                if ($field['type'] == NC_FIELDTYPE_STRING || $field['type'] == NC_FIELDTYPE_TEXT) {
                    $ch_element = $doc->createElement($field['name'], "");
                    $cdata = $doc->createCDATASection($v[$field['name']]);
                    $ch_element->appendChild($cdata);
                } else {
                    $ch_element = $doc->createElement($field['name'], $v[$field['name']]);
                }
                //$ch_element = $doc->createElement($field['name'], str_replace('&nbsp;', ' ', $v[$field['name']]) );
                if ($field['type'] == NC_FIELDTYPE_FILE) {
                    $ch_element->setAttribute('path', nc_file_path($class_id, $message_id, $field['id']));
                }

                $element->appendChild($ch_element);
            }

            $messages_element->appendChild($element);
        }

        $this->core->files->create_dir($this->core->TRASH_FOLDER.$class_id);
        $xml_filename = $new_file ? md5(rand(0, 10000).time().$class_id.$message_id) : $ex_trash['XML_Filename'];

        $xml_filesize = $doc->save($this->core->TRASH_FOLDER.$class_id.'/'.$xml_filename);
        if (!$xml_filesize) {
            throw new nc_Exception_Trash_Folder_Fail('');
            $this->folder_fail = 1;
        }
        @chmod($this->core->TRASH_FOLDER.$class_id.'/'.$xml_filename, $this->core->FILECHMOD);

        $ip = $this->db->escape($_SERVER['REMOTE_ADDR']);
        $ua = $this->db->escape($_SERVER['HTTP_USER_AGENT']);
        foreach ($message_ids as $message_id) {
            $insert[] = "(".$message_id.", ".$class_id.", ".$values[$message_id]['Subdivision_ID'].",
                     ".$values[$message_id]['Sub_Class_ID'].",
                     '".date("Y-m-d H:i:s")."', '".$xml_filename."' , ".$xml_filesize.",
                     '".$ip."', '".$ua."' , '".$AUTH_USER_ID."')";
        }
        $db->query("INSERT INTO `Trash_Data` (`Message_ID` ,`Class_ID`, `Subdivision_ID`,`Sub_Class_ID` ,`Created` ,`XML_Filename` ,`XML_Filesize` ,`IP` ,`UserAgent` ,`User_ID`)
        VALUES ".join(',', $insert));

        if ($this->db->is_error) {
            throw new nc_Exception_DB_Error($this->db->last_query, $this->db->last_error);
        }

        for ($i = 0; $i < count($insert); $i++)
            $this->deleted_ids[] = $db->insert_id + $i;


        if (!$new_file) {
            $db->query("UPDATE `Trash_Data` SET `XML_Filesize` = '".$xml_filesize."' WHERE `XML_Filename` = '".$ex_trash['XML_Filename']."' ");
        }


        /*
          // Отмечаем поля типа "Файл", чтобы потом поменять их статус в таблице filetable
          if( !empty($file_fields) ){
          $deleted_files = $db->query("UPDATE `filetable` SET `Deleted` = 1 WHERE `Field_ID` IN (".join(',',$file_fields).")  AND `Message_ID` IN (".join(',',$messages_to_trash).")");
          }
         *
         */

        return count($this->deleted_ids);
    }


    public function add_comment($comment_ids) {
        global $AUTH_USER_ID;
        $db = $this->db;

        // validate params
        if ( ! is_array($comment_ids)) {
            $comment_ids = array($comment_ids);
        }
        $comment_ids = array_unique(array_map("intval", $comment_ids));



        // проверка лимита корзины
        $r = $db->get_col("SELECT `XML_Filesize` FROM `Trash_Data` GROUP BY `XML_Filename`");
        if ($r && array_sum($r) > $this->core->get_settings('TrashLimit') * 1024 * 1024) {
            $this->trash_full = 1;
            throw new nc_Exception_Trash_Full('Trashbin full');
        }

        // проверка прав на запись файлов
        if (!is_dir($this->core->TRASH_FOLDER) || !is_writable($this->core->TRASH_FOLDER)) {
            throw new nc_Exception_Trash_Folder_Fail('Folder');
        }

        // Проверка наличия необходимых php-расширений
        if(!class_exists('DOMDocument')) {
            nc_print_status(NETCAT_ADMIN_DOMDocument_NOT_FOUND, 'error');
            $db->query("UPDATE `Settings` SET `Value` = '0' WHERE `Key` = 'TrashUse'");
            exit;
        }

        // Проверяем, чтобы ключи не повторялись
        $already_in_trash = $db->get_col("SELECT `Message_ID`
            FROM `Trash_Data`
            WHERE `Type` = " . self::TYPE_COMMENT . "
            AND `Message_ID` IN (" . join(',', $comment_ids) . ")");

        if (!empty($already_in_trash)) {
            throw new nc_Exception_Trash_Already_Exists(0, $already_in_trash);
        }


        $doc = new DOMDocument();

        $doc->preserveWhiteSpace = false;
        $doc->formatOutput       = true;
        $doc->encoding           = 'utf-8';

        // выбираем все данные удаляемых объектов из таблицы
        $data = $db->get_results("SELECT m.*,
                `sub`.`EnglishName`, `sub`.`Checked` AS `subChecked`, `sub`.`Subdivision_Name`, `sub`.`Parent_Sub_ID`, `sub`.`Catalogue_ID`,
                `cc`.`Subdivision_ID`, `cc`.`Sub_Class_Name`, `cc`.`EnglishName` AS `ccEnglishName`, `cc`.`Checked` AS `ccChecked`
            FROM `Comments_Text` m
            LEFT JOIN `Sub_Class` as `cc` ON `cc`.`Sub_Class_ID` = `m`.`Sub_Class_ID`
            LEFT JOIN `Subdivision` as `sub` ON `sub`.`Subdivision_ID` = `cc`.`Subdivision_ID`
            WHERE m.`id` IN (".join(',', $comment_ids).");
        ", ARRAY_A);

        $data = $this->encode_to_file_deep($data);

        // массив значений объекта, номеров объекта, разделов и компонентов
        $values = $message_ids = $subdivisions = $sub_classes = array();

        if ( ! empty($data)) {
            foreach ($data as $value) {
                $values[$value['id']] = $value;
                $subdivisions[$value['Subdivision_ID']] = array('Subdivision_Name' => $value['Subdivision_Name'],
                        'Parent_Sub_ID' => $value['Parent_Sub_ID'],
                        'Catalogue_ID' => $value['Catalogue_ID'],
                        'EnglishName' => $value['EnglishName'],
                        'Checked' => $value['subChecked']);
                $sub_classes[$value['Sub_Class_ID']] = array('Sub_Class_Name' => $value['Sub_Class_Name'],
                        'Subdivision_ID' => $value['Subdivision_ID'],
                        'EnglishName' => $value['ccEnglishName'],
                        'Checked' => $value['ccChecked']);
                $sub_class_ids[] = $value['Sub_Class_ID'];
                $message_ids[] = $value['id'];
            }
        }

        // создаем новый файл или пишем в существующий
        // берем последний файл из корзины данного компонента, смотрим размер и соответствие полей
        $new_file = true;
        $ex_trash = $db->get_row("SELECT `Trash_ID`, `XML_Filename`, `XML_Filesize`
            FROM `Trash_Data`
            WHERE `Type` = " . self::TYPE_COMMENT . "
                AND `Sub_Class_ID` = '".current($sub_class_ids)."'
            ORDER BY UNIX_TIMESTAMP(`Created`) DESC
            LIMIT 1", ARRAY_A);

        if ($db->num_rows && $ex_trash['XML_Filesize'] < $this->max_file_size) {
            $new_file = false;
            $doc->load($this->core->TRASH_FOLDER.'comments/'.$ex_trash['XML_Filename']);
            $xpath = new DOMXPath($doc);
        }

        if ($new_file) {
            $root_element     = $doc->createElement('netcatml');
            $subs_element     = $doc->createElement('subdivisions');
            $ccs_element      = $doc->createElement('sub_classes');
            // $fields_element   = $doc->createElement('fields');
            // $messages_element = $doc->createElement('messages');
            $comments_element = $doc->createElement('comments');

            $doc->appendChild($root_element);
            $root_element->appendChild($subs_element);
            $root_element->appendChild($ccs_element);
            // $root_element->appendChild($fields_element);
            // $root_element->appendChild($messages_element);
            $root_element->appendChild($comments_element);
        } else {
            $subs_element     = $xpath->query("/netcatml/subdivisions")->item(0);
            $ccs_element      = $xpath->query("/netcatml/sub_classes")->item(0);
            // $fields_element   = $xpath->query("/netcatml/fields")->item(0);
            // $fields_element   = $xpath->query("/netcatml/fields")->item(0);
            // $messages_element = $xpath->query("/netcatml/messages")->item(0);
            $comments_element = $xpath->query("/netcatml/comments")->item(0);
        }

        foreach ($subdivisions as $sub_id => $v) {
            if (!$new_file && ($t = $xpath->query("/netcatml/subdivisions/subdivision[@subdivision_id='".$sub_id."']")) && $t->length) {
                continue;
            }

            $element = $doc->createElement('subdivision');
            $element->setAttribute('subdivision_id', $sub_id);
            $element->setAttribute('catalogue_id', $v['Catalogue_ID']);
            $element->setAttribute('parent_sub_id', $v['Parent_Sub_ID']);
            $element->appendChild($doc->createElement('Subdivision_Name', $v['Subdivision_Name']));
            $element->appendChild($doc->createElement('EnglishName', $v['EnglishName']));
            $element->appendChild($doc->createElement('Checked', $v['Checked']));
            $subs_element->appendChild($element);
        }

        foreach ($sub_classes as $cc_id => $v) {
            if (!$new_file && ($t = $xpath->query("/netcatml/sub_classes/sub_class[@sub_class_id='".$cc_id."']")) && $t->length) {
                continue;
            }
            $element = $doc->createElement('sub_class');
            $element->setAttribute('sub_class_id', $cc_id);
            $element->setAttribute('subdivision_id', $v['Subdivision_ID']);
            $element->setAttribute('class_id', $class_id);
            $element->appendChild($doc->createElement('Sub_Class_Name', $v['Sub_Class_Name']));
            $element->appendChild($doc->createElement('EnglishName', $v['EnglishName']));
            $element->appendChild($doc->createElement('Checked', $v['Checked']));
            $ccs_element->appendChild($element);
        }

        // if ($new_file)
        //         foreach ($fields as $v) {
        //         $element = $doc->createElement('field');
        //         $element->setAttribute('field_id', $v['id']);
        //         $element->setAttribute('type_of_data_id', $v['type']);
        //         $element->appendChild($doc->createElement('Field_Name', $v['name']));
        //         $element->appendChild($doc->createElement('Description', $v['description']));
        //         $fields_element->appendChild($element);
        //     }

        if (!empty($data)) {
            $v_ar = array('User_ID', 'Date', 'Updated', 'Comment');
            foreach ($data as $v) {
                $element = $doc->createElement('comment');
                $element->setAttribute('comment_id', $v['id']);
                $element->setAttribute('parent_comment_id', $v['Parent_Comment_ID']);
                $element->setAttribute('parent_comment_id', $v['Parent_Comment_ID']);
                $element->setAttribute('sub_class_id', $v['Sub_Class_ID']);
                $element->setAttribute('message_id', $v['Message_ID']);
                foreach ($v_ar as $pr) {
                    $element->appendChild($doc->createElement($pr, $v[$pr]));
                }
                $comments_element->appendChild($element);
            }
        }

        // $v_ar = array('User_ID', 'Priority', 'Checked',
        //         'IP', 'UserAgent', 'Parent_Message_ID', 'Created', 'LastUpdated', 'LastUser_ID',
        //         'LastIP', 'LastUserAgent', 'Keyword');

        // foreach ($values as $message_id => $v) {
        //     $element = $doc->createElement('message');
        //     $element->setAttribute('message_id', $message_id);
        //     $element->setAttribute('class_id', $class_id);
        //     $element->setAttribute('subdivision_id', $v['Subdivision_ID']);
        //     $element->setAttribute('sub_class_id', $v['Sub_Class_ID']);

        //     foreach ($v_ar as $pr) {
        //         $ch_element = $doc->createElement($pr, $v[$pr]);
        //         $element->appendChild($ch_element);
        //     }

        //     foreach ($fields as $field) {
        //         if ($field['type'] == NC_FIELDTYPE_STRING || $field['type'] == NC_FIELDTYPE_TEXT) {
        //             $ch_element = $doc->createElement($field['name'], "");
        //             $cdata = $doc->createCDATASection($v[$field['name']]);
        //             $ch_element->appendChild($cdata);
        //         } else {
        //             $ch_element = $doc->createElement($field['name'], $v[$field['name']]);
        //         }
        //         //$ch_element = $doc->createElement($field['name'], str_replace('&nbsp;', ' ', $v[$field['name']]) );
        //         if ($field['type'] == NC_FIELDTYPE_FILE) {
        //             $ch_element->setAttribute('path', nc_file_path($class_id, $message_id, $field['id']));
        //         }

        //         $element->appendChild($ch_element);
        //     }

        //     $messages_element->appendChild($element);
        // }


        $trash_dir    = $this->core->TRASH_FOLDER . 'comments' . DIRECTORY_SEPARATOR;
        $xml_filename = $new_file ? md5('comments' . implode($comment_ids) . microtime() . rand(0,9999)) : $ex_trash['XML_Filename'];
        $trash_file   = $trash_dir . $xml_filename;

        $this->core->files->create_dir($trash_dir);

        $xml_filesize = $doc->save($trash_file);

        if ( ! $xml_filesize) {
            throw new nc_Exception_Trash_Folder_Fail('');
            $this->folder_fail = 1;
        }

        @chmod($trash_file, $this->core->FILECHMOD);

        $REMOTE_ADDR = $this->db->escape($_SERVER['REMOTE_ADDR']);
        $USER_AGENT  = $this->db->escape($_SERVER['HTTP_USER_AGENT']);

        foreach ($values as $comment_id => $row) {
            $insert[] = array(
                'Type'           => self::TYPE_COMMENT,
                'Message_ID'     => $comment_id,
                'Subdivision_ID' => $row['Subdivision_ID'],
                'Sub_Class_ID'   => $row['Sub_Class_ID'],
                'Class_ID'       => $row['Class_ID'],
                'Created'        => date("Y-m-d H:i:s"),
                'XML_Filename'   => $xml_filename,
                'XML_Filesize'   => $xml_filesize,
                'IP'             => $REMOTE_ADDR,
                'UserAgent'      => $USER_AGENT,
                'User_ID'        => $AUTH_USER_ID,
            );

            // "(".$message_id.", ".$class_id.", ".$values[$message_id]['Subdivision_ID'].",
            //          ".$values[$message_id]['Sub_Class_ID'].",
            //          '".date("Y-m-d H:i:s")."', '".$xml_filename."' , ".$xml_filesize.",
            //          '".$REMOTE_ADDR."', '".$USER_AGENT."' , '".$AUTH_USER_ID."')";
        }
        // return $insert;
        $insert_values = array();
        foreach ($insert as $row) {
            $insert_values[] = '("' . implode('", "', $row) . '")';
        }

        $db->query("INSERT INTO `Trash_Data` (`".implode('`, `', array_keys($insert[0]))."`) VALUES ".join(',', $insert_values));

        if ($this->db->is_error) {
            throw new nc_Exception_DB_Error($this->db->last_query, $this->db->last_error);
        }

        for ($i = 0; $i < count($insert); $i++) {
            $this->deleted_ids[] = $db->insert_id + $i;
        }


        if ( ! $new_file) {
            $db->query("UPDATE `Trash_Data` SET `XML_Filesize` = '".$xml_filesize."' WHERE `XML_Filename` = '".$ex_trash['XML_Filename']."' ");
        }


        /*
          // Отмечаем поля типа "Файл", чтобы потом поменять их статус в таблице filetable
          if( !empty($file_fields) ){
          $deleted_files = $db->query("UPDATE `filetable` SET `Deleted` = 1 WHERE `Field_ID` IN (".join(',',$file_fields).")  AND `Message_ID` IN (".join(',',$messages_to_trash).")");
          }
         *
         */

        return count($this->deleted_ids);
    }

    // Восстановление из корзины по номеру в корзине
    public function recovery($trash_ids) {
        // Приводим все к массиву
        if (!is_array($trash_ids)) $trash_ids = array($trash_ids);
        $trash_ids = array_map('intval', $trash_ids);

        $db = $this->db;
        $TRASH_FOLDER = $this->core->TRASH_FOLDER;
        $comment_insert_value = array();
        $recovered = 0;

        $trashed_objects = $db->get_results("
    SELECT `Trash_ID`, `Type`, `Message_ID`, `Class_ID`, `Subdivision_ID`, `Sub_Class_ID`,
    `System_Table_ID`, `Created`, `XML_Filename`, `XML_Filesize`, `IP`, `UserAgent`, `User_ID`
    FROM `Trash_Data`
    WHERE `Trash_ID` IN (".join(', ', $trash_ids).")", ARRAY_A);

        if (!$trashed_objects) return 0;

        if ($trashed_objects[0]['Type'] == self::TYPE_COMMENT) {
            return $this->recovery_comment($trash_ids);
        }

        // узнаем номера всех компонентов и файлов, а так же различные связки
        $class_ids = array();
        $xml_filepaths = array();
        $class_file = array(); // связь между компонентом  и файлом
        $message_file = array(); // все объект, находящиеся в данном файле
        foreach ($trashed_objects as $v) {
            $class_ids[] = $v['Class_ID'];
            $filepath = $this->xml_file_name($v);
            $xml_filepaths[] = $filepath;
            $class_file[$filepath] = $v['Class_ID'];
            $message_file[$filepath][] = $v['Message_ID'];
        }
        $class_ids = array_unique($class_ids);
        $xml_filepaths = array_unique($xml_filepaths);

        // получаем все поля каждого компонента
        $fields = array();
        foreach ($class_ids as $class_id) {
            $component = new nc_Component($class_id);
            $fields[$class_id] = $component->get_fields(0, 0);
            unset($component);
        }

        $sys_fields = array('Message_ID', 'User_ID', 'Subdivision_ID',
                'Sub_Class_ID', 'Priority', 'Checked', 'IP', 'UserAgent', 'Parent_Message_ID',
                'Created', 'LastUpdated', 'LastUser_ID', 'LastIP',
                'LastUserAgent', 'Keyword');

        // открываем каждый файл, ищем нужные объекты
        foreach ($xml_filepaths as $xml_filepath) {
            $doc = new DOMDocument('1.0', 'utf-8');
            $doc->load($this->core->TRASH_FOLDER.$xml_filepath);
            $xpath = new DOMXPath($doc);

            $class_id = $class_file[$xml_filepath];
            //смотрим поля на момент удаления
            $del_fields = array();
            $fields_node = $doc->getElementsByTagName('fields')->item(0);
            foreach ($fields_node->childNodes as $field_node) {
                if ($field_node->childNodes)
                        foreach ($field_node->childNodes as $v) {
                        if ($v->nodeName == 'Field_Name')
                                $del_fields[] = $v->nodeValue;
                    }
            }
            // поля, которые будем восстанавливать
            $r_fields = array_intersect($del_fields, $fields[$class_id]);
            $r_fields = array_merge($r_fields, $sys_fields);

            // ищем каждый восстанавливаемый объект в файле
            foreach ($message_file[$xml_filepath] as $id) {
                $node = $xpath->query("/netcatml/messages/message[@message_id='".$id."']");
                $node = $node->item(0);
                if (!is_object($node)) {
                    continue;
                }
                $cc_id = intval($node->getAttribute('sub_class_id'));
                $set_value = array();
                $set_value[] = " `Message_ID` = '".intval($node->getAttribute('message_id'))."' ";
                $set_value[] = " `Subdivision_ID` = '".intval($node->getAttribute('subdivision_id'))."' ";
                $set_value[] = " `Sub_Class_ID` = '".intval($cc_id)."' ";
                foreach ($node->childNodes as $v) {
                    if (!in_array($v->nodeName, $r_fields)) continue;
                    $set_value[] = " `".$db->escape($v->nodeName)."` = '".$db->escape($v->nodeValue)."' ";
                }
                $db->query("INSERT INTO `Message".$class_id."` SET ".$this->encode_to_system(join(',', $set_value)));
                $recovered++;

                $comments = $xpath->query("/netcatml/comments/comment[@message_id='".$id."' and @sub_class_id='".$cc_id."']");
                if ($comments)
                        foreach ($comments as $comment) {
                        $comment_id = intval($comment->getAttribute('comment_id'));
                        $parent_comment_id = intval($comment->getAttribute('parent_comment_id'));
                        foreach ($comment->childNodes as $v) {
                            if ($v->nodeName == 'Comment')
                                    $text = $db->escape($v->nodeValue);
                            if ($v->nodeName == 'User_ID')
                                    $comment_user_id = intval($v->nodeValue);
                            if ($v->nodeName == 'Date')
                                    $comment_date = $db->escape($v->nodeValue);
                            if ($v->nodeName == 'Updated')
                                    $comment_updated = $db->escape($v->nodeValue);
                        }
                        $comment_insert_value[] = "('".$comment_id."', '".$parent_comment_id."', '".$comment_user_id."',
                              '".$text."', '".$comment_date."', '".$comment_updated."',
                              '".$cc_id."', '".$id."' )";
                        $comment->parentNode->removeChild($comment);
                    }


                $node->parentNode->removeChild($node);

                $doc->save($this->core->TRASH_FOLDER.$xml_filepath);
            }
            unset($xpath, $doc);
        }

        if ($this->core->modules->get_by_keyword('comments')) {
            if (!empty($comment_insert_value)) {
                $db->query("INSERT INTO `Comments_Text` (`id`,`Parent_Comment_ID`,`User_ID`,`Comment`,
                                                 `Date`,`Updated`,`Sub_Class_ID`, `Message_ID`)
                    VALUES ".$this->encode_to_system(join(',', $comment_insert_value)));

                require_once nc_module_folder('comments') . 'nc_comments_admin.class.php';
                $nc_comments_admin = new nc_comments_admin();
                $nc_comments_admin->optimizeSave();
            }
        }

        $db->query("DELETE FROM `Trash_Data` WHERE `Trash_ID` IN (".join(', ', $trash_ids).")");

        $this->remove_xml_files($xml_filepaths);

        return $recovered;
    }


    // Восстановление из корзины по номеру в корзине
    public function recovery_comment($trash_ids) {
        // Приводим все к массиву
        if (!is_array($trash_ids)) $trash_ids = array($trash_ids);
        $trash_ids = array_map('intval', $trash_ids);

        $db                   = $this->db;
        $TRASH_FOLDER         = $this->core->TRASH_FOLDER;
        $comment_insert_value = array();
        $recovered            = 0;

        $trashed_objects = $db->get_results("SELECT `Trash_ID`, `Type`,
                `Message_ID`, `Class_ID`, `Subdivision_ID`, `Sub_Class_ID`,
                `System_Table_ID`, `Created`, `XML_Filename`, `XML_Filesize`,
                `IP`, `UserAgent`, `User_ID`
            FROM `Trash_Data`
            WHERE `Type`='".self::TYPE_COMMENT."' AND `Trash_ID` IN (".join(', ', $trash_ids).")", ARRAY_A);

        if (!$trashed_objects) return 0;

        // узнаем номера всех компонентов и файлов, а так же различные связки
        $class_ids     = array();
        $xml_filepaths = array();
        $class_file    = array(); // связь между компонентом  и файлом
        $message_file  = array(); // все объект, находящиеся в данном файле

        foreach ($trashed_objects as $v) {
            $type_id                   = $v['Type'];
            $filepath                  = 'comments/'.$v['XML_Filename'];
            $xml_filepaths[]           = $filepath;
            // $class_file[$filepath]     = $v['Class_ID'];
            $message_file[$filepath][] = $v['Message_ID'];
        }
        // $class_ids = array_unique($class_ids);
        $xml_filepaths = array_unique($xml_filepaths);

        // открываем каждый файл, ищем нужные объекты
        foreach ($xml_filepaths as $xml_filepath) {

            $doc = new DOMDocument('1.0', 'utf-8');
            $doc->load($this->core->TRASH_FOLDER . $xml_filepath);
            $xpath = new DOMXPath($doc);

            // ищем каждый восстанавливаемый объект в файле
            foreach ($message_file[$xml_filepath] as $id) {
                $comments = $xpath->query("/netcatml/comments/comment[@comment_id='".$id."']");

                if (!is_object($comments)) {
                    continue;
                }

                foreach ($comments as $comment) {
                    $recovered++;
                    $comment_id        = intval($comment->getAttribute('comment_id'));
                    $parent_comment_id = intval($comment->getAttribute('parent_comment_id'));
                    $message_id        = intval($comment->getAttribute('message_id'));
                    $sub_class_id      = intval($comment->getAttribute('sub_class_id'));

                    foreach ($comment->childNodes as $v) {
                        if ($v->nodeName == 'Comment')
                                $text = $db->escape($v->nodeValue);
                        if ($v->nodeName == 'User_ID')
                                $comment_user_id = intval($v->nodeValue);
                        if ($v->nodeName == 'Date')
                                $comment_date = $db->escape($v->nodeValue);
                        if ($v->nodeName == 'Updated')
                                $comment_updated = $db->escape($v->nodeValue);
                    }
                    $comment_insert_value[] = "('".$comment_id."', '".$parent_comment_id."', '".$comment_user_id."',
                          '".$text."', '".$comment_date."', '".$comment_updated."',
                          '".$sub_class_id."', '".$message_id."' )";

                    $comment->parentNode->removeChild($comment);
                }

                $doc->save($this->core->TRASH_FOLDER . $xml_filepath);
            }
            unset($xpath, $doc);
        }

        if ($this->core->modules->get_by_keyword('comments')) {
            if (!empty($comment_insert_value)) {
                $db->query("INSERT INTO `Comments_Text` (`id`,`Parent_Comment_ID`,`User_ID`,`Comment`,
                                                 `Date`,`Updated`,`Sub_Class_ID`, `Message_ID`)
                    VALUES ".$this->encode_to_system(join(',', $comment_insert_value)));

                require_once nc_module_folder('comments') . 'nc_comments_admin.class.php';
                $nc_comments_admin = new nc_comments_admin();
                $nc_comments_admin->optimizeSave();
            }
        }

        $db->query("DELETE FROM `Trash_Data` WHERE `Trash_ID` IN (".join(', ', $trash_ids).")");

        $this->remove_xml_files($xml_filepaths);

        return $recovered;
    }

    // Восстановление из корзины по номеру объекта в компоненте, и номеру компонента
    public function trash_recovery_by_message_and_class($message, $class_id) {
        // Приводим все к массиву
        if (!is_array($message)) {
            $messages_to_trash = array(intval($message));
        } else {
            $messages_to_trash = array_map("intval", $message);
        }

        $db = $this->db;

        $objects_to_recovery = $db->get_col("SELECT *
      FROM `Trash_Data`
        WHERE `Message_ID` IN (".join($messages_to_trash).") AND `Class_ID` = ".intval($class_id));

        return $this->recovery($objects_to_recovery);
    }

    // Удаление объекта из корзины по id номеру объекта в компоненте, и номеру компонента
    public function trash_remove_by_message_and_class($message, $class_id) {
        $db = $this->db;
        // Приводим все к массиву
        if (!is_array($message)) {
            $messages_to_remove = array(intval($message));
        } else {
            $messages_to_remove = array_map("intval", $message);
        }

        $class_id = intval($class_id);
        $TRASH_FOLDER = $this->core->TRASH_FOLDER;

        $db->query("DELETE FROM `Trash_Data` WHERE `Message_ID` IN (".join(',', $messages_to_remove).") AND `Class_ID` = ".$class_id." LIMIT ".count($messages_to_remove));
        // Удаляем файлы объектов
        foreach ($messages_to_remove as $id) {
            DeleteMessageFiles($class_id, $id);
        }
    }

    /**
     * Удаление из корзины определенных элементов
     * @param miex id удаляемых элементов
     * @return int количество удаленных элементов
     */
    public function delete($trash_ids) {
        // Приводим все к массиву
        if (!is_array($trash_ids)) $trash_ids = array($trash_ids);
        $trash_ids = array_map('intval', $trash_ids);

        $db = $this->db;
        $TRASH_FOLDER = $this->core->TRASH_FOLDER;
        $deleted = 0;

        $trashed_objects = $db->get_results("
    SELECT `Trash_ID`, `Type`, `Message_ID`, `Class_ID`, `Subdivision_ID`, `Sub_Class_ID`,
    `System_Table_ID`, `Created`, `XML_Filename`, `XML_Filesize`, `IP`, `UserAgent`, `User_ID`
    FROM `Trash_Data`
    WHERE `Trash_ID` IN (".join(', ', $trash_ids).")", ARRAY_A);

        if (!$trashed_objects) return 0;

        // узнаем номера всех компонентов и файлов, а так же различные связки
        $class_ids = array();
        $xml_filepaths = array();
        $class_file = array(); // связь между компонентом  и файлом
        $message_file = array(); // все объект, находящиеся в данном файле
        foreach ($trashed_objects as $v) {
            $class_ids[] = $v['Class_ID'];
            $filepath = $this->xml_file_name($v);
            $xml_filepaths[] = $filepath;
            $class_file[$filepath] = $v['Class_ID'];
            $message_file[$filepath][] = $v['Message_ID'];
            //delete all files, related with Message
            DeleteMessageFiles($v['Class_ID'], $v['Message_ID'], $filepath);
        }
        $class_ids = array_unique($class_ids);
        $xml_filepaths = array_unique($xml_filepaths);


        // открываем каждый файл, ищем нужные объекты
        foreach ($xml_filepaths as $xml_filepath) {
            $doc = new DOMDocument('1.0', 'utf-8');
            $doc->load($this->core->TRASH_FOLDER . $xml_filepath);
            $xpath = new DOMXPath($doc);

            $class_id = $class_file[$xml_filepath];
            //смотрим поля на момент удаления
            $del_fields = array();
            $fields_node = $doc->getElementsByTagName('fields')->item(0);
            if ($fields_node) {
                foreach ($fields_node->childNodes as $field_node) {
                    if ($field_node->childNodes) {
                        foreach ($field_node->childNodes as $v) {
                            if ($v->nodeName == 'Field_Name') {
                                $del_fields[] = $v->nodeValue;
                            }
                        }
                    }
                }
            }

            // ищем каждый восстанавливаемый объект в файле
            foreach ($message_file[$filepath] as $id) {
                $node = $xpath->query("/netcatml/messages/message[@message_id='".$id."']");
                $node = $node->item(0);
                if (!is_object($node)) {
                    continue;
                }
                $cc_id = intval($node->getAttribute('sub_class_id'));
                $deleted++;

                $comments = $xpath->query("/netcatml/comments/comment[@message_id='".$id."' and @sub_class_id='".$cc_id."']");
                if ($comments)
                        foreach ($comments as $comment) {
                        $comment->parentNode->removeChild($comment);
                    }
                $node->parentNode->removeChild($node);
                $doc->save($this->core->TRASH_FOLDER.$xml_filepath);
            }
            unset($xpath, $doc);
        }

        $db->query("DELETE FROM `Trash_Data` WHERE `Trash_ID` IN (".join(', ', $trash_ids).")");

        $this->remove_xml_files($xml_filepaths);

        return $deleted;
    }

    /**
     * Очистка всей корзины
     * @return bool
     */
    public function clean() {
        $all_trashed_objects = $this->db->get_col("SELECT `Trash_ID` FROM `Trash_Data`");
        $this->delete($all_trashed_objects);
        /* //old code
        $this->db->query("TRUNCATE TABLE `Trash_Data`");
        if (($handle = opendir($this->core->TRASH_FOLDER))) {
            while (($file = readdir($handle)) !== false) {
                if (strpos($file, '.') === false && is_dir($this->core->TRASH_FOLDER.$file)) {
                    try {
                        $this->core->files->delete_dir($this->core->TRASH_FOLDER.$file);
                    } catch (Exception $e) {
                        nc_print_status($e->getMessage(), 'error');
                    }
                }
            }
            closedir($handle);
        }
        */
        return true;
    }

    public function get_deleted_ids() {
        return $this->deleted_ids;
    }

    public function is_full() {
        return $this->trash_full;
    }

    public function folder_fail() {
        return $this->folder_fail;
    }

    # выборка файлов из объекта при его удалении, чтобы их также удалить

    function TrashMessageFiles($classID, $message_ids, $recovery = false) {
        $db = $this->db;

        static $storage = array();

        $classID = intval($classID);

        if (empty($storage[$classID])) {
            $storage[$classID] = $db->get_col("SELECT a.`Field_ID`
        FROM `Field` AS a
        WHERE a.`Class_ID` = '".$classID."' AND a.`TypeOfData_ID` = " . NC_FIELDTYPE_FILE);
        }

        if (empty($storage[$classID])) return false;

        // Вызываем смену статуса Deleted в таблице Filetable
        $this->TrashFiles($storage[$classID], $message_ids, $recovery);
    }

    # Восстановление статуса Deleted у файлов объектов при восстановлении из корзины

    function RecoveryMessageFiles($classID, $message_ids) {
        $this->TrashMessageFiles($classID, $message_ids, true);
    }

    /*
      Функция удаления файлов в корзину, т.е. проставление статуса Deleted в таблице Filetable
     */

    function TrashFiles($fields, $message_ids, $recovery = false) {
        $db = $this->db;

        // Приводим первый параметр к массиву чисел
        if (!is_array($fields)) {
            $fields_arr = array(intval($fields));
        } else {
            $fields_arr = array_map("intval", $fields);
        }
        // Приводим второй параметр к массиву чисел
        if (!is_array($message_ids)) {
            $message_arr = array(intval($message_ids));
        } else {
            $message_arr = array_map("intval", $message_ids);
        }
        if (!isset($message)) {
            $message = null;
        }
        $message = intval($message);

        if (!empty($fields_arr) && !empty($message_arr)) {
            $db->query("UPDATE `Filetable` SET `Deleted` = '".($recovery ? '0' : '1')."' WHERE `Field_ID` IN (".join(',', $fields_arr).") AND `Message_ID` IN (".join(',', $message_ids).")");
        } else {
            return false;
        }
        return true;
    }

    public function delete_preform() {

        // проверка лимита корзины
        $r = $this->core->db->get_col("SELECT `XML_Filesize` FROM `Trash_Data` GROUP BY `XML_Filename`");
        if ($r && array_sum($r) > $this->core->get_settings('TrashLimit') * 1024 * 1024) {
            nc_print_status(NETCAT_TRASH_TRASHBIN_IS_FULL, 'info');
        }

        if (!is_dir($this->core->TRASH_FOLDER) || !is_writable($this->core->TRASH_FOLDER)) {
            nc_print_status(sprintf(NETCAT_TRASH_FOLDER_FAIL, $this->core->HTTP_TRASH_PATH), 'info');
        }
    }

    /**
     * В файле корзины данные всегда хранятся в кодировке utf-8,
     * вне зависимости от кодировке системы.
     * Данный метод кодирует текст для записи в файл.
     * Следующий метод ( encode_to_system ) кодирует наоборот: из файла в систему
     */
    public function encode_to_file($str) {
        if ($this->core->NC_UNICODE) return $str;
        return $this->core->utf8->win2utf($str);
    }

    public function encode_to_system($str) {
        if ($this->core->NC_UNICODE) return $str;
        return $this->core->utf8->utf2win($str);
    }

    public function encode_to_file_deep($data) {
        if ($data) {
            foreach ($data as &$val) {
                if (is_array($val) || is_object($val)) {
                    $val = $this->encode_to_file_deep($val);
                }
                else {
                    $val = $this->encode_to_file($val);
                }
            }
        }
        return $data;
    }


    protected function xml_file_name($trash_row) {
        $type = isset($trash_row['Type']) ? $trash_row['Type'] : self::TYPE_MESSAGE;
        switch ($type) {

            case self::TYPE_COMMENT:
                return 'comments/' . $trash_row['XML_Filename'];

            case self::TYPE_MESSAGE:
            default:
                return $trash_row['Class_ID'] . '/' . $trash_row['XML_Filename'];
        }
    }


    protected function remove_xml_files($exclude_list = array()) {
        $xml_exists = array();
        $result = (array)$this->db->get_results("SELECT DISTINCT Type, Class_ID, XML_Filename FROM Trash_Data", ARRAY_A);
        foreach ($result as $row) {
            $xml_exists[] = $this->xml_file_name($row);
        }
        if (!$xml_exists) $xml_exists = array();

        $file_to_del = array_diff($exclude_list, $xml_exists);

        foreach ($file_to_del as $v) {
            unlink($this->core->TRASH_FOLDER . $v);
        }
    }
}
?>