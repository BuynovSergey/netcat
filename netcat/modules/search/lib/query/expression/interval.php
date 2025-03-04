<?php
/**
 *
 */
class nc_search_query_expression_interval extends nc_search_query_expression_composite {

    protected $type = "exclusive";

    public function __construct($value1, $value2, $type = "exclusive") {
        $this->add_item(new nc_search_query_expression_term($value1));
        $this->add_item(new nc_search_query_expression_term($value2));
        if ($type == "inclusive") { $this->type = $type; }
    }

    public function get_type() {
        return $this->type;
    }

    public function is_exclusive() {
        return $this->type == "exclusive";
    }

    public function is_inclusive() {
        return $this->type == "inclusive";
    }
    
}