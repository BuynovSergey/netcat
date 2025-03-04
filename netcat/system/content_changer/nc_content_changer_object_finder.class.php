<?php

/**
 * @method self get_composite_object_id() Выбирает составной ID объекта (компонент:объект). Должен быть последним в цепочке вызовов методов.
 */

class nc_content_changer_object_finder extends nc_content_changer_finder {

    const TABLE_NAME = '';
    const TABLE_PRIMARY_KEY = 'Message_ID';

    protected $component_keyword;

    /**
     * @param string|int $component_keyword ключевое слово или идентификатор компонента
     */
    public function __construct($component_keyword) {
        $this->component_keyword = $component_keyword;
    }

    /**
     * @return int
     */
    public function get_component_id() {
        try {
            return nc_core::get_object()->component->get_by_id($this->component_keyword, 'Class_ID');
        } catch (Exception $e) {
            return 0;
        }
    }

    /**
     * @return string
     */
    protected function get_table_name() {
        return 'Message' . $this->get_component_id();
    }

    /**
     * @param nc_db_table $table
     *
     * @return void
     */
    protected function apply_context(nc_db_table $table) {
        $table->join_using('Sub_Class', 'Sub_Class_ID');
        $table->where_in('Catalogue_ID', array(nc_core::get_object()->catalogue->id(), 0));
    }

    /**
     * @param nc_db_table $table
     *
     * @return mixed
     */
    protected function apply_get_composite_object_id(nc_db_table $table) {
        $value = "CONCAT({$this->get_component_id()}, ':', `Message_ID`) AS `id`";

        return $table->select(new nc_db_expression($value))->get_value();
    }

}
