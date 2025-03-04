<?php

abstract class nc_condition_composite extends nc_condition {

    /** @var nc_condition[] */
    protected $children = array();

    /**
     * @param array $parameters
     */
    public function __construct($parameters = array()) {
        $children_params = (array)$parameters['conditions'];
        foreach ($children_params as $child_param) {
            $this->children[] = self::create($child_param);
        }
    }

    /**
     * @return array|nc_condition[]
     */
    public function get_children() {
        return $this->children;
    }

    /**
     *
     */
    public function has_condition_of_type($type) {
        foreach ($this->children as $child) {
            if ($child->has_condition_of_type($type)) { return true; }
        }
        return false;
    }

    /**
     * @param string $glue
     * @param string $same_type_glue
     * @return string
     */
    protected function get_children_descriptions($glue, $same_type_glue) {
        $descriptions = array();
        $previous_child_class = null;
        $previous_op = null;
        $last = -1;
        foreach ($this->children as $child) {
            $child_class = get_class($child);
            $op = $child->get('op');

            if ($child_class !== $previous_child_class || $op !== $previous_op) {
                $descriptions[] = $child->get_full_description();
                $last++;
            }
            else {
                $descriptions[$last] .= $same_type_glue . $child->get_short_description();
            }

            $previous_child_class = $child_class;
            $previous_op = $op;
        }
        return implode($glue, $descriptions);
    }

    /**
     * Короткое описание (только значение, для повторяющихся условий)
     * @return string
     */
    public function get_short_description() {
        return $this->get_full_description();
    }


    /**
     * @param nc_backup_dumper $dumper
     * @return array
     */
    protected function get_updated_parameters_on_import(nc_backup_dumper $dumper) {
        $children_parameters = array();
        foreach ($this->children as $child) {
            $children_parameters[] = $child->get_updated_raw_options_array_on_import($dumper);
        }

        return array('conditions' => $children_parameters);
    }

}