<?php

/**
 * Класс для получения информации о файлах из полей типа «Файл».
 * Обеспечивает кэширование информации для повторного использования.
 */
class nc_file_info {

    /** @var  string */
    protected $files_folder;

    /**
     * @var array   [component => [name => [field settings]]]
     * @see nc_component::get_fields()
     */
    protected $fields_settings = array();

    /** @var array  "component:object:field" => value */
    protected $field_values = array();

    /** @var array  "component:object:field" => [] */
    protected $filetable_values = array();

    /** @var array  "component:object" => [ User_ID => '', Sub_Class_ID => '' ] */
    protected $object_metadata = array();


    /**
     * Возвращает путь к папке netcat_files (от корня сайта), без слэша на конце
     *
     * @return string
     */
    protected function get_files_folder() {
        // cannot do that in the constructor because $nc_core is not initialized there
        if (!isset($this->files_folder)) {
            $nc_core = nc_core::get_object();
            $this->files_folder = rtrim($nc_core->SUB_FOLDER . $nc_core->HTTP_FILES_PATH, '/\\');
        }
        return $this->files_folder;
    }

    /**
     * Возвращает настройки всех полей типа «файл» указанного компонента
     *
     * @param int|string $component_id
     * @return array
     */
    protected function get_all_fields($component_id) {
        if (!isset($this->fields_settings[$component_id])) {
            $this->fields_settings[$component_id] = array();
            $component = nc_core::get_object()->get_component($component_id);
            $fields = $component->get_all_file_fields();

            foreach ($fields as $field) {
                $this->fields_settings[$component_id][$field['name']] = $field;
            }
        }
        return $this->fields_settings[$component_id];
    }

    /**
     * Возвращает настройки (или одну настройку) поля
     *
     * @param int|string $component_id
     * @param string $field_name
     * @param null|string $option
     * @return array|string
     */
    protected function get_field($component_id, $field_name, $option = null) {
        $fields = $this->get_all_fields($component_id);
        return $option ? $fields[$field_name][$option] : $fields[$field_name];
    }

    /**
     * Возвращает значение поля указанного объекта
     *
     * @param int $component_id
     * @param int $object_id
     * @param string $field_name
     * @param bool $use_cache
     * @return string|null
     */
    protected function get_field_value($component_id, $object_id, $field_name, $use_cache) {
        $cache_key = "$component_id:$object_id:$field_name";

        if (!$component_id || !$object_id || !$field_name) {
            return null;
        }

        if ($use_cache && array_key_exists($cache_key, $this->field_values)) {
            return $this->field_values[$cache_key];
        }

        $nc_core = nc_Core::get_object();
        $db = $nc_core->db;

        if ($nc_core->get_system_table_id_by_name($component_id)) {
            $table = $component_id;
            $pk = $component_id . '_ID';

            $parent_field = '0';
            if ($nc_core->get_component($component_id)->get_field($field_name, 'inheritance')) {
                if ($component_id === 'Subdivision') {
                    $parent_field = '`Parent_Sub_ID`';
                } else if ($component_id === 'Template') {
                    $parent_field = '`Parent_Template_ID`';
                }
            }
        } else {
            $table = 'Message' . (int)$component_id;
            $pk = 'Message_ID';
            $parent_field = '`Parent_Message_ID`';
        }

        list($field_value, $parent_object_id) =
            $db->get_row(
                "SELECT `" . $db->escape($field_name) . "`, $parent_field ".
                "  FROM `$table`" .
                " WHERE `$pk` = " . (int)$object_id,
                ARRAY_N
            )
            ?: array(null, null);

        if (!$field_value && $parent_object_id) {
            $field_value = $this->get_field_value($component_id, $parent_object_id, $field_name, $use_cache);
        }

        $this->field_values[$cache_key] = $field_value;

        return $field_value;
    }

    /**
     * Возвращает значение из таблицы Filetable для указанного поля объекта
     *
     * @param int|string $component_id
     * @param int $object_id
     * @param string $field_name
     * @param bool $use_cache
     * @return array|null
     */
    public function get_filetable_values($component_id, $object_id, $field_name, $use_cache) {
        $cache_key = "$component_id:$object_id:$field_name";
        if (!$use_cache || !array_key_exists($cache_key, $this->filetable_values)) {
            $field_id = (int)$this->get_field($component_id, $field_name, 'id');
            if ($field_id) {
                $this->filetable_values[$cache_key] = nc_db()->get_row(
                    "SELECT * FROM `Filetable`" .
                    " WHERE `Message_ID` = " . (int)$object_id .
                    "   AND `Field_ID` = $field_id",
                    ARRAY_A
                );
            } else {
                $this->filetable_values[$cache_key] = null;
            }
        }

        return $this->filetable_values[$cache_key];
    }

    /**
     * Возвращает массив с информацией о файле
     *
     * @param int|string $component_id
     * @param int $object_id
     * @param string|int $field_name
     * @param bool $include_field_name — если true, то ключи в массиве будут иметь вид FieldName_*
     * @param bool $prefix_with_f      — если true, то ключи в массиве будут иметь вид f_FieldName_*
     * @param bool $use_cache
     * @return array
     *      download_path | FieldName              — ссылка для скачивания под оригинальным именем (ссылка с "h_")
     *      url           | FieldName_url          — путь к файлу от корня сайта
     *      preview_url   | FieldName_preview_url  — путь к превью файла от корня сайта
     *      name          | FieldName_name         — изначальное имя файла
     *      size          | FieldName_size         — размер
     *      type          | FieldName_type         — mime-тип
     *      download      | FieldName_download     — количество скачиваний
     *      fs_type       | FieldName_fs_type      — фактически используемый тип файловой системы,
     *                                               одна из констант NC_FS_ORIGINAL|NC_FS_PROTECTED|NC_FS_SIMPLE
     *      source        | FieldName_source       — nc_image_source
     */
    public function get_file_info($component_id, $object_id, $field_name, $include_field_name = true, $prefix_with_f = false, $use_cache = false) {
        $field_id = is_numeric($field_name) ? $field_name : null;
        // Если передано ID поля, то найдём название поля, иначе - ID поля
        foreach ($this->get_all_fields($component_id) as $field) {
            if ($field_id) {
                if ($field_id == $field['id']) {
                    $field_name = $field['name'];
                    break;
                }
            } else {
                if ($field_name == $field['name']) {
                    $field_id = $field['id'];
                    break;
                }
            }
        }

        if ($include_field_name || $prefix_with_f) {
            $prefix = ($prefix_with_f ? "f_" : "") . $field_name;
            $name_key = "{$prefix}_name";
            $download_path_key = "{$prefix}";
            $path_key = "{$prefix}_url";
            $preview_path_key = "{$prefix}_preview_url";
            $size_key = "{$prefix}_size";
            $type_key = "{$prefix}_type";
            $download_count_key = "{$prefix}_download";
            $fs_type_key = "{$prefix}_fs_type";
        }
        else {
            $name_key = "name";
            $download_path_key = "download_path";
            $path_key = "url";
            $preview_path_key = "preview_url";
            $size_key = "size";
            $type_key = "type";
            $download_count_key = "download";
            $fs_type_key = "fs_type";
        }

        $field_value = $this->get_field_value($component_id, $object_id, $field_name, $use_cache);
        if ($field_value && !is_numeric($field_name)) {
            $filetable_values = $this->get_filetable_values($component_id, $object_id, $field_name, $use_cache);
            $files_folder = $this->get_files_folder();

            if ($filetable_values) { // «Защищённая файловая система»
                $file_info = array(
                    $name_key           => $filetable_values['Real_Name'],
                    $download_path_key  => $files_folder . $filetable_values['File_Path'] . 'h_' . $filetable_values['Virt_Name'],
                    $path_key           => $files_folder . $filetable_values['File_Path'] .        $filetable_values['Virt_Name'],
                    $preview_path_key   => $files_folder . $filetable_values['File_Path'] . 'preview_' .        $filetable_values['Virt_Name'],
                    $size_key           => $filetable_values['File_Size'],
                    $type_key           => $filetable_values['File_Type'],
                    $download_count_key => $filetable_values['Download'],
                    $fs_type_key        => NC_FS_PROTECTED,
                );
            }
            else {
                $field_value_parts = explode(":", $field_value);
                $file_name = $field_value_parts[0];
                if (isset($field_value_parts[3])) {  // «Стандартная файловая система»
                    // OriginalName.jpg:image/jpeg:123456:u/OriginalName.jpg
                    $file_path = $files_folder . '/' . $field_value_parts[3];
                    $parts = explode('/', $file_path);
                    $last_part = count($parts) - 1;
                    $parts[$last_part] = 'preview_' . $parts[$last_part];
                    $preview_file_path = implode('/', $parts);
                    $fs_type = NC_FS_ORIGINAL;
                }
                else { // «Простая файловая система»
                    // OriginalName.jpg:image/jpeg:123456
                    $file_extension = strpos($file_name, ".") ? substr($file_name, strrpos($file_name, ".")) : "";
                    $file_path = $files_folder . '/' .
                                 $this->get_field($component_id, $field_name, 'id') .
                                 "_" .
                                 $object_id .
                                 $file_extension;
                    $preview_file_path = $files_folder . '/' . 'preview_' .
                        $this->get_field($component_id, $field_name, 'id') .
                        "_" .
                        $object_id .
                        $file_extension;
                    $fs_type = NC_FS_SIMPLE;
                }
                $file_info = array(
                    $name_key => $file_name,
                    $download_path_key => $file_path,
                    $path_key => $file_path,
                    $preview_path_key => $preview_file_path,
                    $size_key => $field_value_parts[2],
                    $type_key => $field_value_parts[1],
                    $download_count_key => null,
                    $fs_type_key => $fs_type,
                );
            }
        }
        else { // NO FIELD VALUE
            $file_info = array(
                $name_key => null,
                $download_path_key => null,
                $path_key => null,
                $preview_path_key => null,
                $size_key => null,
                $type_key => null,
                $download_count_key => null,
                $fs_type_key => null,
            );
        }
        $file_path = $file_info[$name_key];
        if ($file_path || nc_is_edit_mode()) {
            $file_extension = $file_info_path = $file_info_url = null;
            if ($file_path) {
                $file_extension = pathinfo($file_path, PATHINFO_EXTENSION);
                $file_info_path = $file_info[$path_key];
                $file_info_url = $file_info[$download_path_key];
            }
            $is_component = is_numeric($component_id);
            $image_path = new nc_image_path_field($file_info_path, $file_extension);
            $image_path
                ->set_entity($is_component ? $component_id : strtolower($component_id))
                ->set_field($field_id)
                ->set_object($object_id)
                ->set_file_url($file_info_url);
            if ($is_component) {
                $image_path
                    ->set_object_infoblock_id($this->get_object_metadata($component_id, $object_id, 'Sub_Class_ID'))
                    ->set_object_user_id($this->get_object_metadata($component_id, $object_id, 'User_ID'));
            }
            $file_info[$download_path_key] = $image_path;
        }
        return $file_info;
    }

    /**
     * Возвращает значения всех переменных, связанных с файловыми полями (в виде f_ИмяПоля_*)
     *
     * @param int|string $component_id
     * @param int $object_id
     * @return array
     */
    public function get_all_object_file_variables($component_id, $object_id) {
        $all_files_info = array();
        $all_field_names = $this->get_all_fields($component_id);
        foreach ($all_field_names as $field_name => $field_settings) {
            $all_files_info += $this->get_file_info($component_id, $object_id, $field_name, true, true, true);
        }
        return $all_files_info;
    }

    /**
     * Возвращает элемент $this->object_metadata (сохраняется в cache_object_data)
     *
     * @param int $component_id
     * @param int $object_id
     * @param string $field
     * @return null|string
     */
    protected function get_object_metadata($component_id, $object_id, $field) {
        if (!isset($this->object_metadata["$component_id:$object_id"][$field])) {
            $this->object_metadata["$component_id:$object_id"][$field] =
                nc_core::get_object()->message->get_by_id($component_id, $object_id, $field);
        }
        return $this->object_metadata["$component_id:$object_id"][$field];
    }

    /**
     * Сохраняет данные о файлах объекта во внутренний кэш для дальнейшего использования.
     *
     * @param int|string $component_id
     * @param array $object_data
     */
    public function cache_object_data($component_id, array $object_data) {
        $fields = $this->get_all_fields($component_id);
        if (!$fields) { return; }

        if (ctype_alpha($component_id)) {
            $id_key = $component_id . "_ID"; // i.e. "User_ID", "Catalogue_ID", ...
        }
        else {
            $id_key = "Message_ID";
        }

        $object_id = isset($object_data[$id_key]) ? (int)$object_data[$id_key] : null;
        if (!$object_id) { return; }

        foreach ($object_data as $field_name => $value) {
            if (isset($fields[$field_name])) {
                $this->field_values["$component_id:$object_id:$field_name"] = $value;
            }
        }

        $this->object_metadata["$component_id:$object_id"] = array(
            'User_ID' => isset($object_data['User_ID']) ? $object_data['User_ID'] : null,
            'Sub_Class_ID' => isset($object_data['Sub_Class_ID']) ? $object_data['Sub_Class_ID'] : null,
        );
    }

    /**
     * Сохраняет данные о файлах объектов из списка во внутренний кэш для дальнейшего использования.
     *
     * @param int|string $component_id
     * @param array $object_list_data
     */
    public function cache_object_list_data($component_id, array $object_list_data) {
        foreach ($object_list_data as $row) {
            $this->cache_object_data($component_id, (array) $row);
        }
    }

    /**
     * Загружает данные из таблицы Filetable для всех объектов с указанными ID.
     *
     * @param int|string $component_id
     * @param array $object_ids
     */
    public function preload_filetable_values($component_id, array $object_ids) {
        $fields = $this->get_all_fields($component_id);
        if (!$fields || !$object_ids) {
            return; // no file fields / no objects
        }

        $empty_values = array();
        $field_ids = array();
        foreach ($fields as $field_name => $field_settings) {
            $field_id = (int)$field_settings['id'];
            if (!$field_id) {
                continue;
            }
            $field_ids[] = $field_id;
            foreach ($object_ids as $object_id) {
                $empty_values["$component_id:$object_id:$field_name"] = null;
            }
        }
        if (!$field_ids) {
            return; // no file fields with IDs
        }
        $field_ids = implode(',', $field_ids);

        $db = nc_db();
        $object_ids = implode(',', $object_ids);
        if (!preg_match('/^[\d,]+$/', $object_ids)) {
            return; // $ids array seems to contain incorrect values
        }

        $this->filetable_values = array_merge(
            $empty_values,
            $this->filetable_values,
            (array)$db->get_results(
                "SELECT ft.*, CONCAT('" . $db->escape($component_id) . "', ':', ft.`Message_ID`, ':', f.`Field_Name`) AS `_Cache_Key`" .
                "  FROM `Filetable` AS ft JOIN `Field` AS f USING (`Field_ID`)" .
                " WHERE ft.`Message_ID` IN ($object_ids)" .
                "   AND ft.`Field_ID` IN ($field_ids)",
                ARRAY_A,
                "_Cache_Key"
            )
        );
    }

    /**
     * Очищает кэш значений файловых полей для записей, значений Filetable, параметров полей
     */
    public function clear_cache() {
        $this->filetable_values = array();
        $this->field_values = array();
        $this->fields_settings = array();
        $this->object_metadata = array();
    }

    /**
     * @param $component_id
     * @param $object_id
     */
    public function clear_object_cache($component_id, $object_id) {
        $component_id = (int)$component_id;
        $object_id = (int)$object_id;
        $prefix = "$component_id:$object_id:";
        $prefix_length = strlen($prefix);

        // array_filter + ARRAY_FILTER_USE_KEY доступны с PHP 5.6
        foreach ($this->field_values as $k => $v) {
            if (substr($k, 0, $prefix_length) === $prefix) {
                unset($this->field_values[$k]);
                unset($this->filetable_values[$k]);
            }
        }
        unset($this->object_metadata["$component_id:$object_id"]);
    }

}