<?php

class nc_Files extends nc_System {

    protected $core;
    protected $tmp_files;
    protected $filetable_ids;

    public function __construct() {
        // load parent constructor
        parent::__construct();
        $this->core = nc_Core::get_object();
        $this->tmp_files = array();
        $this->filetable_ids = array();
    }

    public function create_dir($fullpath) {
        $nc_core = nc_Core::get_object();

        if (is_dir($fullpath)) return true;

        if (($res = mkdir($fullpath, $nc_core->DIRCHMOD, 1))) {
            chmod($fullpath, $nc_core->DIRCHMOD);
        }

        return $res;
    }

    public function delete_dir($path) {
        if (!file_exists($path) || !is_dir($path)) return false;

        if (!is_writable($path)) throw new nc_Exception_Files_Not_Rights($path);

        foreach (scandir($path) as $v) {
            if ($v == "." || $v == "..") continue;

            if (filetype($path . "/" . $v) == "dir") {
                $this->delete_dir($path . "/" . $v);
            } else {
                if (!is_writable($path . "/" . $v))
                    throw new nc_Exception_Files_Not_Rights($path . "/" . $v);
                unlink($path . "/" . $v);
            }
        }

        rmdir($path);

        return true;
    }

    public function save_file($class_id, $field, $message_id, $file) {
        return $this->field_save_file('User', $field, $message_id, $file);
    }

    /**
     * Сохраняет файл в поле типа "6:Файл"
     *
     * @param string|int $class_id - ID компонента или название системной таблицы(User|Catalogue|Subdivision|Template)
     * @param string|int $field    - название или ID поля в которое сохранять
     * @param int $message_id      - ID объекта|пользователя|сайта|раздела|макета
     * @param array|string $file   - массив с данными о файле из $_FILES, либо массив с элементами:
     *      path          — путь к файлу(/netcat/tmp/foto.jpg) или ссылка(http://example.com/foto.jpg)
     *      type          — mime-тип, попытается определить автоматически если не задан, по умолчанию image/jpeg
     *      name          — имя файла, возьмется из ссылки если не задано
     *      folder        — нестандартная папка в которую сохранить файл, только для стандартной ФС
     *             альтернативный вариант - вместо массива строка со значением path
     * @param bool $noеdit         - игнорировать настройки поля изменяющие файл
     * @param bool $message_put    - режим работы внутри системы, дополнительные данные для объекта который еще не создан,
     *                              false при сохранении в существующий объект
     * @param bool $nodelete       - не удалять старый файл
     *
     * @return array               - возвращает массив с данными о сохраненном файле
     *      download_path — ссылка для скачивания под оригинальным именем (ссылка с "h_")
     *      url           — путь к файлу от корня сайта
     *      preview_url   — путь к превью файла от корня сайта
     *      name          — изначальное имя файла
     *      size          — размер
     *      type          — mime-тип
     */
    public function field_save_file($class_id, $field, $message_id, $file, $noedit = false, $message_put = false, $nodelete = false, $is_tmp = false) {
        $DOCUMENT_ROOT = nc_core('DOCUMENT_ROOT');
        $FILES_FOLDER = nc_core('FILES_FOLDER');
        $files_http_path = nc_core('SUB_FOLDER') . nc_core('HTTP_FILES_PATH');
        $db = nc_core('db');

        // *** Проверка входных данных ***

        $message_id = intval($message_id);
        $component = new nc_Component($class_id);
        $system_table_id = $component->get_system_table_id();
        $system_table_name = $system_table_id ? $class_id : '';

        $field_id = null;
        $raw_format = null;
        $field_name = null;


        if ($field === 'ncSMO_Image' || $field === 'ncImage' || $field === 'ncIcon') {
            $fields = array(
               $component->get_smo_image_field(),
               $component->get_nc_image_field(),
               $component->get_nc_icon_field(),
            );
        } else {
            $fields = $component->get_fields(NC_FIELDTYPE_FILE);
        }


        if (!empty($fields)) {
            foreach ($fields as $v) {
                if ($v['id'] == $field || $v['name'] == $field) {
                    $raw_format = $v['format'];
                    $field_id = $v['id'];
                    $field_name = $v['name'];
                }
            }
        }

        $method = __METHOD__ . '()';

        if (!$field_id) {
            trigger_error("$method: wrong component or field", E_USER_WARNING);
            return null; //wrong class or field
        }

        if (!$system_table_id) {
            $msg = $db->get_row("SELECT `Sub_Class_ID`, `Subdivision_ID` FROM `Message{$class_id}` WHERE `Message_ID` = '{$message_id}'", ARRAY_A);
        } else {
            $msg = $db->get_row("SELECT COUNT(*) FROM `{$system_table_name}` WHERE `{$system_table_name}_ID` = {$message_id}", ARRAY_A);
        }
        if ($message_id && empty($msg)) {
            trigger_error("$method: wrong object", E_USER_WARNING);
            return null;  //wrong message
        }
        if (!$message_id && !is_array($message_put)){
            trigger_error("$method: cannot determine the object to save the file to", E_USER_WARNING);
            return null;  //no data for add
        }

        $sub = !empty($msg['Subdivision_ID']) ? $msg['Subdivision_ID'] : (int)nc_array_value($message_put, 'sub');
        $cc  = !empty($msg['Sub_Class_ID'])   ? $msg['Sub_Class_ID']   : (int)nc_array_value($message_put, 'cc');

        $format = nc_field_parse_format($raw_format, NC_FIELDTYPE_FILE);

        if (!is_array($file)) {
            $file = array('path' => $file);
        }

        if (isset($file['path'])) {
            $file_url = $file['path'];
        } elseif (isset($file['url'])) {
            $file_url = $file['url'];
        } else {
            $file_url = null;
        }
        $file_src = nc_array_value($file, 'tmp_name');

        if (!$file_url && !$file_src) {
            trigger_error("$method: no file name", E_USER_WARNING);
            return null; //no file to save
        }

        if (!$file_src && $file_url //файл по ссылке
            && !(preg_match("~^[^=]+://~", $file_url) && $buf = @file_get_contents($file_url)) //по внешней ссылке нет
            && !file_exists($DOCUMENT_ROOT . $file_url)
        ) { //и по внутренней нет
            trigger_error("$method: file not found: " . print_r($file, true), E_USER_WARNING);
            return null;
        }

        // *** Удаление старого файла ***

        if (!$nodelete && $is_tmp === false) {
            DeleteFile($field_id, $field_name, $class_id, $system_table_name, $message_id);
        }

        if (isset($file['name'])) {
            $file_name = $file['name'];
        } else {
            $file_name = array_pop(explode('/', str_replace('\\', '/', $file_url)));
            $file_name = explode('?', $file_name);
            $file_name = $file_name[0];
        }

        // Windows naming convention
        $file_name = str_replace(array('<', '>', ':', '"', '/', chr(0x00), '\\', '|', '?', '*'), '_', $file_name);
        $file_type = nc_array_value($file, 'type');
        // $file_size = $file['size'];
        $folder = trim(nc_array_value($file, 'folder'), '/');
        $ext = substr($file_name, strrpos($file_name, ".")); // расширение файла

        // *** Вычисление имени файла и папки для сохранения ***

        $file_path = null;
        $put_file_name = null;
        $db_string_path = null;

        if (!$system_table_id) {
            $file_path =  "$sub/$cc/";
        } elseif ($system_table_id == 1) {
            $file_path = "c/";
        } elseif ($system_table_id == 3) {
            $file_path = "u/";
        } elseif ($system_table_id == 4) {
            $file_path = "t/";
        } elseif ($system_table_id == 2) {
            $file_path = $message_id . "/";
        }

        switch ($format['fs']) {
            case NC_FS_PROTECTED: // hash
                // имя файла
                $put_file_name = md5($file_name . date("H:i:s d.m.Y") . uniqid("netcat"));
                break;

            case NC_FS_ORIGINAL:
                // пользователь сам указал папку
                if ($folder && preg_match('/^[a-z0-9\/_-]+$/is', $folder) && @$this->create_dir($FILES_FOLDER . $folder . '/')) {
                    $file_path = $folder . '/';
                }

                // сгенерировать имя файла
                $put_file_name = nc_get_filename_for_original_fs($file_name, $FILES_FOLDER . $file_path);

                $db_string_path = ":" . $file_path . $put_file_name;
                break;

            case NC_FS_SIMPLE: // FieldID_MessageID.ext
                $file_path = ''; // в папку netcat_files
                $put_file_name = $field_id . "_" . $message_id . $ext;

                // создание временного файла при добавлении объекта
                // будет переименован после добавления, в field_save_file_afteraction($message)
                if (!$message_id) {
                    $tmp_file['tmp_name'] = 'tmp_' . md5($file_src);
                    $tmp_file['name'] = $field_id . '_%MSG' . $ext;
                    $tmp_file['fld_name'] = $field_name;
                }
                break;
        }

        // *** Сохранение файла на диск ***

        if ($is_tmp === true) {
            $put_file_name = "tmp_" . $put_file_name;
        }

        //итоговый путь куда сохранить файл
        $save_name = isset($tmp_file['tmp_name']) ? $tmp_file['tmp_name'] : $put_file_name;
        $save_path = $FILES_FOLDER . $file_path . $save_name;
        $save_path_preview = $FILES_FOLDER . $file_path . 'preview_' . $save_name;

        @$this->create_dir($FILES_FOLDER . $file_path);
        if ($file_src) {
            @move_uploaded_file($file_src, $save_path);
        } else if (isset($buf)) {
            @file_put_contents($save_path, $buf);
        } else {
            @copy($DOCUMENT_ROOT . $file_url, $save_path);
        }

        // *** Обработка файла ***

        if (!$noedit && @getimagesize($save_path)) {
            $resize_format = nc_field_parse_resize_options($raw_format);

            if ($resize_format['use_preview']) {
                @copy($save_path, $save_path_preview);
                if ($resize_format['preview_use_resize']){
                    @nc_ImageTransform::imgResize($save_path_preview, $save_path_preview, $resize_format['preview_width'], $resize_format['preview_height']);
                }
                if ($resize_format['preview_use_crop']) {
                    @nc_ImageTransform::imgCrop($save_path_preview, $save_path_preview, $resize_format['preview_crop_x0'], $resize_format['preview_crop_y0'], $resize_format['preview_crop_x1'], $resize_format['preview_crop_y1'],
                        NULL, NULL, 0, 0,
                        $resize_format['preview_crop_ignore'] ? $resize_format['preview_crop_ignore_width'] : 0,
                        $resize_format['preview_crop_ignore'] ? $resize_format['preview_crop_ignore_height'] : 0,
                        $resize_format['preview_crop_mode'], $resize_format['preview_crop_width'], $resize_format['preview_crop_height']);
                }
            }

            if ($resize_format['use_resize']) {
                @nc_ImageTransform::imgResize($save_path, $save_path, $resize_format['resize_width'], $resize_format['resize_height']);
            }

            if ($resize_format['use_crop']) {
                @nc_ImageTransform::imgCrop($save_path, $save_path, $resize_format['crop_x0'], $resize_format['crop_y0'], $resize_format['crop_x1'], $resize_format['crop_y1'],
                    NULL, NULL, 0, 0,
                    $resize_format['crop_ignore'] ? $resize_format['crop_ignore_width'] : 0,
                    $resize_format['crop_ignore'] ? $resize_format['crop_ignore_height'] : 0,
                    $resize_format['crop_mode'], $resize_format['crop_width'], $resize_format['crop_height']);
            }
        }
        clearstatcache();
        $file_size = @filesize($save_path);
        $file_type = $file_type ? trim($file_type, '"') : nc_file_mime_type($save_path);

        if ($file_type === 'image/svg+xml' || $file_type === 'image/svg-xml' || strtolower($ext) === 'svg') {
            nc_sanitize_svg_file($save_path);
        }

        // *** Сохранение и выходные данные ***

        $result = array();

        // для защищенной надо добавить файл в Filetable
        if ($format['fs'] == NC_FS_PROTECTED && $is_tmp === false) {
            $query = $db->query(
                "INSERT INTO `Filetable`
                            (`Real_Name`, `File_Path`, `Virt_Name`, `File_Type`, `File_Size`, `Field_ID`, `Content_Disposition`, `Message_ID`)
                     VALUES ('" . $db->escape($file_name) . "', '/" . $db->escape($file_path) . "', '" . $db->escape($put_file_name) . "', '" . $db->escape($file_type) . "',
                            '" . intval($file_size) . "', '" . intval($field_id) . "', '" . intval($format['disposition']) . "', '" . $message_id . "')"
            );

            if ($query) {
                $result['download_path'] = $files_http_path . $file_path . 'h_' . $put_file_name;
                if (!$message_id) {
                    //после добавления объекта нужно обновить таблицу
                    $this->filetable_ids[] = $db->insert_id;
                }
            }
        }

        $db_string = $file_name . ":" . $file_type . ":" . $file_size . $db_string_path;

        if (!$message_put && $is_tmp === false) { //запишем в объект сущности
            $db->query("UPDATE `" . ($system_table_id ? $system_table_name : "Message" . $class_id) . "`
                        SET `{$field_name}` = '" . $db->escape($db_string) . "'
                        WHERE `" . ($system_table_id ? $system_table_name : "Message") . "_ID` = {$message_id}");
            $this->core->file_info->clear_object_cache($class_id, $message_id);
        }

        $result['url']          = $files_http_path . $file_path . $put_file_name;
        $result['preview_url']  = $files_http_path . $file_path . 'preview_' . $put_file_name;
        $result['name']         = $file_name;
        $result['size']         = $file_size;
        $result['type']         = $file_type;
        $result['fldValue']     = $db_string;

        if (!empty($tmp_file)) { //данные о временном файле
            $this->tmp_files[] = $tmp_file;
        }

        return $result;
    }

    public function field_save_file_afteraction($message) {
        $extract_fields = array();
        foreach ($this->tmp_files as $tmp_file) {
            $tmp_file['name'] = str_replace('%MSG', $message, $tmp_file['name']);

            $dest_file = nc_core('FILES_FOLDER') . $tmp_file['name'];
            $dest_file_rpeview = nc_core('FILES_FOLDER') . 'preview_' . $tmp_file['name'];

            @rename(nc_core('FILES_FOLDER') . $tmp_file['tmp_name'], $dest_file);
            @rename(nc_core('FILES_FOLDER') . 'preview_' . $tmp_file['tmp_name'], $dest_file_rpeview);
            @chmod($dest_file, nc_core('FILECHMOD'));
            @chmod($dest_file_rpeview, nc_core('FILECHMOD'));

            $files_http_path = nc_core('SUB_FOLDER') . nc_core('HTTP_FILES_PATH');
            $extract_fields['f_'.$tmp_file['fld_name'].'_url'] = $files_http_path . $tmp_file['name'];
            $extract_fields['f_'.$tmp_file['fld_name'].'_preview_url'] = $files_http_path . 'preview_' . $tmp_file['name'];
        }
        $this->tmp_files = array();

        if (!empty($this->filetable_ids)) {
            nc_db()->query("UPDATE `Filetable` SET `Message_ID` = '".$message."'
                            WHERE `ID`  IN(".join(',', $this->filetable_ids).")");
            $this->filetable_ids = array();
        }

        return $extract_fields;
    }

}
