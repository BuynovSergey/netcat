<?php
/**
 *
 */
abstract class nc_search_query_expression_composite extends nc_search_query_expression {
    /**
     * @var nc_search_query_expression[]   Expression parts in compound expressions
     */
    protected $items = array();


    /**
     * @param nc_search_query_expression (any number of parameters)
     */
    public function __construct() {
        foreach (func_get_args() as $item) { $this->add_item($item); };
    }

    /**
     *
     */
    public function add_item(nc_search_query_expression $item) {
        if ($this->field && !$item->get_field()) { $item->set_field($this->field); }
        $this->items[] = $item;
        return $this;
    }

    /**
     * @return nc_search_query_expression[]
     */
    public function get_items() {
        return $this->items;
    }

    /**
     * @param string $field_name
     * @return nc_search_query_expression_composite
     */
    public function set_field($field_name) {
        $this->field = $field_name;
        foreach ($this->items as $item) {
            if (!$item->get_field()) { $item->set_field($this->field); }
        }
        return $this;
    }

}