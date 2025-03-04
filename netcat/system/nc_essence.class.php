<?php

abstract class nc_Essence extends nc_System {

    protected $current;
    protected $system_tables = array("Catalogue", "Subdivision", "User", "Template");
    protected $inherit_essences = array("Subdivision" => true, "Sub_Class" => true, "Template" => true);
    protected $essence;
    /** @var  nc_db */
    protected $db;
    protected $data;
    protected $core;

    protected $meta = array();

    /** @var string тип данных для учёта времени последнего изменения (Last-Modified) */
    protected $last_modified_type = 'content';

    protected function __construct() {

        // set essence
        $this->essence = str_replace("nc_", "", get_class($this));

        $this->core = nc_Core::get_object();
    }

    public function get_current($item = "") {

        if (empty($this->current)) {
            return false;
        }

        if ($item) {
            return array_key_exists($item, $this->current) ? $this->current[$item] : "";
        }
        else {
            return $this->current;
        }
    }

    public function set_current_by_id($id, $reset = false) {
        // validate
        $id = intval($id);

        if ($id) {
            $this->current = $this->get_by_id_or_keyword($id, "", $reset);
            // return result
            return $this->current;
        }

        // reject
        return false;
    }

    public function set_current_item($item, $value) {

        if (empty($this->current)) {
            return false;
        }

        if (array_key_exists($item, $this->current)) {
            $this->current[$item] = $value;
        }

        return true;
    }

    /**
     * Запоминает значения полей сущностей во внутреннем кэше.
     * Может использоваться, например, в случаях, когда список сущностей загружен отдельным
     * запросом, и нужно использовать эти сведения через соответствующий класс.
     * @param array $data Каждый элемент $data — массив со значениями ВСЕХ полей одной сущности.
     *      Значения должны быть приведены в том виде, в каком они сохранены в базе данных.
     *      Элементы массива должны содержать элемент с полем-идентификатором
     *      (например, Catalogue_ID, Subdivision_ID и т. п.).
     */
    public function set_data(array $data) {
        $id_field = $this->essence . "_ID";
        foreach ($data as $i => $row) {
            if (!is_array($row)) {
                trigger_error(get_class($this) . "::" . __METHOD__ . "(): \$data[$i] is not an array", E_USER_WARNING);
                continue;
            }
            if (!isset($row[$id_field]) || !$row[$id_field]) {
                trigger_error(get_class($this) . "::" . __METHOD__ . "(): cannot set data for $this->essence: no $id_field", E_USER_WARNING);
                continue;
            }
            $id = $row[$id_field];
            $this->data[$id] = $row;
            $this->data[$id]['_nc_final'] = 0;
        }
    }

    /**
     * Возвращает полные значения (с унаследованными значениями, дополнительными
     * элементами для полей типа файл, список, дата и т.п. — как в возвращаемом
     * значении метода get_by_id()) для результатов выборки из БД.
     * @param array $data  Массив с данными из соответствующей таблицы (должен содержать все поля)
     * @return array
     */
    public function process_raw_data(array $data) {
        $id_field = $this->essence . "_ID";
        $this->set_data($data);
        $result = array();

        foreach ($data as $k => $v) {
            $result[$k] = $this->get_by_id($v[$id_field]);
        }

        return $result;
    }

    /**
     * @param string $keyword
     * @return int
     */
    protected function get_id_by_keyword($keyword) {
        return 0;
    }

    /**
     * @param int|string $id_or_keyword
     * @param string $item
     * @param bool $reset
     * @return null|string|array
     */
    public function get_by_id_or_keyword($id_or_keyword, $item = "", $reset = false) {
        $id = null;

        if (preg_match('/^-?\d+$/', $id_or_keyword)) {
            $id = (int)$id_or_keyword;
        } else if (preg_match('/^\w+$/', $id_or_keyword)) {
            $id = $this->get_id_by_keyword($id_or_keyword);
        }

        if (!$id) {
            return null;
        }

        if (empty($this->data[$id]) || $reset) {
            nc_core::get_object()->clear_cache_on_low_memory();
            $this->data[$id] = $this->db->get_row("SELECT * FROM `{$this->essence}` WHERE `{$this->essence}_ID` = '{$id}'", ARRAY_A);
            $this->data[$id]['_nc_final'] = 0;
        }

        if (!$this->data[$id]['_nc_final']) {
            // convert system fields data
            if (in_array($this->essence, $this->system_tables)) {
                $this->data[$id] = $this->convert_system_vars($this->data[$id]);
            }
            // inherit current data
            if (isset($this->inherit_essences[$this->essence])) {
                $this->data[$id] = $this->inherit($this->data[$id]);
            }
            $this->data[$id]['_nc_final'] = 1;
        }

        // if item requested return item value
        if ($item && is_array($this->data[$id])) {
            return array_key_exists($item, $this->data[$id]) ? $this->data[$id][$item] : "";
        }

        return $this->data[$id];
    }

    /**
     * Очистка кэша
     */
    public function clear_cache() {
        $this->data = array();
    }

    public function delete_by_id($id, $class_id = 0, $trash = false) {
        // validate parameters
        $id = intval($id);

        $this->db->query("DELETE FROM `" . $this->essence . "` WHERE `" . $this->essence . "_ID` = '" . $id . "'");

        return $this->db->rows_affected;
    }

    public function convert_system_vars($env_array) {
        // system superior object
        $nc_core = nc_Core::get_object();

        // load system table fields
        $table_fields = $nc_core->get_system_table_fields($this->essence);
        $field_count = count($table_fields);

        $nc_core->file_info->cache_object_data($this->essence, $env_array);

        // Проход по всем полям
        for ($i = 0; $i < $field_count; $i++) {
            $field_name = $table_fields[$i]['name'];
            $field_type = $table_fields[$i]['type'];

            $has_env_array_data = !empty($env_array[$field_name]);
            if ($has_env_array_data || $field_type == NC_FIELDTYPE_MULTISELECT || $field_type == NC_FIELDTYPE_SELECT || $field_type == NC_FIELDTYPE_FILE) {
                switch ($field_type) {
                    case NC_FIELDTYPE_SELECT:
                        if ($has_env_array_data) {
                            $list_item_id = (int)$env_array[$field_name];
                            $table_name = $table_fields[$i]['table'];
                            $list_item = (array)$this->db->get_row(
                                "SELECT `{$table_name}_Name`, `Value`, `CustomSettings`
                                   FROM `Classificator_{$table_name}`
                                  WHERE `{$table_name}_ID` = '$list_item_id'",
                                ARRAY_N
                            );
                            $env_array[$field_name] = isset($list_item[0]) ? $list_item[0] : null;
                            $env_array[$field_name . '_id'] = $list_item_id;
                            $env_array[$field_name . '_value'] = isset($list_item[1]) ? $list_item[1] : null;
                            $env_array[$field_name . '_settings'] = $nc_core->list->evaluate_item_settings($table_name, $list_item[2]);

                        } else {
                            $env_array[$field_name . '_id'] = $env_array[$field_name . '_value'] = null;
                            $env_array[$field_name . '_settings'] = array();
                        }
                        break;
                    case NC_FIELDTYPE_TEXT:
                    case NC_FIELDTYPE_STRING:
                        $format = nc_field_parse_format($table_fields[$i]['format'], $field_type);
                        // html не разрешен
                        if ($format['html'] == 2) {
                            $env_array[$field_name] = htmlspecialchars($env_array[$field_name], ENT_QUOTES, $nc_core->NC_CHARSET, false);
                        }
                        // перенос строки
                        if ($format['br'] == 1) {
                            $env_array[$field_name] = nl2br($env_array[$field_name]);
                        }
                        break;
                    case NC_FIELDTYPE_FILE:
                        $file_data = $nc_core->file_info->get_file_info($this->essence, $env_array[$this->essence . "_ID"], $field_name, true, false, true);
                        $env_array = array_merge($env_array, $file_data);
                        break;
                    case NC_FIELDTYPE_DATETIME:
                        $env_array[$field_name . "_year"] = substr($env_array[$field_name], 0, 4);
                        $env_array[$field_name . "_month"] = substr($env_array[$field_name], 5, 2);
                        $env_array[$field_name . "_day"] = substr($env_array[$field_name], 8, 2);
                        $env_array[$field_name . "_hours"] = substr($env_array[$field_name], 11, 2);
                        $env_array[$field_name . "_minutes"] = substr($env_array[$field_name], 14, 2);
                        $env_array[$field_name . "_seconds"] = substr($env_array[$field_name], 17, 2);
                        break;
                    case NC_FIELDTYPE_MULTISELECT:
                        $list_ids = $list_values = $list_names = array();
                        $value_string = trim($env_array[$field_name], ',');
                        $list_settings = array();

                        if (!empty($value_string)) {
                            // латинское имя списка
                            $table_name = $table_fields[$i]['table'];

                            // получим сами элементы
                            $list_items = $this->db->get_results(
                                "SELECT `" . $table_name . "_ID`,
                                        `" . $table_name . "_Name`,
                                        `Value`,
                                        `CustomSettings`
                                   FROM `Classificator_" . $table_name . "`
                                  WHERE `" . $table_name . "_ID` IN ($value_string)
                                  ORDER BY FIND_IN_SET(`" . $table_name . "_ID`, '$value_string')",
                                ARRAY_N
                            );

                            if ($list_items) {
                                $list_ids =    (array)$this->db->get_col(null, 0);
                                $list_names =  (array)$this->db->get_col(null, 1);
                                $list_values = (array)$this->db->get_col(null, 2);
                                foreach ($list_items as $list_item) {
                                    $list_settings[] = $nc_core->list->evaluate_item_settings($table_name, $list_item[3]);
                                }
                            }
                        }

                        $env_array[$field_name] = $list_names;
                        $env_array[$field_name . "_id"] = $list_ids;
                        $env_array[$field_name . "_value"] = $list_values;
                        $env_array[$field_name . "_settings"] = $list_settings;

                        break;
                }
            }
        }

        foreach (array('Created', 'Updated', 'LastModified') as $field_name) {
            if (!empty($env_array[$field_name])) {
                $nc_core->page->update_last_modified_if_timestamp_is_newer(
                    strtotime($env_array[$field_name]),
                    $this->last_modified_type
                );
            }
        }

        return $env_array;
    }

    /**
     * @param string $system_table_name
     * @param array $parent_array
     * @param array $child_array
     * @param string|null $parent_table_name
     * @return array
     */
    protected function inherit_system_fields($system_table_name, $parent_array, $child_array, $parent_table_name = null) {
        // system superior object
        $nc_core = nc_Core::get_object();

        // load system table fields
        $table_fields = $nc_core->get_system_table_fields($system_table_name);
        // count
        $field_count = count($table_fields);

        if (!$parent_table_name) {
            $parent_table_name = $system_table_name;
        }

        for ($i = 0; $i < $field_count; $i++) {
            // не наследуется
            if (!$table_fields[$i]['inheritance']) {
                continue;
            }
            // field name
            $field_name = $table_fields[$i]['name'];

            $should_inherit_value = (
                !isset($child_array[$field_name]) ||
                $child_array[$field_name] == "" ||
                ($table_fields[$i]['type'] == NC_FIELDTYPE_SELECT && $child_array[$field_name . '_id'] == 0) // список наследуется, если элемент == 0
            );

            if ($should_inherit_value) {
                switch ($table_fields[$i]['type']) {
                    case NC_FIELDTYPE_SELECT:
                        $child_array[$field_name] = (isset($parent_array[$field_name]) && $parent_array[$field_name] ? $parent_array[$field_name] : "");
                        $child_array[$field_name . '_id'] = (isset($parent_array[$field_name . '_id']) && $parent_array[$field_name . '_id'] ? $parent_array[$field_name . '_id'] : "");
                        $child_array[$field_name . '_value'] = (isset($parent_array[$field_name . '_value']) && $parent_array[$field_name . '_value'] ? $parent_array[$field_name . '_value'] : "");
                        break;
                    case NC_FIELDTYPE_FILE:
                        $child_array[$field_name] = (isset($parent_array[$field_name]) && $parent_array[$field_name] ? $parent_array[$field_name] : "");
                        $child_array[$field_name . '_name'] = (isset($parent_array[$field_name . '_name']) && $parent_array[$field_name . '_name'] ? $parent_array[$field_name . '_name'] : "");
                        $child_array[$field_name . '_size'] = (isset($parent_array[$field_name . '_size']) && $parent_array[$field_name . '_size'] ? $parent_array[$field_name . '_size'] : "");
                        $child_array[$field_name . '_type'] = (isset($parent_array[$field_name . '_type']) && $parent_array[$field_name . '_type'] ? $parent_array[$field_name . '_type'] : "");
                        $child_array[$field_name . '_url'] = (isset($parent_array[$field_name . '_url']) && $parent_array[$field_name . '_url'] ? $parent_array[$field_name . '_url'] : "");
                        $child_array[$field_name . '_preview_url'] = (isset($parent_array[$field_name . '_preview_url']) && $parent_array[$field_name . '_preview_url'] ? $parent_array[$field_name . '_preview_url'] : "");
                        break;
                    case NC_FIELDTYPE_DATETIME:
                        $child_array[$field_name] = (isset($parent_array[$field_name]) && $parent_array[$field_name] ? $parent_array[$field_name] : "");
                        $child_array[$field_name . '_day'] = (isset($parent_array[$field_name . '_day']) && $parent_array[$field_name . '_day'] ? $parent_array[$field_name . '_day'] : "");
                        $child_array[$field_name . '_month'] = (isset($parent_array[$field_name . '_month']) && $parent_array[$field_name . '_month'] ? $parent_array[$field_name . '_month'] : "");
                        $child_array[$field_name . '_year'] = (isset($parent_array[$field_name . '_year']) && $parent_array[$field_name . '_year'] ? $parent_array[$field_name . '_year'] : "");
                        $child_array[$field_name . '_hours'] = (isset($parent_array[$field_name . '_hours']) && $parent_array[$field_name . '_hours'] ? $parent_array[$field_name . '_hours'] : "");
                        $child_array[$field_name . '_minutes'] = (isset($parent_array[$field_name . '_minutes']) && $parent_array[$field_name . '_minutes'] ? $parent_array[$field_name . '_minutes'] : "");
                        $child_array[$field_name . '_seconds'] = (isset($parent_array[$field_name . '_seconds']) && $parent_array[$field_name . '_seconds'] ? $parent_array[$field_name . '_seconds'] : "");
                        break;
                    case NC_FIELDTYPE_MULTISELECT:
                        $child_array[$field_name] = (isset($parent_array[$field_name]) && $parent_array[$field_name] ? $parent_array[$field_name] : array());
                        $child_array[$field_name . '_id'] = (isset($parent_array[$field_name . '_id']) && $parent_array[$field_name . '_id'] ? $parent_array[$field_name . '_id'] : array());
                        $child_array[$field_name . '_value'] = (isset($parent_array[$field_name . '_value']) && $parent_array[$field_name . '_value'] ? $parent_array[$field_name . '_value'] : array());
                        break;
                    // other fields
                    default:
                        $child_array[$field_name] = (isset($parent_array[$field_name]) && $parent_array[$field_name] != '' ? $parent_array[$field_name] : "");
                }

                // Если что-то было унаследовано, сохраним информацию об источнике значения в элементе _value_source[$field_name]:
                $value = $child_array[$field_name];
                if ((is_scalar($value) && $value != '') || (is_array($value) && count($value))) {
                    if (isset($parent_array['_value_source'][$field_name])) {
                        $child_array['_value_source'][$field_name] = $parent_array['_value_source'][$field_name];
                    }
                    else {
                        $child_array['_value_source'][$field_name] = array(
                            'type' => $parent_table_name,
                            'id' => $parent_array[$parent_table_name . '_ID'],
                        );
                    }
                }
            }
        }

        return $child_array;
    }

    public function check_available($url) {
        if (!function_exists('curl_init')) {
            return false;
        }

        $curlInit = curl_init($url);
        curl_setopt($curlInit, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($curlInit, CURLOPT_HEADER, true);
        curl_setopt($curlInit, CURLOPT_NOBODY, true);
        curl_setopt($curlInit, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($curlInit);
        curl_close($curlInit);

        if ($response) {
            return true;
        }
        return false;
    }

    public function get_alternative_link() {
        $nc_core = nc_Core::get_object();
        $current_catalogue = $nc_core->catalogue->get_current();
        $current_sub = $nc_core->subdivision->get_current();

        if ($current_catalogue['ncMobile'] == 1) {
            $Catalogue_ID = $current_catalogue['ncMobileSrc'];

            $SQL = "SELECT Sub_Class_ID,
                           Subdivision_ID,
                           SrcMirror
                    FROM Sub_Class
                        WHERE Subdivision_ID = " . $current_sub['Subdivision_ID'];

            $result = (array)$this->db->get_results($SQL);

            $sub_class_id = null;

            foreach ($result as $row) {
                if ($row->SrcMirror) {
                    $sub_class_id = $row->SrcMirror;
                    break;
                }
            }

            if ($sub_class_id) {
                $Hidden_URL = $nc_core->sub_class->get_by_id($sub_class_id, 'Hidden_URL');
            }
        }
        else {
            $Catalogue_ID = $this->db->get_var("SELECT Catalogue_ID FROM Catalogue WHERE ncMobileSrc = " . $current_catalogue['Catalogue_ID']);

            $SQL = "SELECT Sub_Class_ID,
                           Subdivision_ID,
                           SrcMirror
                    FROM Sub_Class
                        WHERE SrcMirror IN (SELECT Sub_Class_ID FROM Sub_Class WHERE Subdivision_ID = {$current_sub['Subdivision_ID']})";

            $result = (array)$this->db->get_row($SQL, ARRAY_A);

            if ($result['Sub_Class_ID']) {
                $Hidden_URL = $nc_core->sub_class->get_by_id($result['Sub_Class_ID'], 'Hidden_URL');
            }
        }

        $Domain = $nc_core->catalogue->get_by_id($Catalogue_ID, 'Domain');
        $suffix = '';

        if ($nc_core->NC_UNICODE && nc_preg_match("/[а-яё]+/i", $Domain)) {
            require_once 'Net/IDNA2.php'; // netcat/require/lib
            $idn = new Net_IDNA2;
            $Domain = $idn->encode($Domain);
        }

        global $action;

        switch ($action) {
            case 'full' :
                global $f_Keyword, $cc_keyword, $message;
                $suffix = $f_Keyword ? $f_Keyword : $cc_keyword . "_" . $message;
                $suffix .= '.html';
                break;
        }

        $REQUEST_URI = (string)$_SERVER['REQUEST_URI'];

        if (!$Hidden_URL && $REQUEST_URI = !'/') {
            $Hidden_URL = $REQUEST_URI;
            if ($REQUEST_URI[strlen($REQUEST_URI) - 1] != '/') {
                $url_array = explode('/', $REQUEST_URI);
                array_pop($url_array);
                $Hidden_URL = join('/', $url_array) . '/';
            }

            $SQL = "SELECT COUNT(*)
                        FROM Subdivision
                            WHERE Hidden_URL = '$Hidden_URL'
                                AND Catalogue_ID = " . $Catalogue_ID;

            $result = $this->db->get_var($SQL);
            $url = $result ? $Domain . $Hidden_URL . $suffix : $Domain;

        }
        else {
            $url = $Domain . $Hidden_URL . $suffix;
        }
        return $url;
    }

    /**
     * Наследование значений (stub, переопределяется в классах, для которых
     * есть наследование — разделы, инфоблоки, макеты).
     * @param $data
     */
    protected function inherit($data) {
        return $data;
    }


    /**
     * Запомнить дополнительную информацию для записи с указанным ID.
     * Не сохраняется в БД, хранится только в памяти.
     * @internal не является частью API. Может быть изменено (удалено) в будущих версиях.
     * @param int $id
     * @param string $key
     * @param mixed $value
     */
    public function set_meta($id, $key, $value) {
        $this->meta[(int)$id][$key] = $value;
    }

    /**
     * Получить дополнительную информацию, установленную методом set_meta().
     * @internal не является частью API. Может быть изменено (удалено) в будущих версиях.
     * @param int $id
     * @param string $key
     * @return mixed
     */
    public function get_meta($id, $key) {
        return isset($this->meta[(int)$id][$key]) ? $this->meta[$id][$key] : null;
    }

}
