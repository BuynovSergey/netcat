<?php

class nc_landing_preset_netcat_landing_goods_3_block_divider extends nc_landing_preset_block {

    protected $component = 'netcat_page_block_divider';
    protected $default_component_template = 'line';
    protected $default_infoblock_keyword = 'divider';
    protected $default_infoblock_name = 'Линия-разделитель';

    protected $ignore_user_objects = true;
    protected $ignore_user_infoblock_settings = array();


    protected function get_default_infoblock_settings($subdivision_id, $settings, array $landing_data) {
        return array();
    }

    protected function get_objects_properties($infoblock_id, $settings, array $landing_data) {
        return array();
    }
    
}
