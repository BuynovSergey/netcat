<?php

class nc_landing_preset_netcat_landing_goods_1_block_footer extends nc_landing_preset_block {

    protected $component = 'netcat_page_block_footer';
    protected $default_component_template = 'plain';
    protected $default_infoblock_keyword = 'footer';
    protected $default_infoblock_name = 'Подвал';


    protected function get_objects_properties($infoblock_id, $settings, array $landing_data) {
        return array(
            array(
                'Text' => '© ' . nc_array_value($landing_data, 'Copyright', 'netcat-shop.ru') . ', ' . date('Y'),
            )
        );
    }

}