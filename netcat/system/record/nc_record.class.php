<?php

/**
 * Базовый класс для работы со структурированными данными.
 *
 * Реализует базовую функциональность ActiveRecord, но может использоваться
 * и без привязки к базе данных.
 *
 */
abstract class nc_record implements ArrayAccess, Iterator, Serializable {

    /**
     * Массив для хранения значений свойств
     */
    protected $properties = array();

    /**
     * Режим работы со свойствами. Если TRUE, то при обращении
     * к несуществующему в массиве $properties ключу будет выброшено исключение
     */
    protected $strict_property_mode = false;

    // -------------------------------------------------------------------------
    // Свойства, относящиеся к persistence
    // -------------------------------------------------------------------------

    /**
     * Первичный ключ в таблице
     *
     * @var string
     */
    protected $primary_key = "id";

    /**
     * Key: property name
     * К сожалению, подобное свойство необходимо из-за того, что в Netcat разные
     * соглашения о правиле наименований в PHP (small_underscored) и БД (CamelCase)
     * Value: sql column name
     *
     * Можно так:  $mapping = array("id" => "Smth_ID", "_generate"=>true);
     * остальные поля будут сгенерированы автоматически из свойств ($properties)
     *
     * Если тождественно равно false, то конвертация между названиями свойств и
     * названиями колонок таблицы в БД  не производится.
     *
     * @var array|boolean
     */
    protected $mapping = array();

    /**
     * Исключить из генерируемого mapping указанные свойства ($properties)
     *
     * @var array
     */
    protected $mapping_exclude = array();

    /**
     * Список из свойств, которые не должны быть сохранены в БД, например,
     * если в record содержится гибридный результирующий массив из двух и более таблиц БД
     *
     * Названия ключей должны совпадать с названиями ключей свойства $mapping
     *
     * @var array
     */
    protected $mapping_only_for_loading = array();

    /**
     * Имя таблицы в БД. Должно быть обязательно определено!
     *
     * @var string
     */
    protected $table_name = "";

    /**
     * Свойства ($properties), которые перед сохранением в БД должны быть сериализованы
     *
     * @var array
     */
    protected $serialized_properties = array();

    /**
     * Производить десериализацию свойств рекурсивно
     *
     * @var bool
     */
    protected $deep_unserialize = false;

    /**
     * Свойства класса, которые сохраняются при сериализации
     *
     * @var array
     */
    protected $serialized_object_properties = array('properties');

    /**
     * Если TRUE, то в случае возникновения ошибки будет выброшено исключение
     * nc_record_exception; иначе будет сгенерирован E_USER_WARNING
     */
    protected $throw_exception_on_error = true;

    /**
     * @see self::generate_mapping()
     * @var array
     */
    static protected $mapping_cache = array();

    /**
     * @var string
     */
    private $current_query;


    /**
     * @param int $id
     *
     * @return static
     * @throws InvalidArgumentException|nc_record_exception
     */
    public static function get_by_id($id) {
        if (!is_scalar($id)) {
            throw new InvalidArgumentException("Id must be an integer or a string");
        }

        return new static($id);
    }

    /**
     * Конструктор
     *
     * @param mixed $values
     *      (a) массив со значениями свойств, или
     *      (b) ID объекта (внимание! если $throw_exception_on_error = true, то
     *          выбросит исключение!)
     *
     * @throws nc_record_exception
     */
    public function __construct($values = null) {
        if (is_array($values)) {
            $this->set_values($values);
        }

        if (isset($this->table_name)) {
            if (isset($this->mapping["_generate"]) && $this->mapping["_generate"]) {
                $this->generate_mapping();
            }

            if ($values && is_scalar($values)) {
                $this->load($values);
            }
        }
    }


    // -------------------------------------------------------------------------
    // Общая функциональность
    // (не обязательно используется вместе с сохранением в БД)
    // -------------------------------------------------------------------------

    /**
     *
     * @param string $property ключ в массиве $this->properties
     * @param mixed $value новое значение
     * @param boolean $add_new_property добавить свойство, если оно не было ранее определено
     *
     * @return self
     * @throws nc_record_exception
     */
    public function set($property, $value, $add_new_property = false) {
        if (!$add_new_property) {
            $this->check_property($property);
        }
        $this->properties[$property] = $value;

        return $this;
    }

    /**
     *
     * @param string $property ключ в массиве $this->properties
     *
     * @return mixed значение
     * @throws nc_record_exception
     */
    public function get($property) {
        if ($this->strict_property_mode) {
            $this->check_property($property);
        }

        return isset($this->properties[$property]) ? $this->properties[$property] : null;
    }

    /**
     * Проверка наличия свойства
     *
     * @param string $property
     *
     * @return boolean
     */
    public function has_property($property) {
        return array_key_exists($property, $this->properties);
    }

    /**
     * Бросает исключение, если нет свойства $property
     *
     * @param $property
     *
     * @return void
     * @throws nc_record_exception
     */
    public function check_property($property) {
        if ($this->strict_property_mode && !array_key_exists($property, $this->properties)) {
            throw new nc_record_exception("Invalid properties key '$property' (class " . get_class($this) . ")");
        }
    }

    /**
     * Установка параметров из массива
     *
     * @param array $values
     * @param boolean $ignore_unknown skip properties which are not present in the current class
     *
     * @return static
     */
    public function set_values(array $values, $ignore_unknown = false) {
        foreach ($values as $k => $v) {
            if (!$ignore_unknown || $this->has_property($k)) {
                $this->set($k, $v);
            }
        }

        return $this;
    }

    /**
     * Добавление элемента к массиву $property
     *
     * @param string $property property key
     * @param mixed $value новое значение в массиве
     * @param boolean $only_if_unique для обеспечения уникальности значений.
     *                                  значение должно быть string/integer!
     *
     * @return static
     */
    public function push_to($property, $value, $only_if_unique = false) {
        $this->check_property($property);
        if (!is_array($this->properties[$property])) {
            $this->properties[$property] = array($value);
        }
        elseif (!$only_if_unique || !in_array($value, $this->properties[$property])) {
            $this->properties[$property][] = $value;
        }

        return $this;
    }

    /**
     * Получить все значения $properties в виде массива
     *
     * @return array
     */
    public function to_array() {
        return $this->properties;
    }

    /**
     * Регистрация ошибки. В зависимости от значения $this->throw_exception_on_error
     * генерирует warning или исключение.
     * $message может содержать спецсимволы для printf (%s и т.п.), в этом
     * случае параметры для printf берутся из второго и последующих аргументов:
     * $this->process_error("Invalid input: %s", $some_value)
     * Подставляемые параметры будут обработаны htmlspecialchars.
     */
    protected function process_error($message) {
        // prepend class name to the message
        $message = get_class($this) . ": $message";
        $args = func_get_args();
        $inject = array_slice($args, 1);
        if ($inject) { // вставить дополнительные параметры
            foreach ($inject as $k => $v) {
                $inject[$k] = htmlspecialchars($v, ENT_QUOTES);
            }
            $message = vsprintf($message, $inject);
        }

        if ($this->throw_exception_on_error) {
            throw new nc_record_exception($message);
        }
        else {
            user_error($message, E_USER_WARNING);
        }
    }

    // -------------------------------------------------------------------------
    // Методы, относящиеся к сохранению и загрузке из БД
    // -------------------------------------------------------------------------

    /**
     *
     * @return string
     */
    public function get_table_name() {
        return $this->table_name;
    }

    /**
     *
     * @return string
     */
    public function get_primary_key() {
        return $this->primary_key;
    }

    /**
     * Получить название колонки в таблице по названию свойства.
     *
     * NB. При изменении этого метода также следует изменить get_all_column_names
     *
     * @param string $property_name
     *
     * @return string|null table column name
     * @throws nc_record_exception
     */
    protected function property_to_column($property_name) {
        if ($this->mapping === false) {
            return $property_name;
        }

        if (isset($this->mapping[$property_name])) {
            return $this->mapping[$property_name];
        }

        $this->process_error(get_class($this) . ": no mapping for the property '$property_name'");

        return null;
    }

    /**
     * Получить имя свойства по названию колонки.
     *
     * @param string $column_name
     *
     * @return mixed FALSE, если такого свойства нет
     */
    protected function column_to_property($column_name) {
        // Если $column_name является существующим именем свойства, вернуть $column_name
        // («оптимизация», @see $this->get_all_column_names())
        if (in_array($column_name, $this->mapping_only_for_loading) || $this->mapping === false ||
            isset($this->mapping[$column_name])
        ) {
            return $column_name;
        }

        return array_search($column_name, $this->mapping);
    }

    /**
     * Имена всех полей в виде строки для SQL запроса
     */
    protected function get_all_column_names($add_property_aliases = true) {
        static $column_names; // shared among all instances
        if ($this->mapping === false) {
            return "*";
        }
        //в PHP 8.2 без этого сброса строится неправильный запрос
        $column_names = null;
        if (!$column_names) {
            if ($add_property_aliases) { // adds property names as aliases
                $mapping = array();
                foreach ($this->mapping as $property => $column) {
                    if(in_array($property, $this->mapping_only_for_loading)){
                        continue;
                    }

                    $mapping[] = "`$column` AS `$property`";
                }
                $column_names = join(", ", $mapping);
            }
            else { // column names "as is", without aliases
                $column_names = "`" . join("`, `", array_values($this->mapping)) . "`";
            }
        }

        return $column_names;
    }

    /**
     * Получить имена колонок, соответствующие указанным свойствам
     *
     * @param array $properties если пустой массив или null - возвращает все имена полей
     *
     * @return string
     */
    public function get_column_names(array $properties = null) {
        if (!$properties) {
            return $this->get_all_column_names();
        }
        $result = array();
        foreach ($properties as $property) {
            $result[] = "`{$this->property_to_column($property)}`";
        }

        return join(", ", $result);
    }

    /**
     * Подготавливает значение для вставки в SQL-запрос.
     * Если значение равно null, возвращает строку NULL.
     * Для boolean возвращает 0 или 1.
     * Для прочих значений экранирует значение и добавляет одинарные кавычки.
     *
     * @param mixed
     *
     * @return string|int
     */
    protected function escape_value($v) {
        if (is_null($v)) {
            $v = 'NULL';
        }
        elseif (is_bool($v)) {
            $v = (int)$v;
        }
        elseif (is_float($v)) {
            $v = str_replace(",", ".", $v);
        }
        else {
            $v = "'" . nc_db()->prepare($v) . "'";
        }

        return $v;
    }

    /**
     * Преобразовать массив вида ('key', 'value', 'key2', 'value2') в условие для
     * WHERE-части SQL-запроса
     *
     * @return string
     * @throws nc_record_exception Если указано поле, отсутствующее в $mapping
     */
    protected function make_condition() {
        $args = func_get_args();
        if (is_array($args[0])) {
            $args = $args[0];
        }
        $property_conditions = $this->make_assoc_array($args);
        $column_conditions = array();

        foreach ($property_conditions as $property_name => $property_value) {
            $condition_column = $this->property_to_column($property_name);
            $column_conditions[] = "`$condition_column` = " .
                $this->escape_value($property_value);
        }

        return join(' AND ', $column_conditions);
    }

    /**
     * Вспомогательный метод, чтобы не печатать большой $mapping руками (неинтересно ведь)
     *
     * @return static
     */
    protected function generate_mapping() {
        $class_name = get_class($this);
        if (!isset(self::$mapping_cache[$class_name])) {
            $result = $this->mapping;
            if (isset($result["_generate"])) {
                unset($result["_generate"]);
            }
            foreach (array_keys($this->properties) as $property_name) {
                if (in_array($property_name, $this->mapping_exclude)) {
                    continue;
                } // excluded from the mapping
                if (isset($this->mapping[$property_name])) {
                    continue;
                } // already set
                // convert to CamelCase
                // actually, this is a little bit redundant because MySQL column names are case-insensitive
                $n = ucwords(str_replace("_", " ", $property_name));
                $n = preg_replace("/ Id$/", "_ID", $n); // "property_id" => "Property_ID", not "PropertyId"
                $n = str_replace(" ", "", $n);
                $result[$property_name] = $n;
            }
            self::$mapping_cache[$class_name] = $result;
        }
        $this->mapping = self::$mapping_cache[$class_name];

        return $this;
    }

    /**
     * @return int|string
     */
    public function get_id() {
        return $this->get($this->primary_key);
    }

    /**
     *
     * @param int|string $value
     *
     * @return static
     */
    public function set_id($value) {
        return $this->set($this->primary_key, $value);
    }

    /**
     * @return void
     * @throws nc_record_exception
     */
    protected function check_mapping_settings() {
        if (!$this->table_name) {
            $this->process_error('no $table_name');
        }
        if ($this->mapping !== false && !sizeof($this->mapping)) {
            $this->process_error('no $mapping');
        }
    }

    /**
     * Get the body of the SET statement for INSERT/REPLACE
     *
     * @return string
     */
    protected function prepare_set_clause() {
        $set = array();
        foreach (array_keys($this->properties) as $k) {
            if (in_array($k, $this->mapping_only_for_loading) || $this->mapping !== false && !isset($this->mapping[$k])) {
                continue;
            }
            $v = $this->get($k);

            if (in_array($k, $this->serialized_properties)) {
                if ((is_array($v) && empty($v)) || (is_string($v) && $v == '') || $v === null) {
                    $v = '';
                }
                else {
                    $v = serialize($v);
                }
            }

            $set[] = "`{$this->property_to_column($k)}` = " . $this->escape_value($v);
        }
        $set = join(", ", $set);

        return $set;
    }

    /**
     * Сохранение в БД
     *
     * @return static
     * @throws nc_record_exception
     */
    public function save() {
        $this->check_mapping_settings();
        $db = nc_db();

        $record_id = $this->get($this->primary_key);
        $set_clause = $this->prepare_set_clause();
        $where_clause = $this->make_condition($this->primary_key, $record_id);
        $update = strlen($record_id) > 0 && $db->get_var("SELECT 1 FROM `$this->table_name` WHERE $where_clause");

        if ($update) {
            $query = "UPDATE `$this->table_name` 
                           SET $set_clause 
                         WHERE $where_clause";

            $db->query($query);
        }
        else {
            $query = "INSERT INTO `$this->table_name` SET $set_clause";
            $db->query($query);
            if ($db->insert_id) {
                $this->set_id($db->insert_id);
            }
        }

        $this->current_query = $query;

        if ($db->is_error) {
            $this->process_error("cannot save to the database (computer says no: '%s')", $db->last_error);
        }

        return $this;
    }

    /**
     * Удаление из БД
     *
     * @return static
     * @throws nc_record_exception
     */
    public function delete() {
        $this->check_mapping_settings();
        $pk = $this->get_id();
        $db = nc_db();

        if (!strlen($pk)) {
            return $this;
        }

        $query = "DELETE FROM `$this->table_name`" .
            " WHERE " . $this->make_condition($this->primary_key, $pk);

        $db->query($query);
        $this->current_query = $query;

        if ($db->is_error) {
            $this->process_error("cannot delete record in the database ('%s')", $db->last_error);
        }

        return $this;
    }

    /**
     * Загрузка из БД
     *
     * @param mixed $id
     *
     * @return static
     * @throws nc_record_exception
     */
    public function load($id) {
        //$result = $this->load_where($this->primary_key, $id);
        // save few function methods for this frequent method
        $query = "SELECT " . $this->get_all_column_names() .
            " FROM `$this->table_name`" .
            " WHERE `" . ($this->property_to_column($this->primary_key)) . "` = " . $this->escape_value($id) .
            " LIMIT 1";

        $this->current_query = $query;
        $result = $this->select_from_database($query);

        if (!$result) {
            $this->process_error("object with {$this->primary_key} = %s not found", $id);
        }

        return $this;
    }


    /**
     * Загрузка записи из БД по указанному SQL-запросу.
     * Можно использовать %t% для подстановки имени таблицы:
     * $record->select_from_database("SELECT * FROM `%t` WHERE id=123")
     *
     * @param $query
     *
     * @return static|bool
     * @throws nc_record_exception
     */
    protected function select_from_database($query) {
        $this->check_mapping_settings();
        $db = nc_db();

        $query = str_replace('%t%', $this->table_name, $query);
        $result = $db->get_row($query, ARRAY_A);
        $this->current_query = $query;

        if ($db->is_error) {
            $this->process_error("cannot load data object: '%s'", $db->last_error);
        }

        if (!$result) {
            return false;
        }

        return $this->set_values_from_database_result($result);
    }

    /**
     * как всё неуклюже до 5.3 получается :(
     *
     * @return static|bool
     */
    public function load_where() {
        $args = func_get_args();
        $query = "SELECT " . $this->get_all_column_names() .
            " FROM `$this->table_name`" .
            " WHERE " . $this->make_condition($args) .
            " LIMIT 1";

        $this->current_query = $query;

        return $this->select_from_database($query);
    }

    /**
     * Аналог для set_values для загрузки результатов из БД
     *
     * @param array $values
     *
     * @return static
     */
    public function set_values_from_database_result(array $values) {
        foreach ($values as $column_name => $value) {


            $property_name = $this->column_to_property($column_name);
            if ($property_name) {
                if (in_array($property_name, $this->serialized_properties)) {
                    $value = unserialize($value);

                    if($this->deep_unserialize && is_array($value)){
                        array_walk_recursive($value, function (&$item){
                           $item = @unserialize($item) ?: $item;
                        });
                    }
                }

                $this->set($property_name, $value, true);
            }
        }

        return $this;
    }

    /**
     * @return string
     */
    public function get_current_query_string() {
        return $this->current_query;
    }

    /**
     * array('key1', 'value1', 'key2', 'value2) → array('key1' => 'value1', 'key2' => 'value2')
     *
     * @param array $list
     *
     * @return array
     */
    protected function make_assoc_array(array $list) {
        $result = array();
        $count = sizeof($list);
        if ($count % 2 == 1) {
            $list[] = null;
        } // pad an array if needed

        for ($i = 0; $i < $count; $i = $i + 2) {
            $result[$list[$i]] = $list[$i + 1];
        }

        return $result;
    }

    // -------- ArrayAccess interface -----------
    public function offsetGet($offset) {
        return $this->get($offset);
    }

    public function offsetSet($offset, $value) {
        $this->set($offset, $value);
    }

    public function offsetExists($offset) {
        return $this->has_property($offset);
    }

    public function offsetUnset($offset) {
        unset($this->properties[$offset]);
    }

    // --------- Iterator interface -----------
    public function rewind() {
        reset($this->properties);
    }

    public function current() {
        return $this->offsetGet(key($this->properties));
    }

    public function key() {
        return key($this->properties);
    }

    public function next() {
        return next($this->properties);
    }

    public function valid() {
        return (key($this->properties) !== null);
    }

    // --------- Serializable interface -----------

    public function serialize() {
        $data = array();
        foreach ($this->serialized_object_properties as $property) {
            $data[$property] = $this->$property;
        }

        return serialize($data);
    }

    public function unserialize($serialized) {
        $data = unserialize($serialized);
        foreach ($data as $property => $value) {
            $this->$property = $value;
        }
    }

}
