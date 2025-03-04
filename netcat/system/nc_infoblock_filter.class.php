<?php

class nc_infoblock_filter {
    static protected $table = 'Filter';
    static protected $primary_key = 'Filter_ID';

    static protected $filter_types = array(
        NC_FIELDTYPE_STRING => array('string', 'list', 'multiple'),
        NC_FIELDTYPE_TEXT => array('string'),
        NC_FIELDTYPE_INT => array('number'),
        NC_FIELDTYPE_FLOAT => array('number'),
        NC_FIELDTYPE_SELECT => array('list', 'multiple'),
        NC_FIELDTYPE_MULTISELECT => array('list', 'multiple'),
        NC_FIELDTYPE_DATETIME => array('date', 'date_range'),
    );

    static protected $instances = array();

    protected $id;
    protected $fields = array();
    protected $data_infoblock_id;
    protected $data_table_name;
    /** @var null|array */
    protected $values_query_suffix = null;


    /**
     * Возвращает объект фильтра по ID блока с фильтром
     * @param int $filter_infoblock_id
     * @return nc_infoblock_filter
     * @throws Exception
     */
    public static function get_by_filter_infoblock_id($filter_infoblock_id) {
        return self::load_by_field_value('Filter_Sub_Class_ID', $filter_infoblock_id);
    }

    /**
     * Возвращает объект фильтра по ID блока с данными
     * @param int $data_infoblock_id
     * @return nc_infoblock_filter
     * @throws Exception
     */
    public static function get_by_data_infoblock_id($data_infoblock_id) {
        return self::load_by_field_value('Data_Sub_Class_ID', $data_infoblock_id);
    }

    /**
     * Вспомогательный метод для загрузки и инциализации объекта фильтра по значению одного из полей
     * @param string $filter_table_field
     * @param int $filter_table_value
     * @return nc_infoblock_filter
     * @throws Exception
     */
    protected static function load_by_field_value($filter_table_field, $filter_table_value) {
        if (isset(self::$instances[$filter_table_field][$filter_table_value])) {
            $filter = self::$instances[$filter_table_field][$filter_table_value];
        } else {
            $filter_data = nc_db()->get_row("SELECT * FROM `" . self::$table . "` WHERE `$filter_table_field` = " . (int)$filter_table_value, ARRAY_A);
            $filter = new self($filter_data);
        }
        return $filter;
    }

    /**
     * Возвращает массив с возможными типами фильтров для разных типов полей
     * @return array тип поля => [типы фильтров]
     */
    static public function get_filter_types() {
        return self::$filter_types;
    }

    /**
     * Возвращает массив с типами полей, для которых может быть применён фильтр
     * @return array
     */
    public static function get_field_types() {
        return array_keys(self::get_filter_types());
    }

    /**
     * Создаёт запись о фильтре в БД
     * @param array $properties
     * @return int|null
     * @throws Exception
     */
    public static function create(array $properties) {
        $filter_id = nc_db_table::make(self::$table)->insert($properties);
        if (!$filter_id) {
            throw new Exception("Unable to create filter \n" . nc_db()->last_error);
        }
        self::update_filtered_infoblock($properties['Data_Sub_Class_ID']);
        return $filter_id;
    }

    /**
     * Обновляет запись о фильтре в БД
     * @param array $properties
     * @return bool
     * @throws Exception
     */
    public static function update(array $properties) {
        $db = nc_db();
        $old_filtered_infoblock_id = $db->get_var("SELECT `Data_Sub_Class_ID` FROM `" . self::$table . "` WHERE `" . self::$primary_key . "` = " . (int)$properties['Filter_ID']);
        $filter_id = nc_db_table::make(self::$table, self::$primary_key)->update($properties);
        if (!$filter_id) {
            throw new Exception("Unable to update filter \n" . $db->last_error);
        }
        if ($old_filtered_infoblock_id != $properties['Data_Sub_Class_ID']) {
            self::update_filtered_infoblock($old_filtered_infoblock_id);
            self::update_filtered_infoblock($properties['Data_Sub_Class_ID']);
        }
        return $filter_id;
    }

    /**
     * Обновляет значение HasFilter у фильтруемого инфоблока
     * @param int $filtered_infoblock_id
     */
    public static function update_filtered_infoblock($filtered_infoblock_id) {
        $db = nc_db();
        $filtered_infoblock_id = (int)$filtered_infoblock_id;
        if ($filtered_infoblock_id) {
            $has_filter = (int)$db->get_var("SELECT 1 FROM `" . self::$table . "` WHERE `Data_Sub_Class_ID` = $filtered_infoblock_id LIMIT 1");
            $db->query("UPDATE `Sub_Class` SET `HasFilter` = $has_filter WHERE `Sub_Class_ID` = $filtered_infoblock_id");
        }
    }

    protected function __construct(array $filter_data = null) {
        if ($filter_data) {
            $this->init($filter_data);
        }
    }

    /**
     * Инициализирует все необходимые переменные, заполняет данные полей для конкретного фильтра.
     * @param array $filter_data
     * @throws Exception
     */
    protected function init(array $filter_data) {
        $nc_core = nc_Core::get_object();
        $this->id = $filter_data['Filter_ID'];
        $this->data_infoblock_id = $filter_data['Data_Sub_Class_ID'];
        $data_component = $nc_core->get_component_by_infoblock_id($this->data_infoblock_id);
        $filter_fields = (array)json_decode($filter_data['Settings'], true);

        foreach ($filter_fields as $field_name => $field_settings) {
            $data_field = $data_component->get_field($field_name);
            $filter_fields[$field_name]['field_data'] = $data_field;
            $filter_fields[$field_name]['values'] = null;
        }
        $this->fields = $filter_fields;

        $filter_values = (array)nc_array_value($nc_core->input->fetch_get('filter'), $this->data_infoblock_id, array());
        if ($filter_values) {
            $this->set_filter_values($filter_values);
        }

        if ($data_component->get_system_table_id()) {
            $this->data_table_name = $nc_core->get_system_table_name_by_id($data_component->get_system_table_id());
        } else {
            $this->data_table_name = 'Message' . $nc_core->sub_class->get_by_id($this->data_infoblock_id, 'Class_ID');
        }

        // кэширование
        self::$instances['Data_Sub_Class_ID'][$this->data_infoblock_id] = $this;
        self::$instances['Filter_Sub_Class_ID'][$filter_data['Filter_Sub_Class_ID']] = $this;
    }

    /**
     * Возвращает ID фильтра (записи о фильтре в таблице Filter). Не то же, что ID блока с фильтром.
     * @return int|null
     */
    public function get_id() {
        return $this->id;
    }

    /**
     * Возвращает ID фильтруемого инфоблока (null, если фильтра по факту нет)
     * @return int|null
     */
    public function get_data_infoblock_id() {
        return $this->data_infoblock_id;
    }

    /**
     * Возвращает данные полей фильтра
     * @return array|null
     */
    public function get_fields() {
        return $this->fields;
    }

    /**
     * Возвращает true, если у фильтра заданы поля (фильтр существует)
     * @return bool
     */
    public function exists() {
        return !empty($this->fields);
    }

    /**
     * Возвращает строку с настройками полей (JSON). Используется при редактировании настроек.
     * @return string
     */
    public function get_settings_string() {
        if ($this->id) {
            return nc_db()->get_var("SELECT `Settings` FROM `Filter` WHERE `Filter_ID` = " . (int)$this->id);
        }
        return '{}';
    }

    /**
     * Устанавливает значения полей, применяемые для фильтрации данных
     * @param array $data
     */
    public function set_filter_values(array $data = array()) {
        foreach ($data as $field_name => $values) {
            if (isset($this->fields[$field_name])) {
                $this->fields[$field_name]['values'] = $values;
            }
        }
    }

    /**
     * Вспомогательный метод, возвращающий суффикс запроса для метода get_field_possible_values().
     * @return string
     */
    protected function get_values_query_suffix() {
        if ($this->values_query_suffix === null) {
            $partial = new nc_partial_object_list($this->data_infoblock_id);
            $query_params = $partial->get_filter_query_where();

            $query_suffix = ' ' . $query_params['join'];
            $query_where = $query_params['where'];
            if (strpos($query_where, 'sub.`Catalogue_ID`') !== false) {
                $query_suffix .= ' LEFT JOIN `Subdivision` AS sub ON a.`Subdivision_ID` = sub.`Subdivision_ID`';
            }
            $query_suffix .= ($query_where ? ' WHERE ' . $query_where : '');

            $this->values_query_suffix = $query_suffix;
        }
        return $this->values_query_suffix;
    }

    /**
     * Получаем возможные значения для полей фильтра (пограничные значения, элементы списка)
     *
     * @param string $field_name
     * @param string|null $filter_type если не задан, берётся из настроек поля
     * @return array
     */
    public function get_field_possible_values($field_name) {
        $nc_core = nc_Core::get_object();
        $db = $nc_core->db;

        if (!isset($this->fields[$field_name])) {
            return array();
        }

        $field = $this->fields[$field_name];
        $field_data = $field['field_data'];

        $table_name = $this->data_table_name;
        $query_suffix = $this->get_values_query_suffix();

        if ($field_data['type'] == NC_FIELDTYPE_STRING) {
            return $db->get_col(
                "SELECT DISTINCT a.`$field_data[name]` 
                 FROM `$table_name` AS a 
                 $query_suffix
                 ORDER BY a.`$field_data[name]`"
            );
        }
        if ($field_data['type'] == NC_FIELDTYPE_SELECT) {
            $list_ids = $db->get_col("SELECT DISTINCT a.`$field_data[name]`  FROM `$table_name` AS a " . $query_suffix);
            if ($list_ids) {
                $list = $field_data['table'];
                return $db->get_results(
                    "SELECT `{$list}_ID` AS `id`, `{$list}_Name` AS `name`
                     FROM `Classificator_{$list}`
                     WHERE `{$list}_ID` IN (" . implode(',', $list_ids) . ")".
                    $this->get_list_order_by($list),
                    ARRAY_A,
                    'id'
                );
            }
        }
        if ($field_data['type'] == NC_FIELDTYPE_MULTISELECT) {
            $list_ids_raw = join('', $db->get_col("SELECT a.`" . $field_data['name'] . "` FROM `" . $table_name . "` AS a " . $query_suffix));
            $list_ids = array_unique(array_filter(explode(',', $list_ids_raw)));
            if ($list_ids) {
                $list = $field_data['table'];
                return $db->get_results(
                    "SELECT `{$list}_ID` AS id, `{$list}_Name` AS name
                     FROM `Classificator_{$list}`
                     WHERE `{$list}_ID` IN (" . implode(',', $list_ids) . ")" .
                    $this->get_list_order_by($list),
                    ARRAY_A,
                    'id'
                );
            }
        }

        return array();
    }

    /**
     * Возвращает ORDER BY для выборки из списка
     * @param string $list_keyword
     * @return string
     */
    protected function get_list_order_by($list_keyword) {
        return nc_core::get_object()->list->get_order_by($list_keyword);
    }

    /**
     * Возвращает массив из двух элементов — минимального и максимального значения
     * @param $field_name
     * @return array
     */
    protected function get_field_possible_values_range($field_name) {
        $nc_core = nc_Core::get_object();
        $db = $nc_core->db;

        if (!isset($this->fields[$field_name])) {
            return array();
        }

        $field = $this->fields[$field_name];
        $field_data = $field['field_data'];

        $table_name = $this->data_table_name;
        $query_suffix = $this->get_values_query_suffix();

        return $db->get_row(
            "SELECT MIN(a.`" . $field_data['name'] . "`) AS `min`, MAX(a.`" . $field_data['name'] . "`) AS `max`
            FROM `" . $table_name . "` AS a " . $query_suffix,
            ARRAY_N
        ) ?: array(null, null);
    }

    /**
     * Возвращает поле фильтра с использованием стандартной для фильтра разметки (как в методе get_form())
     * @param string $field_name
     * @param array $options
     * @return string|null
     */
    public function get_field_input($field_name, array $options = array()) {
        if (!isset($this->fields[$field_name])) {
            return null;
        }

        $field = $this->fields[$field_name];

        $field_name = $field['field_data']['name'];
        $result =
            '<div class="tpl-filter-row"><div class="tpl-filter-description' .
            (isset($options['description_class']) ? ' ' . $options['description_class'] : '') .
            '">' . $field['field_data']['description'] . '</div>';

        switch ($field['filter_type']) {
            // текстовое поле
            case 'string':
                $result .= '<div class="tpl-filter-string"><input type="text" name="filter[' . $this->data_infoblock_id . '][' . $field_name . ']" value="' . htmlspecialchars($field['values']) . '"></div>';
                break;

            // числовое поле от...до
            case 'number':
                // list($min, $max) = $this->get_field_possible_values_range($field_name);
                $result .= '<div class="tpl-filter-number">' .
                    '<div class="tpl-filter-number-from"><label>' . NC_FILTER_FORM_NUMBER_FROM . '</label>' .
                    '<input type="text" name="filter[' . $this->data_infoblock_id . '][' . $field_name . '][min]" value="' . htmlspecialchars($field['values']['min']) . '"></div>' .
                    '<div class="tpl-filter-number-to"><label>' . NC_FILTER_FORM_NUMBER_TO . '</label> ' .
                    '<input type="text" name="filter[' . $this->data_infoblock_id . '][' . $field_name . '][max]" value="' . htmlspecialchars($field['values']['max']) . '"></div>' .
                    '</div>';
                break;

            // список
            case 'list':
                $result .= '<div class="tpl-filter-list"><select name="filter[' . $this->data_infoblock_id . '][' . $field_name . ']">';
                $result .= '<option value="">' . NETCAT_MODERATION_LISTS_CHOOSE . '</option>';
                $field_possible_values = $this->get_field_possible_values($field_name);
                if (!$field_possible_values) {
                    return null;
                }
                foreach ($field_possible_values as $value) {
                    if ($field['field_type'] == NC_FIELDTYPE_STRING) {
                        $result .= '<option value="' . htmlspecialchars($value) . '"' . ($value == $field['values'] ? ' selected' : null) . '>' . htmlspecialchars($value) . '</option>';
                    } else {
                        $result .= '<option value="' . htmlspecialchars($value['id']) . '"' . ($value['id'] == $field['values'] ? ' selected' : null) . '>' . htmlspecialchars($value['name']) . '</option>';
                    }
                }
                $result .= '</select></div>';
                break;

            // множественный выбор
            case 'multiple':
                $result .= '<div class="tpl-filter-multiple">';
                $field_possible_values = $this->get_field_possible_values($field_name);
                if (!$field_possible_values) {
                    return null;
                }
                foreach ($field_possible_values as $value) {
                    $result .= '<label>';
                    if ($field['field_type'] == NC_FIELDTYPE_STRING) {
                        $result .= '<input type="checkbox" name="filter[' . $this->data_infoblock_id . '][' . $field_name . '][]" value="' . htmlspecialchars($value) . '"' . (isset($field['values']) && in_array($value, $field['values']) ? ' checked' : null) . '> ' . htmlspecialchars($value);
                    } else {
                        $result .= '<input type="checkbox" name="filter[' . $this->data_infoblock_id . '][' . $field_name . '][]" value="' . htmlspecialchars($value['id']) . '"' . (isset($field['values']) && in_array($value['id'], $field['values']) ? ' checked' : null) . '> ' . htmlspecialchars($value['name']);
                    }
                    $result .= '</label>';
                }
                $result .= '</div>';
                break;

            // дата
            case 'date':
                $result .= '<div class="tpl-filter-date">';
                $result .= '<input type="date" name="filter[' . $this->data_infoblock_id . '][' . $field_name . ']" value="' . htmlspecialchars($field['values']) . '">';
                $result .= '</div>';
                break;

            // дата от...до
            case 'date_range':
                // list($min, $max) = $this->get_field_possible_values_range($field_name);
                // $min = $min ? current(explode(' ', $min)) : $min;
                // $max = $max ? current(explode(' ', $max)) : $max;
                $result .= '<div class="tpl-filter-date">';
                $result .= '<div class="tpl-filter-date-from"><label>' . NC_FILTER_FORM_DATE_FROM . '</label><input type="date" name="filter[' . $this->data_infoblock_id . '][' . $field_name . '][min]" value="' . htmlspecialchars($field['values']['min']) . '"></div>';
                $result .= '<div class="tpl-filter-date-to"><label>' . NC_FILTER_FORM_DATE_TO . '</label><input type="date" name="filter[' . $this->data_infoblock_id . '][' . $field_name . '][max]" value="' . htmlspecialchars($field['values']['max']) . '"></div>';
                $result .= '</div>';
                break;
        }
        $result .= '</div>';

        return $result;
    }

    /**
     * Возвращает кнопку применения фильтра с использованием стандартной для фильтра разметки (как в методе get_form())
     * @param string|null $caption
     * @return string
     */
    public function get_submit_button($caption = null) {
        return '<div class="tpl-filter-row"><button type="submit">' . ($caption ?: NC_FILTER_SUBMIT) . '</button></div>';
    }

    /**
     * Возвращает action формы фильтра
     * @param string $action_type куда ведёт форма фильтра:
     *      'subdivision' (по умолчанию) — на страницу раздела
     *      'infoblock' — на страницу инфоблока
     * @return string
     */
    public function get_form_action($action_type = 'subdivision') {
        $nc_core = nc_core::get_object();
        if ($action_type === 'infoblock' || $action_type === 'cc') {
            $action = nc_infoblock_path($this->data_infoblock_id);
        } else {
            $action = nc_folder_path($nc_core->sub_class->get_by_id($this->data_infoblock_id, 'Subdivision_ID'));
        }
        return $action;
    }

    /**
     * Стандартная форма фильтра.
     * @param array $options
     *      'submit' - текст для кнопки сабмита фильтра
     *      'action_type' - action для формы. subdivision - адрес подраздела, infoblock или cc - адрес инфоблока
     *      'form_class' - class для формы
     *      'description_class' - дополнительный class для названия поля
     * @return string
     * @throws Exception
     */
    public function get_form(array $options = array()) {
        $nc_core = nc_Core::get_object();
        if (empty($this->fields)) {
            return $nc_core->admin_mode ? '<div class="tpl-filter-not-configured">' . NC_FILTER_NOT_CONFIGURED . '</div>' : '';
        }

        if (!$options['action_type']) {
            $options['action_type'] = 'subdivision';
        }

        $result =
            '<form action="' . $this->get_form_action($options['action_type']) . '" method="get"' .
            ($options['form_class'] ? ' class="' . $options['form_class'] . '"' : '') .
            '>';

        foreach ($this->fields as $field_name => $field) {
            $result .= $this->get_field_input($field_name, $options);
        }

        $result .= $this->get_submit_button($options['submit']);
        $result .= '</form>';

        return $result;
    }

    /**
     * Возвращает массив с HTML-элементами для полей и кнопкой отправки фильтра
     * @param array $options
     * @return array
     */
    public function get_form_elements(array $options = array()) {
        $result = array();
        if (!$this->fields) {
            return array();
        }
        foreach ($this->fields as $field_name => $field) {
            $result[$field_name]['html'] = $this->get_field_input($field_name, $options);
        }
        $result = array_filter($result);
        if ($result) {
            $result['#submit']['html'] = $this->get_submit_button(nc_array_value($options, 'submit'));
        }
        return $result;
    }

    /**
     * Возвращает условия для WHERE в выборке в списке объектов
     * @return string
     */
    public function get_query_conditions() {
        $nc_core = nc_Core::get_object();
        $conditions_query = array();

        foreach ($this->fields as $field_name => $field) {
            if ($field['values'] === null) {
                continue;
            }
            switch ($field['filter_type']) {
                case 'date_range':
                case 'number':
                    if (isset($field['values']['min']) && $field['values']['min'] !== '') {
                        $conditions_query[] = "a.`" . $field_name . "` >= '" . $nc_core->db->escape($field['values']['min']) . "'";
                    }
                    if (isset($field['values']['max']) && $field['values']['max'] !== '') {
                        $conditions_query[] = "a.`" . $field_name . "` <= '" . $nc_core->db->escape($field['values']['max']) . "'";
                    }
                    break;

                case 'string':
                    if ($field['values'] !== '') {
                        $conditions_query[] = "a.`" . $field_name . "` LIKE '%" . $nc_core->db->escape($field['values']) . "%'";
                    }
                    break;

                case 'list':
                    if ($field['field_data']['type'] == NC_FIELDTYPE_MULTISELECT) {
                        if ($field['values']) {
                            $conditions_query[] = "FIND_IN_SET(" . (int)$field['values'] . ", a.`" . $field_name . "`)";
                        }
                    } else if ($field['values'] !== '') {
                        $conditions_query[] = "a.`" . $field_name . "` = '" . $nc_core->db->escape($field['values']) . "'";
                    }
                    break;

                case 'multiple':
                    if (is_array($field['values']) && !empty($field['values'])) {
                        // список или текст
                        if ($field['field_data']['type'] == NC_FIELDTYPE_STRING || $field['field_data']['type'] == NC_FIELDTYPE_SELECT) {
                            $field['values'] = array_map(function ($v) use ($nc_core) {
                                return "'" . $nc_core->db->escape($v) . "'";
                            }, $field['values']);

                            $conditions_query[] = "a.`" . $field_name . "` IN (" . implode(',', $field['values']) . ")";
                        }

                        // мультисписок
                        if ($field['field_data']['type'] == NC_FIELDTYPE_MULTISELECT) {
                            $multilist_subqueries = array();
                            foreach ($field['values'] as $value) {
                                $multilist_subqueries[] = "FIND_IN_SET(" . (int)$value . ", a.`" . $field_name . "`)";
                            }

                            $conditions_query[] = "(" . implode(' OR ', $multilist_subqueries) . ")";
                        }
                    }
                    break;
            }
        }

        return implode(' AND ', $conditions_query);
    }

    /**
     * @param int $filter_infoblock_id
     * @return string
     */
    public function get_settings_dialog_url($filter_infoblock_id) {
        $nc_core = nc_Core::get_object();
        return $nc_core->SUB_FOLDER . $nc_core->HTTP_ROOT_PATH . 'action.php?' .
            http_build_query(array(
                'ctrl' => 'admin.infoblock_filter',
                'action' => 'show_filter_settings',
                'filter_infoblock_id' => $filter_infoblock_id,
            ), null, '&');
    }

}
