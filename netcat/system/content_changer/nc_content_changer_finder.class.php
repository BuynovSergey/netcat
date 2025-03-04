<?php

/**
 * Класс для передачи параметров запроса с сервера, генерирующего инструкции, на выполняющий инструкции сайт.
 * Доступны те же методы, что и в nc_db_table(); но при вызове методов get_* и подобных не делает запрос к БД.
 * Для выполнения выборки на сайте-приёмнике используется этот же класс:
 * — метод apply() выполнит запрос к БД;
 * — при приведении к строке выполняет запрос (если возвращает массив, то значения будут через запятую);
 * — метод get_table() возвращает таблицу с применёнными условиями, без собственно запроса к БД.
 *
 * @method $this join($select = '*')
 * @method $this where_id($id)
 * @method $this where($key, $operator = null, $value = null)
 * @method $this or_where($key, $operator = null, $value = null)
 * @method $this or_where_id($operator = null, $value = null)
 * @method $this where_in($key, $values)
 * @method $this where_in_id($values)
 * @method $this or_where_in($key, $values)
 * @method $this or_where_in_id($values)
 * @method $this order_by($field, $type = 'asc')
 * @method $this group_by($field)
 * @method $this limit($limit_or_offset, $limit = null)
 * @method $this get_result()
 * @method $this get_row($where_key_value = null)
 * @method $this get_value($field = null)
 * @method $this get_list($index_by, $values = null)
 */

abstract class nc_content_changer_finder {

    // Параметры основной таблицы:

    const TABLE_NAME = 'OVERRIDE_THIS';
    const TABLE_PRIMARY_KEY = 'OVERRIDE_THIS';

    // При добавлении свойств к этому классу следует помнить, что он сериализуется в инструкциях изменения контента

    // Параметры запроса:

    protected $table_query = array();
    protected $table_action = array();

    /**
     * Возвращает объект с условиями для выборки данных.
     * Пример:
     *    nc_content_changer_infoblock_finder::select('Sub_Class_ID')->where('EnglishName', 'LIKE', 'smth_%')
     *
     * @param string|null $property если указан, при выполнении выборки будет возвращено значение поля первого результата
     *      (то же, что и метод first())
     * @return static
     */
    public static function select($property = null) {
        $finder = new static;
        if ($property) {
            $finder->limit(1)->get_value($property);
        }
        return $finder;
    }

    /**
     * Возвращает объект с условиями для выборки идентификатора.
     * Пример:
     *    nc_content_changer_infoblock_finder::select_id()->where('EnglishName', 'LIKE', 'smth_%')
     *    то же самое, что:
     *    nc_content_changer_infoblock_finder::select('Sub_Class_ID')->where('EnglishName', 'LIKE', 'smth_%')
     *
     */
    public static function select_id() {
        $finder = new static;
        $finder->limit(1)->get_value($finder::TABLE_PRIMARY_KEY);
        return $finder;
    }

    /**
     * Обеспечивает сохранение вызова методов для отложенного исполнения.
     * На удалённой копии при применении локатора будет выполнен метод apply_ИМЯ_МЕТОДА, а если такого нет —
     * одноимённый метод на объекте nc_db_table.
     * @param string $name
     * @param array $arguments
     * @return static
     */
    public function __call($name, array $arguments) {
        // Действия для выборки (такие как get_value()) записываются отдельно от условий выборки,
        // чтобы можно было их указывать в любом порядке (в начале формулирования запроса)
        if (strpos($name, 'get_') === 0) {
            $clone = clone $this;
            $clone->table_action = array($name, $arguments);
            return $clone;
        }

        $this->table_query[] = array($name, $arguments);
        return $this;
    }

    /**
     * Возвращает имя таблицы, из которой выполняется выборка
     * @return string
     */
    protected function get_table_name() {
        return static::TABLE_NAME;
    }

    /**
     * Применяет «контекст» к запросу, например, ограничивает выборку по Catalogue_ID
     * @param nc_db_table $table
     * @return void
     */
    protected function apply_context(nc_db_table $table) {
        $table->where_in('Catalogue_ID', array(nc_core::get_object()->catalogue->id(), 0));
    }

    /**
     * Возвращает nc_db_table соответствующую с применёнными условиями (без выполнения выборки)
     * @return nc_db_table
     */
    public function get_table() {
        $table = nc_db_table::make($this->get_table_name(), static::TABLE_PRIMARY_KEY);
        $this->apply_context($table);
        foreach ($this->table_query as $query) {
            list($method, $arguments) = $query;
            if (method_exists($this, 'apply_' . $method)) {
                $apply_method_arguments = array_merge(array($table), $arguments);
                call_user_func_array(array($this, 'apply_' . $method), $apply_method_arguments);
                // can use spread operator in PHP 7 instead of ↑
                // $this->{'apply_' . $method}($table, ...$arguments);
            } else {
                call_user_func_array(array($table, $method), $arguments);
            }
        }
        return $table;
    }

    /**
     * Выполняет запрос к таблице
     * @return string|array
     */
    public function apply() {
        $table = $this->get_table();

        if (!empty($this->table_action)) {
            list($method, $arguments) = $this->table_action;
        } else {
            $method = 'get_value';
            $arguments = array(static::TABLE_PRIMARY_KEY);
        }

        if (method_exists($this, 'apply_' . $method)) {
            $apply_method_arguments = array_merge(array($table), $arguments);
            return call_user_func_array(array($this, 'apply_' . $method), $apply_method_arguments);
            // can use spread operator in PHP 7 instead of ↑
            // $this->{'apply_' . $method}($table, ...$arguments);
        }

        return call_user_func_array(array($table, $method), $arguments);
    }

    /**
     * Выполняет запрос к таблице, возвращает результат в виде строки
     * @return string
     */
    public function __toString() {
        $result = $this->apply();
        if (is_array($result)) {
            $result = implode(', ', $result);
        }
        return (string)$result;
    }

}
