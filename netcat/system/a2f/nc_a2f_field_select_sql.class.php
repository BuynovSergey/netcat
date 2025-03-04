<?php

class nc_a2f_field_select_sql extends nc_a2f_field_select {

    protected $sqlquery;

    public function get_extend_parameters() {
        return array('sqlquery' => array('type' => 'string', 'caption' => NETCAT_CUSTOM_EX_QUERY));
    }

    public function render_value_field($html = true) {
        $nc_core = nc_Core::get_object();

        // просто узнаем элементы списка
        $res = $nc_core->db->get_results($this->sqlquery, ARRAY_A);

        if ($nc_core->db->is_error) {
            return NETCAT_CUSTOM_ONCE_ERROR_QUERY;
        }

        if ($res) {
            foreach ($res as $v) {
                $this->values[$v['id']] = $v['name'];
            }
        }

        // сама прорисовка реализована в родительском классе
        return parent::render_value_field($html);
    }

}

