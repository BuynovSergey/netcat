<?php



class nc_db_table
{

    //-------------------------------------------------------------------------

    const QUERY_SELECT = 1;
    const QUERY_INSERT = 2;
    const QUERY_UPDATE = 3;
    const QUERY_DELETE = 4;
    const QUERY_REPLACE = 5;

    //--------------------------------------------------------------------------

    protected static $table_fields = array();

    //-------------------------------------------------------------------------

    // OBJECT; ARRAY_A
    protected $default_result_type = ARRAY_A;

    protected $fields_determination = false;

    //-------------------------------------------------------------------------

    protected $table;
    protected $primary_key;
    protected $fields;

    //-------------------------------------------------------------------------

    protected $result_type = false;
    protected $index_by    = false;
    protected $last_query  = '';
    protected $last_result = null;

    protected $data          = array();
    protected $query         = array();
    protected $saved_queries = array();

    //-------------------------------------------------------------------------

    public static function make($table, $primary_key = null) {
        $object = new nc_db_table();
        $object->set_table($table);
        $object->set_primary_key($primary_key);

        return $object;
    }

    //-------------------------------------------------------------------------

    public function __construct() {
        $this->set_primary_key();
        $this->reset_query();
        $this->reset_data();
        $this->init();
    }

    /**************************************************************************
        Переопределяемые методы
    **************************************************************************/

    protected function init() {}
    protected function before_execute($type) {}
    protected function after_execute($type) {}

    /**************************************************************************
        Вспомогательные методы
    **************************************************************************/

    public function reset_query() {
        $this->query = array(
            'type'     => null,
            'join'     => null,
            'select'   => '',
            'where'    => null,
            'order_by' => null,
            'group_by' => null,
            'limit'    => null,
        );
    }

    //-------------------------------------------------------------------------

    public function reset_data() {
        $this->data = array();
    }

    //-------------------------------------------------------------------------

    public function get_fields() {
        if (!$this->fields && $this->fields_determination) {
            $table = $this->get_table();
            if (isset(self::$table_fields[$table])) {
                $this->fields = self::$table_fields[$table];
            } else {
                $fields = nc_db()->get_results("DESCRIBE `{$table}`");
                $this->fields = array();

                foreach ($fields as $field) {
                    $field_name = $field->Field;
                    $this->fields[$field_name] = array();
                }

                self::$table_fields[$table] = $this->fields;
            }
        }

        return (array) $this->fields;
    }

    //-------------------------------------------------------------------------

    public function get_field_names() {
        return array_keys((array)$this->fields);
    }

    //--------------------------------------------------------------------------

    public function set_fields_determination($enable) {
        $this->fields_determination = (bool) $enable;
    }

    //-------------------------------------------------------------------------

    public function get_table() {
        return $this->table;
    }

    //-------------------------------------------------------------------------

    public function set_table($table) {
        $this->table = $table;
    }

    //-------------------------------------------------------------------------

    public function get_primary_key() {
        return $this->primary_key;
    }

    //-------------------------------------------------------------------------

    public function set_primary_key($primary_key = null) {
        if ($primary_key !== null) {
            $this->primary_key = $primary_key;
        }
        else if (!$this->primary_key && $this->table) {
            $this->primary_key = $this->table . '_ID';
        }
    }

    //-------------------------------------------------------------------------

    public function get_last_query() {
        return $this->last_query;
    }

    //-------------------------------------------------------------------------

    public function get_last_result() {
        return $this->last_result;
    }

    //-------------------------------------------------------------------------

    public function get_last_error() {
        return nc_db()->last_error;
    }

    /**************************************************************************
        "SQL методы"
    **************************************************************************/

    public function raw($method, $val) {
        $this->query[$method] = $val;
        return $this;
    }

    //-------------------------------------------------------------------------

    public function select($select = '*') {
        if (func_num_args() > 1) {
            $select = func_get_args();
        }

        if (is_array($select)) {
            $select = implode(', ', $select);
        }

        $this->query['select'] = $select;

        return $this;
    }

    //-------------------------------------------------------------------------

    public function join($table, $on, $type = 'LEFT') {
        $this->query['join'] .= " $type JOIN $table ON $on ";

        return $this;
    }

    //-------------------------------------------------------------------------

    public function join_using($table, $using, $type = 'LEFT') {
        $this->query['join'] .= " $type JOIN $table USING ($using) ";

        return $this;
    }

    //-------------------------------------------------------------------------

    public function where_id($id) {
        return $this->where($this->get_primary_key(), $id);
    }

    //-------------------------------------------------------------------------

    public function where($key, $operator = null, $value = null) {
        $args = func_get_args();
        return $this->_where('AND', $args);
    }

    //-------------------------------------------------------------------------

    public function or_where($key, $operator = null, $value = null) {
        $args = func_get_args();
        return $this->_where('OR', $args);
    }

    //-------------------------------------------------------------------------

    public function or_where_id($operator = null, $value = null) {
        return $this->or_where($this->get_primary_key(), $operator, $value);
    }

    //-------------------------------------------------------------------------

    public function where_in($key, $values) {
        return $this->_where('AND', array($key, 'IN', $values));
    }

    //-------------------------------------------------------------------------

    public function where_in_id($values) {
        return $this->where_in($this->get_primary_key(), $values);
    }

    //-------------------------------------------------------------------------

    public function or_where_in($key, $values) {
        return $this->_where('OR', array($key, 'IN', $values));
    }

    //-------------------------------------------------------------------------

    public function or_where_in_id($values) {
        return $this->or_where_in($this->get_primary_key(), $values);
    }

    //-------------------------------------------------------------------------

    public function order_by($field, $type = 'asc') {
        if ($this->query['order_by']) {
            $this->query['order_by'] .= ', ';
        }

        $field = $this->escape($field);

        if (strpos($field, '.')) {
            $field = str_replace('.', '.`', $field) . '`';
        } else {
            $field = '`' . $field . '`';
        }

        $this->query['order_by'] .= "{$field} " . (strtolower($type) == 'asc' ? 'ASC' : 'DESC');

        return $this;
    }

    //-------------------------------------------------------------------------

    public function group_by($field) {
        if ($this->query['group_by']) {
            $this->query['group_by'] .= ', ';
        }

        $this->query['group_by'] .= "{$field}";

        return $this;
    }

    //-------------------------------------------------------------------------

    public function set($field, $value = null) {

        if (is_array($field)) {
            $data = $field;
        } else {
            $data = array($field => $value);
        }

        if ($fields = $this->get_fields()) {
            foreach ($data as $name => $val) {
                if (!isset($fields[$name])) continue;
                $this->data[$name] = $val;
            }
        } else {
            $this->data = $data;
        }

        return $this;
    }

    //-------------------------------------------------------------------------

    public function limit($limit) {
        if (func_num_args() <= 2) {
            $args = func_get_args();
            $this->query['limit'] = implode(',', $args);
        }

        return $this;
    }

    /**************************************************************************
        Результирующие методы
    **************************************************************************/


    public function get_result() {
        $result_type = $this->result_type ? $this->result_type : $this->default_result_type;
        $index_by    = $this->index_by;

        $this->index_by = false;

        $result = (array) $this->execute(self::QUERY_SELECT, 'get_results');

        if ($index_by) {
            $new_result = array();

            if ($result_type == ARRAY_A) {
                foreach ($result as $row) {
                    $new_result[$row[$index_by]] = $row;
                }
            } else {
                foreach ($result as $row) {
                    $new_result[$row->$index_by] = $row;
                }
            }

            return $new_result;
        }

        return $result;
    }

    //-------------------------------------------------------------------------

    public function get_row($where_key_value = null) {
        if ($where_key_value !== null) {
            $this->where($this->get_primary_key(), $where_key_value);
        }

        return $this->execute(self::QUERY_SELECT, 'get_row');
    }

    //-------------------------------------------------------------------------

    public function get_value($field = null) {
        if ($field && empty($this->query['select'])) {
            $this->select($field);
        }

        $row = (array) $this->get_row();

        if ($field) {
            return isset($row[$field]) ? $row[$field] : null;
        } else {
            return current($row);
        }
    }

    //-------------------------------------------------------------------------

    /**
     * Возвращает результат запроса в виде массив [key => value(s)]
     * $model->get_list('name') // Вернет массив вида ((id=>name), …)
     * $model->get_list('custom_id', 'name') // Вернет массив вида ((custom_id=>name), …)
     * $model->get_list(array('name', 'notice')) // Вернет массив вида ((id=>[name=>, notice=>]), …)
     * $model->get_list('login', array('name', 'notice')) // Вернет массив вида ((login=>[name=>, notice=>]), …)
     * @return array
     */
    public function get_list($index_by, $values = null) {
        if (func_num_args() == 1) {
            $values   = $index_by;
            $index_by = $this->get_primary_key();
        } else {
            $this->group_by($index_by);
        }

        if (empty($this->query['select'])) {
            $this->select(array_merge((array)$index_by, (array)$values));
        }

//        $result_type = $this->result_type ? $this->result_type : $this->default_result_type;

        $list   = array();
        $result = (array) $this->execute(self::QUERY_SELECT, 'get_results');

        if (is_string($values)) {
            foreach ($result as $row) {
                $list[$row[$index_by]] = $row[$values];
            }
        } else {
            foreach ($result as $row) {
                foreach ($values as $value_key) {
                    $list[$row[$index_by]][$value_key] = $row[$value_key];
                }
            }
        }

        return $list;
    }

    //-------------------------------------------------------------------------

    public function count_all() {
        $count = $this->select('COUNT(*)')->get_value();

        if ($count === null) {
            return null;
        }

        return (int) $count;
    }

    //-------------------------------------------------------------------------

    public function insert($data = null) {
        if ($data !== null) {
            $this->set($data);
        }

        $result = $this->execute(self::QUERY_INSERT, 'query');

        if ($result) {
            return nc_core('db')->insert_id;
        }

        return $result;
    }

    //-------------------------------------------------------------------------

    public function replace($data = null) {
        if ($data !== null) {
            $this->set($data);
        }

        $result = $this->execute(self::QUERY_REPLACE, 'query');

        if ($result) {
            return nc_core('db')->insert_id;
        }

        return $result;
    }

    //-------------------------------------------------------------------------

    public function update($data = null) {
        if ($data !== null) {
            // Автоматически задаем условие where_id() если ID есть в массиве $data
            $this->where_id_from_array($data, true);
            $this->set($data);
        }

        return $this->execute(self::QUERY_UPDATE, 'query');
    }

    //-------------------------------------------------------------------------

    public function force_update() {
        return $this->execute(self::QUERY_UPDATE, 'query', true);
    }

    //-------------------------------------------------------------------------

    public function delete() {
        return $this->execute(self::QUERY_DELETE, 'query');
    }
    //-------------------------------------------------------------------------

    public function force_delete() {
        return $this->execute(self::QUERY_DELETE, 'query', true);
    }

    //-------------------------------------------------------------------------

    // public function save($data) {
    //     // update
    //     if (!empty($data[$this->primary_key])) {
    //         $id = $data[$this->primary_key];
    //         unset($data[$this->primary_key]);
    //     // insert
    //     } else {

    //     }
    // }

    /**************************************************************************
        Result config helpers
    **************************************************************************/

    public function index_by($key) {
        $this->index_by = $key;

        return $this;
    }

    //-------------------------------------------------------------------------

    public function index_by_id() {
        $this->index_by = $this->get_primary_key();

        return $this;
    }

    //-------------------------------------------------------------------------

    public function as_array() {
        $this->result_type = ARRAY_A;

        return $this;
    }

    //-------------------------------------------------------------------------

    public function as_object() {
        $this->result_type = OBJECT;

        return $this;
    }

    /**************************************************************************
        Public helpers
    **************************************************************************/

    public function save_query($key = 'default') {
        $this->saved_queries[$key] = $this->query;
        $this->reset_query();

        return $this;
    }

    //-------------------------------------------------------------------------

    public function load_query($key = 'default') {
        if (isset($this->saved_queries[$key])) {
            $this->query = $this->saved_queries[$key];
        }

        return $this;
    }

    //-------------------------------------------------------------------------

    public function validate($data) {
        return true;
    }

    //-------------------------------------------------------------------------

    public function make_form($data) {
        $data = (object) $data;

        $fields = $this->get_fields();

        foreach ($fields as $name => &$field) {
            if (empty($field['field'])) {
                unset($fields[$name]);
                continue;
            }

            $field['type']    = $field['field'];
            $field['value']   = isset($data->$name) ? $data->$name : null;
            $field['caption'] = isset($field['title']) ? $field['title'] : null;

            if (empty($field['size'])) $field['size'] = 64;

            if ($field['type'] !== 'checkbox') {
                if (!empty($field['required'])) {
                    $field['caption'] .= ' (*):';
                }
                else {
                    $field['caption'] .= ":";
                }
            }
        }

        $form = new nc_a2f($fields, 'data');
        $form->show_default_values(false)->show_header(false);

        return
            nc_core::get_object()->token->get_input() .
            $form->render(
                false,
                array(
                    'checkbox' => '<div class="nc-field %CLASS"><label>%VALUE %CAPTION</label></div>',
                    'default' => '<div class="nc-field %CLASS"><span class="nc-field-caption">%CAPTION</span>%VALUE</div>',
                ),
                false,
                false
            );
    }

    /**************************************************************************
        PROTECTED
    **************************************************************************/

    protected function where_id_from_array(&$data, $remove_key_form_data = false) {
        if ($this->primary_key && !empty($data[$this->primary_key])) {
            $id = $data[$this->primary_key];

            if (!$this->query['where']) {
                $this->where_id($id);
            }

            if ($remove_key_form_data) {
                unset($data[$this->primary_key]);
            }
        }
    }


    /**
     * Делает из $value строку, готовую для подстановки в SQL-запрос
     * @param mixed $value
     * @return string
     */
    protected function value_to_sql($value) {
        if ($value === null) {
            return "NULL";
        }

        if ($value instanceof nc_db_expression) {
            return $value->to_string();
        }

        if (is_array($value)) {
            $value = array_map(array($this, __METHOD__), $value);
            return "(" . join(", ", $value) . ")";
        }

        // $db->prepare() отличается от $db->escape() отсутствием stripslashes()
        return "'" . nc_db()->prepare($value) . "'";
    }

    //-------------------------------------------------------------------------

    protected function _where($cond = 'AND', array $where) {
        $num_arguments = count($where);

        // $table->where(array('Field1' => 10, 'Field2 => 20))  →  'Field1 = 10 AND Field2 = 20'
        if ($num_arguments === 1 && is_array($where[0])) {
            foreach ($where[0] as $key => $value) {
                $this->_where($cond, array($key, $value));
            }
            return $this;
        }

        if ($num_arguments == 1) { // $table->where('Field > 10 OR Field < 1000')->...
            $where = $where[0];
        }
        else {
            $field = $where[0];
            if ($num_arguments == 2) { // $table->where('Field', 10)->...
                $operator = '=';
                $value = $where[1];
            }
            else if ($num_arguments == 3) {
                $operator = $where[1];
                $value = $where[2];
            }
            else {
                throw new InvalidArgumentException(
                    __CLASS__ . '::' . __METHOD__ . '() must have from 1 to 3 where elements'
                );
            }

            $operator = strtoupper($operator);
            if ($value === null && $operator != 'IS' && $operator != 'IS NOT') {
                $operator = 'IS';
            }

            $where = "`$field` $operator " . $this->value_to_sql($value);
        }

        $cond = $this->query['where'] ? " {$cond} " : '';
        $this->query['where'] .= $cond . $where;

        return $this;
    }

    //-------------------------------------------------------------------------

    protected function escape($val) {
        return nc_core('db')->escape($val);
    }

    //-------------------------------------------------------------------------

    public function make_query($type = self::QUERY_SELECT, $force = false) {
        $sql    = '';
        $table  = $this->table;
        $where  = '';

        if ($this->query['where']) {
            $where = ' WHERE ' . $this->query['where'];
        }

        switch ($type) {

            case self::QUERY_SELECT:
                $select   = $this->query['select'] ? $this->query['select'] : '*';
                $join     = $this->query['join'] ? $this->query['join'] : '';
                $group_by = $this->query['group_by'] ? ' GROUP BY ' . $this->query['group_by'] : '';
                $order_by = $this->query['order_by'] ? ' ORDER BY ' . $this->query['order_by'] : '';
                $limit    = $this->query['limit'] ? ' LIMIT ' . $this->query['limit'] : '';
                $sql      = "SELECT {$select} FROM `{$table}`" . $join . $where . $group_by . $order_by . $limit;
                break;

            case self::QUERY_INSERT:
                $set = $this->prepare_set_clause();

                if ($set) {
                    $sql = "INSERT INTO `{$table}` SET {$set}";
                }
                break;

            case self::QUERY_REPLACE:
                $set = $this->prepare_set_clause();

                if ($set) {
                    $sql = "REPLACE INTO `{$table}` SET {$set}";
                }
                break;

            case self::QUERY_UPDATE:
                $set = $this->prepare_set_clause();

                if ($set && ($force || $where)) {
                    $set = ' SET ' . $set;
                    $sql = "UPDATE `{$table}`{$set}{$where}";
                }
                break;

            case self::QUERY_DELETE:
                if ($force || $where) {
                    $sql = "DELETE FROM `{$table}`{$where}";
                }
                break;
        }
        $sql = $this->replace_keywords($sql);
        $this->reset_query();

        return $sql;
    }

    //-------------------------------------------------------------------------

    protected function execute($type, $exec_method = 'query', $force = false) {
        if ($this->before_execute($type) === FALSE) {
            return;
        }

        $result_type = $this->result_type ? $this->result_type : $this->default_result_type;

        $sql = $this->make_query($type, $force);

        $this->reset_data();
        $this->last_query  = $sql;
        $this->result_type = null;

        if ($sql) {
            $this->last_result = nc_db()->$exec_method($sql, $result_type);
        } else {
            $this->last_result = false;
        }
        $this->after_execute($type);

        return $this->last_result;
    }

    //-------------------------------------------------------------------------

    protected function prepare_set_clause() {
        $set = '';
        foreach ($this->data as $name => $value) {
            // сохранять значение поля как NULL, если в настройках поля указан
            // параметр "save_empty_value_as_null" => true (для INT/FLOAT полей с возможным NULL-значением),
            if (strlen(trim($value)) == 0 &&
                isset($this->fields[$name]["save_empty_value_as_null"]) &&
                $this->fields[$name]["save_empty_value_as_null"]
            ) {
                $value = null;
            }

            $set .= ($set ? ', ' : '') . "`$name` = " . $this->value_to_sql($value);
        }

        return $set;
    }

    //-------------------------------------------------------------------------

    protected function replace_keywords($string) {
        if (strpos($string, '{') !== false) {
            $dict = array(
                '{table}' => '`' . $this->get_table() . '`',
                // '{table_alias}' => $this->get_table_alias()
            );
            $string = str_replace(array_keys($dict), $dict, $string);
        }
        return $string;
    }
}