<?php

abstract class nc_condition {

    static protected $CLASS_PREFIX = 'nc_condition_';
    static protected $CONST_PREFIX = 'NETCAT_COND_';

    /**
     * Creates a nc_condition_X instance, where X depends on the
     * 'type' key of the $options array
     * @param array $options
     * @return static
     */
    public static function create(array $options) {
        $class = static::$CLASS_PREFIX . $options['type'];
        if (!class_exists($class)) {
            $class = 'nc_condition_dummy';
        }
        return new $class($options);
    }

    /**
     * Возвращает значение константы {static::$CONST_PREFIX}_{$name}, а если она не определена — NETCAT_COND_{$name}
     * @param string $name
     * @return string
     */
    protected static function constant($name) {
        $name = strtoupper($name);
        if (defined(static::$CONST_PREFIX . $name)) {
            $constant_prefix = static::$CONST_PREFIX;
        } else {
            $constant_prefix = 'NETCAT_COND_';
        }
        return constant($constant_prefix . $name);
    }

    /**
     * @param array $parameters
     */
    public function __construct($parameters = array()) {
        foreach ($parameters as $k => $v) {
            if (property_exists($this, $k)) { $this->$k = $v; }
        }
    }

    /**
     * @param string $parameter
     * @return mixed
     */
    public function get($parameter) {
        if (property_exists($this, $parameter)) { return $this->$parameter; }
        return null;
    }

    /**
     * Проверяет, принадлежит ли условие (или его составные части) к указанному
     * типу $type условий.
     * Например, has_condition_of_type('order') будет TRUE для всех условий классов
     * nc_condition_order_property и т. п. и составных условий (and, or),
     * содержащих данный тип условий (но не для nc_condition_orders_item и т. п.)
     *
     * @param string $type    строка в нижнем регистре, например 'item', 'order', 'cart'
     * @return bool
     */
    public function has_condition_of_type($type) {
        $class_name_part_regexp = '/^' . static::$CLASS_PREFIX . $type . '(?:_|\b)/';
        return (bool)preg_match($class_name_part_regexp, get_class($this));
    }

    /**
     * @param $value1
     * @param string $operator
     * @param $value2
     * @param int|null $value_type   one of NC_FIELDTYPE_ values
     * @return bool
     */
    protected function compare($value1, $operator, $value2, $value_type = null) {
        if ($value_type) {
            if ($value_type == NC_FIELDTYPE_DATETIME) {
                $value1 = date("Ymd", $this->get_datetime_value($value1));
                $value2 = date("Ymd", $this->get_datetime_value($value2));
            }
            elseif ($value_type == NC_FIELDTYPE_MULTISELECT) {
                $value1 = ",$value1,";
                $value2 = ",$value2,";
            }
        }

        switch ($operator) {
            case 'eq': return $value1 == $value2;
            case 'ne': return $value1 != $value2;
            case 'gt': return $value1 >  $value2;
            case 'ge': return $value1 >= $value2;
            case 'lt': return $value1 <  $value2;
            case 'le': return $value1 <= $value2;
            // case-insensitive
            case 'contains': return nc_stripos($value1, $value2) !== false;
            case 'notcontains': return nc_stripos($value1, $value2) === false;
            case 'begins': return nc_stripos($value1, $value2) === 0;
            default: trigger_error("Unknown comparison operator '$operator'"); return false;
        }
    }

    /**
     * @param $value
     * @return int
     */
    protected function get_datetime_value($value) {
        // on 32-bit systems date range is limited to years 1901—2038
        return strtotime($value);
    }

    /**
     * @param string $value
     * @return string
     */
    protected function convert_decimal_point($value) {
        if (preg_match('/^\d+,\d+$/', $value)) {
            return str_replace(",", ".", $value);
        }
        return $value;
    }

    /**
     * Полное описание: название свойства + значение
     * @return string
     */
    public function get_full_description() {
        $condition_type = str_replace(static::$CLASS_PREFIX, "", get_class($this));
        return static::constant($condition_type) . ' ' . $this->get_short_description();
    }

    /**
     * Короткое описание (только значение, для повторяющихся условий)
     * @return string
     */
    public function get_short_description() {
        if (isset($this->value)) {
            return $this->add_operator_description($this->value);
        }
        return '?';
    }

    /**
     * @param mixed $value
     * @param string|null $op
     * @return string
     */
    protected function add_operator_description($value, $op = null) {
        if (!$op) {
            if (isset($this->op)) { $op = $this->op; }
            else { $op = "eq"; }
        }
        $string = static::constant('OP_' . $op);
        return sprintf($string, $value);
    }

    /**
     * @param nc_backup_dumper $dumper
     * @return array
     */
    public function get_updated_raw_options_array_on_import(nc_backup_dumper $dumper) {
        return array('type' => substr(get_class($this), strlen(static::$CLASS_PREFIX))) +
               $this->get_updated_parameters_on_import($dumper);
    }

    /**
     * @param nc_backup_dumper $dumper
     * @return array
     */
    protected function get_updated_parameters_on_import(nc_backup_dumper $dumper) {
        return get_object_vars($this);
    }

    /**
     * @param $string
     * @return nc_ui_html
     */
    public function get_formatted_error_description($string) {
        return nc_ui_html::get('em')->modificator('status-error')->text($string);
    }

    /**
     * @param nc_condition_visitor $visitor
     * @return mixed
     */
    public function visit(nc_condition_visitor $visitor) {
        return $visitor->accept_condition($this);
    }

    /**
     * @param string $table_name
     * @param string $field_name
     * @param $value
     * @param string $operator
     * @param int|null $value_type   one of NC_FIELDTYPE_ values
     * @return string
     */
    public function convert_to_sql($table_name, $field_name, $value, $operator, $value_type = null) {
        $db = nc_db();
        $value = $db->escape($value);
        $value = $db->escape($value);
        $field = "`$table_name`.`$field_name`";

        switch ($value_type) {
            case NC_FIELDTYPE_DATETIME:
                $field = "DATE(`$table_name`.`$field_name`)";
                $value = date('Y-m-d', $this->get_datetime_value($value));
                break;
            case NC_FIELDTYPE_MULTISELECT:
                $value = ",$value,";
                break;
            default:
                break;
        }

        switch ($operator) {
            case 'eq': return "$field =  '$value'";
            case 'ne': return "$field != '$value'";
            case 'gt': return "$field >  '$value'";
            case 'ge': return "$field >= '$value'";
            case 'lt': return "$field <  '$value'";
            case 'le': return "$field <= '$value'";
            // case-insensitive
            case 'contains': return "$field LIKE '%$value%'";
            case 'notcontains': return "$field NOT LIKE '%$value%'";
            case 'begins': return "$field LIKE '$value%'";
            case 'notbegins': return "$field NOT LIKE '$value%'";
            default: trigger_error("Unknown comparison operator '$operator'"); return false;
        }
    }
}