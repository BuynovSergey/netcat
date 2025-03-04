<?php

class nc_condition_object_parentsub extends nc_condition {

    /**
     * Parameters:
     *   op
     *   value   -- ID of the subdivision
     */

    protected $op;
    protected $value;

    /** @var array [ sub => parent ] */
    protected $parent_cache = array();

    /**
     * @return string
     */
    public function get_full_description() {
        return ($this->op === 'ne' ? NETCAT_COND_ITEM_PARENTSUB_NE : NETCAT_COND_ITEM_PARENTSUB) . ' ' . $this->get_short_description();
    }

    /**
     * Короткое описание (только значение, для повторяющихся условий)
     * @return string
     */
    public function get_short_description() {
        try {
           $subdivision = nc_core('subdivision')->get_by_id($this->value);
           $subdivision_link = "<a href='$subdivision[HiddenURL]' target='_blank'>$subdivision[Subdivision_Name]</a>";
           return sprintf(NETCAT_COND_QUOTED_VALUE, $subdivision_link) . ' ' . NETCAT_COND_ITEM_PARENTSUB_DESCENDANTS;
        }
        catch (Exception $e) {
            return $this->get_formatted_error_description(NETCAT_COND_NONEXISTENT_SUB);
        }
    }

    /**
     * @param nc_backup_dumper $dumper
     * @return array
     */
    public function get_updated_parameters_on_import(nc_backup_dumper $dumper) {
        return array('op' => $this->op, 'value' => $dumper->get_dict('Subdivision_ID', $this->value));
    }

}