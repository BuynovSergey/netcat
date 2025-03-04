<?php

abstract class nc_version_record extends nc_record {

    const INITIAL = 'initial';
    const CREATED = 'created';
    const UPDATED = 'updated';
    const ENABLED = 'enabled';
    const DISABLED = 'disabled';
    const DELETED = 'deleted';

    /** @var array поля, которые не добавляются в результат метода get_changes() */
    static protected $ignore_field_changes = array();

    /** @var string таблица, в которой хранится основная запись (например, 'Message123') */
    protected $entity_table;

    protected $mapping = false;
    protected $primary_key = 'Version_ID';
    protected $table_name = 'Version';

    protected $properties = array(
        'Version_ID' => null,
        'Version_Changeset_ID' => null,
        'Previous_Version_ID' => null,
        'Restored_Version_ID' => null,
        'Entity' => '',
        'Catalogue_ID' => null,
        'Subdivision_ID' => null,
        'Sub_Class_ID' => null,
        'Class_ID' => null,
        'Message_ID' => null,
        'Timestamp' => '',
        'User_ID' => null,
        'Action' => '',
        'IsActual' => 1,
        'Snapshot' => array(),
    );

    protected $serialized_properties = array('Snapshot');

    /** @var nc_version_file_collection */
    protected $files;

    /**
     * @var int|null ID версии, которая сейчас восстанавливается через restore()
     * (к сожалению, в текущей версии нельзя передавать дополнительные данные в событиях, поэтому приходится сохранять здесь)
     */
    static protected $currently_restored_version_id;

    /**
     * @param string $query
     * @return nc_version_record
     */
    public static function load_by_query($query) {
        $row = nc_db()->get_row($query, ARRAY_A);
        if (!$row) {
            throw new nc_record_exception("object not found");
        }

        /** @var self $record */
        $class = 'nc_version_record_' . $row['Entity'];
        $record = new $class;
        return $record->set_values_from_database_result($row);
    }

    /**
     * @param string $property
     * @param mixed $value
     * @param bool $add_new_property
     * @return $this
     * @throws nc_record_exception
     */
    public function set($property, $value, $add_new_property = false) {
        // Санирование значений полей с идентификаторами
        if (substr($property, -3) === '_ID') {
            $value = (int)$value;
        }

        // Проверка значения Entity (должно соответствовать текущему классу)
        if ($property === 'Entity') {
            if (get_class($this) !== 'nc_version_record_' . $value) {
                throw new nc_record_exception("Wrong 'Entity' value '$value' for " . get_class($this));
            }
        }

        parent::set($property, $value, $add_new_property);
        return $this;
    }

    /**
     * @param array $values
     * @return $this
     */
    public function set_values_from_database_result(array $values) {
        parent::set_values_from_database_result($values);
        $this->normalize_snapshot_data();
        return $this;
    }

    /**
     *
     */
    protected function normalize_snapshot_data() {
        // Массив в Snapshot может быть либо массив с полями одной записи (до 6.1.0.22122),
        // либо массив для нескольких записей (где индекс — primary key).
        // «Нормализуем» старый вариант в новый (массив для нескольких записей):
        $normalized_data = array();

        if (!empty($this->properties['Snapshot'])) {
            foreach ($this->properties['Snapshot'] as $table_name => $data) {
                $primary_key = $this->get_snapshot_table_primary_key($table_name);
                if (!is_numeric(key($data))) {
                    $key = isset($data[$primary_key]) ? $data[$primary_key] : $this->get_entity_id();
                    $normalized_data[$table_name] = array($key => $data);
                } else {
                    $normalized_data[$table_name] = $data;
                }
            }

            $this->properties['Snapshot'] = $normalized_data;
        }
    }

    /**
     * @return $this
     */
    public function save() {
        $nc_core = nc_core::get_object();
        $is_new = !$this->get_id();
        $opened_own_changeset = false;

        if ($is_new) {
            $this->set('Previous_Version_ID', $this->find_previous_version_id());
            $this->set('Restored_Version_ID', self::get_restored_version_id());
            $this->set('Snapshot', array_filter($this->get_entity_snapshot_data()));

            // вызываем получение описания изменений после set('Snapshot'), чтобы были загружены необходимые данные
            $changeset_id = $nc_core->version->get_changeset_id();
            if (!$changeset_id) {
                $changeset_id = $nc_core->version->init_changeset($this->generate_description());
                $opened_own_changeset = true;
            }
            $this->set('Version_Changeset_ID', $changeset_id);
        }

        parent::save();

        if ($this->get('IsActual')) {
            $this->update_other_records_is_actual();
        }

        if ($is_new) {
            $nc_core->db->query(
                "UPDATE `Version_Changeset` 
                    SET `ChangeCount` = `ChangeCount` + 1 
                  WHERE `Version_Changeset_ID` = " . $this->get('Version_Changeset_ID')
            );

            $files = $this->get_entity_current_files();
            if ($files) {
                $files_collection = nc_version_file_collection::from_array($files, $this);
                $files_collection->each('save');
                $this->set_files($files_collection);
            }

            nc_version_history::update($this);
        }

        if ($opened_own_changeset) {
            $nc_core->version->commit_changeset();
        }

        return $this;
    }

    /**
     * @return int
     */
    protected function find_previous_version_id() {
        $previous_version_id = $this->get('Previous_Version_ID');
        if ($previous_version_id === null) {
            $previous_version_id = nc_db()->get_var(
                "SELECT `Version_ID`
                   FROM `Version`
                  WHERE {$this->get_same_item_query_condition()}
                    AND `IsActual` = 1"
            ) ?: 0;
        }
        return $previous_version_id;
    }

    /**
     * Возвращает условия для выборки записей с теми же ID
     * @return string
     */
    protected function get_same_item_query_condition() {
        $entity = $this->get('Entity');
        $id_field = $this->entity_table . '_ID';
        return "`Entity` = '$entity' AND `$id_field` = " . $this->get($id_field);
    }

    /**
     *
     */
    protected function update_other_records_is_actual() {
        nc_db()->query(
            "UPDATE `Version`
                SET `IsActual` = 0,
                    `Timestamp` = `Timestamp`
              WHERE {$this->get_same_item_query_condition()}
                AND `IsActual` = 1
                AND `Version_ID` != {$this->get_id()}"
        );
    }

    /**
     * Возвращает название действия, можно использовать в get_description()
     *
     * @return string
     */
    protected function get_action_description() {
        $constant = 'NETCAT_VERSION_ACTION_' . strtoupper($this->get('Action'));
        return constant($constant);
    }

    /**
     * Возвращает описание изменений
     *
     * @return string
     */
    abstract public function generate_description();

    /**
     *
     */
    public function get_name_from_snapshot() {
        return $this->get_value_from_snapshot($this->entity_table . '_Name');
    }

    /**
     *
     */
    public function get_value_from_snapshot($key) {
        if ($this->get('Action') === self::DELETED && $this->get('Previous_Version_ID')) {
            $previous_version = new static($this->get('Previous_Version_ID'));
            $snapshot = $previous_version->get('Snapshot');
        } else {
            $snapshot = $this->get('Snapshot');
        }

        if (!$snapshot) {
            $snapshot = $this->get_entity_snapshot_data();
        }

        $entity_id = $this->get_entity_id();

        return
            isset($snapshot[$this->entity_table][$entity_id][$key])
                ? $snapshot[$this->entity_table][$entity_id][$key]
                : null;
    }

    /**
     *
     */
    public function get_changes() {
        $snapshot = $this->get('Snapshot');
        $id = $this->get_entity_id();

        $changes = array();

        if (!empty($snapshot[$this->entity_table][$id])) {
            foreach ($snapshot[$this->entity_table][$id] as $key => $value) {
                if (isset(static::$ignore_field_changes[$key])) {
                    continue;
                }
                $changes[$key]['old'] = null;
                $changes[$key]['new'] = $value;
            }
        }

        $previous_version_id = $this->get('Previous_Version_ID');
        if ($previous_version_id) {
            $previous_version = new static($previous_version_id);
            $previous_snapshot = $previous_version->get('Snapshot');
            if (!empty($previous_snapshot[$this->entity_table][$id])) {
                foreach ($previous_snapshot[$this->entity_table][$id] as $key => $value) {
                    if (isset(static::$ignore_field_changes[$key])) {
                        continue;
                    }

                    $changes[$key]['old'] = $value;
                    if (!isset($changes[$key]['new'])) {
                        $changes[$key]['new'] = null;
                    }

                    if ((string)$changes[$key]['new'] === (string)$changes[$key]['old']) {
                        unset($changes[$key]);
                    }
                }
            }
        }

        foreach ($changes as $key => $change) {
            // всё falsy не будет показано в изменениях: null, '', 0 — обычно это значения по умолчанию
            if ($change['old'] == false && $change['new'] == false) {
                unset($changes[$key]);
            }
        }

        return $changes;
    }

    /**
     * @return int
     */
    public function get_entity_id() {
        $primary_key = $this->get_snapshot_table_primary_key($this->entity_table);
        return $this->get($primary_key);
    }

    /**
     * @return array
     */
    abstract protected function get_entity_snapshot_data();

    /**
     * Восстанавливает запись, если их нет в БД (отмена удаления)
     *
     * @return bool
     */
    protected function create_entity() {
        if ($this->entity_exists() || !$this->parent_entity_exists()) {
            return false;
        }
    }

    abstract protected function delete_entity();

    /**
     * @param int|string $component_id
     * @param int $object_id
     * @param string $table
     * @param int $field_type
     * @return array|null
     */
    protected function get_entity_snapshot_file_data($component_id, $object_id, $table, $field_type) {
        $nc_core = nc_core::get_object();

        $file_fields = $nc_core->get_component($component_id)->get_fields($field_type, false);
        if (!$file_fields) {
            return null;
        }

        $file_field_ids = implode(', ', array_keys($file_fields));

        return $nc_core->db->get_results(
            "SELECT * FROM `$table` WHERE `Message_ID` = $object_id AND `Field_ID` IN ($file_field_ids)",
            ARRAY_A,
            'ID'
        );
    }

    /**
     * @return nc_version_file_collection
     */
    public function get_files() {
        if (!$this->files) {
            $this->files = nc_version_file_collection::for_version($this);
        }
        return $this->files;
    }

    /**
     * @param nc_version_file_collection $files
     * @return $this
     */
    public function set_files(nc_version_file_collection $files) {
        $this->files = $files;
        return $this;
    }

    /**
     * @param static $version
     */
    protected static function set_restored_version(nc_version_record $version) {
        // в случае, если restore() когда-то будет вызываться внутри restore(), понадобится более сложное хранение ID
        self::$currently_restored_version_id = $version->get_id();
    }

    /**
     * @return void
     */
    protected static function reset_restored_version_id() {
        self::$currently_restored_version_id = null;
    }

    /**
     * @return int|null
     */
    protected static function get_restored_version_id() {
        return self::$currently_restored_version_id;
    }

    /**
     * Восстановление предыдущей версии
     * @return bool
     */
    public function undo() {
        $action = $this->get('Action');

        if ($action === self::CREATED) {
            $this->delete_entity();
            return true;
        }

        $previous_version_id = $this->get('Previous_Version_ID');
        if (!$previous_version_id) {
            return false;
        }

        $previous_version = new static($previous_version_id);
        return $previous_version->restore();
    }

    /**
     * Восстановление версии
     *
     * @return bool
     */
    public function restore() {
        // Сохранение ID восстанавливаемой версии, он будет использоваться в обработчике события 'after'
        // при создании записи в Version. NB: необходимо сбросить перед выходом из метода!
        self::set_restored_version($this);

        $entity_exists = $this->entity_exists();

        if ($this->get('Action') === self::DELETED) {
            // восстанавливаем удаление
            if ($entity_exists) {
                $this->delete_entity();
            }
            self::reset_restored_version_id();
            return true;
        }

        if (!$this->parent_entity_exists()) {
            self::reset_restored_version_id();
            return false;
        }

        $event_action = $entity_exists ? 'updated' : 'created';
        if ($event_action !== 'created') {
            $current_files = $this->get_entity_current_files();
        } else {
            $current_files = array();
        }

        $this->execute_event('before', $event_action);
        $this->prepare_version_restore();

        $this->restore_snapshot();
        $this->restore_files($current_files);

        nc_core::get_object()->clear_cache();

        $this->finalize_version_restore();
        $this->execute_event('after', $event_action);

        self::reset_restored_version_id();
        return true;
    }

    /**
     * Выполнение действий до восстановления записей в БД и файлов
     */
    protected function prepare_version_restore() {
    }

    /**
     * Выполнение действий после восстановления записей в БД и файлов
     */
    protected function finalize_version_restore() {
    }

    /**
     *
     */
    protected function restore_snapshot() {
        foreach ($this->get('Snapshot') as $table_name => $data) {
            $primary_key = $this->get_snapshot_table_primary_key($table_name);
            foreach ($data as $id => $params) {
                $table = nc_db_table::make($table_name, $primary_key);
                if ($table->select(1)->where_id($id)->get_value()) {
                    $table->update($params);
                } else {
                    $table->insert($params);
                }
            }
        }
    }

    /**
     * Восстанавливает файлы, относящиеся к текущей версии, и удаляет файлы, которых в восстанавливаемой версии нет
     *
     * @param array $current_files файлы, которые есть у записи сейчас (результат метода get_entity_current_files)
     */
    protected function restore_files(array $current_files) {
        $nc_core = nc_core::get_object();
        $restored_files = $this->get_files();

        // Удаление файлов, которые будут отсутствовать после восстановления этой версии
        foreach ($current_files as $current_relative_paths) {
            foreach ((array)$current_relative_paths as $current_relative_path) {
                // Пустое значение для файловых полей компонента и пользовательских настроек указывает на то, что файла нет
                if (!$current_relative_path) {
                    continue;
                }
                $restored_file_hash = $restored_files->get_file_hash($current_relative_path);
                if (!$restored_file_hash || $restored_file_hash !== nc_version_file::get_file_hash($current_relative_path)) {
                    unlink($nc_core->DOCUMENT_ROOT . $current_relative_path);
                }
            }
        }

        // Копирование файлов, которые отличаются в этой версии от предыдущей версии
        /** @var nc_version_file $restored_file */
        foreach ($restored_files as $restored_file) {
            $restored_file_relative_path = $restored_file->get('File_Path');
            $target_path = $nc_core->DOCUMENT_ROOT . $restored_file->get('File_Path');
            $target_path_exists = file_exists($target_path);
            if (!$target_path_exists || nc_version_file::get_file_hash($restored_file_relative_path) !== $restored_file->get('File_Hash')) {
                if ($target_path_exists) {
                    unlink($target_path);
                }
                copy($restored_file->get_version_file_path(), $target_path);
                chmod($target_path, $nc_core->FILECHMOD);
            }
        }
    }

    /**
     * Общий метод, удаляющий записи из Filetable и Multifield перед восстановлением версии
     * (для использования в методе prepare_version_restore)
     *
     * @param string|int $type Catalogue, Subdivision, Sub_Class или ID компонента (для объекта)
     * @param int $id ID записи (сайта, раздела, инфоблока или объекта)
     */
    protected function remove_file_records_on_version_restore($type, $id) {
        $nc_core = nc_core::get_object();
        $component = $nc_core->get_component($type);

        $field_types = array(
            NC_FIELDTYPE_FILE => 'Filetable',
            NC_FIELDTYPE_MULTIFILE => 'Multifield',
        );

        foreach ($field_types as $field_type => $table_name) {
            $file_fields = implode(', ', array_keys($component->get_fields($field_type, false)));
            if ($file_fields) {
                $nc_core->db->query("DELETE FROM `$table_name` WHERE `Field_ID` IN ($file_fields) AND `Message_ID` = $id");
            }
        }
    }

    /**
     * Общий метод, удаляющий сгенерированные изображения файловых полей после восстановления версии (чтобы они были пересозданы)
     * (для использования в методе finalize_version_restore)
     *
     * @param string|int $type Catalogue, Subdivision, Sub_Class или ID компонента (для объекта)
     * @param int $id ID записи (сайта, раздела, инфоблока или объекта)
     */
    protected function remove_generated_field_images($type, $id) {
        $file_fields = nc_core::get_object()->get_component($type)->get_fields(array(NC_FIELDTYPE_FILE, NC_FIELDTYPE_MULTIFILE), false);
        foreach ($file_fields as $field_name) {
            nc_image_generator::remove_generated_images($type, $field_name, $id);
        }
    }

    /**
     * Общий метод, удаляющий сгенерированные изображения пользовательских настроек после восстановления версии (чтобы они были пересозданы)
     * (для использования в методе finalize_version_restore)
     *
     * @param string $type Catalogue, Subdivision, Sub_Class
     * @param int $id ID записи (сайта, раздела, инфоблока или объекта)
     */
    protected function remove_generated_settings_images($type, $id) {
        $nc_core = nc_core::get_object();
        if ($type === 'Catalogue' || $type === 'Subdivision') {
            $entity = 'template_settings';
            $template_id = $nc_core->{strtolower($type)}->get_by_id($id, 'Template_ID');
            $settings = $nc_core->template->get_by_id($template_id, 'CustomSettings');
        } else if ($type === 'Sub_Class') {
            $entity = 'class_settings';
            $settings = $nc_core->sub_class->get_by_id($id, 'CustomSettingsTemplate');
        } else {
            throw new UnexpectedValueException("Unsupported type '$type'");
        }

        $a2f = new nc_a2f($settings);

        foreach ($a2f->get_fields() as $field) {
            if ($field instanceof nc_a2f_field_file) {
                nc_image_generator::remove_generated_images($entity, $field->get_name(), $id);
            }
        }
    }

    /**
     * Метод для проверки существования родительской записи для текущей версии
     *
     * @return bool
     */
    abstract protected function parent_entity_exists();

    /**
     * Метод для проверки существования записи, соответствующей текущей версии
     *
     * @return bool
     */
    abstract protected function entity_exists();

    /**
     * Выполняет обработчик события
     *
     * @param string $type
     * @param string $action
     */
    protected function execute_event($type, $action) {
        $nc_core = nc_Core::get_object();
        $event = constant('nc_event::' . strtoupper($type . '_' . $this->get('Entity') . '_' . $action));
        $nc_core->event->execute($event, $this->get('Catalogue_ID'), $this->get('Subdivision_ID'), $this->get('Sub_Class_ID'), $this->get('Class_ID'), $this->get('Message_ID'));
    }

    /**
     * Возвращает первичный ключ по имени таблицы
     *
     * @param string $table_name
     * @return string
     */
    protected function get_snapshot_table_primary_key($table_name) {
        // В таблицах Filetable, Multifile первичный ключ — ID
        if ($table_name === 'Filetable' || $table_name === 'Multifile') {
            return 'ID';
        }
        if (preg_match('/^Message\d+$/', $table_name)) {
            return 'Message_ID';
        }
        if (strpos($table_name, 'Sub_Class_AreaCondition_') === 0) {
            return 'Condition_ID';
        }
        return $table_name . '_ID';
    }

    /**
     * Метод для получения списка файлов (должен всегда возвращать массив)
     *
     * @return array
     */
    abstract protected function get_entity_current_files();

    /**
     * Общий метод для получения списка файлов из файловых полей и полей множественной загрузки
     * (для использования в методе get_entity_current_files)
     *
     * @param int|string $component_id
     * @param int $object_id
     * @return array
     */
    protected function get_entity_files_from_fields($component_id, $object_id) {
        $all_files = array();

        $nc_core = nc_core::get_object();
        $component = $nc_core->get_component($component_id);

        // Файлы в полях типа «файл»
        foreach ($component->get_all_file_fields() as $file_field_info) {
            $field_name = $file_field_info['name'];
            $file_info = $nc_core->file_info->get_file_info($component_id, $object_id, $field_name, false, false, true);
            if ($file_info['url'] && file_exists($nc_core->DOCUMENT_ROOT . $file_info['url'])) {
                $all_files[$field_name][] = $file_info['url']; // путь с учётом $SUB_FOLDER
                if (file_exists($nc_core->DOCUMENT_ROOT . $file_info['preview_url'])) {
                    $all_files[$field_name][] = $file_info['preview_url'];
                }
            } else {
                $all_files[$field_name] = null;
            }
        }

        // Файлы в полях типа «множественная загрузка»
        $multiple_files = nc_get_multifile_field_values($component_id, $object_id);
        if ($multiple_files) {
            foreach ($multiple_files as $field_name => $multifile_field_info) {
                /** @var nc_multifield $multifile_field_info */
                /** @var nc_multifield_file $multifile_field_data */
                $all_files[$field_name] = null;
                foreach ($multifile_field_info->to_array() as $multifield_file_data) {
                    if (file_exists($nc_core->DOCUMENT_ROOT . $multifield_file_data->Path)) {
                        $all_files[$field_name][] = $multifield_file_data->Path; // путь с учётом $SUB_FOLDER
                        if (file_exists($multifield_file_data->get_full_preview_path())) {
                            $all_files[$field_name][] = $multifield_file_data->Preview;
                        }
                    }
                }
            }
        }

        return $all_files;
    }

    /**
     * Общий метод для получения списка файлов из пользовательских настроек
     * (для использования в методе get_entity_current_files)
     *
     * @param string $type
     * @param int $id
     * @param string|array $a2f_settings
     * @param string|array $a2f_values
     * @return array
     */
    protected function get_entity_files_from_settings($type, $id, $a2f_settings, $a2f_values) {
        if (!$a2f_settings) {
            return array();
        }

        $files = array();

        $a2f = new nc_a2f($a2f_settings, 'settings', false, $id, $type);
        $a2f->set_value($a2f_values);

        foreach ($a2f->get_fields() as $field) {
            if ($field instanceof nc_a2f_field_file) {
                $file = $field->get_value();
                $value = $file instanceof nc_a2f_field_file_value ? $file->get_path() : null;
                $files["$type/{$field->get_name()}"][] = $value;
            }
        }

        return $files;
    }

    /**
     * Общий метод для получения списка файлов из настроек миксинов
     * (для использования в методе get_entity_current_files)
     *
     * @param string $field_name
     * @param string|array $settings
     * @return array
     */
    protected function get_entity_files_from_mixin_settings($field_name, $settings) {
        $nc_core = nc_core::get_object();

        if (!is_array($settings)) {
            if (!$settings) {
                return array();
            }
            $settings = json_decode($settings, true);
        }

        $all_files = array();

        foreach ($settings as $key => $value) {
            if (is_array($value)) {
                $all_files += $this->get_entity_files_from_mixin_settings("$field_name/$key", $value);
            } else if ($value && $value[0] === '/' && strpos($value, '..') === false && file_exists($nc_core->DOCUMENT_ROOT . $value[0])) {
                $all_files["$field_name/$key"][] = $value;
            }
        }

        return $all_files;
    }

    /**
     * @return nc_version_changeset|null
     * @throws nc_record_exception
     */
    public function get_changeset() {
        $changeset_id = $this->get('Version_Changeset_ID');
        if ($changeset_id) {
            return new nc_version_changeset($changeset_id);
        }
        return null;
    }

    /**
     * @return string|null
     */
    public function get_changeset_description() {
        return nc_db()->get_var(
            "SELECT `Description` FROM `Version_Changeset` WHERE `Version_Changeset_ID` = " . (int)$this->get("Version_Changeset_ID")
        );
    }

}
