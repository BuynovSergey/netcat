<?php
/**
 *
 */
class nc_search_query_expression_term extends nc_search_query_expression_terminal {

    protected $boost = 1;

    public function set_boost($boost) {
        $this->boost = (float)$boost;
        return $this;
    }

    public function get_boost() {
        return $this->boost;
    }

}