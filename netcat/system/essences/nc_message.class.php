<?php

class nc_Message extends nc_essence {

    protected $db;

    /**
     * Constructor function
     */
    public function __construct() {
        // load parent constructor
        parent::__construct();

        // system superior object
        $nc_core = nc_Core::get_object();
        // system db object
        if (is_object($nc_core->db)) $this->db = $nc_core->db;

        $this->register_event_listeners();
    }

    /**
     * Обработчики для обновления и сброса кэша
     */
    protected function register_event_listeners() {
        $event = nc_core::get_object()->event;
        $on_change = array($this, 'update_cache_on_change');
        $event->add_listener(nc_event::AFTER_OBJECT_CREATED, $on_change);
        $event->add_listener(nc_event::AFTER_OBJECT_UPDATED, $on_change);
        $event->add_listener(nc_event::AFTER_OBJECT_ENABLED, $on_change);
        $event->add_listener(nc_event::AFTER_OBJECT_DISABLED, $on_change);
        $event->add_listener(nc_event::AFTER_OBJECT_DELETED, $on_change);
    }


    public function get_current($item = "") {
        // dummy
        return false;
    }

    public function set_current_by_id($id, $reset = false) {
        // dummy
        return false;
    }

    /**
     * @param int $class_id
     * @param int $id
     * @param string $item
     * @param bool $reset
     * @return array|string
     */
    public function get_by_id($class_id, $id, $item = "", $reset = false) {
        // validate parameters
        $class_id = intval($class_id);
        $id = intval($id);

        // check inited object
        if (empty($this->data[$class_id][$id]) || $reset) {
            nc_core::get_object()->clear_cache_on_low_memory();
            $this->data[$class_id][$id] = $this->db->get_row("SELECT * FROM `".$this->essence.$class_id."`
      WHERE `".$this->essence."_ID` = '".$id."'", ARRAY_A);
        }

        // if item requested return item value
        if ($item && is_array($this->data[$class_id][$id])) {
            return array_key_exists($item, $this->data[$class_id][$id]) ? $this->data[$class_id][$id][$item] : "";
        }

        return $this->data[$class_id][$id];
    }

    /**
     * Функция для удаления объектов компонентов
     * @param int|int[] $ids - идентификаторы объектов подлежащих удалению в виде массива, или цифры
     * @param int $class_id номер компонента, в котором производится удаление
     * @param bool $trash параметр, определяющий, будет ли объект помещен в корзину перед удалением.
     * true - объект будет помещен в корзину, затем удален,
     * false - объект будет удален.
     * @return int количество удалённых записей
     * @throws nc_Exception_DB_Error
     */
    public function delete_by_id($ids, $class_id = 0, $trash = false) {
        $nc_core = nc_Core::get_object();

        // validate parameters
        $classID = (int)$class_id;

        // Приводим первый параметр к массиву
        if (!is_array($ids)) {
            $messages_to_delete = array((int)$ids);
        } else {
            $messages_to_delete = array_map('intval', $ids);
        }

        if (empty($messages_to_delete)) {
            return 0;
        }

        // Выясняем данные по всем объектам для удаления - номера разделов,
        // компонентов в разделе и номера сайтов, а также формируем массив номеров удаляемых объектов

        $message_list = $this->db->get_results(
            "SELECT m.*, 
                    m.`Message_ID` AS `m_id`, 
                    m.`Subdivision_ID` AS `sub_id`, 
                    m.`Sub_Class_ID` AS `cc_id`, 
                    i.`Catalogue_ID` AS `cat_id`
             FROM `Message{$classID}` AS m,
                  `Sub_Class` AS i
             WHERE m.`Sub_Class_ID` = i.`Sub_Class_ID`
               AND m.`Message_ID` IN (" . implode(',', $messages_to_delete). ')',
            ARRAY_A
        );

        $message_to_delete_ids = array();

        if (empty($message_list)) {
            return 0;
        }

        $message_stash = array();

        foreach ($message_list as $message_item) {
            $message_catalogue = (int)$message_item['cat_id'];
            $message_sub = (int)$message_item['sub_id'];
            $message_cc = (int)$message_item['cc_id'];
            $message_id = (int)$message_item['m_id'];

            $message_stash_key = $message_catalogue . ':' . $message_sub . ':' . $message_cc;
            if (!isset($message_stash[$message_stash_key])) {
                $message_stash[$message_stash_key] = array(
                    'site_id' => $message_catalogue,
                    'sub_id' => $message_sub,
                    'cc_id' => $message_cc,
                    'messages_in_cc' => array(),
                    'message_list' => array()
                );
            }

            $message_stash[$message_stash_key]['messages_in_cc'][] = $message_id;
            $message_stash[$message_stash_key]['message_list'][$message_id] = $message_item;

            $message_to_delete_ids[] = $message_id;
        }

        if ($message_stash) {
            foreach ($message_stash as $message_block_info) {
                $nc_core->event->execute(
                    nc_Event::BEFORE_OBJECT_DELETED,
                    $message_block_info['site_id'],
                    $message_block_info['sub_id'],
                    $message_block_info['cc_id'],
                    $classID,
                    $message_block_info['messages_in_cc'],
                    $message_block_info['message_list']
                );
            }
        }

        $message_to_delete_ids_string = implode(',', $message_to_delete_ids);

        if ($trash) {
            try {
                $nc_core->trash->add($messages_to_delete, $classID);
            } catch (nc_Exception_Trash_Full $e) {
                $trash = false;
            } catch (nc_Exception_Trash_Folder_Fail $e) {
                $trash = false;
            } catch (nc_Exception_Trash_Already_Exists $e) {
                $trash = false;
            }
        }

        // Удаляем комментарии
        if (nc_module_check_by_keyword('comments')) {
            include_once nc_module_folder('comments') . 'function.inc.php';
            // get need ids
            $comments_temp = $this->db->get_results(
                "SELECT `Message_ID`, `Sub_Class_ID`
                 FROM `Message{$classID}`
                 WHERE `Message_ID` IN ({$message_to_delete_ids_string})
                 OR `Parent_Message_ID` IN ({$message_to_delete_ids_string})", ARRAY_A);

            // compile arrays
            $temp_messages = array();
            $temp_ccs = array();
            foreach ((array) $comments_temp AS $comments_temp_value) {
                if (!in_array($comments_temp_value['Message_ID'], $temp_messages, true)) {
                    $temp_messages[] = $comments_temp_value['Message_ID'];
                }
                if (!in_array($comments_temp_value['Sub_Class_ID'], $temp_ccs, true)) {
                    $temp_ccs[] = $comments_temp_value['Sub_Class_ID'];
                }
            }

            nc_comments::dropComments($this->db, $temp_ccs, 'Sub_Class', $temp_messages);
            nc_comments::dropRuleMessage($this->db, $temp_messages, $temp_ccs);
            unset($comments_temp, $temp_ccs, $temp_messages);
        }

        // delete related files
        // поочередно удаляем файлы у всех перечисленных к удалению объектов
        // если они не отправляются в корзину, иначе файлам ставим метку deleted
        if (!$trash) {
            foreach ($message_to_delete_ids as $id) {
                DeleteMessageFiles($classID, $id);
            }
        } else {
            $nc_core->trash->TrashMessageFiles($classID, $message_to_delete_ids);
        }

        $this->db->query("DELETE FROM `Message{$classID}` WHERE `Message_ID` IN ({$message_to_delete_ids_string})");
        $affected = $this->db->rows_affected;

        if ($this->db->is_error) {
            throw new nc_Exception_DB_Error($this->db->last_query, $this->db->last_error);
        }

        if ($message_stash) {
            foreach ($message_stash as $message_block_info) {
                $nc_core->event->execute(
                    nc_Event::AFTER_OBJECT_DELETED,
                    $message_block_info['site_id'],
                    $message_block_info['sub_id'],
                    $message_block_info['cc_id'],
                    $classID,
                    $message_block_info['messages_in_cc'],
                    $message_block_info['message_list']
                );
            }
        }

        // Удаление блоков страницы полного вывода
        foreach ($message_to_delete_ids as $object_id) {
            $full_page_area_keyword = $nc_core->get_component($classID)->get_full_page_area_keyword($object_id);
            $nc_core->sub_class->delete_by_area_keyword(0, $full_page_area_keyword);
        }

        // Удаление дочерних объектов
        $children_ids = $this->db->get_col(
            "SELECT `Message_ID`
             FROM `Message{$classID}`
             WHERE `Parent_Message_ID` IN ({$message_to_delete_ids_string})"
        );

        if ($this->db->is_error) {
            throw new nc_Exception_DB_Error($this->db->last_query, $this->db->last_error);
        }

        if (!empty($children_ids)) {
            $affected += $this->delete_by_id($children_ids, $classID, $trash);
        }

        return $affected;
    }

    /**
     * Создание объекта
     * @param $infoblock_id
     * @param array $object_properties
     * @return int ID созданного объекта
     * @throws Exception
     */
    public function create($infoblock_id, array $object_properties) {
        $nc_core = nc_core::get_object();
        $now = new nc_db_expression('NOW()');

        $infoblock_id = (int)$infoblock_id;
        $component_id = $nc_core->sub_class->get_by_id($infoblock_id, 'Class_ID');
        $subdivision_id = $nc_core->sub_class->get_by_id($infoblock_id, 'Subdivision_ID');
        $site_id = $nc_core->sub_class->get_by_id($infoblock_id, 'Catalogue_ID');
        $user_id = nc_array_value($GLOBALS, 'AUTH_USER_ID', 0);

        $table = nc_db_table::make('Message' . $component_id, 'Message_ID');
        if (!isset($object_properties['Priority'])) {
            $priority = 1 + $nc_core->db->get_var(
                "SELECT MAX(`Priority`)
                   FROM `Message$component_id`
                  WHERE `Sub_Class_ID` = $infoblock_id
                    AND `Parent_Message_ID` = " . nc_array_value($object_properties, 'Parent_Message_ID', 0)
            );
        } else {
            $priority = $object_properties['Priority'];
        }

        $component = $nc_core->get_component($component_id);
        $files = array();
        foreach ($component->get_all_file_fields() as $file_field) {
            $file_field_name = $file_field['name'];
            if (isset($object_properties[$file_field_name])) {
                $files[$file_field_name] = $object_properties[$file_field_name];
                unset($object_properties[$file_field_name]);
            }
        }

        $multiple_files = array();
        foreach ($component->get_fields(NC_FIELDTYPE_MULTIFILE) as $file_field) {
            $file_field_name = $file_field['name'];
            if (isset($object_properties[$file_field_name])) {
                $multiple_files[$file_field_name] = $object_properties[$file_field_name];
                unset($object_properties[$file_field_name]);
            }
        }

        unset($object_properties['Message_ID'], $object_properties['Subdivision_ID'],  $object_properties['Sub_Class_ID']);

        $object_properties += array(
            'User_ID' => $user_id,
            'Subdivision_ID' => $subdivision_id,
            'Sub_Class_ID' => $infoblock_id,
            'Priority' => $priority,
            'Checked' => 1,
            'IP' => $_SERVER['REMOTE_ADDR'],
            'UserAgent' => $_SERVER['HTTP_USER_AGENT'],
            'Created' => $now,
        );

        $nc_core->event->execute(nc_event::BEFORE_OBJECT_CREATED, $site_id, $subdivision_id, $infoblock_id, $component_id, null);
        $object_id = $table->insert($object_properties);

        if (!$object_id) {
            throw new Exception("Unable to create object:\n" . $nc_core->db->last_error);
        }

        foreach ($files as $field_name => $file_path) {
            $nc_core->files->field_save_file($component_id, $field_name, $object_id, $file_path);
        }

        if ($multiple_files) {
            $this->save_new_object_multifield_files($multiple_files, $component_id, $object_id);
        }

        $nc_core->event->execute(nc_event::AFTER_OBJECT_CREATED, $site_id, $subdivision_id, $infoblock_id, $component_id, $object_id);

        return $object_id;
    }

    /**
     * Сохраняет файлы в поля множественной загрузки для только что созданного объекта
     * (предназначен только для использования в методе create()).
     *
     * @param array $multiple_files
     *    Массив: ключ — имя поля, значения — массив, элементы которого могут быть:
     *    — строкой — путь к файлу от корня CMS (относительные пути с '../' не допускаются)
     *    — nc_multifield_file
     *    — массивом с элементами 'name', 'path'
     * @param int $component_id
     * @param int $object_id
     */
    protected function save_new_object_multifield_files(array $multiple_files, $component_id, $object_id) {
        $component = nc_core::get_object()->get_component($component_id);
        foreach ($multiple_files as $field_name => $files) {
            $multifield_data = $component->get_field($field_name);
            $multifield = new nc_multifield($field_name, $multifield_data['name'], $multifield_data['format'], $multifield_data['id'], $component_id);

            $priority = 0;
            foreach ($files as $file) {
                $file_data = array(
                    'field_id' => $multifield->id,
                    'object_id' => $object_id,
                    'priority' => $priority++,
                );

                if ($file instanceof nc_multifield_file || is_array($file)) {
                    $file_data['name'] = nc_array_value($file, 'name');
                    $file_data['source_file'] = nc_array_value($file, 'path');
                } else if (is_string($file)) { // только путь к файлу от корня CMS
                    $file_data['source_file'] = $file;
                } else {
                    trigger_error("Wrong multiple file value for $field_name: $file", E_USER_WARNING);
                }

                $multifield_file = new nc_multifield_file($file_data);
                $multifield_file->set_multifield($multifield);
                $multifield_file->save();
            }
        }
    }

    /**
     * Возвращает данные об объекте в виде массива, пригодного для создания нового объекта методом create()
     *
     * @param int $component_id
     * @param int $object_id
     * @param array $fields_map
     * @return array
     * @throws Exception
     */
    protected function get_data_for_duplication($component_id, $object_id, array $fields_map = array()) {
        $nc_core = nc_core::get_object();

        $component_id = (int)$component_id;
        $component = $nc_core->get_component($component_id);

        $object_id = (int)$object_id;
        $object_properties = $nc_core->db->get_row(
            "SELECT * FROM `Message{$component_id}` WHERE `Message_ID` = $object_id",
            ARRAY_A
        );

        if (!$object_properties) {
            throw new Exception("Object $object_id does not exist in component $component_id");
        }

        unset(
            $object_properties['Message_ID'],
            $object_properties['Sub_Class_ID'],
            $object_properties['Subdivision_ID'],
            $object_properties['Priority'],

            $object_properties['Created'],
            $object_properties['User_ID'],
            $object_properties['UserAgent'],
            $object_properties['IP'],

            $object_properties['LastUpdated'],
            $object_properties['LastUser_ID'],
            $object_properties['LastUserAgent'],
            $object_properties['LastIP']
        );

        // Файлы в полях типа «файл»
        $nc_core->file_info->cache_object_data($component_id, $object_properties);
        $file_fields = $component->get_all_file_fields();
        foreach ($file_fields as $file_field) {
            $file_field_name = $file_field['name'];
            $file_data = $nc_core->file_info->get_file_info($component_id, $object_id, $file_field_name, false);
            if ($file_data['url']) {
                $object_properties[$file_field_name] = array(
                    'path' => $file_data['url'],
                    'type' => $file_data['type'],
                    'name' => $file_data['name'],
                );
            } else {
                $object_properties[$file_field_name] = null;
            }
        }

        // Файлы в полях «множественной загрузки»
        $multifile_field_values = nc_get_multifile_field_values($component_id, $object_id);
        if ($multifile_field_values) {
            /** @var nc_multifield $file_data */
            foreach ($multifile_field_values as $file_field_name => $file_data) {
                $object_properties[$file_field_name] = $file_data->records;
            }
        }

        // Маппинг полей (используется при сохранении объекта в другой компонент)
        foreach ($fields_map as $from => $to) {
            if (array_key_exists($from, $object_properties) && $to) {
                $object_properties[$to] = $object_properties[$from];
            }
            unset($object_properties[$from]);
        }

        return $object_properties;
    }

    /**
     * Общая функциональность копирования объектов для методов duplicate() и duplicate_to_another_component()
     *
     * @param $component_id
     * @param $object_id
     * @param $destination_infoblock_id
     * @param array $fields_map
     * @return int
     * @throws nc_Exception_Class_Doesnt_Exist
     */
    protected function create_duplicate($component_id, $object_id, $destination_infoblock_id, array $fields_map = array()) {
        $nc_core = nc_core::get_object();
        $component_id = (int)$nc_core->component->get_by_id($component_id, 'Class_ID');
        $object_id = (int)$object_id;

        $object_properties = $this->get_data_for_duplication($component_id, $object_id, $fields_map);

        // Если у исходного объекта кастомная страница полного вывода, что унаследовано от родительского инфоблока,
        // а инфоблок-приёмник имеет другие настройки для страниц полного вывода,
        // то у копии устанавливаем явный ncFullPageMode = custom:
        $object_own_full_page_mode = $object_properties['ncFullPageMode'];
        $object_effective_full_page_mode = $this->get_full_page_mode($component_id, $object_id);
        if (
            $object_own_full_page_mode === 'inherit' &&
            $object_effective_full_page_mode === 'custom' &&
            $nc_core->sub_class->get_by_id($destination_infoblock_id, 'FullPageMode') !== 'custom'
        ) {
            $object_properties['ncFullPageMode'] = 'custom';
        }

        $new_object_id = $this->create($destination_infoblock_id, $object_properties);

        if ($this->get_full_page_mode($component_id, $new_object_id) === 'custom') {
            $this->duplicate_full_page_blocks($component_id, $object_id, $new_object_id);
        }

        $children = $nc_core->db->get_col("SELECT `Message_ID` FROM `Message$component_id` WHERE `Parent_Message_ID` = $object_id");
        foreach ($children as $child_id) {
            $child_properties = $this->get_data_for_duplication($component_id, $child_id, $fields_map);
            $child_properties['Parent_Message_ID'] = $new_object_id;
            $new_child_id = $this->create($destination_infoblock_id, $child_properties);

            if ($this->get_full_page_mode($component_id, $new_child_id) === 'custom') {
                $this->duplicate_full_page_blocks($component_id, $child_id, $new_child_id);
            }
        }

        return $new_object_id;
    }

    /**
     * Возвращает тип страницы полного вывода
     *
     * @param int $component_id
     * @param int $object_id
     * @param array $object_data если загружены свойства объекта, нужно передать их сюда, чтобы не делать новый запрос к БД.
     *      Должны быть элементы: Sub_Class_ID, Parent_Message_ID, ncFullPageMode
     * @return string|null
     * @throws Exception
     * @internal не является частью публичного API
     */
    public function get_full_page_mode($component_id, $object_id, array $object_data = array()) {
        $component_id = (int)$component_id;
        $object_id = (int)$object_id;

        $has_all_data = isset(
            $object_data['Sub_Class_ID'],
            $object_data['Parent_Message_ID'],
            $object_data['ncFullPageMode']
        );

        if (!$has_all_data) {
            $object_data = $this->get_by_id($component_id, $object_id);
        }

        $mode = $object_data['ncFullPageMode'];

        if ($mode === 'inherit') {
            if ($object_data['Parent_Message_ID']) {
                return $this->get_full_page_mode($component_id, $object_data['Parent_Message_ID']);
            }

            $mode = nc_core::get_object()->sub_class->get_by_id($object_data['Sub_Class_ID'], 'FullPageMode');
        }

        return $mode;
    }

    /**
     * Дублирование блоков страницы полного вывода объекта при копировании объекта
     *
     * @param int $component_id
     * @param int $source_object_id
     * @param int $copy_object_id
     * @return void
     * @throws Exception
     */
    protected function duplicate_full_page_blocks($component_id, $source_object_id, $copy_object_id) {
        $nc_core = nc_core::get_object();
        $component = $nc_core->get_component($component_id);
        $source_area_keyword = $component->get_full_page_area_keyword($source_object_id);
        $full_page_blocks = $nc_core->sub_class->get_by_area_keyword($source_area_keyword);

        if ($full_page_blocks) {
            $copy_area_keyword = $component->get_full_page_area_keyword($copy_object_id);
            $destination = array('AreaKeyword' => $copy_area_keyword);
            foreach ($full_page_blocks as $block_data) {
                $nc_core->sub_class->duplicate($block_data['Sub_Class_ID'], $destination);
            }
        }
    }

    /**
     * Копирование объекта
     *
     * @param int|string $component_id ID компонента или ключевое слово
     * @param int $object_id
     * @param int $destination_infoblock_id
     * @return int ID созданного объекта
     * @throws Exception
     * @throws nc_Exception_Class_Doesnt_Exist
     */
    public function duplicate($component_id, $object_id, $destination_infoblock_id) {
        $nc_core = nc_core::get_object();
        if ($nc_core->sub_class->get_by_id($destination_infoblock_id, 'Class_ID') != $component_id) {
            throw new Exception('Destination infoblock belongs to different component');
        }
        return $this->create_duplicate($component_id, $object_id, $destination_infoblock_id);
    }

    /**
     * Копирование объекта с конвертированием в этот или другой компонент с сопоставлением полей
     *
     * @param string|int $component_id
     * @param int $object_id
     * @param int $destination_infoblock_id
     * @param array $fields_map сопоставление названий полей (название поля в источнике => название поля в приёмнике или null)
     * @return int
     * @throws Exception
     * @throws nc_Exception_Class_Doesnt_Exist
     */
    public function duplicate_to_another_component($component_id, $object_id, $destination_infoblock_id, array $fields_map) {
        return $this->create_duplicate($component_id, $object_id, $destination_infoblock_id, $fields_map);
    }

    /**
     * Создаёт запись-«заглушку».
     * Если в папке компонента есть RecordMockData.html, он должен вернуть массив
     * со значениями полей (для каждого поля может быть задан массив, тогда значение
     * поля будет выбрано из значений массива случайным образом)
     * @param $infoblock_id
     * @return int ID записи
     * @throws Exception
     */
    public function create_mock($infoblock_id) {
        $nc_core = nc_core::get_object();
        $component_id = $nc_core->sub_class->get_by_id($infoblock_id, 'Class_ID');

        $fields = $nc_core->get_component($component_id)->get_fields();
        $object_properties = array();
        foreach ($fields as $field) {
            if ($field['not_null'] && !$field['default']) {
                switch ($field['type']) {
                    case NC_FIELDTYPE_BOOLEAN:
                    case NC_FIELDTYPE_INT:
                    case NC_FIELDTYPE_FLOAT:
                        $value = 0;
                        break;
                    case NC_FIELDTYPE_DATETIME:
                        $value = date("Y-m-d H:i:s");
                        break;
                    default:
                        $value = '';
                        break;
                }
                $object_properties[$field['name']] = $value;
            }
        }

        if ($nc_core->component->get_by_id($component_id, 'File_Mode')) {
            $component_template_id = $nc_core->sub_class->get_by_id($infoblock_id, 'ClassTemplate');
            $mock_data = $this->get_mock_data_file($component_template_id ?: $component_id);
            if ($mock_data) {
                foreach ($mock_data as $key => $value) {
                    if (is_array($value)) {
                        $mock_data[$key] = $value[rand(0, count($value) - 1)];
                    }
                }
                $object_properties = array_merge($object_properties, $mock_data);
            }
        }

        return $this->create($infoblock_id, $object_properties);
    }

    /**
     * @param $component_template_id
     * @return array|null
     */
    protected function get_mock_data_file($component_template_id) {
        $nc_core = nc_core::get_object();
        $path =
            $nc_core->CLASS_TEMPLATE_FOLDER .
            $nc_core->component->get_by_id($component_template_id, 'File_Path') .
            'RecordMockData.html';

        if (file_exists($path)) {
            $mock_data = include $path;
            if (is_array($mock_data)) {
                return $mock_data;
            }
        }

        $component_id = $nc_core->component->get_by_id($component_template_id, 'ClassTemplate');
        if ($component_id) {
            return $this->get_mock_data_file($component_id);
        }

        return null;
    }

    /**
     * @param $site_id
     * @param $subdivision_id
     * @param $infoblock_id
     * @param $component_id
     * @param $object_id
     */
    public function update_cache_on_change($site_id, $subdivision_id, $infoblock_id, $component_id, $object_id) {
        nc_core::get_object()->file_info->clear_object_cache($component_id, $object_id);
        foreach ((array)$object_id as $id) {
            unset($this->data[$component_id][$id]);
        }
    }

    public function get_object_name($class_id, $message_id) {
        $nc_core = nc_Core::get_object();
        $message = $this->get_by_id($class_id, $message_id);

        try {
            $class_template_id = $nc_core->sub_class->get_by_id($message['Sub_Class_ID'], 'Class_Template_ID');
        } catch (Exception $e) {
            $class_template_id = $class_id;
        }

        $component = nc_Core::get_object()->get_component($class_template_id ?: $class_id);
        $field_with_object_name = $component->get_possible_object_name_field();

        $object_name = $this->get_by_id($class_id, $message_id, $field_with_object_name);

        if ($field_with_object_name === 'Message_ID' || !$object_name) {
            return sprintf($component->get_default_object_name_template(), $message_id);
        }

        return sprintf($component->get_object_name_template(), $object_name);
    }

}
