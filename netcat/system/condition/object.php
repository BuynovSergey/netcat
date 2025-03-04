<?php

class nc_condition_object extends nc_condition {

    /**
     * Parameters:
     *    'op'
     *    'value'  -- object id in the format "ClassID:Message_ID"
     */

    protected $op;
    protected $component_id;
    protected $object_id;

    public function __construct($parameters = array()) {
        $this->op = $parameters['op'];

        $value = explode(':', $parameters['value']);
        $this->component_id = $value[0];
        $this->object_id = $value[1];
    }

    /**
     * Короткое описание (только значение, для повторяющихся условий)
     * @return string
     */
    public function get_short_description() {
        $nc_core = nc_Core::get_object();
        $object = $nc_core->message->get_by_id($this->component_id, $this->object_id);
        $object_name = $nc_core->message->get_object_name($this->component_id, $this->object_id);
        $object_url = nc_object_url($this->component_id, $this->object_id);

        if (!$object['Sub_Class_ID']) {
            return "<em class='nc--status-error'>" . NETCAT_COND_NONEXISTENT_ITEM . '</em>';
        }

        return sprintf(
            NETCAT_COND_QUOTED_VALUE,
            $this->add_operator_description("<a target='_blank' href='$object_url'>$object_name</a>")
        );
    }

    /**
     * @param nc_backup_dumper $dumper
     * @return array
     */
    protected function get_updated_parameters_on_import(nc_backup_dumper $dumper) {
        $new_component_id = $dumper->get_dict('Class_ID', $this->component_id);
        $new_object_id = $dumper->get_dict("Message{$new_component_id}.Message_ID", $this->object_id);

        return array('op' => $this->op, 'value' => $new_component_id . ':' . $new_object_id);
    }

}