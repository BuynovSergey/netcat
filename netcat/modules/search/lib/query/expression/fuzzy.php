<?php
/**
 *
 */
class nc_search_query_expression_fuzzy extends nc_search_query_expression_terminal {

    protected $similarity = 0.5;

    public function __construct($value, $similarity) {
        $this->value = $value;
        if ($similarity) { $this->similarity = (float)$similarity; }
    }

    public function get_similarity() {
        return $this->similarity;
    }

}