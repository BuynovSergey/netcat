<?php
/**
 *
 */
abstract class nc_search_query_expression {

    protected $field;
    protected $is_required;
    protected $is_excluded;

    /**
     * @param string $field_name
     * @return nc_search_query_expression
     */
    public function set_field($field_name) {
        $this->field = $field_name;
        return $this;
    }

    /**
     * @return string|null
     */
    public function get_field() {
        return $this->field;
    }

    /**
     * @param boolean $is_required
     * @return nc_search_query_expression
     */
    public function set_required($is_required) {
        $this->is_required = (bool)$is_required;
        return $this;
    }

    /**
     * @return boolean
     */
    public function is_required() {
        return $this->is_required;
    }

    /**
     * @param boolean $is_excluded
     * @return nc_search_query_expression
     */
    public function set_excluded($is_excluded) {
        $this->is_excluded = (bool)$is_excluded;
        return $this;
    }

    /**
     * @return boolean
     */
    public function is_excluded() {
        return $this->is_excluded;
    }

}