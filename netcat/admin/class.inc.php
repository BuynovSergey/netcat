<?php

function GetClassNameByID($ClassID) {
    return nc_Core::get_object()->db->get_var("SELECT Class_Name FROM Class WHERE Class_ID = '" . (int)$ClassID . "'");
}

/**
 * Распарсить формат поля
 * Для поля типа "Файл" возвращаемое значение - хэш-массив с ключами:
 * size - размер;
 * type - массив с mime-type. Каждый элемент - массив, 0 - то, что стоит до /, 1 - то, что стоит после
 * fs - тип файловой системы
 * disposition - content-disposition, 0 - inline, 1 - attachment
 * download - считать скачивания?
 *
 * Для поля "Текстовый блок":
 * html - разрешить тэги
 * br - перенос строки - <br>
 * fck - встроить редактор в поле
 * rows, cols - высота и шириина
 * bbcode - доступены bb-коды
 *
 * @param string $format
 * @param int $field_type
 * @return array
 */
function nc_field_parse_format($format, $field_type) {
    $ret = array(); //возвращаемое значение
    $format = str_replace(' ', '', $format); // уберем пробелы

    switch ($field_type) {
        case NC_FIELDTYPE_FILE:
        case NC_FIELDTYPE_MULTIFILE:
            // значения по умолчанию
            $ret['size'] = 0;
            $ret['type'] = array();
            $ret['fs'] = NC_FS_PROTECTED;
            $ret['download'] = 0;
            $ret['disposition'] = 0;
            $ret['icon'] = 0;
            $ret['onlyicon'] = 0;
            // если формат пустой - вернуть значения по умолчанию
            if (!$format) {
                break;
            }
            // формат в общем случае:   size:type1/type,type2/type:fs1|fs2|fs3:inline|attachment:download

            // уберем из формата лишнее
            list($format) = explode(';', $format);

            // определение фс
            if (preg_match('/(:?)(fs)(\d+)/', $format, $match)) {
                $ret['fs'] = $match[3];
                // уберем из формата тип фс
                $format = nc_preg_replace('/(:?)(fs)(\d+)/', '', $format);
            }

            if (!$format) {
                break;
            }
            // определение download
            if (strpos($format, 'download') !== false) {
                $ret['download'] = 1;
            }
            $format = nc_preg_replace('/(:?)(download)/', '', $format); // уберем download
            // определение onlyicon
            if (strpos($format, 'onlyicon') !== false) {
                $ret['onlyicon'] = 1;
            }
            $format = nc_preg_replace('/(:?)(onlyicon)/', '', $format); // уберем icon
            // определение icon
            if (strpos($format, 'icon') !== false) {
                $ret['icon'] = 1;
            }
            $format = nc_preg_replace('/(:?)(icon)/', '', $format); // уберем icon
            // определение content-disposition
            if (strpos($format, 'attachment') !== false) {
                $ret['disposition'] = 1;
            }
            $format = nc_preg_replace('/(:?)((attachment)|(inline))/', '', $format); // уберем attachment

            $format_array = explode(':', $format);
            if (empty($format_array)) {
                break;
            }
            if ($format_array[0]) {
                $ret['size'] = $format_array[0]; // размер
            }
            // определение mime-type
            if (!empty($format_array[1])) {
                $file_format = explode(',', $format_array[1]); // определим каждый тип
                foreach ($file_format as $k => $v) {
                    $ret['type'][$k] = explode('/', $v);
                }
            }
            break;
        case NC_FIELDTYPE_TEXT:
            // значения по умолчанию
            $ret['rows'] = 5; // количество строк
            $ret['cols'] = 60; // и столбцов
            $ret['html'] = 0; // разрешить тэги
            $ret['br'] = 0; // перенос строки - br
            $ret['fck'] = 0; // редактор встроен в поле
            $ret['panel'] = 0;
            $ret['typo'] = 0;
            $ret['bbcode'] = 0;

            if (!$format) {
                return $ret;
            }

            $params = array('html', 'br', 'fck', 'panel', 'typo', 'bbcode');
            // пробуем найти каждый параметр
            foreach ($params as $param) {
                if (($start = nc_strpos($format, $param)) !== false) {
                    $ret[$param] = (int)nc_substr($format, $start + nc_strlen($param) + 1, 1);
                }
            }

            // высоту и ширину ищем отдельно
            if ($format[0] > 0) {
                $format = strtok($format, ';');
                $ret['rows'] = strtok($format, ':');
                $ret['cols'] = strtok(':');
            }
            break;
        case NC_FIELDTYPE_DATETIME:
            $ret['type'] = '';
            $ret['calendar'] = 0;
            if (nc_strpos($format, 'calendar') !== false) {
                $ret['calendar'] = 1;
                $format = str_replace(array(';', 'calendar'), '', $format);
            }
            if ($format) {
                $ret['type'] = $format;
            }
            break;
        case NC_FIELDTYPE_STRING:
            $format = explode(':', $format);
            $ret['format'] = $format[0];
            $ret['protect_email'] = nc_array_value($format, 1) === 'protect';
            $ret['use_transliteration'] = nc_array_value($format, 2);
            $ret['transliteration_field'] = nc_array_value($format, 3);
            $ret['use_url_rules'] = nc_array_value($format, 4);
            // для упрощения проверки на допустимость HTML — свойства как у текстовых полей:
            $ret['html'] = $format[0] === 'html' ? 1 : 2;
            $ret['bbcode'] = 0;
            $ret['br'] = 2;
            break;
    }

    return $ret;
}

/**
 * Parses resize options
 * from field format
 *
 * @param string $format
 * @return array
 */
function nc_field_parse_resize_options($format) {
    $result = array(
        'use_resize' => 0,
        'resize_width' => 0,
        'resize_height' => 0,
        'use_crop' => 0,
        'crop_mode' => 0,
        'crop_width' => 0,
        'crop_height' => 0,
        'crop_x0' => 0,
        'crop_y0' => 0,
        'crop_x1' => 0,
        'crop_y1' => 0,
        'crop_ignore' => 0,
        'crop_ignore_width' => 0,
        'crop_ignore_height' => 0,
        'use_preview' => 0,
        'preview_use_resize' => 0,
        'preview_width' => 0,
        'preview_height' => 0,
        'preview_use_crop' => 0,
        'preview_crop_mode' => 0,
        'preview_crop_width' => 0,
        'preview_crop_height' => 0,
        'preview_crop_x0' => 0,
        'preview_crop_y0' => 0,
        'preview_crop_x1' => 0,
        'preview_crop_y1' => 0,
        'preview_crop_ignore' => 0,
        'preview_crop_ignore_width' => 0,
        'preview_crop_ignore_height' => 0,
        'multifile_min' => 0,
        'multifile_max' => 0,
    );

    $format = explode(';', $format);
    foreach ($format as $parameter) {
        $parameter = explode(':', $parameter);
        if (isset($parameter[1], $result[$parameter[0]])) {
            $result[$parameter[0]] = (int)$parameter[1];
        }
    }

    return $result;
}

/**
 * Функция копирует один файл из первого поля во второй
 * в пределах одного объекта
 * новый файл будет в ФС, которая задана в формате поле-приемника
 *
 * @param int $message - id объекта
 * @param int $field_src - id поля источника
 * @param int $field_dst - id поля приемника
 * @param int $classID (опционально) id компонента
 *
 * @todo Реализовать копирование файлов системных таблиц
 * @todo Реализовать копирование файлов различных объектов (возможно, из разных компонентов)
 * @return bool
 */
function nc_copy_filefield($message, $field_src, $field_dst, $classID = 0) {
    $nc_core = nc_Core::get_object();

    $message = (int)$message;
    $field_src = (int)$field_src;
    $field_dst = (int)$field_dst;
    $classID = (int)$classID;

    if (!$message || !$field_src || !$field_dst) {
        return 0;
    }

    $field_info_src = $nc_core->db->get_row("SELECT `Class_ID`, `Field_Name` FROM `Field` WHERE `Field_ID` = '{$field_src}'", ARRAY_A);

    if (!$field_info_src || !$field_info_src['Field_Name']) {
        return 0;
    }

    if (!$classID) {
        $classID = (int)$field_info_src['Class_ID'];
        if (!$classID) {
            return 0;
        }
    }

    // Значение поля в таблице объектов
    $message_field = $nc_core->db->get_row("SELECT * FROM `Message{$classID}` WHERE `Message_ID` = '{$message}'", ARRAY_A);
    $file_data = explode(':', $message_field[$field_info_src['Field_Name']]);
    list($file_name, $file_type, $file_size) = $file_data;
    $extension = pathinfo($file_name, PATHINFO_EXTENSION);

    // если ли файл в Filetable?
    $file_table = $nc_core->db->get_row(
        "SELECT `File_Path`, `Virt_Name` FROM `Filetable` WHERE `Message_ID` = '{$message}' AND `Field_ID` = '{$field_src}'",
        ARRAY_A
    );

    // определение полного пути к файлу
    if ($file_table) { // исходный файл в protected
        $path_src = rtrim($nc_core->FILES_FOLDER, '/') . $file_table['File_Path'] . $file_table['Virt_Name'];
    } else {
        if ($file_data[3]) { // original
            $path_src = $nc_core->FILES_FOLDER . $file_data[3];
        } else { // simple
            $path_src = $nc_core->FILES_FOLDER . $field_src . '_' . $message . '.' . $extension;
        }
    }

    # копирование
    // получение информации о поле-приемнике
    $field_info_dst = $nc_core->db->get_row("SELECT `Field_Name`, `Format` FROM `Field` WHERE `Field_ID` = '{$field_dst}'", ARRAY_A);

    if (!$field_info_dst) {
        return 0;
    }

    // удаление старого файла
    DeleteFile($field_dst, $field_info_dst['Field_Name'], $classID, 0, $message);

    // определение типа фс применика
    $fs = nc_field_parse_format($field_info_dst['Format'], NC_FIELDTYPE_FILE);
    $fs = $fs['fs'];

    // определение имени файла на диске и директории
    $file_info = $file_name . ':' . $file_type . ':' . $file_size;
    switch ($fs) {
        case NC_FS_PROTECTED:
            $path_dsc = $message_field['Subdivision_ID'] . '/' . $message_field['Sub_Class_ID'] . '/';
            $name_dsc = md5($file_name . date('H:i:s d.m.Y') . uniqid('netcat', true));
            $nc_core->db->query(
                "INSERT INTO `Filetable` (`Real_Name`, `Virt_Name`, `File_Path`, `File_Type`, `File_Size`, `Message_ID`, `Field_ID`)
                 VALUES('{$file_name}', '{$name_dsc}', '/{$path_dsc}', '{$file_type}', '{$file_size}', '{$message}', '{$field_dst}')");
            copy($path_src, $nc_core->FILES_FOLDER . $path_dsc . $name_dsc);
            print $nc_core->FILES_FOLDER . $path_dsc . $name_dsc;
            break;
        case NC_FS_ORIGINAL:
            $path_dsc = $message_field['Subdivision_ID'] . '/' . $message_field['Sub_Class_ID'] . '/';
            $name_dsc = nc_get_filename_for_original_fs($file_name, $nc_core->FILES_FOLDER . $path_dsc);
            $file_info .= ':' . $path_dsc . $name_dsc;
            copy($path_src, $nc_core->FILES_FOLDER . $path_dsc . $name_dsc);
            print $nc_core->FILES_FOLDER . $path_dsc . $name_dsc;
            break;
        case NC_FS_SIMPLE:
            $name_dsc = $field_dst . '_' . $message . '.' . $extension;
            copy($path_src, $nc_core->FILES_FOLDER . $name_dsc);
            print $nc_core->FILES_FOLDER . $name_dsc;
            break;
    }

    $nc_core->db->query(
        "UPDATE `Message{$classID}`
         SET `{$field_info_dst['Field_Name']}` = '{$file_info }'
         WHERE `Message_ID` = '{$message}'"
    );

    return 1;
}

/**
 * Сгенерировать имя файла для записи на диск
 *
 * @param string $file_name оригинальное имя файла
 * @param string $path путь к файлу
 * @param array|null $disallow массив строк с недопустимыми именами
 * @return string
 */
function nc_get_filename_for_original_fs($file_name, $path, $disallow = null) {
    $use_index = false; // надо ли к файлу добавлять индекс
    if (!empty($disallow) && in_array($file_name, $disallow, true)) {
        $use_index = true;
    }

    $file_name = nc_transliterate($file_name);
    $file_name = preg_replace('/[^a-z0-9.]+/is', '_', $file_name);
    $file_name = preg_replace('/\.php\d*\b/i', '.phps', $file_name);
    $file_name = preg_replace('/^\./', '0$0', $file_name);
    if (file_exists($path . $file_name)) {
        $use_index = true;
    }

    if (!$use_index) {
        return $file_name;
    }

    $k = 0;
    $extension = pathinfo($file_name, PATHINFO_EXTENSION);

    while (
        file_exists($path . ($temp = pathinfo($file_name, PATHINFO_FILENAME) . '_' . $k . '.' . $extension)) ||
        in_array($temp, (array)$disallow, true)
    ) {
        $k++;
    }
    $file_name = $temp;

    return $file_name;
}
