<?php

function getFileCount($classID, $systemTableID) {
    global $db;

    return $db->get_var("SELECT COUNT(*) FROM `Field`
    WHERE ".($systemTableID ? "`System_Table_ID` = '".intval($systemTableID)."'" : "`Class_ID` = '".intval($classID)."'")."
    AND `TypeOfData_ID` in (" . NC_FIELDTYPE_FILE . ", " . NC_FIELDTYPE_MULTIFILE . ")");
}

function unhtmlentities($string) {
    $trans_tbl = get_html_translation_table(HTML_ENTITIES);
    $trans_tbl = array_flip($trans_tbl);

    return strtr($string, $trans_tbl);
}

# удаление конкретного файла

function DeleteFile($field_id, $field_name, $classID, $systemTableName, $message, $trashxml = NULL) {
    global $nc_core, $db;

    $field_id = (int)$field_id;
    $classID = (int)$classID;
    $message = (int)$message;
    $systemTableName = $db->escape($systemTableName);
    if (!nc_preg_match('/^\w+$/i', $field_name)) {
        return 0;
    }

    global ${'f_' . $field_name . '_old'};

    $content_table = $systemTableName ?: "Message{$classID}";
    $content_table_key = ($systemTableName ?: 'Message') . '_ID';

    $field = $db->get_var(
        "SELECT `{$field_name}`
         FROM `{$content_table}`
         WHERE `{$content_table_key}` = '{$message}'");

    if (is_object($trashxml)) {
        $field = $trashxml->query("/netcatml/messages/message[@message_id='".$message."']/".$field_name);
        $field = $field->item(0)->textContent;
    }

    if (!$field) {
        return;
    }

    $field_array = explode(':', $field);
    $name = $field_array[0];
    // имя на диске при использовании ФС Original
    $name_on_disk = isset($field_array[3]) ? $field_array[3] : '';
    // Расширение файла
    $extension = pathinfo($name, PATHINFO_EXTENSION);
    // Полный пусть к файлу
    $fullPathToFile = $nc_core->FILES_FOLDER . ($name_on_disk ?: $field_id . '_' . $message . '.' . $extension);
    $parts = explode('/', $fullPathToFile);
    $parts[count($parts) - 1] = 'preview_' . $parts[count($parts) - 1];
    $fullPathToPreviewFile = implode('/', $parts);

    //путь в случае использовании Filetable
    if ($systemTableName === 'User') {
        $File_Path = 'u/';
    } elseif ($systemTableName === 'Template') {
        $File_Path = 't/';
    } elseif ($systemTableName === 'Catalogue') {
        $File_Path = 'c/';
    } elseif ($systemTableName) {
        $File_Path = $message . '/';
    } else {
        list($subdivid, $subclassid) = $db->get_row("SELECT `Subdivision_ID`, `Sub_Class_ID` FROM `Message{$classID}` WHERE `Message_ID` = '{$message}'", ARRAY_N);
        $File_Path = $subdivid . '/' . $subclassid . '/';
    }

    $q = $db->get_row("SELECT `ID`, `Virt_Name` FROM `Filetable` WHERE `Message_ID` = '{$message}' AND `Field_ID` = '{$field_id}'", ARRAY_N);

    if ($db->num_rows) {
        list($fs_id, $fs_virt_name) = $q;
        //delete file
        if (is_writable($nc_core->FILES_FOLDER . $File_Path . $fs_virt_name)) {
            unlink($nc_core->FILES_FOLDER . $File_Path . $fs_virt_name);
            @unlink($nc_core->FILES_FOLDER . $File_Path . 'preview_' . $fs_virt_name);
        }
        $db->query("DELETE FROM `Filetable` WHERE `ID` = '{$fs_id}' LIMIT 1");
    }

    global ${'f_' . $field_name . '_old'};

    $res = $db->query("UPDATE `{$content_table}` SET `LastUpdated` = `LastUpdated`, {$field_name} = '' WHERE `{$content_table_key}` = '{$message}'");

    if ($res && is_writable($fullPathToFile)) {
        unlink($fullPathToFile);
        @unlink($fullPathToPreviewFile);
    }
    if (is_object($trashxml) && is_writable($fullPathToFile)) {
        unlink($fullPathToFile);
        @unlink($fullPathToPreviewFile);
    }

    // Удаление сгенерированных изображений для данного объекта
    $class_id_or_sys_table_lc_keyword = $classID ?: nc_strtolower($systemTableName);
    nc_image_generator::remove_generated_images($class_id_or_sys_table_lc_keyword, $field_id, $message);
}

# выборка файлов из объекта при его удалении, чтобы их также удалить

function DeleteMessageFiles($classID, $message, $trashfile = '') {
    global $db, $FILES_FOLDER;
    static $storage = array();

    $classID = intval($classID);
    $message = intval($message);

    if (empty($storage[$classID])) {
        $storage[$classID] = $db->get_results("SELECT a.`Field_ID`, a.`Field_Name`, b.`System_Table_Name` FROM `Field` AS a
      LEFT JOIN `System_Table` AS b ON a.`System_Table_ID` = b.`System_Table_ID`
      WHERE a.`Class_ID` = '".$classID."' AND a.`TypeOfData_ID` = " . NC_FIELDTYPE_FILE, ARRAY_N);
    }

    if (!empty($storage[$classID])) {
        $xpath = null;

        if ($trashfile) {
            $ncCore = nc_Core::get_object();
            /* @var $ncCore nc_Core */
            $doc = new DOMDocument('1.0', 'utf-8');
            $doc->load($ncCore->TRASH_FOLDER . $trashfile);
            $xpath = new DOMXPath($doc);
        }

        foreach ($storage[$classID] as $field) {
            list($field_id, $field_name, $systemTableName) = $field;
            DeleteFile($field_id, $field_name, $classID, $systemTableName, $message, $xpath);
        }
    }

    //удаление файлов из поля Мультизагрузка
    //поле "Мультифайл"
    $nc_core = nc_Core::get_object();

    $sql = "SELECT m.`ID`, m.`Field_ID`, m.`Path`, m.`Preview` FROM `Multifield` AS m " .
        "LEFT JOIN `Field` as f ON m.`Field_ID` = f.`Field_ID` " .
        "WHERE m.`Message_ID` = {$message} AND f.`Class_ID` = {$classID} AND f.`TypeOfData_ID` = " . NC_FIELDTYPE_MULTIFILE;

    $multifields = (array)$db->get_results($sql, ARRAY_A);
    foreach($multifields as $multifield) {
        $id = $multifield['ID'];
        $field_id = $multifield['Field_ID'];
        
        $settings_path = nc_standardize_path_to_folder($nc_core->DOCUMENT_ROOT . '/' . $nc_core->SUB_FOLDER);

        foreach(array('Path', 'Preview') as $path) {
            $file_path = $multifield[$path];

            if ($file_path) {
                @unlink(nc_standardize_path_to_file($settings_path . $file_path));
            }
        }

        $remove_dir = nc_standardize_path_to_folder($settings_path . "/" . $nc_core->HTTP_FILES_PATH . "/multifile/" . $field_id . "/" . $message . "/");
        if (file_exists($remove_dir)) {
            @rmdir($remove_dir);
        }

        $sql = "DELETE FROM `Multifield` " .
            "WHERE `Message_ID` = {$message} AND `Field_ID` = {$field_id}";
        $db->query($sql);

        // Удаление сгененированных для поля изображений
        nc_image_generator::remove_generated_images($classID, $field_id, $message, $id);
    }
}

function DeleteSystemTableFiles($table, $message_id) {
    global $db;

    $systables = array('Catalogue' => 1, 'Subdivision' => 2, 'User' => 3, 'Template' => 4);

    if (!in_array($table, array_keys($systables))) {
        trigger_error("Wrong parameter \$table for DeleteSystemTableMessageFiles() [".$table."]", E_USER_WARNING);
        return;
    }

    $res = $db->get_results("SELECT `Field_ID`, `Field_Name`
    FROM `Field`
    WHERE `Class_ID` = 0
    AND `System_Table_ID` = '".$systables[$table]."'
    AND `TypeOfData_ID` = " . NC_FIELDTYPE_FILE, ARRAY_N);

    if (!empty($res)) {
        foreach ($res as $field) {
            list($field_id, $field_name) = $field;
            DeleteFile($field_id, $field_name, 0, $table, $message_id);
        }
    }

    if ($systables[$table] == 3) {
        //удаление файлов из поля Мультизагрузка
        //поле "Мультифайл"
        $nc_core = nc_Core::get_object();

        $sql = "SELECT m.`Field_ID`, m.`Path`, m.`Preview` FROM `Multifield` AS m " .
            "LEFT JOIN `Field` as f ON m.`Field_ID` = f.`Field_ID` " .
            "WHERE m.`Message_ID` = {$message_id} AND f.`Class_ID` = 0 " .
            "AND f.`System_Table_ID` = {$systables[$table]} AND f.`TypeOfData_ID` = " . NC_FIELDTYPE_MULTIFILE;

        $multifields = (array)$db->get_results($sql, ARRAY_A);
        foreach($multifields as $multifield) {
            $field_id = $multifield['Field_ID'];

            $settings_http_path = nc_standardize_path_to_folder($nc_core->HTTP_FILES_PATH . "/multifile/{$field_id}/");
            $settings_path = nc_standardize_path_to_folder($nc_core->DOCUMENT_ROOT . '/' . $nc_core->SUB_FOLDER . '/' . $settings_http_path);

            foreach(array('Path', 'Preview') as $path) {
                $file_path = $multifield[$path];

                if ($file_path) {
                    $parts = explode('/', nc_standardize_path_to_file($file_path));
                    $file_name = array_pop($parts);

                    @unlink($settings_path . $file_name);
                }
            }

            $sql = "DELETE FROM `Multifield` " .
                "WHERE `Message_ID` = {$message_id} AND `Field_ID` = {$field_id}";
            $db->query($sql);
        }
    }
}

/**
 * Удаление директории файлов шаблона в разделе $cc
 * @param int $cc идентификатор шаблона в разделе
 * @return bool
 */
function DeleteSubClassDir($cc) {
    global $nc_core, $db, $FILES_FOLDER;

    $dir_path = $nc_core->db->get_row("SELECT * FROM `Sub_Class` WHERE `Sub_Class_ID` = '".intval($cc)."' ", ARRAY_A);

    $path = $FILES_FOLDER.$dir_path['Subdivision_ID']."/".$dir_path['Sub_Class_ID'];

    if (is_dir($path) && count(glob($path."/*")) === 0 && is_writable($path)) {
        return @rmdir($path);
    }

    return false;
}

/**
 * Удаление директории раздела с идентификатором $sub
 * @param int $sub идентификатор раздела
 * @return bool
 */
function DeleteSubdivisionDir($sub) {
    global $FILES_FOLDER, $nc_core;

    $path = $FILES_FOLDER.$sub;
    try {
        $nc_core->files->delete_dir($path);
    } catch (nc_Exception_Files_Not_Rights $e) {
        ; //nc_print_status(sprintf(NETCAT_ERROR_UNABLE_TO_DELETE_FILES, $path), 'error');
    }

    return false;
}

/**
 * Удаление директории компонента $cc в  разделе $sub
 *
 * @param int $sub идентификатор раздела
 * @param int $cc идентификатор компонента в разделе
 *
 * @return bool
 */
function DeleteSubClassDirAlways($sub, $cc) {
    global $FILES_FOLDER;

    $path = $FILES_FOLDER.$sub."/".$cc;

    if (!is_dir($path)) return false;

    $files = array();
    $dh = opendir($path);

    if (!is_resource($dh)) return false;

    while (false !== ($filename = readdir($dh))) {
        if ($filename == "." || $filename == "..") continue;
        if (is_writable($path."/".$filename)) {
            unlink($path."/".$filename);
        }
    }
    closedir($dh);

    if (count(glob($path."/*")) === 0 && is_writable($path)) {
        return rmdir($path);
    }
}

/**
 * Удаление файлов шаблона в разделе $cc с идентификатором шаблона $classID
 * @param int $cc идентификатор шаблона в разделе
 * @param int $classID идентификатор шаблона
 * @return bool
 */
function DeleteSubClassFiles($cc, $classID) {
    global $db;

    $cc+= 0;
    $classID+= 0;

    $res = $db->get_results("SELECT `Field_ID`, `Field_Name`
    FROM `Field`
    WHERE `Class_ID` = '".$classID."'
    AND `System_Table_ID` = 0
    AND `TypeOfData_ID` = " . NC_FIELDTYPE_FILE, ARRAY_A);

    if ($res) {
        foreach ($res as $field) {
            $messages = $db->get_col("SELECT `Message_ID`, `".$field['Field_Name']."` FROM `Message".$classID."` WHERE `Sub_Class_ID` = '".$cc."'");
            if ($messages) {
                foreach ($messages as $message_id) {
                    DeleteFile($field['Field_ID'], $field['Field_Name'], $classID, "", $message_id);
                }
            }
        }
    }

    // delete dir
    DeleteSubClassDir($cc);

    return true;
}

/**
 * Возвращает mime-тип файла
 * @param string $file_path
 * @param string $default_mime_type   значение, если не удалось определить MIME-type
 * @return string
 */
function nc_file_mime_type($file_path, $default_mime_type = 'application/octet-stream') {
    // (1) finfo
    if (function_exists('finfo_open')) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $result = finfo_file($finfo, $file_path);
        finfo_close($finfo);
        return $result;
    }

    // (2) fallback: try to guess by file content (images only)
    $file_type = null;

    if (filesize($file_path) > 4) {
        // для картинок пробуем угадать по первым байтам
        $headers = array(
            "\xFF\xD8\xFF" => 'image/jpeg',
            "\x89\x50\x4E\x47" => 'image/png',
            "\x47\x49\x46\x38" => 'image/gif',
        );

        $fp = fopen($file_path, 'r');
        $file_header = fread($fp, 4);
        foreach ($headers as $signature => $mime_type) {
            if (substr($file_header, 0, strlen($signature)) == $signature) {
                $file_type = $mime_type;
                break;
            }
        }

        if (!$file_type) { // look for <svg
            $more_content = $file_header . fread($fp, 1020); // read up to 1K (in addition to 4 bytes we read before)
            if (strpos($more_content, '<?xml') !== false && preg_match('/<svg\b/', $more_content)) {
                $file_type = 'image/svg+xml';
            }
        }

        fclose($fp);
    }

    return $file_type ?: $default_mime_type;

}

/**
 * Отдает содержимое файла на загрузку.
 * @param $file_path        - Полный путь к файлу
 * @param null $file_name   - Название файла
 * @param null $file_type   - Тип содержимого файла
 * @param null $attachment
 *    false — нет заголовка Content-Disposition ()
 *    true — Content-Distposition: attachment
 *    null — Content-Disposition: inline
 * @throws Exception
 */
function nc_send_file($file_path, $file_name = null, $file_type = null, $attachment = null) {
    if (file_exists($file_path) && !is_dir($file_path)) {
        while (@ob_end_clean());
        $nc_core = nc_core::get_object();
        $file_name = $file_name ?: pathinfo($file_path, PATHINFO_BASENAME);
        $file_size = @filesize($file_path);
        if (!strlen($file_type)) {
            $file_type = 'application/octet-stream';
        }
        nc_set_http_response_code(200);
        header('Content-Type: ' . $file_type);
        if ($attachment !== false) {
            $transliterated_name = $file_name;
            // транслитерация имени файла как fallback для [очень] старых браузеров
            if (nc_preg_match('/[^a-zA-Z0-9_.-]/', $file_name)) {
                if (!function_exists('nc_transliterate')) {
                    include_once $nc_core->ADMIN_FOLDER . 'lang/' . $nc_core->lang->detect_lang() . '.php';
                }
                $transliterated_name = nc_transliterate($file_name);
                $transliterated_name = preg_replace('/[^a-zA-Z0-9_.-]/', '_', $transliterated_name);
                $transliterated_name = preg_replace('/_+/', '_', $transliterated_name);
            }
            header(
                'Content-Disposition: ' . ($attachment ? 'attachment' : 'inline') .
                '; filename="' . $transliterated_name . '"' .
                '; filename*=' . $nc_core->NC_CHARSET . "''" . rawurlencode($file_name) // RFC 5987
            );
            header('Content-Transfer-Encoding: binary');
        }
        if ($file_size) {
            header('Content-Length: ' . $file_size);
            header('Connection: close');
        }
        $fp = fopen($file_path, 'rb');
        while (!feof($fp)) {
            echo fread($fp, 8192);
            flush();
        }
        fclose($fp);
    }
    exit;
}
