<?php

class nc_list extends nc_Essence {
    const SORT_NAME = 1;
    const SORT_PRIORITY = 2;

    public function __construct() {
        parent::__construct();
        $this->essence = 'Classificator';
    }

    /**
     * Возвращает настройки списка
     *
     * @param $list_id_or_keyword
     * @param string $item
     * @param bool $reset
     * @return array|string|null
     */
    public function get_by_id($list_id_or_keyword, $item = '', $reset = false) {
        $db = nc_db();
        if ($reset || !isset($this->data[$list_id_or_keyword])) {
            if (is_numeric($list_id_or_keyword)) {
                $where_cond = "`{$this->essence}_ID` = " . (int)$list_id_or_keyword;
            } elseif (preg_match('/^\w+$/', $list_id_or_keyword)) {
                $where_cond = "`Table_Name` = '{$db->escape($list_id_or_keyword)}'";
            } else {
                throw new UnexpectedValueException('Wrong list ID or keyword');
            }

            $data = $db->get_row("SELECT * FROM `{$this->essence}` WHERE " . $where_cond, ARRAY_A);

            if ($data) {
                $this->data[$data[$this->essence . '_ID']] = $this->data[$data['Table_Name']] = $data;
            } else {
                $this->data[$list_id_or_keyword] = array();
            }
        }

        // if item requested return item value
        if ($item && is_array($this->data[$list_id_or_keyword])) {
            return array_key_exists($item, $this->data[$list_id_or_keyword]) ? $this->data[$list_id_or_keyword][$item] : null;
        }

        return $this->data[$list_id_or_keyword];
    }

    /**
     * Алиас метода get_by_id()
     *
     * @param int|string $list_id_or_keyword
     * @param string $item
     * @param bool $reset
     * @return array|string|null
     */
    public function get_by_id_or_keyword($list_id_or_keyword, $item = "", $reset = false) {
        return $this->get_by_id($list_id_or_keyword, $item, $reset);
    }

    /**
     * Обновляет параметры списка
     *
     * @param int $id
     * @param array $params
     * @return bool
     * @throws nc_Exception_DB_Error
     */
    public function update($id, $params) {
        $db = nc_db();
        $nc_core = nc_Core::get_object();

        $nc_core->event->execute(nc_Event::BEFORE_LIST_UPDATED, $id, $params);

        $id = (int)$id;
        if (!$id || !is_array($params)) {
            return false;
        }

        $params_int = array(
            'System',
            'Sort_Type',
            'Sort_Direction',
        );

        $params_text = array(
            'Classificator_Name',
            'Table_Name',
            'CustomSettingsTemplate',
        );

        $query = array();

        foreach ($params as $k => $v) {
            if (in_array($k, $params_int)) {
                $query[] = "`" . $db->escape($k) . "` = " . (int)trim($v);
            } elseif (in_array($k, $params_text)) {
                $query[] = "`" . $db->escape($k) . "` = '" . $db->prepare($v) . "'";
            }
        }

        if (!empty($query)) {
            $db->query("UPDATE `" . $this->essence . "` SET " . join(",\n        ", $query) . " WHERE `" . $this->essence . "_ID` = " . $id);
            if ($db->is_error) {
                throw new nc_Exception_DB_Error($db->last_query, $db->last_error);
            }
        }

        $nc_core->event->execute(nc_Event::AFTER_LIST_UPDATED, $id, $params);

        $this->data = array();
        return true;
    }

    /**
     * Возвращает поле, по которому производится сортировка списка
     *
     * @internal пока не является частью публичного API
     * @param $list_id_or_keyword
     * @return string
     */
    public function get_sort_field($list_id_or_keyword) {
        $list = $this->get_by_id($list_id_or_keyword);
        switch ($list['Sort_Type']) {
            case self::SORT_NAME:
                return $list['Table_Name'] . '_Name';
            case self::SORT_PRIORITY:
                return $list['Table_Name'] . '_Priority';
            default:
                return $list['Table_Name'] . '_ID';
        }
    }

    /**
     * Возвращает ORDER BY для выборки из списка
     *
     * @param string $list_id_or_keyword
     * @return string
     */
    public function get_order_by($list_id_or_keyword) {
        return ' ORDER BY `' . $this->get_sort_field($list_id_or_keyword) . '` ' .
               ($this->get_by_id($list_id_or_keyword, 'Sort_Direction') ? 'DESC' : 'ASC');
    }


    /**
     * Возвращает максимальный приоритет существующих в списке элементов
     *
     * @param $list_id_or_keyword
     * @return int
     */
    public function get_max_priority($list_id_or_keyword) {
        $table_name = $this->get_by_id($list_id_or_keyword, 'Table_Name');
        $max_priority = nc_db()->get_var("SELECT MAX(" . $table_name . "_Priority) FROM " . $this->essence . "_" . $table_name);
        return $max_priority ?: 0;
    }

    /**
     * Добавляет к элементу списка поля: ID, Name, Priority (вместо полей ИмяТаблицы_*),
     * Settings (значения из CustomSettings)
     *
     * @param int $list_id_or_keyword
     * @param array $item
     */
    protected function add_item_fields($list_id_or_keyword, array $item) {
        $table_name = is_numeric($list_id_or_keyword)
            ? $this->get_by_id($list_id_or_keyword, 'Table_Name')
            : $list_id_or_keyword;

        $item['Name'] = $item[$table_name . '_Name'];
        $item['ID'] = $item[$table_name . '_ID'];
        $item['Priority'] = $item[$table_name . '_Priority'];
        $item['Settings'] = $this->evaluate_item_settings($list_id_or_keyword, $item['CustomSettings']);

        return $item;
    }

    /**
     * Возвращает все элементы списка
     *
     * @param $list_id_or_keyword
     * @return array
     */
    public function get_items($list_id_or_keyword) {
        $table_name = $this->get_by_id($list_id_or_keyword, 'Table_Name');
        $items = array();
        if ($table_name) {
            $items = (array)nc_db()->get_results(
                "SELECT * FROM `" . $this->essence . "_" . $table_name . "`" .
                $this->get_order_by($list_id_or_keyword),
                ARRAY_A
            );
            foreach ($items as $i => $item) {
                $items[$i] = $this->add_item_fields($list_id_or_keyword, $item);
            }
        }
        return $items;
    }

    /**
     * Возвращает элемент списка по его идентификатору
     *
     * @param int|string $list_id_or_keyword
     * @param int $item_id
     * @return array
     */
    public function get_item($list_id_or_keyword, $item_id) {
        $item = array();
        $table_name = $this->get_by_id($list_id_or_keyword, 'Table_Name');
        if ($table_name) {
            $item = (array)nc_db()->get_row(
                "SELECT * FROM `" . $this->essence . "_" . $table_name . "`" .
                " WHERE `" . $table_name . "_ID` = " . (int)$item_id,
                ARRAY_A
            );
            if ($item) {
                $item = $this->add_item_fields($list_id_or_keyword, $item);
            }
        }
        return $item;
    }

    /**
     * Создаёт элемент списка
     *
     * @param int|string $list_id_or_keyword
     * @param array $item_properties массив со значениями (могут быть указаны ID, Name, Priority)
     * @return int|null
     * @throws Exception
     */
    public function create_item($list_id_or_keyword, array $item_properties) {
        $nc_core = nc_Core::get_object();

        $list_id = $this->get_by_id($list_id_or_keyword, 'Classificator_ID');
        $table_name = $this->get_by_id($list_id_or_keyword, 'Table_Name');
        $item_properties = $this->merge_mirror_fields($table_name, $item_properties);

        // если нет приоритета, то берем максимальный в текущем списке + 1
        if (!$item_properties[$table_name . '_Priority']) {
            $item_properties[$table_name . '_Priority'] = $this->get_max_priority($list_id_or_keyword) + 1;
        }

        $nc_core->event->execute(nc_Event::BEFORE_LIST_ITEM_CREATED, $list_id, 0, $item_properties);

        $item_id = nc_db_table::make($this->essence . '_' . $table_name)->insert($item_properties);
        if (!$item_id) {
            throw new Exception("Unable to create list item \n" . nc_db()->last_error);
        }

        $nc_core->event->execute(nc_Event::AFTER_LIST_ITEM_CREATED, $list_id, $item_id, $item_properties);

        return $item_id;
    }

    /**
     * @param int|string $list_id_or_keyword
     * @param int $item_id
     * @param array $item_properties
     * @throws nc_Exception_DB_Error
     */
    public function update_item($list_id_or_keyword, $item_id, array $item_properties) {
        $nc_core = nc_Core::get_object();
        $db = $nc_core->db;

        $list_id = $this->get_by_id($list_id_or_keyword, 'Classificator_ID');
        $table_name = $this->get_by_id($list_id_or_keyword, 'Table_Name');
        $item_properties = $this->merge_mirror_fields($table_name, $item_properties);

        $nc_core->event->execute(nc_Event::BEFORE_LIST_ITEM_UPDATED, $list_id, $item_id, $item_properties);

        nc_db_table::make($this->essence . '_' . $table_name, $table_name . '_ID')->where_id($item_id)->update($item_properties);
        if ($db->is_error) {
            throw new nc_Exception_DB_Error($db->last_query, $db->last_error);
        }
        $nc_core->event->execute(nc_Event::AFTER_LIST_ITEM_UPDATED, $list_id, $item_id, $item_properties);
    }

    /**
     * Копирует значения из "зеркальных" свойств (например, ID) в "нормальные" (например, ИмяТаблицы_ID).
     * Если "нормальные" заполнены, то они в приоритете.
     *
     * @param $table_name
     * @param $properties
     * @return mixed
     */
    private function merge_mirror_fields($table_name, $properties) {
        $mirror_keys = array('ID', 'Name', 'Priority');
        foreach ($properties as $key => $value) {
            if (in_array($key, $mirror_keys)) {
                $property_key = $table_name . '_' . $key;
                if (!$properties[$property_key]) {
                    $properties[$property_key] = $value;
                }
                unset($properties[$key]);
            }
        }

        return $properties;
    }

    /**
     * Возвращает значения дополнительных настроек элемента списка
     *
     * @param int|string $list_id_or_keyword
     * @param string $item_settings
     * @return array
     */
    public function evaluate_item_settings($list_id_or_keyword, $item_settings) {
        $custom_settings = $this->get_by_id($list_id_or_keyword, 'CustomSettingsTemplate');
        if (!$custom_settings) {
            return array();
        }

        $a2f = new nc_a2f($custom_settings);
        if ($item_settings) {
            $a2f->set_values($item_settings);
        }
        return $a2f->get_values_as_array();
    }

}