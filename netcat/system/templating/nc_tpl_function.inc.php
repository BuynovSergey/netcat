<?php

if (!function_exists('nc_standardize_path_to_folder')) {
    function nc_standardize_path_to_folder($path) {
        return nc_standardize_path_to_file($path) . '/';
    }
}

if (!function_exists('nc_standardize_path_to_file')) {
    function nc_standardize_path_to_file($path) {
        $path = str_replace('\\', '/', $path);
        while (!(strpos($path, '//') === false)) {
            $path = str_replace('//', '/', $path);
        }
        return rtrim($path, '/');
    }
}

function nc_get_path_to_parent_folder($path) {
    $folder_array = get_folder_array($path);
    array_pop($folder_array);
    return nc_standardize_path_to_folder(join('/', $folder_array));
}

function nc_get_path_to_main_parent_folder($path) {
    $parts = explode('/', $path, 3);
    return isset($parts[1]) && strlen($parts[1]) ? "/$parts[1]/" : "/";
}

function nc_get_path_to_parent_file($path) {
    $folder_array = get_folder_array($path);
    $file = array_pop($folder_array);
    array_pop($folder_array);
    $folder_array[] = $file;
    return nc_standardize_path_to_file(join('/', $folder_array));
}

function get_folder_array($path) {
    return explode('/', rtrim(nc_standardize_path_to_folder($path), '/'));
}

function nc_check_file($file, $die_if_not_found = false) {
    if ( file_exists($file) ) {
        return true;
    } else if ($die_if_not_found) {
        nc_print_status( sprintf(NETCAT_FILE_NOT_FOUND, $file), 'error');
        exit;
    } else {
        return false;
    }
}

function nc_check_folder($dir) {
    if ( is_dir($dir) ) {
        return true;
    } else {
        nc_print_status( sprintf(NETCAT_DIR_NOT_FOUND, $dir), 'error');
        exit;
    }
}

function nc_get_file($file_path) {
    return filesize($file_path) ? file_get_contents($file_path) : null;
}

function nc_save_file($file, $content = '') {
    if (file_put_contents($file, $content) !== false) {
        return true;
    } else {
        nc_print_status(NETCAT_CAN_NOT_WRITE_FILE, 'error');
        exit;
    }
}

/**
 * @deprecated deprecated since version 5.0.0
 */
function nc_delete_all_file_and_folder($directory) {
    $files = nc_double_array_shift(scandir($directory));
    $directory = nc_standardize_path_to_folder($directory);

    foreach ($files as $file) {
        $full_path = $directory.$file;

        if ( !file_exists($full_path) ) continue;

        if (is_dir($full_path)) {
            nc_delete_all_file_and_folder($full_path);
        } else {
            unlink($full_path);
        }
    }
    if ( is_dir($directory) ) rmdir($directory);
}

if (!function_exists('nc_double_array_shift')) {
    function nc_double_array_shift($array) {
        array_shift($array);
        array_shift($array);
        return $array;
    }
}

function nc_create_folder($file) {
    if (is_dir($file) || @mkdir($file)) {
        return true;
    } else {
        nc_print_status(NETCAT_CAN_NOT_CREATE_FOLDER.' '.$file, 'error');
        exit;
    }
}

function nc_get_file_mode($type, $id = null) {
    static $mode = array();

    $id = (int)$id;

    if (isset($mode[$type][$id])) {
        return $mode[$type][$id];
    }

    $File_Mode = +nc_array_value($_REQUEST, 'fs');

    if (!$File_Mode && $id) {
        $SQL = "SELECT File_Mode
                  FROM $type
                      WHERE {$type}_ID = $id";
        $File_Mode = nc_Core::get_object()->db->get_var($SQL);
    }

    $mode[$type][$id] = $File_Mode;
    return $File_Mode;
}

function nc_get_string_service_prefix_for_RecordTemplate() {
    return '<?/* Служебная часть */'."\r"
    .'for ($f_RowNum = 0; $f_RowNum < $rowCount; $f_RowNum++) {'."\r"
    .'    if($fetch_row[$f_RowNum] instanceof Iterator) {'."\r"
    .'        extract($fetch_row[$f_RowNum]->to_array(), EXTR_PREFIX_ALL, "f");'."\r"
    .'    } else {'."\r"
    .'        extract($fetch_row[$f_RowNum], EXTR_PREFIX_ALL, "f");'."\r"
    .'    }'."\r"
    .'    foreach($iteration_RecordTemplate[$f_RowNum] as $value) {'."\r"
    .'        extract($value);'."\r"
    .'    }'."\r"
    .'    !isset($cc_env["convert2txt"]) ? $cc_env["convert2txt"] = null : null;'."\r"
    .'    eval($cc_env["convert2txt"]);'."\r"
    .'    ob_start();'."\r"
    .'/* Конец служебной части */?>'."\n\r";
}

function nc_get_string_service_suffix_for_RecordTemplate() {
    return "\n\r"
    .'<?/* Служебная часть */'."\r"
    .'    echo nc_finishing_RecordTemplate(ob_get_clean(), $inside_admin, $classID, $f_RowID, $parent_message, $cc, $cc_env["Class_Name"], $no_cache_marks);'."\r"
    .'}'."\r"
    .'/* Конец служебной части */?>';
}

function nc_add_service_string_suffix_for_RecordTemplate($string) {
    return nc_get_string_service_prefix_for_RecordTemplate() . nc_cleaned_RecordTemplate_of_string_service($string) . nc_get_string_service_suffix_for_RecordTemplate();
}

function nc_finishing_RecordTemplate($row, $inside_admin, $classID, $f_RowID, $parent_message, $cc, $Class_Name, $no_cache_marks) {
    if (nc_core::get_object()->admin_mode) {
        $row_id_string = nc_get_string_row_id($classID, $f_RowID, $parent_message, $cc, $Class_Name);
        $class_name = nc_get_record_css_class_name($f_RowID);

        if (preg_match("#^(\s*<(\w+))([^>]*)>#s", $row, $regs) && preg_match("#</$regs[2]>\s*$#si", $row)) {
            $row = substr($row, 0, strlen($regs[1])) . // до начала атрибутов первого тега
                   ' ' . nc_add_record_css_class_name($regs[3], $class_name) . // оригинальные атрибуты с добавлением классов для админки
                   ' ' . $row_id_string . // идентификаторы для D&D
                   substr($row, strlen($regs[0])-1); // остаток строки
        }
        else {
            $row = "<div $row_id_string class='$class_name'>$row</div>";
        }
    }

    return $no_cache_marks ? nc_add_no_cache_marks($row, $f_RowID) : $row;
}

function nc_get_record_css_class_name($f_RowID) {
    return "nc-infoblock-object" .  ($f_RowID && $f_RowID == nc_array_value($_REQUEST, 'highlights') ? " nc-bg-green" : "");
}

function nc_add_record_css_class_name($attributes, $class_name) {
    $class_attribute_regexp =
        '/^
            (.* \b class \s* = \s*)
            (\'|"|\w+)
            (.+)
        $/Sixs';

    if (preg_match($class_attribute_regexp, $attributes, $regs)) {
        if ($regs[2] == '"' || $regs[2] == "'") { // quoted value
            $result = $regs[1] . $regs[2] . $class_name . ' ' . $regs[3];
        }
        else {
            $result = $regs[1] . "'" . $class_name . ' ' . $regs[2] . "'" . $regs[3];
        }
    }
    else {
        $result = "class='$class_name' $attributes";
    }

    return $result;
}

function nc_get_string_row_id($classID, $f_RowID, $parent_message, $cc, $Class_Name) {
    $return = "id='message" . $classID . "-" . $f_RowID .
        "' messageId='" . $f_RowID .
        "' messageParent='" . $parent_message .
        "' messageClass='" . $classID .
        "' messageSubclass='" . $cc .
        "' dragLabel='" . htmlspecialchars($Class_Name . " #" . $f_RowID, ENT_QUOTES) .
        "'";
    return $return;
}

function nc_add_no_cache_marks($row, $f_RowNum) {
    return "<!-- nocache_object_".$f_RowNum." -->".$row."<!-- /nocache_object_".$f_RowNum." -->";
}

function nc_cleaned_RecordTemplate_of_string_service($string) {
    return nc_preg_replace('#([\n\r ]*<\?/\* Служебная часть \*/.*?/\* Конец служебной части \*/\?>[\n\r ]*)+#s', '', $string);
}

function nc_get_file_mode_and_file_path($id) {
    global $db, $cc_env;
    if (!isset($cc_env['File_Mode'])) {
        $SQL = "SELECT File_Path,
                       File_Mode,
                       File_Hash
                    FROM Class
                        WHERE Class_ID = $id";
        $result = $db->get_row($SQL, ARRAY_A);
        $File_Mode = $result['File_Mode'];
        $File_Path = $result['File_Path'];
        $File_Hash = $result['File_Hash'];
    } else {
        $File_Mode = $cc_env['File_Mode'];
        $File_Path = $cc_env['File_Path'];
        $File_Hash = $cc_env['File_Hash'];
    }
    return array('File_Mode' => $File_Mode, 'File_Path' => $File_Path, 'File_Hash' => $File_Hash);
}

function nc_check_changes($hash, $path, $type) {
    return md5(file_get_contents(nc_standardize_path_to_file($path . '/' . $type . '.html'))) == $hash;
}