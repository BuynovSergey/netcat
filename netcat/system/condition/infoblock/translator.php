<?php

class nc_condition_infoblock_translator implements nc_condition_visitor {

    /** @var nc_condition */
    protected $conditions;

    /** @var int */
    protected $sub_class_id;

    // QUERY PARTS
    protected $select_fields = array();
    protected $joins = array();

    // Just for short
    /** @var nc_db */
    protected $db;

    /**
     * @param string|array|nc_condition $conditions
     * @param $sub_class_id
     */
    public function __construct($conditions, $sub_class_id) {
        if (is_string($conditions)) {
            $conditions = json_decode($conditions, true);
        }
        if (is_array($conditions) && count($conditions)) {
            $conditions = nc_condition::create($conditions);
        }

        if ($conditions instanceof nc_condition) {
            $this->conditions = $conditions;
        }

        $this->sub_class_id = (int)$sub_class_id;

        $this->db = nc_core('db');
    }

    /**
     * @return string
     */
    public function get_sql_condition() {
        return $this->accept_composite($this->conditions);
    }

    /**
     * @param nc_condition $condition
     * @return string
     */
    public function accept_condition(nc_condition $condition) {
        if ($condition instanceof nc_condition_composite) {
            return $this->accept_composite($condition);
        }

        $condition_type = str_replace("nc_condition_", "", get_class($condition));
        $method = "accept_{$condition_type}";
        if (method_exists($this, $method)) {
            return $this->$method($condition);
        }

        trigger_error("Condition translator cannot process condition of type " . get_class($condition), E_USER_ERROR);
    }

    public function accept_object(nc_condition $condition) {
        return $condition->convert_to_sql('a', 'Message_ID', $condition->get('object_id'), $condition->get('op'));
    }

    public function accept_object_property(nc_condition $condition) {
        return $condition->convert_to_sql('a', $condition->get('field_name'), $condition->get('value'), $condition->get('op'), $condition->get('field_type'));
    }

    public function accept_object_sub(nc_condition $condition) {
        return $condition->convert_to_sql('sub', 'Subdivision_ID', $condition->get('value'), $condition->get('op'));
    }

    public function accept_object_parentsub(nc_condition $condition) {
        try {
            $parent_sub_hidden_url = nc_Core::get_object()->subdivision->get_by_id($condition->get('value'), 'Hidden_URL');
        } catch (Exception $e) {
            $parent_sub_hidden_url = '-1';
        }

        switch ($condition->get('op')) {
            case 'ne':
                $operator = 'notbegins';
                break;
            case 'eq':
            default:
                $operator = 'begins';
                break;
        }

        return $condition->convert_to_sql('sub', 'Hidden_URL', $parent_sub_hidden_url, $operator);
    }

    /**
     * @param nc_condition_composite $condition
     * @return string
     */
    protected function accept_composite(nc_condition_composite $condition) {
        $sql_conditions = array();

        foreach ($condition->get_children() as $child) {
            $sql_conditions[] = $child->visit($this);
        }

        if (!count($sql_conditions)) {
            $sql_conditions[] = '1';
        }

        $operator = null;
        if ($condition instanceof nc_condition_and) {
            $operator = 'AND';
        } elseif ($condition instanceof nc_condition_or) {
            $operator = 'OR';
        } else {
            trigger_error('Condition translator cannot process condition of type ' . get_class($condition), E_USER_ERROR);
        }

        return '(' . implode("\n$operator ", $sql_conditions) . ')';
    }

    /**
     *
     */
    protected function escape($value) {
        if (is_numeric($value)) {
            return $value;
        }

        return "'" . $this->db->escape($value) . "'";
    }

    /**
     * @param $field
     * @param $operator
     * @param $value
     * @return string
     */
    protected function compare($field, $operator, $value) {
        switch ($operator) {
            case 'eq': return "$field = "  . $this->escape($value);
            case 'ne': return "$field != " . $this->escape($value);
            case 'gt': return "$field > "  . $this->escape($value);
            case 'ge': return "$field >= " . $this->escape($value);
            case 'lt': return "$field < "  . $this->escape($value);
            case 'le': return "$field <= " . $this->escape($value);
            // case-insensitive
            case 'contains': return "LOCATE(UPPER(" . $this->escape($value) . "), UPPER($field)) > 0";
            case 'notcontains': return "LOCATE(UPPER(" . $this->escape($value) . "), UPPER($field)) = 0";
            case 'begins': return "LOCATE(UPPER(" . $this->escape($value) . "), UPPER($field)) = 1";
            default: trigger_error("Unknown comparison operator '$operator'", E_USER_ERROR); return false;
        }
    }

}