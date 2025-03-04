<?php
/**
 *
 */
class nc_search_provider_index_field_manager extends nc_search_data_persistent_collection {

    protected static $cache = null;

    protected $items_class = 'nc_search_provider_index_field';
    protected $index_property = 'compound_key';

    /**
     * Получить составной ключ по определяющим свойствами поля (используется для
     * индексации элементов в коллекции данного типа с целью быстрого поиска полей)
     *
     * @param nc_search_field|nc_search_provider_index_field $field
     * @return string
     */
    public static function get_key_options($field) {
        return $field->get('name') . "#" .
               sprintf("%.2F", $field->get('weight')) . '#' .
               $field->get('type') . '#' .
               (int)$field->get('is_sortable');
    }

    /**
     * Получить все поля индекса
     * (Отдельный от load_all() метод, т.к. ограничения PHP 5.2 не позволяют сделать
     * простой универсальный код для подклассов из-за отсутствия "static::".)
     * @return nc_search_provider_index_field_manager
     */
    static public function get_all() {
        if (!self::$cache) {
            self::$cache = $collection = new self();
            $collection->select_from_database("SELECT * FROM `%t%`");
        }
        return self::$cache;
    }

    /**
     * Получить объект nc_search_provider_index_field, соответствующий
     * указанному nc_search_field. Создаёт таблицу в БД при необходимости.
     * @param nc_search_field $field
     * @return nc_search_provider_index_field
     */
    public function get_index_field(nc_search_field $field) {
        $index_field = $this->offsetGet(self::get_key_options($field));
        if (!$index_field) {
            // create the index field record (== about the "index field table")
            $index_field = new nc_search_provider_index_field();
            $index_field->copy_options($field)->save();
            $this->add($index_field);

            // create the table
            $is_sortable = $field->get('is_sortable');
            if ($field->get('type') == nc_search_field::TYPE_INTEGER) {
                $raw_data_type = "BIGINT";
                $raw_data_index = ($is_sortable) ? ', INDEX `RawData` (`RawData`)' : '';
            }
            else { // only two field types at the moment: integer and text
                $raw_data_type = "LONGTEXT character set utf8 collate utf8_general_ci";
                $raw_data_index = ($is_sortable) ? ', INDEX `RawData` (`RawData`(255))' : '';
            }

            // latin1_bin collation will make regexp conditions case sensitive,
            // which is probably good for performance reasons
            nc_db()->query("
              CREATE TABLE IF NOT EXISTS `{$index_field->get_field_table_name()}` (
                `Document_ID` INT(11) UNSIGNED NOT NULL,
                `Content` LONGTEXT character set latin1 collate latin1_bin,
                `RawData` $raw_data_type,
                PRIMARY KEY  (`Document_ID`),
                FULLTEXT KEY `Content` (`Content`)
                $raw_data_index
              ) ENGINE=MyISAM
            ");

        }
        return $index_field;
    }

    /**
     * @return array
     */
    public function get_all_table_names() {
        return $this->each('get_field_table_name');
    }

    public function drop_empty_tables() {
        $db = nc_db();
        /** @var $field nc_search_provider_index_field */
        foreach ($this->items as $key => $field) {
            $table_name = $field->get_field_table_name();
            $num_records = $db->get_var("SELECT COUNT(*) FROM `$table_name`");
            if (!$num_records) {
                $db->query("DROP TABLE `$table_name`");
                $field->delete();
                unset($this->items[$key]);
            }
        }
        return $this;
    }

    public function optimize_tables() {
        $table_names = "`" . join("`, `", $this->get_all_table_names()) . "`";
        nc_db()->query("OPTIMIZE TABLE $table_names");
        return $this;
    }

}