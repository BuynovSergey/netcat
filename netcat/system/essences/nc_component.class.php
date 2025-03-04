<?php

class nc_Component extends nc_Essence {

    const MAX_KEYWORD_LENGTH = 64;

    const FILE_ADD_ACTION_TEMPLATE = 'AddActionTemplate.html';
    const FILE_ADD_COND = 'AddCond.html';
    const FILE_ADD_TEMPLATE = 'AddTemplate.html';
    const FILE_CHECK_ACTION_TEMPLATE = 'CheckActionTemplate.html';
    const FILE_DELETE_ACTION_TEMPLATE = 'DeleteActionTemplate.html';
    const FILE_DELETE_COND = 'DeleteCond.html';
    const FILE_DELETE_TEMPLATE = 'DeleteTemplate.html';
    const FILE_EDIT_ACTION_TEMPLATE = 'EditActionTemplate.html';
    const FILE_EDIT_COND = 'EditCond.html';
    const FILE_EDIT_TEMPLATE = 'EditTemplate.html';
    const FILE_FORM_PREFIX = 'FormPrefix.html';
    const FILE_FORM_SUFFIX = 'FormSuffix.html';
    const FILE_FULL_SEARCH_TEMPLATE = 'FullSearchTemplate.html';
    const FILE_RECORD_TEMPLATE = 'RecordTemplate.html';
    const FILE_RECORD_TEMPLATE_FULL = 'RecordTemplateFull.html';
    const FILE_REQUIRED_ASSETS = 'RequiredAssets.html';
    const FILE_SEARCH_TEMPLATE = 'SearchTemplate.html';
    const FILE_SETTINGS = 'Settings.html';
    const FILE_SITE_STYLES = 'SiteStyles.css';
    const FILE_BLOCK_SETTINGS_DIALOG = 'BlockSettingsDialog.html';

    protected $db;
    protected $_class_id, $_system_table_id;
    protected $_fields, $_field_count;
    // массив полей, попадающих в запрос, и переменные, им соответствующие
    protected $_fields_query, $_fields_vars, $_fields_vars_columns;
    protected $_joins;
    // все используемые поля всех компонентов
    protected static $all_fields;
    protected static $event_fields = array();

    protected $ids_by_keyword = array();
    protected $reserved_keywords = array('sys', 'table', 'Catalogue', 'Subdivision', 'User', 'Template');

    protected $last_modified_type = 'content';

    protected $possible_fields_with_object_name = array('textTitle', 'H1', 'h1', 'Name', 'name', 'Title', 'title');

    /**
     * Для системных таблиц:
     *   $user_table = new nc_component(0, 3)
     *   или
     *   $user_table = new nc_component('User');
     *
     *
     * @param int|string $class_id integer or 'Catalogue|Subdivision|User|Template'
     * @param int $system_table_id
     */
    public function __construct($class_id = 0, $system_table_id = 0) {
        parent::__construct();

        $this->essence = "Class";

        $nc_core = nc_Core::get_object();

        if (is_object($nc_core->db)) {
            $this->db = $nc_core->db;
        }

        $system_table_id_by_name = $nc_core->get_system_table_id_by_name($class_id);
        if ($system_table_id_by_name) {
            $system_table_id = $system_table_id_by_name;
            $class_id = 0;
        }

        $class_id = intval($class_id);
        $system_table_id = intval($system_table_id);

        // загружаем конкретный компонент
        if ($class_id || $system_table_id) {
            $this->_class_id = $class_id;
            $this->_system_table_id = $system_table_id;
        } else {
            $this->register_event_listeners();
        }
    }

    /**
     * Обработчики для обновления и сброса кэша
     */
    protected function register_event_listeners() {
        $event = nc_core::get_object()->event;
        $clear_cache = array($this, 'clear_cache');
        $event->add_listener(nc_event::AFTER_COMPONENT_UPDATED, $clear_cache);
        $event->add_listener(nc_event::AFTER_COMPONENT_TEMPLATE_UPDATED, $clear_cache);
    }

    /**
     * @param int|string $id_or_keyword
     * @param string $item
     * @param bool $reset
     * @return null|string|array
     * @throws nc_Exception_Class_Doesnt_Exist
     */
    public function get_by_id($id_or_keyword, $item = '', $reset = false) {
        $nc_core = nc_Core::get_object();

        $id = $keyword = null;
        if (ctype_digit((string)$id_or_keyword)) {
            $id = (int)$id_or_keyword;
        }
        else if (preg_match('/^\w+$/', $id_or_keyword)) {
            $keyword = $id_or_keyword;
            if (isset($this->ids_by_keyword[$keyword])) {
                $id = $this->ids_by_keyword[$keyword];
            }
        }

        if (!$id && !$keyword) {
            throw new nc_Exception_Class_Doesnt_Exist(var_export($id_or_keyword, true));
        }

        $res = array();

        if (isset($this->data[$id]) && !$reset) {
            $res = $this->data[$id];
        }

        if (empty($res)) {
            $nc_core->clear_cache_on_low_memory();

            if (!$id) {
                $res = $nc_core->db->get_results(
                    "SELECT `template`.*,
                     IF (`template`.`IsMultipurpose` = 1, `cache`.`Compatible_Class_ID`, `template`.`ClassTemplate`) AS 'ClassTemplate'
                     FROM `Class` AS `template`
                     LEFT JOIN `Class` AS `parent`
                     ON (`template`.`ClassTemplate` = `parent`.`Class_ID`)
                     LEFT JOIN `Class_Multipurpose_Template_Cache` AS `cache`
                     ON `template`.`Class_ID` = `cache`.`Class_ID`
                     WHERE (`template`.`Keyword` = '$keyword' AND `template`.`ClassTemplate` = 0)
                     OR `parent`.`Keyword` = '$keyword'
                     OR `cache`.`Compatible_Class_ID` IN (SELECT `Class_ID` FROM `Class` WHERE `Keyword` = '$keyword')",
                    ARRAY_A);
            }
            else {
                $res = $nc_core->db->get_results(
                    "SELECT `Class`.*,
                      IF (`Class`.`IsMultipurpose` = 1, `cache`.`Compatible_Class_ID`, `Class`.`ClassTemplate`) AS 'ClassTemplate'
                      FROM `Class`
                      LEFT JOIN `Class_Multipurpose_Template_Cache` AS `cache`
                      ON `Class`.`Class_ID` = `cache`.`Class_ID`
                      WHERE `Class`.`Class_ID` = $id
                      OR `Class`.`ClassTemplate` = $id
                      OR `cache`.`Compatible_Class_ID` = $id",
                    ARRAY_A
                );
            }

            if (empty($res)) {
                throw new nc_Exception_Class_Doesnt_Exist($id ?: $keyword);
            }

            $row_nums = count($res);

            for ($i = 0; $i < $row_nums; $i++) {
                if (false && $res[$i]['File_Mode']) { //for debug
                    $class_editor = new nc_tpl_component_editor($nc_core->CLASS_TEMPLATE_FOLDER, $nc_core->db);
                    $class_editor->load($res[$i]['Class_ID'], $res[$i]['File_Path'], $res[$i]['File_Hash']);
                    $class_editor->fill_fields();
                    $res[$i] = array_merge($res[$i], $class_editor->get_fields());
                }
                $this->data[$res[$i]['Class_ID']] = $res[$i];
                $this->data[$res[$i]['Class_ID']]['_nc_final'] = 0;
                $this->data[$res[$i]['Class_ID']]['Real_Class_ID'] = $res[$i]['Class_ID'];

                if ($res[$i]['ClassTemplate'] == 0) {
                    if (!$id) {
                        $id = $res[$i]['Class_ID'];
                    }
                    if ($res[$i]['Keyword']) {
                        $this->ids_by_keyword[$res[$i]['Keyword']] = $res[$i]['Class_ID'];
                    }
                }
            }
        }

        if (!$this->data[$id]['_nc_final'] && $this->data[$id]['ClassTemplate']) {
            $component_id = $this->data[$id]['ClassTemplate'];
            // визуальные настройки наследуются [целиком] от компонента, если не заданы
            if (!$this->data[$id]['CustomSettingsTemplate']) {
                $this->data[$id]['CustomSettingsTemplate'] = $this->get_by_id($component_id, 'CustomSettingsTemplate');
            }

            if (empty($this->data[$component_id]['File_Mode'])) {
                $macrovars = array('%Prefix%' => 'FormPrefix',
                    '%Record%' => 'RecordTemplate',
                    '%Suffix%' => 'FormSuffix',
                    '%Full%' => 'RecordTemplateFull',
                    '%Settings%' => 'Settings',
                    '%TitleTemplate%' => 'TitleTemplate',
                    '%Order%' => 'SortBy',
                    '%AddForm%' => 'AddTemplate',
                    '%AddCond%' => 'AddCond',
                    '%AddAction%' => 'AddActionTemplate',
                    '%EditForm%' => 'EditTemplate',
                    '%EditCond%' => 'EditCond',
                    '%EditAction%' => 'EditActionTemplate',
                    '%DeleteForm%' => 'DeleteTemplate',
                    '%DeleteCond%' => 'DeleteCond',
                    '%DeleteAction%' => 'DeleteActionTemplate',
                    '%SearchForm%' => 'FullSearchTemplate',
                    '%Search%' => 'SearchTemplate',
                    '%CheckAction%' => 'CheckActionTemplate');

                foreach ($macrovars as $var => $field) {
                    if (strpos($this->data[$id][$field], $var) !== false) {
                        $this->data[$id][$field] = str_replace($var, $this->get_by_id($component_id, $field), $this->data[$id][$field]);
                    }
                }
            }
        }

        $this->data[$id]['_nc_final'] = 1;

        if ($item && is_array($this->data[$id])) {
            return array_key_exists($item, $this->data[$id]) ? $this->data[$id][$item] : "";
        }
        return $this->data[$id];
    }


    /**
     * @param array $properties - Свойства компонента
     * @param null|integer $base_component_id - Базовый компонент (из которого берутся настройки и поля)
     * @param null|integer $parent_component_id - Родительский компонент (создаем не сам компонент, а шаблон для компонента)
     * @return null|mixed $component_id - Id созданного компонента
     */
    public function create(array $properties = array(), $base_component_id = null, $parent_component_id = null) {
        $nc_core = nc_Core::get_object();
        $db = $nc_core->db;

        // Компонент или шаблон компонента?
        $is_template = !empty($parent_component_id);

        // Если шаблон - проверим есть ли базовый компонент
        $parent_component_data = null;
        if ($is_template) {
            try {
                $parent_component_data = $this->get_by_id($parent_component_id);

                // Для шаблонов компонентов жестко зашито данное значение
                $properties['Class_Group'] = CONTROL_CLASS_CLASS_TEMPLATE_GROUP;
            } catch (Exception $e) {
                // Компонент-родитель не найден
                trigger_error('Parent component not found', E_USER_WARNING);
                return null;
            }
        }

        // Данные базового компонента
        $base_component_properties = array();
        if ($base_component_id) {
            // Если базовый компонент есть, то найдем его данные, и дополним массив параметров
            try {
                $base_component_properties = $this->get_by_id($base_component_id);
            } catch (Exception $e) {
                // Компонент-родитель не найден
                trigger_error('Base component not found', E_USER_WARNING);
                return null;
            }
        }

        // Создадим запись о компоненте
        $default_component_properties = array(
            'Type' => 'useful',
            'File_Mode' => '1',
            'SortBy' => 'a.`Priority` ASC, a.`Message_ID` ASC',
            'TitleTemplate' => '$f_Name',
        );

        $new_component_properties = array_merge(
            $default_component_properties,
            $base_component_properties,
            $properties
        );

        $changed_component_properties = array(
            'Type' => $is_template ? $new_component_properties['Type'] : 'useful',
            'ClassTemplate' => $is_template ? $parent_component_id : 0,
        );

        // Предусмотрим пустые данные и пустой базовый компонент
        if (empty($new_component_properties['Class_Group'])) {
            $new_component_properties['Class_Group'] = CONTROL_CLASS_NEWGROUP;
        }
        if (empty($new_component_properties['Keyword'])) {
            $new_component_properties['Keyword'] = $is_template ? 'new_template' : 'new_component';
        }
        if (empty($new_component_properties['Class_Name'])) {
            $new_component_properties['Class_Name'] = $is_template ? CONTROL_CLASS_NEWTEMPLATE : CONTROL_CLASS_NEWCLASS;
        }

        // Подберем "keyword"
        $counter = 0;
        $component_keyword = $new_component_properties['Keyword'];
        $original_component_keyword = $component_keyword;
        do {
            $suffix = '';
            if ($counter > 0) {
                $suffix = '_' . $counter;
            }
            $max_component_keyword_length = self::MAX_KEYWORD_LENGTH - mb_strlen($suffix);
            $component_keyword = mb_strlen($original_component_keyword) <= $max_component_keyword_length ?
                $original_component_keyword : mb_substr($original_component_keyword, 0, $max_component_keyword_length);
            $component_keyword .= $suffix;
            $keyword_validation_result = $this->validate_keyword($component_keyword, null, $is_template ? $parent_component_id : null);
            $counter++;
        } while($keyword_validation_result !== true);
        $changed_component_properties['Keyword'] = $component_keyword;

        // Подберем "name"
        $counter = 0;
        $component_name = $new_component_properties['Class_Name'];
        $_component_name = $component_name;
        do {
            if ($counter > 0) {
                $component_name = $_component_name . " ({$counter})";
            }
            $component_name = $db->escape($component_name);
            $where = "`Class_Name`='{$component_name}' ";
            $where .= $is_template ? " AND `ClassTemplate`='{$parent_component_id}'" : null;
            $has_component_name = $db->get_var("SELECT `Class_ID` FROM `Class` WHERE {$where}");
            $counter++;
        } while($has_component_name);
        $changed_component_properties['Class_Name'] = $component_name;

        $new_component_properties = array_merge(
            $new_component_properties,
            $changed_component_properties
        );

        $all_component_properties = $db->get_col("DESCRIBE `Class`");
        $all_component_properties = array_flip($all_component_properties);
        unset($all_component_properties['Class_ID']);
        unset($all_component_properties['File_Path']);
        unset($all_component_properties['File_Hash']);
        $all_component_properties = array_keys($all_component_properties);

        $properties_names = array_keys($new_component_properties);
        foreach ($properties_names as $property_name) {
            if (in_array($property_name, $all_component_properties)) {
                continue;
            }
            unset($new_component_properties[$property_name]);
        }

        $component_id = nc_db_table::make('Class')->insert($new_component_properties);

        // Папка для хранения файлов компонента
        $component_folder_relative_path = ($is_template ? trim($parent_component_data['File_Path'], '/') . '/' : '') . ($component_keyword ?: $component_id);
        $component_folder_path = $nc_core->CLASS_TEMPLATE_FOLDER . $component_folder_relative_path;
        $nc_core->files->create_dir($component_folder_path);

        // Файлы компонента
        $component_files = array(
            'AddActionTemplate.html',
            'AddCond.html',
            'AddTemplate.html',
            'BlockSettingsDialog.html',
            'CheckActionTemplate.html',
            'Class.html',
            'DeleteActionTemplate.html',
            'DeleteCond.html',
            'DeleteTemplate.html',
            'EditActionTemplate.html',
            'EditCond.html',
            'EditTemplate.html',
            'FormPrefix.html',
            'FormSuffix.html',
            'FullSearchTemplate.html',
            'RecordTemplate.html',
            'RecordTemplateFull.html',
            'SearchTemplate.html',
            'Settings.html',
            'SiteStyles.css',
        );

        // Путь к базовому компоненту
        $base_component_folder_path = null;
        if (!empty($base_component_properties)) {
            $base_component_folder_path = rtrim($nc_core->CLASS_TEMPLATE_FOLDER, '/') . rtrim($base_component_properties['File_Path'], '/');
        }

        // Перенесем файлы (или создадим пустые)
        foreach ($component_files as $component_file) {
            $base_component_file_path = $base_component_folder_path . '/' . $component_file;
            $component_file_path = $component_folder_path . '/' . $component_file;

            if (file_exists($base_component_file_path)) {
                copy($base_component_file_path, $component_file_path);
            } else {
                file_put_contents($component_file_path, '');
            }
        }

        // Обновим поле File_Hash
        $tpl = new nc_tpl($component_folder_path, $db);
        $tpl->load($component_id, 'Class');
        nc_tpl_parser::parts2main($tpl);

        // Обновим поле File_Path
        $db->query("UPDATE `Class` SET `File_Path` ='/{$component_folder_relative_path}/' WHERE `Class_ID`='{$component_id}'");

        // Если не шаблон - создадим таблицу и создадим поля компонента
        if (!$is_template) {
            // Создадим таблицу компонента
            $sql = "
                CREATE TABLE `Message{$component_id}` (
                    `Message_ID` int AUTO_INCREMENT PRIMARY KEY,
                    `User_ID` int NOT NULL,
                    `Subdivision_ID` int NOT NULL,
                    `Sub_Class_ID` int NOT NULL,
                    `Priority` int NOT NULL DEFAULT 0,
                    `Keyword` char(255) NOT NULL,
                    `ncTitle` varchar(255) default NULL,
                    `ncKeywords` text default NULL,
                    `ncDescription` text default NULL,
                    `ncSMO_Title` varchar(255) default NULL,
                    `ncSMO_Description` text,
                    `ncSMO_Image` text default NULL,
                    `ncFullPageMode` enum('inherit', 'template', 'custom', 'none') NOT NULL DEFAULT 'inherit',
                    `ncFullPageCreated` tinyint NOT NULL DEFAULT 0,
                    `ncDemoContent` tinyint NOT NULL DEFAULT 0,
                    `Checked` tinyint NOT NULL DEFAULT 1,
                    `IP` char(15) NULL,
                    `UserAgent` char(255) NULL,
                    `Parent_Message_ID` int NOT NULL DEFAULT 0,
                    `Created` datetime NOT NULL,
                    `LastUpdated` timestamp NOT NULL,
                    `LastUser_ID` int NOT NULL,
                    `LastIP` char(15) NULL,
                    `LastUserAgent` char(255) NULL,
                    index(`User_ID`),
                    index(`LastUser_ID`),
                    index(`Subdivision_ID`),
                    index(`Parent_Message_ID`), 
                    index(`Keyword`),
                    index(`Priority`, `LastUpdated`),
                    index(`ncDemoContent`, `Subdivision_ID`),
                    index(`Checked`),
                    index(`Created`),
                    unique (`Sub_Class_ID`, `Message_ID`, `Keyword`)
                ) ENGINE=MyISAM DEFAULT CHARSET=utf8;
            ";
            $db->query($sql);

            // Добавим поля, копируя их из базового компонента (если указан)
            if (!empty($base_component_id)) {
                // Выберем поля базового компонента
                $new_component_properties = $db->get_results("SELECT * FROM `Field` WHERE `Class_ID`='{$base_component_id}' ORDER BY `Priority`", ARRAY_A);
                if (!empty($new_component_properties)) {
                    foreach ($new_component_properties as $field) {
                        $nc_core->component->add_field($component_id, array(
                            'Field_Name' => $field['Field_Name'],
                            'Description' => $field['Description'],
                            'TypeOfData_ID' => $field['TypeOfData_ID'],
                            'Format' => $field['Format'],
                            'NotNull' => $field['NotNull'],
                            'DefaultState' => $field['DefaultState'],
                            'DoSearch' => $field['DoSearch']
                        ));
                    }
                }

                // Скопируем также шаблоны базового компонента если они есть
                $component_templates = $db->get_results("SELECT * FROM `Class` WHERE `ClassTemplate`='{$base_component_id}'", ARRAY_A);
                if (!empty($component_templates)) {
                    foreach ($component_templates as $c_tpl) {
                        $nc_core->component->create(array(
                            'Keyword' => $c_tpl['Keyword'],
                            'Class_Name' => $c_tpl['Class_Name'],
                            'Type' => $c_tpl['Type'],
                        ), $c_tpl['Class_ID'], $component_id);
                    }
                }
            }
        }

        return $component_id;
    }

    /**
     * Добавление поля для компонента
     * @param $component_id
     * @param array $properties
     *     $properties['TypeOfData_ID'] - ID типа поля
     *     $properties['Field_Name']    - Название поля
     *     $properties['Description']   - Описание поля
     *     $properties['Format']        - Формат поля
     *     $properties['DefaultState']  - Значение по-умолчанию
     *     $properties['NotNull']       - Может или не может быть null
     *     $properties['DoSearch']      - Разрешен ли поиск по полю
     * @return mixed $field_id
     */
    public function add_field($component_id, $properties = array()) {
        $nc_core = nc_Core::get_object();
        $db = $nc_core->db;

        // Добавим поле в таблицу "Field"
        $type = (int)$properties['TypeOfData_ID'];
        $name = $db->escape($properties['Field_Name']);
        $description = $db->escape($properties['Description']);
        $format = $db->escape($properties['Format']);
        $default = $db->escape($properties['DefaultState']);
        $not_null = (int)$properties['NotNull'];
        $searchable = (int)$properties['DoSearch'];

        $fields = array(
            'Class_ID' => $component_id,
            'Field_Name' => $name,
            'Description' => $description,
            'TypeOfData_ID' => $type,
            'Format' => $format,
            'NotNull' => $not_null,
            'Priority' => (int)$db->get_var("SELECT MAX(`Priority`) FROM `Field` WHERE `Class_ID`={$component_id}") + 1,
            'DoSearch' => $searchable,
            'DefaultState' => $default,
            'Inheritance' => 0,
            'System_Table_ID' => 0,
            'Widget_Class_ID' => 0,
            'TypeOfEdit_ID' => 1,
            'Checked' => 1,
            'InTableView' => 0,
        );
        foreach ($fields as $key => &$val) {
            $val = $val !== 'NULL' ? "'{$val}'" : $val;
            $val = "`{$key}`={$val}";
        }
        unset($val);
        $db->query("INSERT INTO `Field` SET " . implode(',', $fields));
        $field_id = $db->insert_id;

        $this->add_field_to_message_table($component_id, $properties);

        $this->update_cache_for_multipurpose_templates();

        return $field_id;
    }

    /**
     * @internal Не является частью публичного API
     * @param $component_id
     * @param array $properties
     *     $properties['TypeOfData_ID'] - ID типа поля
     *     $properties['Field_Name']    - Название поля
     *     $properties['DefaultState']  - Значение по-умолчанию
     *     $properties['NotNull']       - Может или не может быть null
     *     $properties['DoSearch']      - Разрешен ли поиск по полю
     */
    public function add_field_to_message_table($component_id, $properties) {
        $nc_core = nc_Core::get_object();
        $db = $nc_core->db;

        $type = (int)$properties['TypeOfData_ID'];
        $name = $db->escape($properties['Field_Name']);
        $default = $db->escape($properties['DefaultState']);
        $not_null = (bool)$properties['NotNull'];
        $searchable = (bool)$properties['DoSearch'];

        // Модифицируем таблицу компонента
        $column_types = array(
            NC_FIELDTYPE_STRING => 'CHAR(255)',
            NC_FIELDTYPE_INT => 'INT',
            NC_FIELDTYPE_TEXT => 'LONGTEXT',
            NC_FIELDTYPE_SELECT => 'INT',
            NC_FIELDTYPE_BOOLEAN => 'TINYINT',
            NC_FIELDTYPE_FILE => 'TEXT',
            NC_FIELDTYPE_FLOAT => 'DOUBLE',
            NC_FIELDTYPE_DATETIME => 'DATETIME',
            NC_FIELDTYPE_RELATION => 'INT',
            NC_FIELDTYPE_MULTISELECT => 'TEXT',
            NC_FIELDTYPE_MULTIFILE => 'CHAR(255)'
        );

        if ($default !== '' && !in_array($type, array(NC_FIELDTYPE_TEXT, NC_FIELDTYPE_FILE, NC_FIELDTYPE_MULTIFILE, NC_FIELDTYPE_DATETIME), true)) {
            $column_properties = "NOT NULL DEFAULT '$default'";
        } else if ($not_null) {
            $column_properties = 'NOT NULL';
        } else {
            $column_properties = 'NULL';
        }

        $db->query("ALTER TABLE `Message$component_id` ADD `$name` $column_types[$type] $column_properties");

        if ($searchable && !in_array($type, array(NC_FIELDTYPE_TEXT, NC_FIELDTYPE_FILE, NC_FIELDTYPE_MULTIFILE), true)) {
            $db->query("ALTER TABLE `Message$component_id` ADD INDEX (`$name`)");
        }
    }

    /**
     * Возвращает шаблон компонента по ключевому слову этого шаблона
     * @param string|int $component_id  ID или ключевое слово компонента
     * @param string|int $template_keyword   ID или ключевое слово шаблона компонента
     * @param null|string $item  Возвращаемое свойство
     * @param bool $reset  Сброс кэша
     * @return array|mixed|null
     */
    public function get_component_template_by_keyword($component_id, $template_keyword, $item = '', $reset = false) {
        if (ctype_digit((string)$template_keyword)) { // число считаем идентификатором
            return $this->get_by_id($template_keyword, $item, $reset);
        }

        // загрузка + получение ID компонента по ключевому слову
        $component_id = $this->get_by_id($component_id, 'Class_ID', $reset);

        foreach ($this->data as $row) {
            if ($row['ClassTemplate'] == $component_id && $row['Keyword'] == $template_keyword) {
                if ($item) {
                    return isset($row[$item]) ? $row[$item] : null;
                }
                else {
                    return $row;
                }
            }
        }

        return null;
    }

    /**
     * Возвращает шаблоны компонентов указанного типа для компонента
     * @param int|string $id_or_keyword
     * @param string|array|null $template_types
     * @param bool $reset
     * @return array
     */
    public function get_component_templates($id_or_keyword, $template_types = null, $reset = false) {
        // загрузка + получение ID компонента по ключевому слову
        try {
            $class_id = $this->get_by_id($id_or_keyword, 'Class_ID', $reset);
        } catch (nc_Exception_Class_Doesnt_Exist $e) {
            return array();
        }

        $result = array();
        foreach ($this->data as $row) {
            if ($row['ClassTemplate'] == $class_id) {
                $type_match =
                    !$template_types ||
                    (is_array($template_types) && in_array($row['Type'], $template_types, true)) ||
                    ($row['Type'] === $template_types);
                if ($type_match) {
                    if (!$row['_nc_final']) {
                        try {
                            $row = $this->get_by_id($row['Class_ID']);
                        } catch (nc_Exception_Class_Doesnt_Exist $e) {
                            continue;
                        }
                    }
                    $result[] = $row;
                }
            }
        }

        return $result;
    }

    /**
     * Проверяет, есть ли у компонента или хотя бы одного его шаблона флаг IsOptimizedForMultipleMode
     * @param int|string $id_or_keyword
     * @return bool
     */
    public function is_optimized_for_multiple_mode($id_or_keyword) {
        if ($this->get_by_id($id_or_keyword, 'IsOptimizedForMultipleMode')) {
            return true;
        }
        $parent_id = $this->get_by_id($id_or_keyword, 'ClassTemplate');
        if (!$parent_id) { // Это компонент, а не шаблон. Проверяем шаблоны компонента
            $templates = $this->get_component_templates($id_or_keyword, 'useful');
            foreach ($templates as $template) {
                if ($template['IsOptimizedForMultipleMode']) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * @param $sub_class_id
     * @param $class_id
     * @param $nc_ctpl
     * @param int $nc_tpl_in_cc
     * @param string $type
     * @return bool|null|string|array
     */
    public function get_for_cc($sub_class_id, $class_id, $nc_ctpl, $nc_tpl_in_cc = 0, $type = '') {
        $nc_core = nc_Core::get_object();

        $class_id = (int)$class_id;

        $this->get_by_id($class_id);

        $template_id = $nc_core->sub_class->get_by_id($sub_class_id, 'Class_Template_ID');

        if (!$type) {
            if ($nc_core->admin_mode) {
                $type = 'admin_mode';
            }
            if ($nc_core->inside_admin) {
                $type = 'inside_admin';
            }
            if ($nc_core->get_page_type() === 'rss') {
                $type = 'rss';
            }
            if ($nc_core->get_page_type() === 'xml') {
                $type = 'xml';
            }
            if ($nc_ctpl === 'title') {
                $type = 'title';
                $nc_ctpl = 0;
            }
        }

        // выбор по шаблону nc_ctpl переданному в s_list
        if ($nc_ctpl && $nc_ctpl !== 'title') {
            foreach ($this->data as $id => $v) {
                if ($v['Class_ID'] == $nc_ctpl || ($v['ClassTemplate'] == $class_id && $v['Keyword'] == $nc_ctpl)) {
                    return $this->get_by_id($v['Class_ID']);
                }
            }
        }

        // поиск по типу специального шаблона компонента
        if ($type) {
            foreach ($this->data as $id => $v) {
                if ($v['ClassTemplate'] == $class_id && $v['Type'] == $type) {
                    return $this->get_by_id($v['Class_ID']);
                }
            }
        }

        // выбор по шаблону в инфоблоке источнике для s_list
        if ($nc_tpl_in_cc) {
            foreach ($this->data as $id => $v) {
                if ($v['Class_ID'] == $nc_tpl_in_cc) {
                    return $this->get_by_id($v['Class_ID']);
                }
            }
        }

        // выбор по многоцелевому шаблону
        if ($template_id && $this->get_by_id($template_id, 'IsMultipurpose')) {
            return $this->get_by_id($template_id);
        }

        // выбор по номеру компонента если никакие шаблоны не подошли
        foreach ($this->data as $id => $v) {
            if (!$nc_ctpl && $v['Class_ID'] == $class_id) {
                return $this->get_by_id($v['Class_ID']);
            }
        }

        return false;
    }

    /**
     * @return string
     */
    public function get_fields_query() {
        if (empty($this->_fields_query)) {
            $this->make_query();
        }

        return join(', ', $this->_fields_query);
    }

    /**
     * @return string
     */
    public function get_fields_vars() {
        if (empty($this->_fields_vars)) {
            $this->make_query();
        }

        return join(', ', $this->_fields_vars);
    }

    /**
     * Возвращает код для $cc_env['convert2txt'] (используется для экранирования
     * значений строковых/текстовых полей в full.php, nc_objects_list)
     * @param array $cc_env
     * @return string
     */
    public function get_convert2txt_code(array $cc_env) {
        $convert2txt = array();

        $text_fields = $this->get_fields(array(NC_FIELDTYPE_TEXT, NC_FIELDTYPE_STRING));
        foreach ($text_fields as $field) {
            $format = nc_field_parse_format($field['format'], $field['type']);
            $html_disabled = (!($cc_env['AllowTags']) && !$format['html']) || $format['html'] == 2;
            // разрешить html
            if ($html_disabled || $format['bbcode']) {
                $convert2txt[] = '$f_' . $field['name'] . ' = htmlspecialchars($f_' . $field['name'] . ', ENT_QUOTES, $nc_core->NC_CHARSET, false);';
            }
            if ($format['bbcode']) {
                $convert2txt[] = '$f_' . $field['name'] . ' = nc_bbcode($f_' . $field['name'] . ', ($fullDateLink ?: $fullLink));';
            }
            // перенос строки
            if (($cc_env['NL2BR'] && !$format['br']) || $format['br'] == 1) {
                $convert2txt[] = '$f_' . $field['name'] . ' = nl2br($f_' . $field['name'] . ');';
            }
        }

        return join("\n", $convert2txt);
    }

    /**
     * @throws nc_Exception_DB_Error
     */
    protected function _load_fields() {
        // загрузка их статических данных
        // если их нет - то взять из базы
        $cache_key = $this->_class_id . '-' . $this->_system_table_id;
        if (!isset(self::$all_fields[$cache_key])) {
            $result = $this->db->get_results(
                    "SELECT `Field_ID` as `id`,
                            `Field_Name` as `name`,
                            `TypeOfData_ID` as `type`,
                            `Format` as `format`,
                            `Description` AS `description`,
                            `NotNull` AS `not_null`,
                            `DefaultState` as `default`,
                            `TypeOfEdit_ID` AS `edit_type`,
                            `System_Table_ID` AS `system_table_id`,
                            `Class_ID` AS `class_id`,
                            IF(`TypeOfData_ID` IN (" . NC_FIELDTYPE_SELECT . ", " . NC_FIELDTYPE_MULTISELECT . "),
                               SUBSTRING_INDEX(`Format`, ':', 1),
                               '') AS `table`,
                            " . (!$this->_system_table_id ? "`DoSearch`" : "1") . " AS `search`,
                            `Inheritance` AS `inheritance`,
                            `Extension` as `extension`,
                            `InTableView` AS `in_table_view`
                       FROM `Field`
                      WHERE `Checked` = 1  AND " .
                    ($this->_system_table_id
                        ? " `System_Table_ID` = '" . $this->_system_table_id . "'"
                        : " `Class_ID` = '" . $this->_class_id . "'") . "
                      ORDER BY `Priority`",
                    ARRAY_A) ?: array();

            if ($this->db->is_error) {
                throw new nc_Exception_DB_Error($this->db->last_query, $this->db->last_error);
            }

            foreach ($result as $key => $row) {
                $result[$key]['extension'] = str_replace('%ID', $row['id'], $row['extension']);
            }

            self::$all_fields[$cache_key] = $result;
        }

        $this->_fields = self::$all_fields[$cache_key];
        $this->_field_count = count($this->_fields);
    }

    /**
     * @return mixed
     */
    public function get_joins() {
        return $this->_joins;
    }

    /**
     * @param $res
     * @return array
     */
    public function get_old_vars($res) {
        if (!$this->_fields_vars_columns) {
            return array();
        }
        $old_vars = array();
        foreach ($this->_fields_vars_columns as $variable => $column) {
            if (!isset($res[$variable])) {
                if (isset($res[$column])) {
                    $old_vars[$variable] = $res[$column];
                }
            }
        }

        return $old_vars;
    }

    /**
     *
     */
    public function make_query() {
        $nc_core = nc_Core::get_object();

        $this->_load_fields();

        if ($this->_system_table_id == 3) {
            $this->_fields_query = array('a.`User_ID`', 'a.`PermissionGroup_ID`');
            $this->_fields_vars = array('$f_User_ID', '$f_PermissionGroup_ID');
        } else {
            $sub_folder = $nc_core->db->escape($nc_core->SUB_FOLDER);

            $this->_fields_query = array(
                'a.`Message_ID`',
                'a.`User_ID`',
                'a.`IP`',
                'a.`UserAgent`',
                'a.`LastUser_ID`',
                'a.`LastIP`',
                'a.`LastUserAgent`',
                'a.`Priority`',
                'a.`Parent_Message_ID`',
                'a.`ncTitle`',
                'a.`ncKeywords`',
                'a.`ncDescription`',
                'a.`ncSMO_Title`',
                'a.`ncSMO_Description`',
                'a.`ncSMO_Image`',
                'a.`ncFullPageMode`',
                'a.`ncFullPageCreated`',
                /*'a.`ncDemoContent`',*/
                'sub.`Subdivision_ID`',
                'CONCAT(\'' . $sub_folder . '\', sub.`Hidden_URL`) AS `Hidden_URL`',
                'cc.`Sub_Class_ID`',
                'cc.`EnglishName`'
            );
            $this->_fields_vars = array(
                '$f_Message_ID',
                '$f_User_ID',
                '$f_IP',
                '$f_UserAgent',
                '$f_LastUser_ID',
                '$f_LastIP',
                '$f_LastUserAgent',
                '$f_Priority',
                '$f_Parent_Message_ID',
                '$f_ncTitle',
                '$f_ncKeywords',
                '$f_ncDescription',
                '$f_ncSMO_Title',
                '$f_ncSMO_Description',
                '$f_ncSMO_Image',
                '$f_ncFullPageMode',
                '$f_ncFullPageCreated',
                /*'$f_ncDemoContent',*/
                '$f_Subdivision_ID',
                '$f_Hidden_URL',
                '$f_Sub_Class_ID',
                '$f_EnglishName'
            );

            $this->_joins .=
                " LEFT JOIN `Subdivision` AS `sub` ON sub.`Subdivision_ID` = a.`Subdivision_ID`
                  LEFT JOIN `Sub_Class` AS `cc` ON cc.`Sub_Class_ID` = a.`Sub_Class_ID` ";
        }

        $this->_fields_query[] = 'a.`Checked`';
        $this->_fields_query[] = 'a.`Created`';
        $this->_fields_query[] = 'a.`Keyword`';
        $this->_fields_query[] = 'a.`LastUpdated` + 0 AS `LastUpdated`';

        $this->_fields_vars[] = '$f_Checked';
        $this->_fields_vars[] = '$f_Created';
        $this->_fields_vars[] = '$f_Keyword';
        $this->_fields_vars[] = '$f_LastUpdated';


        if (!$this->_system_table_id && $nc_core->admin_mode && $nc_core->AUTHORIZE_BY !== 'User_ID') {
            $this->_fields_query[] = "uAdminInterfaceAdd.`" . $nc_core->AUTHORIZE_BY . "` AS `f_AdminInterface_user_add` ";
            $this->_fields_query[] = "uAdminInterfaceChange.`" . $nc_core->AUTHORIZE_BY . "` AS `f_AdminInterface_user_change` ";

            $this->_fields_vars[] = '$f_AdminInterface_user_add';
            $this->_fields_vars[] = '$f_AdminInterface_user_change';

            $this->_joins .= " LEFT JOIN `User` AS `uAdminInterfaceAdd` ON a.`User_ID` = uAdminInterfaceAdd.`User_ID`
                               LEFT JOIN `User` AS `uAdminInterfaceChange` ON a.`LastUser_ID` = uAdminInterfaceChange.`User_ID` ";
        }


        for ($i = 0; $i < $this->_field_count; $i++) {
            $field = & $this->_fields[$i];
            if ($field['type'] == NC_FIELDTYPE_MULTIFILE) {
                continue;
            }

            switch ($field['type']) {
                case NC_FIELDTYPE_SELECT:
                    $table = $field['table'];
                    $this->_joins .= " LEFT JOIN `Classificator_" . $table . "` AS `tbl" . $field['id'] . "` ON a.`" . $field['name'] . "` = tbl" . $field['id'] . ".`" . $table . "_ID` ";

                    $this->_fields_query[] = "tbl" . $field['id'] . ".`" . $table . "_Name` AS `" . $field['name'] . '`';
                    $this->_fields_query[] = "tbl" . $field['id'] . ".`" . $table . "_ID` AS `" . $field['name'] . "_id`";
                    $this->_fields_query[] = "tbl" . $field['id'] . ".`Value` AS `" . $field['name'] . "_value` ";
                    $this->_fields_query[] = "tbl" . $field['id'] . ".`CustomSettings` AS `" . $field['name'] . "_settings` ";

                    $this->_fields_vars[] = "\$f_" . $field['name'];
                    $this->_fields_vars[] = "\$f_" . $field['name'] . "_id";
                    $this->_fields_vars[] = "\$f_" . $field['name'] . "_value";
                    $this->_fields_vars[] = "\$f_" . $field['name'] . "_settings";
                    break;
                case NC_FIELDTYPE_DATETIME:
                    $format = explode(";", $field['format']);
                    if (!empty($format[0]) && in_array($format[0], array('event_date', 'event_time'))) {
                        switch($format[0]) {
                            case "event_date":
                                $this->_fields_query[] = "DATE_FORMAT(a.`" . $field['name'] . "`,'%Y-%m-%d') AS `" . $field['name'] . "`";
                                break;
                            case "event_time":
                                $this->_fields_query[] = "DATE_FORMAT(a.`" . $field['name'] . "`,'%H:%i:%s') AS `" . $field['name'] . "`";
                                break;
                        }
                    } else {
                        $this->_fields_query[] = "a.`" . $field['name'] . '`';
                    }

                    $this->_fields_vars[] = "\$f_" . $field['name'];

                    $this->_fields_query[] = "DATE_FORMAT(a.`" . $field['name'] . "`,'%Y') AS `" . $field['name'] . "_year`";
                    $this->_fields_query[] = "DATE_FORMAT(a.`" . $field['name'] . "`,'%m') AS `" . $field['name'] . "_month`";
                    $this->_fields_query[] = "DATE_FORMAT(a.`" . $field['name'] . "`,'%d') AS `" . $field['name'] . "_day`";
                    $this->_fields_query[] = "DATE_FORMAT(a.`" . $field['name'] . "`,'%H') AS `" . $field['name'] . "_hours`";
                    $this->_fields_query[] = "DATE_FORMAT(a.`" . $field['name'] . "`,'%i') AS `" . $field['name'] . "_minutes`";
                    $this->_fields_query[] = "DATE_FORMAT(a.`" . $field['name'] . "`,'%s') AS `" . $field['name'] . "_seconds`";

                    $this->_fields_vars[] = "\$f_" . $field['name'] . "_year";
                    $this->_fields_vars[] = "\$f_" . $field['name'] . "_month";
                    $this->_fields_vars[] = "\$f_" . $field['name'] . "_day";
                    $this->_fields_vars[] = "\$f_" . $field['name'] . "_hours";
                    $this->_fields_vars[] = "\$f_" . $field['name'] . "_minutes";
                    $this->_fields_vars[] = "\$f_" . $field['name'] . "_seconds";

                    break;
                case NC_FIELDTYPE_MULTISELECT:
                    $this->_fields_query[] = "a.`" . $field['name'] . '`';
                    $this->_fields_vars[] = "\$f_" . $field['name'];
                    break;
                default:
                    $this->_fields_query[] = "a.`" . $field['name'] . '`';
                    $this->_fields_vars[] = "\$f_" . $field['name'];
                    break;
            }
        }

        $this->_fields_vars_columns = array();
        foreach ($this->_fields_vars as $i => $var) {
            $field_name = preg_replace('/^\\$(?:f_)?/', "", $var);
            $field_query = $this->_fields_query[$i];
            if (stripos($field_query, " as ")) {
                $field_column = preg_replace('/^.+\sAS\s+`?(\w+).?$/is', '$1', $field_query);
            } else {
                $field_column = preg_replace('/^.*?([\w_]+?)[`]?$/i', '$1', $field_query);
            }
            $this->_fields_vars_columns[$field_name] = $field_column;
        }
    }

    /**
     * @param int|int[]|null $type
     * @param int $output_all
     * @return array
     *    Если output_all = true, то массив со следующими элементами для каждого поля:
     *      id
     *      name
     *      type
     *      format
     *      description
     *      not_null
     *      default
     *      edit_type
     *      table   (таблица классификатора)
     *      search
     *      inheritance
     *      extension
     *      in_table_view
     *   Если output_all = false, то массив id поля => name (не description) поля
     */
    public function get_fields($type = null, $output_all = 1) {
        $this->_load_fields();

        if (!$type && $output_all) {
            return $this->_fields;
        }

        $types = array_flip((array)$type);
        $result = array();
        for ($i = 0; $i < $this->_field_count; $i++) {
            if (!$type || isset($types[$this->_fields[$i]['type']])) {
                if ($output_all) {
                    $result[] = $this->_fields[$i];
                } else {
                    $result[$this->_fields[$i]['id']] = $this->_fields[$i]['name'];
                }
            }
        }

        return $result;
    }

    /**
     * Возвращает массив с информацией о полях, у которых имя (name) начинается с указанного префикса
     * @param string $prefix    Искомый префикс
     * @param null $type        Аналогично методу get_fields()
     * @param bool $output_all  Аналогично методу get_fields()
     * @return array
     */
    public function get_fields_by_name_prefix($prefix, $type = null, $output_all = true) {
        $fields = $this->get_fields($type, $output_all);

        if ($output_all) {
            $result = array();
            $prefix_length = strlen($prefix);
            foreach ($fields as $f) {
                if (substr($f['name'], 0, $prefix_length) === $prefix) {
                    $result[] = $f;
                }
            }
            return $result;
        } else {
            return preg_grep("/^" . preg_quote($prefix) . "/", $fields);
        }
    }

    /**
     * Возвращает массив с информацией о полях, у которых имя (name) заканчивается указанным суффиксом
     * @param string $suffix    Искомый суффикс
     * @param null $type        Аналогично методу get_fields()
     * @param bool $output_all  Аналогично методу get_fields()
     * @return array
     */
    public function get_fields_by_name_suffix($suffix, $type = null, $output_all = true) {
        $fields = $this->get_fields($type, $output_all);

        if ($output_all) {
            $result = array();
            $suffix_length = strlen($suffix);
            foreach ($fields as $f) {
                if (substr($f['name'], -$suffix_length) === $suffix) {
                    $result[] = $f;
                }
            }
            return $result;
        } else {
            return preg_grep("/" . preg_quote($suffix) . '$/', $fields);
        }
    }

    /**
     * Получить все настройки поля с указанным именем или идентификатором,
     * или, если указан, параметр $parameter_name этого поля
     *
     * $component->get_field('City')  → array
     * $component->get_field('City', 'description') → string
     *
     * @param string $field_name_or_id
     * @param string|null $parameter_name
     * @return null|array|string
     */
    public function get_field($field_name_or_id, $parameter_name = null) {
        $this->_load_fields();

        foreach ($this->_fields as $field) {
            if ($field['name'] == $field_name_or_id || $field['id'] == $field_name_or_id) {
                if ($parameter_name) {
                    return isset($field[$parameter_name]) ? $field[$parameter_name] : null;
                } else {
                    return $field;
                }
            }
        }
        return null;
    }

    /**
     * Возвращает информацию для поля ncSMO_Image
     */
    public function get_smo_image_field() {
        if ($this->_system_table_id && $this->_system_table_id != 2) {
            return array();
        }

        return array(
            'id' => 'ncSMO_Image',
            'name' => 'ncSMO_Image',
            'type' => NC_FIELDTYPE_FILE,
            'format' => '10485760:image/*:fs2',
            'description' => defined('NETCAT_MODERATION_STANDART_FIELD_NC_SMO_IMAGE') ? NETCAT_MODERATION_STANDART_FIELD_NC_SMO_IMAGE : 'ncSMO_Image',
            'not_null' => 0,
            'default' => '',
            'edit_type' => 1,
            'table' => null,
            'search' => 0,
        );
    }
    /**
     * Возвращает информацию для поля ncImage
     */
    public function get_nc_image_field() {
        if ($this->_system_table_id != 2) {
            return array();
        }

        return array(
            'id' => 'ncImage',
            'name' => 'ncImage',
            'type' => NC_FIELDTYPE_FILE,
            'format' => '10485760:image/*:fs2',
            'description' => defined('NETCAT_MODERATION_STANDART_FIELD_NC_IMAGE') ? NETCAT_MODERATION_STANDART_FIELD_NC_IMAGE : 'ncImage',
            'not_null' => 0,
            'default' => '',
            'edit_type' => 1,
            'table' => null,
            'search' => 0,
        );
    }
    /**
     * Возвращает информацию для поля ncIcon
     */
    public function get_nc_icon_field() {
        if ($this->_system_table_id != 2) {
            return array();
        }

        return array(
            'id' => 'ncIcon',
            'name' => 'ncIcon',
            'type' => NC_FIELDTYPE_FILE,
            'format' => '10485760:image/*:fs2:icon',
            'description' => defined('NETCAT_MODERATION_STANDART_FIELD_NC_ICON') ? NETCAT_MODERATION_STANDART_FIELD_NC_ICON : 'ncIcon',
            'not_null' => 0,
            'default' => '',
            'edit_type' => 1,
            'table' => null,
            'search' => 0,
        );
    }

    /**
     * Возвращает массив с параметрами «стандартных» полей
     * @return array
     */
    public function get_nc_file_fields() {
        return array_filter(
            array(
                'ncSMO_Image' => $this->get_smo_image_field(),
                'ncImage' => $this->get_nc_image_field(),
                'ncIcon' => $this->get_nc_icon_field(),
            )
        );
    }

    /**
     * Возвращает массив со свойствами всех пользовательских и встроенных полей типа «файл»
     * @return array
     */
    public function get_all_file_fields() {
        return $this->get_fields(NC_FIELDTYPE_FILE) + $this->get_nc_file_fields();
    }

    /**
     * Проверяет, существует ли поле с указанным именем в компоненте
     * @param string $field_name
     * @param int $field_type
     * @return bool
     */
    public function has_field($field_name, $field_type = null) {
        $this->_load_fields();
        $t = $this->get_field($field_name, 'type');
        if ($field_type) {
            return $t == $field_type;
        }
        else {
            return (bool)$t;
        }
    }

    /**
     * Возвращает имя поля с типа дата с форматом event или event_date,
     * если таковое существует, или false
     */
    public function get_date_field() {
        $key = $this->_class_id . "-" . $this->_system_table_id;

        if (!isset(self::$event_fields[$key])) {
            self::$event_fields[$key] = false;

            foreach ($this->get_fields() as $field) {
                if ($field['type'] != NC_FIELDTYPE_DATETIME) {
                    continue;
                }

                $format = nc_field_parse_format($field['format'], NC_FIELDTYPE_DATETIME);
                if ($format['type'] == 'event' || $format['type'] == 'event_date') {
                    self::$event_fields[$key] = $field['name'];
                    break;
                }
            }
        }

        return self::$event_fields[$key];
    }

    /**
     * @param $srchPat
     * @return array
     */
    public function get_search_query($srchPat, $cc = null) {
        if ($cc != null && nc_core::get_object()->inside_admin) {
            $this->_fields = array_values($this->get_additional_search_fields($cc));
            $this->_field_count = count($this->_fields);
            $srchPatName = "srchPatAdd";
        } else {
            $this->_load_fields();
            $srchPatName = "srchPat";
        }
        // return if search params not set
        if (empty($srchPat)) {
            return array("query" => "", "link" => "");
        }

        $search_param = array();
        if (isset($srchPat['OR']) && $srchPat['OR'] == '1') {
            $search_param[] = "srchPat[OR]=1";
        }

        $search_string = $fullSearchStr = '';
        $or_and = '';
        for ($i = 0, $j = 0; $i < $this->_field_count; $i++) {
            $field = & $this->_fields[$i];
            if ($search_string > '') {
                $or_and = ((isset($srchPat['OR']) && $srchPat['OR'] == '1') ? 'OR' : 'AND');
            }
            if ($field['search']) {
                switch ($field['type']) {
                    case NC_FIELDTYPE_STRING:
                        if ($srchPat[$j] == "") {
                            break;
                        }
                        $search_string .= " " . $or_and . " a.`$field[name]` LIKE '%" . $this->db->escape($srchPat[$j]) . "%'";
                        $search_param[] = "" . $srchPatName . "[" . $j . "]=" . rawurlencode($srchPat[$j]);
                        break;
                    case NC_FIELDTYPE_INT:
                        if (trim($srchPat[$j]) != "") {
                            $search_string .= " " . $or_and . " ";
                            if (trim($srchPat[$j + 1]) != "") {
                                $search_string .= "(";
                            }
                            $search_string .= "a.`$field[name]` >= " . trim((int)$srchPat[$j]);
                            $search_param[] = "" . $srchPatName . "[" . $j . "]=" . trim((int)$srchPat[$j]);
                        }
                        $j++;
                        if (trim($srchPat[$j]) != "") {
                            if (trim($srchPat[$j - 1]) != "") {
                                $search_string .= " AND ";
                            } else {
                                $search_string .= " " . $or_and . " ";
                            }
                            $search_string .= " a.`$field[name]` <= " . trim((int)$srchPat[$j]);
                            if (trim($srchPat[$j - 1]) != "") {
                                $search_string .= ")";
                            }
                            $search_param[] = "" . $srchPatName . "[" . $j . "]=" . trim((int)$srchPat[$j]);
                        }
                        break;
                    case NC_FIELDTYPE_TEXT:
                        if ($srchPat[$j] == "") {
                            break;
                        }
                        $srch_str = $this->db->escape($srchPat[$j]);
                        $search_string .= " " . $or_and . " a.`$field[name]` LIKE '%" . $srch_str . "%'";
                        $search_param[] = "" . $srchPatName . "[" . $j . "]=" . rawurlencode($srchPat[$j]);
                        break;
                    case NC_FIELDTYPE_SELECT:
                        if ($srchPat[$j] == "") {
                            break;
                        }
                        $srchPat[$j] += 0;
                        $search_string .= " " . $or_and . " a.`$field[name]` = " . $srchPat[$j];
                        $search_param[] = "" . $srchPatName . "[" . $j . "]=" . $srchPat[$j];
                        break;
                    case NC_FIELDTYPE_BOOLEAN:
                        if ($srchPat[$j] == "") {
                            break;
                        }
                        $srchPat[$j] += 0;
                        $search_string .= " " . $or_and . " a.`$field[name]` = " . $srchPat[$j];
                        $search_param[] = "" . $srchPatName . "[" . $j . "]=" . $srchPat[$j];
                        break;
                    case NC_FIELDTYPE_FILE:
                        if ($srchPat[$j] == "") {
                            break;
                        }
                        $srch_str = $this->db->escape($srchPat[$j]);
                        $search_string .= " " . $or_and . " SUBSTRING_INDEX(a.`$field[name]`,':',1) LIKE '%" . $srch_str . "%'";
                        $search_param[] = "" . $srchPatName . "[" . $j . "]=" . $srchPat[$j];
                        break;
                    case NC_FIELDTYPE_FLOAT:
                        if (trim($srchPat[$j]) != "") {
                            $search_string .= " " . $or_and . " ";
                            if (trim($srchPat[$j + 1]) != "") {
                                $search_string .= "(";
                            }
                            $srchPat[$j] = str_replace(',', '.', (float)$srchPat[$j]);
                            $search_string .= "a.`$field[name]` >= " . $srchPat[$j];
                            $search_param[] = "" . $srchPatName . "[" . $j . "]=" . $srchPat[$j];
                        }
                        $j++;

                        if (trim($srchPat[$j]) != "") {
                            if (trim($srchPat[$j - 1]) != "") {
                                $search_string .= " AND ";
                            } else {
                                $search_string .= " " . $or_and . " ";
                            }
                            $srchPat[$j] = str_replace(',', '.', (float)$srchPat[$j]);
                            $search_string .= " a.`$field[name]` <= " . $srchPat[$j];
                            if (trim($srchPat[$j - 1]) != "") {
                                $search_string .= ")";
                            }
                            $search_param[] = "" . $srchPatName . "[" . $j . "]=" . $srchPat[$j];
                        }
                        break;
                    case NC_FIELDTYPE_DATETIME:
                        $date_from['d'] = ($srchPat[$j] && ($search_param[] = "".$srchPatName."[" . $j . "]=" . $srchPat[$j]) ? sprintf("%02d", $srchPat[$j]) : false);
                        $j++;
                        $date_from['m'] = ($srchPat[$j] && ($search_param[] = "".$srchPatName."[" . $j . "]=" . $srchPat[$j]) ? sprintf("%02d", $srchPat[$j]) : false);
                        $j++;
                        $date_from['Y'] = ($srchPat[$j] && ($search_param[] = "".$srchPatName."[" . $j . "]=" . $srchPat[$j]) ? sprintf("%04d", $srchPat[$j]) : false);
                        $j++;
                        $date_from['H'] = ($srchPat[$j] && ($search_param[] = "".$srchPatName."[" . $j . "]=" . $srchPat[$j]) ? sprintf("%02d", $srchPat[$j]) : false);
                        $j++;
                        $date_from['i'] = ($srchPat[$j] && ($search_param[] = "".$srchPatName."[" . $j . "]=" . $srchPat[$j]) ? sprintf("%02d", $srchPat[$j]) : false);
                        $j++;
                        $date_from['s'] = ($srchPat[$j] && ($search_param[] = "".$srchPatName."[" . $j . "]=" . $srchPat[$j]) ? sprintf("%02d", $srchPat[$j]) : false);
                        $j++;
                        $date_to['d'] = ($srchPat[$j] && ($search_param[] = "".$srchPatName."[" . $j . "]=" . $srchPat[$j]) ? sprintf("%02d", $srchPat[$j]) : false);
                        $j++;
                        $date_to['m'] = ($srchPat[$j] && ($search_param[] = "".$srchPatName."[" . $j . "]=" . $srchPat[$j]) ? sprintf("%02d", $srchPat[$j]) : false);
                        $j++;
                        $date_to['Y'] = ($srchPat[$j] && ($search_param[] = "".$srchPatName."[" . $j . "]=" . $srchPat[$j]) ? sprintf("%04d", $srchPat[$j]) : false);
                        $j++;
                        $date_to['H'] = ($srchPat[$j] && ($search_param[] = "".$srchPatName."[" . $j . "]=" . $srchPat[$j]) ? sprintf("%02d", $srchPat[$j]) : false);
                        $j++;
                        $date_to['i'] = ($srchPat[$j] && ($search_param[] = "".$srchPatName."[" . $j . "]=" . $srchPat[$j]) ? sprintf("%02d", $srchPat[$j]) : false);
                        $j++;
                        $date_to['s'] = ($srchPat[$j] && ($search_param[] = "".$srchPatName."[" . $j . "]=" . $srchPat[$j]) ? sprintf("%02d", $srchPat[$j]) : false);

                        $date_format_from = ($date_from['Y'] ? '%Y' : '') . ($date_from['m'] ? '%m' : '') . ($date_from['d'] ? '%d' : '') . ($date_from['H'] ? '%H' : '') . ($date_from['i'] ? '%i' : '') . ($date_from['s'] ? '%s' : '');
                        $date_format_to = ($date_to['Y'] ? '%Y' : '') . ($date_to['m'] ? '%m' : '') . ($date_to['d'] ? '%d' : '') . ($date_to['H'] ? '%H' : '') . ($date_to['i'] ? '%i' : '') . ($date_to['s'] ? '%s' : '');

                        if ($date_format_from || $date_format_to) {
                            $search_string .= " " . $or_and . " (";
                        }
                        if ($date_format_from) {
                            $search_string .= " DATE_FORMAT(a.`$field[name]`,'" . $date_format_from . "')>=" . $date_from['Y'] . $date_from['m'] . $date_from['d'] . $date_from['H'] . $date_from['i'] . $date_from['s'];
                        }
                        if ($date_format_to) {
                            if ($date_format_from) {
                                $search_string .= " AND ";
                            }
                            $search_string .= " DATE_FORMAT(a.`$field[name]`,'" . $date_format_to . "')<=" . $date_to['Y'] . $date_to['m'] . $date_to['d'] . $date_to['H'] . $date_to['i'] . $date_to['s'] ;
                        }
                        if ($date_format_from || $date_format_to) {
                            $search_string .= ")";
                        }
                        break;
                    case NC_FIELDTYPE_MULTISELECT:
                        if (!$srchPat[$j]) {
                            $j++;
                            break;
                        }

                        // значения могут быть указаны в виде строки (разделитель '-') или в виде массива с ID элементов списка
                        if (!is_array($srchPat[$j])) {
                            $srchPat[$j] = explode('-', $srchPat[$j]);
                        }

                        $multiselect_conditions = array();
                        foreach ((array)$srchPat[$j] as $v) {
                            $v = (int)$v;
                            if ($v) {
                                $multiselect_conditions[] = "FIND_IN_SET($v, a.`$field[name]`)";
                            }
                        }

                        $j++; // второй параметр — это тип поиска

                        if (empty($multiselect_conditions)) {
                            break;
                        }

                        if (empty($srchPat[$j])) {
                            $srchPat[$j] = 0;
                        }
                        $srchPat[$j] = (int)$srchPat[$j];

                        // Возможные типы поиска по полю множественного выбора:
                        if ($srchPat[$j] === 1) { // в поле должны быть все указанные элементы списка и ничего кроме них
                            $multiselect_operator = 'AND';
                            $multiselect_count_operator = '=';
                        } else if ($srchPat[$j] === 2) { // в поле должен быть хотя бы один из указанных элементов списка
                            $multiselect_operator = 'OR';
                            $multiselect_count_operator = false;
                        } else { // в поле должны быть все указанные элементы списка, но могут быть и другие (по умолчанию)
                            $multiselect_operator = 'AND';
                            $multiselect_count_operator = '>=';
                        }

                        $search_string .= " $or_and (";

                        // поиск значений:
                        $search_string .= implode(" $multiselect_operator ", $multiselect_conditions);
                        // подсчёт количества значений (при записи значения добавляются запятые в начале и конце):
                        if ($multiselect_count_operator) {
                            $search_string .= " AND LENGTH(a.`$field[name]`) - LENGTH(REPLACE(a.`$field[name]`, ',', '')) $multiselect_count_operator " . (count($multiselect_conditions) + 1);
                        }

                        $search_string .= ')';

                        unset($multiselect_conditions, $multiselect_operator, $multiselect_count_operator);

                        $search_param[] = $srchPatName . "[" . ($j - 1) . "]=" . join('-', array_map('intval', $srchPat[$j - 1]));
                        $search_param[] = $srchPatName . "[" . $j . "]=" . $srchPat[$j];

                        break;
                }
                $j++;
            }
        }

        if (!empty($search_string)) {
            $fullSearchStr = " AND( " . $search_string . ")";
        }
        if (!empty($search_param)) {
            $search_params['link'] = join('&amp;', $search_param);
        }
        $search_params['query'] = $fullSearchStr;

        return $search_params;
    }

    /**
     * @param $catalogue
     * @param $sub
     * @param $cc
     * @param int $eval
     * @return string
     */
    public function add_form($catalogue, $sub, $cc, $eval = 0) {
        // в форме добавления могут использоваться различные глобальные переменные... :(
        extract($GLOBALS);

        list($catalogue, $sub, $cc) = func_get_args();
        $eval = func_num_args() > 3 ? func_get_arg(3) : 0;

        $nc_core = nc_Core::get_object();
        $classID = $class_id = $this->_class_id;

        $File_Mode = nc_get_file_mode('Class', $this->_class_id);
        if ($File_Mode) {
            $sub_class_settings = $nc_core->sub_class->get_by_id($cc);
            $file_class = new nc_tpl_component_view($nc_core->CLASS_TEMPLATE_FOLDER, $nc_core->db);
            $file_class->load($sub_class_settings['Real_Class_ID'], $sub_class_settings['File_Path'], $sub_class_settings['File_Hash']);
            $file_class->include_all_required_assets();

            $nc_parent_field_path = $file_class->get_parent_field_path('AddTemplate');
            $nc_field_path = $file_class->get_field_path('AddTemplate');
            if (filesize($nc_field_path)) {
                ob_start();
                include $nc_field_path;
                return ob_get_clean();
            }
        }

        $alter_form = '';
        if ($this->_class_id) {
            $alter_form = $this->get_by_id($this->_class_id, 'AddTemplate');
        }

        if ($alter_form) {
            $result = $alter_form;
        } else {
            $this->_load_fields();
            $result = nc_fields_form('add', $this->_fields, $this->_class_id);
        }
        if ($eval && !$File_Mode) {
            $addForm = null;
            eval(nc_check_eval("\$addForm = \"" . $result . "\"; "));
            return $addForm;
        }
        return $result;
    }

    /**
     * @param int $short
     * @param null|array $filter_additional_fields
     * @return string
     * @throws nc_Exception_Class_Doesnt_Exist
     */
    public function search_form($short = 1, $filter_additional_fields = NULL) {
        if ($this->_class_id) {
            $alter_form = $this->get_by_id($this->_class_id, $short ? 'FullSearchTemplate' : 'SearchTemplate');
            if ($alter_form) {
                return $alter_form;
            }
        }

        $result = nc_fields_form('search', $this->_fields, 0, $filter_additional_fields);

        return $result;
    }

    /**
     * Добавление нового компонента ( шаблона компонента )
     *
     * @param string $class_name - имя компонента
     * @param string $class_group - группа компонента
     * @param array $params - массив параметров компонента
     * @param int $class_template - номер класса, если идёт создание шаблона
     * @param string $type - тип шаблона компонента
     *
     * @throws nc_Exception_DB_Error|nc_Exception_Class_Invalid_Keyword
     * @return int номер созданного компонент
     */
    public function add($class_name, $class_group, $params, $class_template = 0, $type = 'useful') {
        $nc_core = nc_Core::get_object();
        $db = $nc_core->db;
        $class_name = $db->escape($class_name);
        $class_group = $db->escape($class_group);
        $type = $db->escape($type);
        $class_template = intval($class_template);

        $File_Mode = nc_get_file_mode('Class', $class_template);

        if ($File_Mode) {
            $class_editor = new nc_tpl_component_editor($nc_core->CLASS_TEMPLATE_FOLDER, $db);
            $class_editor->load($class_template);
            if (is_array($params)) {
                $template_content = array_merge((array)$nc_core->input->fetch_post(), $params);
            }
        }

        if ($class_template && empty($params['SortBy'])) {
            $params['SortBy'] = 'a.`Priority` ASC, a.`Message_ID` ASC';
        }

        // все параметры компонента
        $params_int = array(
            'AllowTags', 'RecordsPerPage', 'NL2BR', 'UseCaptcha', 'FullPageMode', 'CacheForUser',
            'IsAuxiliary', 'IsOptimizedForMultipleMode', 'IsMultipurpose',
            'DisableBlockMarkup', 'DisableBlockListMarkup',
        );
        if (!$class_template) {
            $params_int[] = 'System_Table_ID';
        }
        $params_int_null = array('MinRecordsInInfoblock', 'MaxRecordsInInfoblock');
        $params_text = array('FormPrefix', 'FormSuffix', 'RecordTemplate', 'SortBy', 'RecordTemplateFull',
            'TitleTemplate', 'AddTemplate', 'EditTemplate', 'AddActionTemplate', 'EditActionTemplate', 'SearchTemplate',
            'FullSearchTemplate', 'SubscribeTemplate', 'Settings', 'AddCond', 'EditCond', 'SubscribeCond',
            'DeleteCond', 'CheckActionTemplate', 'DeleteActionTemplate', 'CustomSettingsTemplate',
            'ClassDescription', 'DeleteTemplate', 'TitleList', 'CompatibleFields');

        if ($File_Mode) {
            $params_text = $class_editor->get_clear_fields($params_text);
            $params['File_Mode'] = 1;
            $params_text[] = 'File_Mode';
        }

        // проверка ключевого слова
        $keyword = trim(nc_array_value($params, 'Keyword'));
        $keyword_validation_result = $this->validate_keyword($keyword, null, $class_template);

        if ($keyword_validation_result !== true) {
            throw new nc_Exception_Class_Invalid_Keyword($keyword_validation_result);
        }

        // добавление имени, группы, ключевого слова
        $query = array("`Class_Name`", "`Class_Group`", "`Keyword`");
        $values = array("'$class_name'", "'$class_group'", "'" . $db->escape($keyword) . "'");

        // добавление шаблона компонента
        if ($class_template) {
            $query[] = "`ClassTemplate`";
            $values[] = "'" . $class_template . "'";
            // System Table ID в любом случае берётся от компонента
            $query[] = "`System_Table_ID`";
            $values[] = "'" . $this->get_by_id($class_template, 'System_Table_ID') . "'";
        }
        // тип шаблона компонента
        if ($type) {
            $query[] = "`Type`";
            $values[] = "'" . $type . "'";
        }

        // добавление всех параметров компонента
        foreach ($params_int as $v) {
            $value = isset($params[$v]) ? intval($params[$v]) : 0;

            $query[] = "`" . $v . "`";
            $values[] = "'" . $value . "'";
        }

        foreach ($params_text as $v) {
            $value = isset($params[$v]) ? $params[$v] : '';

            $query[] = "`" . $v . "`";
            $values[] = "'" . $db->prepare($value) . "'";
        }

        foreach ($params_int_null as $v) {
            $value = isset($params[$v]) && strlen(trim($params[$v])) ? (int)trim($params[$v]) : 'NULL';

            $query[] = "`" . $v . "`";
            $values[] = $value;
        }

        if (!$class_template) {
            $nc_core->event->execute(nc_Event::BEFORE_COMPONENT_CREATED, 0);
        } else {
            $nc_core->event->execute(nc_Event::BEFORE_COMPONENT_TEMPLATE_CREATED, $class_template, 0);
        }

        // собственно добавление
        $SQL = "INSERT INTO `Class` (" . join(', ', $query) . ") VALUES (" . join(', ', $values) . ") ";
        $db->query($SQL);

        if ($db->is_error) {
            throw new nc_Exception_DB_Error($db->last_query, $db->last_error);
        }

        $new_class_id = $db->insert_id;

        if ($File_Mode) {
            $class_editor->save_new_class($new_class_id, $template_content);
        }

        // трансляция события создания компонента
        if (!$class_template) {
            CreateMessageTable($new_class_id, $db);
            $nc_core->event->execute(nc_Event::AFTER_COMPONENT_CREATED, $new_class_id);
        } else {
            $nc_core->event->execute(nc_Event::AFTER_COMPONENT_TEMPLATE_CREATED, $class_template, $new_class_id);
        }

        $this->update_cache_for_multipurpose_templates();

        return $new_class_id;
    }

    /**
     * @param $id
     * @param array $params
     * @return bool
     * @throws nc_Exception_DB_Error|nc_Exception_Class_Invalid_Keyword
     */
    public function update($id, $params = array()) {
        $nc_core = nc_Core::get_object();
        $db = $this->db;

        $id = (int)$id;
        if (!$id || !is_array($params)) {
            return false;
        }

        if (isset($params['action_type']) && $params['action_type'] == 1) {
            $params_int = array(
                'CacheForUser',
                'IsAuxiliary',
                'IsOptimizedForMultipleMode',
                'IsMultipurpose',
                'Main_ClassTemplate_ID'
            );
            $params_int_null = array();
            $params_text = array(
                'Class_Name',
                'Class_Group',
                'Keyword',
                'ObjectName',
                'ObjectNameSingular',
                'ObjectNamePlural',
                'ObjectNameSingularGenitive',
                'CompatibleFields',
            );

            // Проверка ключевого слова
            $keyword = trim(nc_array_value($params, 'Keyword'));
            $keyword_validation_result = $this->validate_keyword($keyword, $id);

            if ($keyword_validation_result !== true) {
                throw new nc_Exception_Class_Invalid_Keyword($keyword_validation_result);
            }

            $old_keyword = $this->get_by_id($id, 'Keyword');

        } else {

            $params_int = array(
                'AllowTags',
                'RecordsPerPage',
                'System_Table_ID',
                'NL2BR',
                'UseCaptcha',
                'FullPageMode',
                'UseAltTitle',
            );

            $params_int_null = array(
                'MinRecordsInInfoblock',
                'MaxRecordsInInfoblock',
            );

            $params_text = array(
                'FormPrefix',
                'FormSuffix',
                'RecordTemplate',
                'SortBy',
                'RecordTemplateFull',
                'TitleTemplate',
                'AddTemplate',
                'EditTemplate',
                'AddActionTemplate',
                'EditActionTemplate',
                'SearchTemplate',
                'FullSearchTemplate',
                'SubscribeTemplate',
                'Settings',
                'AddCond',
                'EditCond',
                'SubscribeCond',
                'DeleteCond',
                'CheckActionTemplate',
                'DeleteActionTemplate',
                'CustomSettingsTemplate',
                'ClassDescription',
                'DeleteTemplate',
                'TitleList',
            );

            $keyword = $old_keyword = null;
        }

        if (empty($ClassTemplate)) {
            $nc_core->event->execute(nc_Event::BEFORE_COMPONENT_UPDATED, $id);
        } else {
            $nc_core->event->execute(nc_Event::BEFORE_COMPONENT_TEMPLATE_UPDATED, $ClassTemplate, $id);
        }

        $File_Mode = nc_get_file_mode('Class', $id);
        if ($File_Mode) {
            $class_editor = new nc_tpl_component_editor($nc_core->CLASS_TEMPLATE_FOLDER, $db);
            $class_editor->load($id);
            $class_editor->save_fields($only_isset_post = true);
            $params_text = $class_editor->get_clear_fields($params_text);
        }

        $query = array();

        foreach ($params as $k => $v) {
            $is_nullable_int = in_array($k, $params_int_null);
            if (!in_array($k, $params_int) && !in_array($k, $params_text) && !$is_nullable_int) {
                continue;
            }

            if ($is_nullable_int && !strlen(trim($v))) {
                if (strlen(trim($v))) {
                    $query[] = "`" . $db->escape($k) . "` = " . (int)trim($v);
                }
                else {
                    $query[] = "`" . $db->escape($k) . "` = NULL";
                }
            }
            else {
                $query[] = "`" . $db->escape($k) . "` = '" . $db->prepare($v) . "'";
            }
        }

        foreach (array('DisableBlockMarkup', 'DisableBlockListMarkup', 'IsMultipurpose') as $int_property) {
            if (isset($params[$int_property]) && strlen($int_property)) {
                $query[] = "`$int_property` = " . (int)$params[$int_property];
            }
        }

        $ClassTemplate = $db->get_var("SELECT `ClassTemplate` FROM `Class` WHERE `Class_ID` = '" . $id . "' ");

        if (!empty($query)) {
            $db->query("UPDATE `Class` SET " . join(",\n        ", $query) . " WHERE `Class_ID` = " . $id);
            if ($db->is_error) {
                throw new nc_Exception_DB_Error($db->last_query, $db->last_error);
            }

            if ($keyword != $old_keyword && $params['action_type'] == 1 && $File_Mode && isset($class_editor)) {
                $class_editor->update_keyword($keyword ?: $id);
            }

            $this->cache_compatible_components($id);
        }

        if (!$ClassTemplate) {
            $nc_core->event->execute(nc_Event::AFTER_COMPONENT_UPDATED, $id);
        } else {
            $nc_core->event->execute(nc_Event::AFTER_COMPONENT_TEMPLATE_UPDATED, $ClassTemplate, $id);
        }

        $this->data = array();
        return true;
    }

    public function get_system_table_id() {
        return $this->_system_table_id;
    }

    /**
     * @return array
     */
    public function get_standart_fields() {
        $db = nc_core('db');

        $sql = "SELECT `FieldsInTableView` FROM `Class` WHERE `Class_ID` = {$this->_class_id}";
        $in_table_fields = @json_decode($db->get_var($sql), true);

        if (!$in_table_fields) {
            $in_table_fields = array();
        }

        $standart_fields = array(
            'User_ID' => NETCAT_MODERATION_STANDART_FIELD_USER_ID,
            'User' => NETCAT_MODERATION_STANDART_FIELD_USER,
            'Priority' => NETCAT_MODERATION_STANDART_FIELD_PRIORITY,
            'Keyword' => NETCAT_MODERATION_STANDART_FIELD_KEYWORD,
            'ncTitle' => NETCAT_MODERATION_STANDART_FIELD_NC_TITLE,
            'ncKeywords' => NETCAT_MODERATION_STANDART_FIELD_NC_KEYWORDS,
            'ncDescription' => NETCAT_MODERATION_STANDART_FIELD_NC_DESCRIPTION,
            'ncSMO_Title' => NETCAT_MODERATION_STANDART_FIELD_NC_SMO_TITLE,
            'ncSMO_Description' => NETCAT_MODERATION_STANDART_FIELD_NC_SMO_DESCRIPTION,
            'ncSMO_Image' => NETCAT_MODERATION_STANDART_FIELD_NC_SMO_IMAGE,
            'IP' => NETCAT_MODERATION_STANDART_FIELD_IP,
            'UserAgent' => NETCAT_MODERATION_STANDART_FIELD_USER_AGENT,
            'Created' => NETCAT_MODERATION_STANDART_FIELD_CREATED,
            'LastUpdated' => NETCAT_MODERATION_STANDART_FIELD_LAST_UPDATED,
            'LastUser_ID' => NETCAT_MODERATION_STANDART_FIELD_LAST_USER_ID,
            'LastUser' => NETCAT_MODERATION_STANDART_FIELD_LAST_USER,
            'LastIP' => NETCAT_MODERATION_STANDART_FIELD_LAST_IP,
            'LastUserAgent' => NETCAT_MODERATION_STANDART_FIELD_LAST_USER_AGENT,
        );

        $result = array();

        foreach ($standart_fields as $field => $description) {
            $result[] = array(
                'id' => $field,
                'name' => $field,
                'description' => $description,
                'standart' => true,
                'in_table_view' => in_array($field, $in_table_fields),
            );
        }

        return $result;
    }

    public function get_additional_search_fields($cc)
    {
        $db = nc_core('db');
        $filter_additional_fields = array(
          'Message_ID' => array(
            'id' => 'Message_ID',
            'name' => 'Message_ID',
            'description' => NETCAT_FILTER_FIELD_MESSAGE_ID,
            'search' => 0,
            'type' => 2,
            'format' => 0,
          ),
          'Created' => array(
            'id' => 'Created',
            'name' => 'Created',
            'description' => NETCAT_FILTER_FIELD_CREATED,
            'search' => 0,
            'type' => 8,
            'format' => 'event;calendar',
          ),
          'LastUpdated' => array(
            'id' => 'LastUpdated',
            'name' => 'LastUpdated',
            'description' => NETCAT_FILTER_FIELD_LAST_UPDATED,
            'search' => 0,
            'type' => 8,
            'format' => 'event;calendar',
          )
        );
        $sql = "SELECT Field_Name FROM FieldFilter WHERE SubClass_ID='" . $cc . "' AND DoSearch='1'";
        $res = $db->get_results($sql, ARRAY_A);

        if (!empty($res)) {
            foreach ($res as $field) {
                $filter_additional_fields[$field['Field_Name']]['search'] = 1;
            }
        }
        return $filter_additional_fields;
    }

    /**
     * @param string $keyword
     * @param int|null $for_component_id    ID компонента или шаблона компонента
     * @param int|null $parent_component_id
     * @return bool|string   возвращает true или текст ошибки
     */
    public function validate_keyword($keyword, $for_component_id = null, $parent_component_id = null) {
        $keyword = trim($keyword);
        $length = strlen($keyword);
        $for_component_id = (int)$for_component_id;

        // Пустое ключевое слово — OK
        if ($length == 0) {
            return true;
        }

        // Длина больше 64 — не ОК
        if ($length > self::MAX_KEYWORD_LENGTH) {
            return sprintf(CONTROL_CLASS_KEYWORD_TOO_LONG, self::MAX_KEYWORD_LENGTH);
        }

        // Только цифры — не ОК
        if (preg_match('/^\d+$/', $keyword)) {
            return CONTROL_CLASS_KEYWORD_ONLY_DIGITS;
        }

        // В ключевом слове допустимы только a-z 0-9 _ .
        if (!preg_match('/^[\w.]+$/', $keyword) || $keyword[0] === '.' || substr($keyword, -1) === '.') {
            return CONTROL_CLASS_KEYWORD_INVALID_CHARACTERS;
        }

        // Определяем родительский компонент (для шаблонов)
        if ($parent_component_id === null && $for_component_id) {
            $parent_component_id = (int)$this->get_by_id($for_component_id, 'ClassTemplate');
        }
        else {
            $parent_component_id = (int)$parent_component_id;
        }

        // Зарезервированные ключевые слова для компонента
        if (!$parent_component_id && in_array($keyword, $this->reserved_keywords)) {
            return sprintf(CONTROL_CLASS_KEYWORD_RESERVED, $keyword);
        }

        // Зарезервированные ключевые слова для шаблонов
        if ($parent_component_id && preg_match('/^(?:assets|images?|styles?|scripts?|fonts?|img|css|js)$/', $keyword)) {
            return sprintf(CONTROL_CLASS_KEYWORD_RESERVED, $keyword);
        }

        // Уникальность ключевого слова в пределах родителя
        $existing_component = $this->db->get_row(
            "SELECT `Class_ID`, `Class_Name`
               FROM `Class`
              WHERE `Keyword` = '$keyword'
                AND `ClassTemplate` = $parent_component_id
                AND `Class_ID` != '$for_component_id'", ARRAY_A); // $keyword безопасен

        // Ключевое слово уже используется
        if ($existing_component) {
            $message = $parent_component_id ? CONTROL_CLASS_KEYWORD_TEMPLATE_NON_UNIQUE : CONTROL_CLASS_KEYWORD_NON_UNIQUE;
            return sprintf($message, $keyword, htmlspecialchars($existing_component['Class_Name'] ?: $existing_component['Class_ID']));
        }

        // Претензий не имеем
        return true;
    }

    /**
     * Возвращает стандартные имена классов для шаблона компонента
     * ("tpl-component-КЛЮЧЕВОЕ_СЛОВО_ИЛИ_ID_КОМПОНЕНТА tpl-template-КЛЮЧЕВОЕ_СЛОВО_ИЛИ_ID_ШАБЛОНА")
     * @param int $template_id
     * @param int $component_id
     * @return string
     */
    public function get_css_class_name($template_id, $component_id = 0) {
        if (!$this->get_by_id($template_id, 'File_Mode')) {
            return '';
        }

        // Если используется многоцелевой шаблон, то базовые стили компонента игнорируются
        if ($this->get_by_id($template_id, 'IsMultipurpose')) {
            $component_id = $template_id;
        }

        if (!$component_id) {
            $component_id = (int)$this->get_by_id($template_id, 'ClassTemplate') ?: $template_id;
        }

        $component_string = trim($this->get_by_id($component_id, 'File_Path'), '/');
        $result = 'tpl-component-' . nc_camelcase_to_dashcase($component_string);

        if ($template_id != $component_id) {
            $template_string = $this->get_by_id($template_id, 'Keyword') ?: $template_id;
            $result .= ' tpl-template-' . nc_camelcase_to_dashcase($template_string);
        }

        return $result;
    }

    /**
     * Возвращает путь к картинке с эскизом списка объектов шаблона компонента
     * от корня сайта
     * @param int $component_id
     * @param bool $only_if_exists   проверять существование файла
     * @return string|null
     */
    public function get_list_preview_relative_path($component_id, $only_if_exists = true) {
        $nc_core = nc_core::get_object();
        if (!$this->get_by_id($component_id, 'File_Mode')) {
            return null;
        }

        $relative_path =
            $nc_core->SUB_FOLDER .
            $nc_core->HTTP_TEMPLATE_PATH .
            'class' .
            $this->get_by_id($component_id, 'File_Path') .
            'Class.png';

        if ($only_if_exists && !file_exists($nc_core->DOCUMENT_ROOT . $relative_path)) {
            return null;
        }

        return $relative_path;
    }

    /**
     *
     */
    public function clear_cache() {
        parent::clear_cache();
        self::$event_fields = array();
        self::$all_fields = array();
    }

    /**
     * Проверяет, нужно (можно) ли добавлять дополнительную разметку (div.tpl-*) при выводе блока.
     * @param $component_template_id
     * @return bool
     * @throws Exception
     * @throws nc_Exception_Class_Doesnt_Exist
     */
    public function can_add_block_markup($component_template_id) {
        if (!$this->get_by_id($component_template_id, 'File_Mode')) {
            return false;
        }

        if ($this->get_by_id($component_template_id, 'DisableBlockMarkup')) {
            return false;
        }

        $template_type = $this->get_by_id($component_template_id, 'Type');
        if ($template_type === 'rss' || $template_type === 'xml') {
            return false;
        }

        $page_type = $nc_core = nc_core::get_object()->page->get_routing_result('format');

        return !($page_type === 'rss' || $page_type === 'xml');
    }

    /**
     * Проверяет, можно ли добавлять разметку вокруг списка объектов
     * @param int $component_template_id
     * @return bool
     * @throws nc_Exception_Class_Doesnt_Exist
     */
    public function can_add_block_list_markup($component_template_id) {
        return
            $this->can_add_block_markup($component_template_id) &&
            !$this->get_by_id($component_template_id, 'DisableBlockListMarkup');
    }

    /**
     * Получение пути к файлу компонента или шаблона компонента по имени или пути
     * (из пути берется имя файла)
     * @param int|string $component_id - id или keyword компонента
     * @param int|string|null $template_keyword - id или keyword шаблона компонента
     * @param string $file_name_or_path - название или путь к файлу
     *               В качестве названия лучше использовать константы nc_Component::FILE_*
     *               В файлах шаблонов лучше использовать __FILE__
     * @return null|string
     */
    public function get_template_file_path($file_name_or_path, $component_id, $template_keyword = null) {
        $nc_core = nc_core::get_object();

        if ($template_keyword) {
            $component = $this->get_component_template_by_keyword($component_id, $template_keyword);
        } else {
            try {
                $component = $this->get_by_id($component_id);
            } catch (nc_Exception_Class_Doesnt_Exist $e) {
                return null;
            }
        }
        if (empty($component)) {
            return null;
        }
        $file_name = $file_name_or_path;
        if (file_exists($file_name)) {
            $file_name = pathinfo($file_name, PATHINFO_BASENAME);
        }
        $component_file_path = rtrim($nc_core->CLASS_TEMPLATE_FOLDER, '/') . $component['File_Path'] . $file_name;
        if (!file_exists($component_file_path)) {
            return null;
        }
        return $component_file_path;
    }

    public function get_object_name_template($prefix = '', $suffix = '') {
        return "$prefix%s$suffix";
    }

    public function get_default_object_name_template() {
        return NETCAT_MODERATION_OBJECT . ' #%d';
    }

    public function get_object_name_field() {
        try {
            $object_name_field = $this->get_by_id($this->_class_id, 'ObjectName');
        } catch (nc_Exception_Class_Doesnt_Exist $e) {
            return '';
        }

        return $object_name_field;
    }

    public function get_possible_object_name_field() {
        $object_name_field = $this->get_object_name_field();

        if ($object_name_field) {
            return $object_name_field;
        }

        $component_field_names = $this->get_fields(NC_FIELDTYPE_STRING, 0);
        $matches = array_intersect($this->possible_fields_with_object_name, $component_field_names);

        return reset($matches) ?: 'Message_ID';
    }

    public function get_compatible_components_by_id($class_id) {
        $class_id = (int)$class_id;

        try {
            $fields_plain = $this->get_by_id($class_id, 'CompatibleFields');
        } catch (nc_Exception_Class_Doesnt_Exist $e) {
            return array();
        }

        if (!$fields_plain) {
            return array();
        }

        if (trim($fields_plain) === '*') {
            return $this->db->get_col(
                "SELECT DISTINCT `c`.`Class_ID` 
                FROM `Class` AS `c` JOIN `Field` AS `f` USING (`Class_ID`)
                WHERE `c`.`ClassTemplate` = 0 AND `c`.`Class_ID` != $class_id"
            );
        }

        $fields = preg_split('@\R@', $fields_plain);
        $fields = array_filter($fields);

        if (!$fields) {
            return array();
        }

        $escaped_fields = array();

        foreach ($fields as $field) {
            $escaped_fields[] = "'{$this->db->escape($field)}'";
        }

        $fields_string = implode(', ', $escaped_fields);
        $fields_count = count($escaped_fields);

        return $this->db->get_col(
            "SELECT DISTINCT `Field`.`Class_ID`
             FROM `Field`
             LEFT JOIN `Class` ON `Field`.`Class_ID` = `Class`.`Class_ID`
             WHERE `Field`.`Class_ID` != $class_id
             AND `Field_Name` IN ($fields_string)
             AND `ClassTemplate` = 0
             GROUP BY `Field`.`Class_ID`
             HAVING COUNT(`Field`.`Class_ID`) = $fields_count;"
        );
    }

    public function cache_compatible_components($class_id) {
        $class_id = (int)$class_id;

        try {
            $component = $this->get_by_id($class_id);
        } catch (nc_Exception_Class_Doesnt_Exist $e) {
            return false;
        }

        $this->clear_compatible_components_cache_by_id($class_id);

        if (!$component['IsMultipurpose'] || !$component['CompatibleFields']) {
            return false;
        }

        $compatible_components = $this->get_compatible_components_by_id($class_id);

        foreach ($compatible_components as $compatible_component) {
            $this->db->query("INSERT INTO `Class_Multipurpose_Template_Cache` (`Class_ID`, `Compatible_Class_ID`)
                              VALUES ($class_id, $compatible_component);");
        }

        return true;
    }

    public function clear_compatible_components_cache_by_id($class_id) {
        $class_id = (int)$class_id;

        return $this->db->query("DELETE FROM `Class_Multipurpose_Template_Cache` WHERE `Class_ID` = $class_id;");
    }

    public function delete_compatible_components_cache_by_id($class_id) {
        $class_id = (int)$class_id;

        return $this->db->query("DELETE FROM `Class_Multipurpose_Template_Cache` WHERE `Class_ID` = $class_id OR `Compatible_Class_ID` = $class_id;");
    }

    public function is_template_compatible_with_component($template_id, $class_id) {
        $template_id = (int)$template_id;
        $class_id = (int)$class_id;

        return (bool)$this->db->get_var(
            "SELECT 1 FROM `Class_Multipurpose_Template_Cache` WHERE `Class_ID` = $template_id AND `Compatible_Class_ID` = $class_id"
        );
    }

    public function update_cache_for_multipurpose_templates() {
        $multipurpose_templates = $this->db->get_col('SELECT `Class_ID` FROM `Class` WHERE `IsMultipurpose` = 1');

        foreach ($multipurpose_templates as $multipurpose_template) {
            $this->cache_compatible_components($multipurpose_template);
        }
    }

    public function get_multipurpose_templates_for_component($class_id, $optimized_for_multiple_mode_only = false) {
        $class_id = (int)$class_id;

        $optimized_for_multiple_mode_only_condition = '';

        if ($optimized_for_multiple_mode_only) {
            $optimized_for_multiple_mode_only_condition = 'AND `Class`.`IsOptimizedForMultipleMode` = 1';
        }

        return (array)$this->db->get_results(
            "SELECT `Class`.`Class_ID`,
             `Class`.`Class_Group`,
             `Class`.`Class_Name`,
             `Class`.`IsOptimizedForMultipleMode`
             FROM `Class`
             LEFT JOIN `Class_Multipurpose_Template_Cache` ON `Class`.`Class_ID` = `Class_Multipurpose_Template_Cache`.`Class_ID`
             WHERE `Class_Multipurpose_Template_Cache`.`Compatible_Class_ID` = $class_id
             AND `Class`.`ClassTemplate` = 0
             $optimized_for_multiple_mode_only_condition
             ORDER BY `Class`.`Class_Group`, `Class`.`Priority`, `Class`.`Class_ID`",
            ARRAY_A
        );
    }

    public function get_full_page_area_keyword($object_id) {
        return "O_{$this->_class_id}_{$object_id}";
    }

    public function get_full_page_area_keyword_regexp() {
        return "^O_{$this->_class_id}_[0-9]+$";
    }

    public function is_full_page_area($area_keyword) {
        return preg_match('/^O_\d+_\d+$/', $area_keyword);
    }

    public function get_full_page_area_object($area_keyword) {
        if (!preg_match('/^O_(\d+)_(\d+)$/', $area_keyword, $matches)) {
            return array(null, null);
        }
        return array($matches[1], $matches[2]);
    }

    public function create_default_full_page_block($object_id, array $block_properties) {
        $nc_core = nc_core::get_object();
        $db = $nc_core->db;
        $component_id = $this->_class_id;

        $keyword = $this->get_full_page_area_keyword($object_id);

        $block_properties += array(
            'Sub_Class_Type' => 'object_partial',
            'AreaKeyword' => $keyword,
            'Class_ID' => $component_id,
            'Sub_Class_Name' => $nc_core->message->get_object_name($component_id, $object_id),
            'EnglishName' => strtolower($keyword),
            // 'Catalogue_ID' => 0, // см. create(); не привязывается к сайту для облегчения выборки с $ignore_catalogue
        );

        $nc_core->sub_class->create($component_id, $block_properties);

        $db->query(
            "UPDATE `Message{$component_id}` 
            SET `ncFullPageCreated` = 1
            WHERE `Message_ID` = $object_id"
        );
    }

    public function get_full_page_templates_for_area($area_keyword, $include_generic_compatible_multipurpose_templates = false) {
        list($component_id, $object_id) = $this->get_full_page_area_object($area_keyword);
        if (!$component_id) {
            return array();
        }

        $nc_core = nc_core::get_object();
        $db = $nc_core->db;
        $object_name = $nc_core->component->get_by_id($component_id, 'Class_Name');
        $group = $db->escape(nc_ucfirst($object_name ?: NETCAT_MODERATION_OBJECT));

        $own_templates = (array)$db->get_results(
            "SELECT `Class_ID`, 
                    '$group' AS `Class_Group`, 
                    `Class_Name`, 
                    $component_id AS `ClassTemplate`, 
                    `IsOptimizedForMultipleMode`, 
                    `IsMultipurpose`, 
                    `Type`, 
                    `File_Path`, 
                    `File_Hash`, 
                    1 AS `_IsObjectTemplate`
               FROM `Class` AS `component`
              WHERE (`Class_ID` = $component_id OR `ClassTemplate` = $component_id)
                AND `File_Mode` = 1
                AND `Type` IN ('useful', 'object_partial')
                AND `IsAuxiliary` = 0
                AND `IsOptimizedForMultipleMode` = 1
             ORDER BY `Class_Group`, `ClassTemplate` = 0, `Priority`, `Class_ID`",
            ARRAY_A
        );

        $multipurpose_template_types = $include_generic_compatible_multipurpose_templates ? "'useful', 'object_partial'" : "'object_partial'";
        $multipurpose_templates = (array)$db->get_results(
            "SELECT `mp`.`Class_ID`, 
                    '$group' AS `Class_Group`, 
                    `Class_Name`,
                    $component_id AS `ClassTemplate`, 
                    `IsOptimizedForMultipleMode`, 
                    `IsMultipurpose`,
                    `Type`, 
                    `File_Path`, 
                    `File_Hash`, 
                     1 AS `_IsObjectTemplate`
               FROM `Class_Multipurpose_Template_Cache` AS `mp`
                    LEFT JOIN `Class` USING (`Class_ID`)
              WHERE `mp`.`Compatible_Class_ID` = $component_id
                AND `Type` IN ($multipurpose_template_types)
                AND `IsOptimizedForMultipleMode` = 1
              ORDER BY `Class_Group`, `ClassTemplate` = 0, `Priority`, `Class_ID`",
            ARRAY_A
        );

        $all_templates = array_merge($own_templates, $multipurpose_templates);

        foreach ($all_templates as $i => $component) {
            if ($component['Type'] === 'useful' && $this->is_full_page_template_empty($component['Class_ID'])) {
                unset($all_templates[$i]);
            }
        }

        return $all_templates;
    }

    protected function is_full_page_template_empty($component_id) {
        $template_path = $this->get_template_file_path(nc_component::FILE_RECORD_TEMPLATE_FULL, $component_id);
        if (!file_exists($template_path)) {
            return true;
        }

        $ignored_content = array('<?php', '<?', 'require_once', 'require', '$nc_parent_field_path', ';', '(', ')', '?>');
        $template_content = file_get_contents($template_path);
        $template_content = str_replace($ignored_content, '', $template_content);

        return trim($template_content) === '';
    }
}
