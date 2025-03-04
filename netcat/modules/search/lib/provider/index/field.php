<?php
/**
 * Информация о поле документа. Является отдельной таблицей в индексе.
 *
 * Отдельный (от nc_search_field) класс существует потому, что nc_search_field
 * сохранены в базе данных (управляются из панели управления модулем) только
 * для HTML-документов (парсеры других типов документов могут создавать собственные
 * поля, информация о которых не будет содержаться в БД). При этом, теоретически,
 * могут существовать поля с одним и тем же названием, но с разными параметрами
 * (например, с разным весом), если они созданы разными парсерами
 *
 * Кроме того, отдельная абстракция упрощает управление изменениями при модификации
 * параметров полей (вес, возможность сортировки, тип данных поля).
 */
class nc_search_provider_index_field extends nc_search_data_persistent {
    const TYPE_STRING = nc_search_field::TYPE_STRING;
    const TYPE_INTEGER = nc_search_field::TYPE_INTEGER;

    /**
     * @var string
     */
    protected $table_name = "Search_Index_Field";

    /**
     * @var string
     */
    protected $table_name_prefix = "Search_Index_Field";

    /**
     * @var array
     */
    protected $properties = array(
        'id' => null, // primary key
        'name' => null,
        'weight' => 1,
        'type' => self::TYPE_STRING, // тип поля
        'is_stored' => false, // хранится начальное значение в индексе
//        'is_indexed' => true, // участвует в поиске (можно сделать запрос)
//        'is_normalized' => true, // анализируется (разбивка на токены, морфоанализ)
        'is_sortable' => false, // allow field sort queries?
        // «виртуальное» свойство: compound_key, используется в field_collection
    );

    /**
     * @var array
     */
    protected $mapping = array(
        'id' => 'Field_ID',
        '_generate' => true
    );

    /**
     * Скопировать свойства поля документа в свойства поля индекса
     * @param nc_search_field $field
     * @return nc_search_provider_index_field
     */
    public function copy_options(nc_search_field $field) {
        foreach ($this->properties as $o => $v) {
            if ($o != 'id') { $this->set($o, $field->get($o)); }
        }
        return $this;
    }

    /**
     *
     */
    public function get($option) {
        if ($option == 'compound_key') {
            return nc_search_provider_index_field_manager::get_key_options($this);
        }
        return parent::get($option);
    }

    /**
     * Получить имя таблицы, в которой хранится индекс поля; не путать с $this->get_table_name()!
     * @return string
     * @throws nc_search_provider_index_exception
     */
    public function get_field_table_name() {
        $id = $this->get_id();
        if (!$id) {
            throw new nc_search_provider_index_exception(
                "Wrong call to " . __CLASS__ . "::" . __METHOD__ .
                "(): no field ID yet (save " . __CLASS__ . " first!)");
        }
        return $this->table_name_prefix . (int)$id;
    }

}