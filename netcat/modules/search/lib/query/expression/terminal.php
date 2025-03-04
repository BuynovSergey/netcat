<?php
/**
 *
 */
abstract class nc_search_query_expression_terminal extends nc_search_query_expression {

    protected $value;

    /**
     * @param string $value
     */
    public function __construct($value) {
        $this->value = (string)$value;
    }

    public function get_value() {
        return $this->value;
    }
}