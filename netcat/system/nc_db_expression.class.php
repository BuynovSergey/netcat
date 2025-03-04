<?php

/**
 * Class nc_db_expression
 * Используется для передачи SQL-выражений в nc_db_table (вставляется в запрос as is)
 */
class nc_db_expression {
    protected $expression;

    public function __construct($expression) {
        $this->expression = $expression;
    }

    public function __toString() {
        return (string)$this->expression;
    }

    public function to_string() {
        return (string)$this->expression;
    }
}