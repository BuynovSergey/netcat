<?php

class nc_landing_preset_netcat_landing_goods_3_block_image extends nc_landing_preset_block {

    protected $component = 'netcat_page_block_image';
    protected $default_component_template = 'wide';
    protected $default_infoblock_keyword = 'image';
    protected $default_infoblock_name = 'Изображение';
    protected $ignore_user_objects = true;

    
    protected function get_objects_properties($infoblock_id, $settings, array $landing_data) {
        $item = $landing_data['item'];

        if ($item) {
            $image = $item['BigImage'] ?: $item['Image'];
        }
        else {
            $image = $this->get_image_path('image2.jpg');
        }

        return array(
            array('SlideDescription' => '', 'Slide' => $image),
        );
    }
    

    protected function get_default_infoblock_settings($subdivision_id, $settings, array $landing_data) {
        $item = $landing_data['item'];

        if ($item) {
            $header = $item['Name'];
        }
        else {
            $header = "NetPhone";
        }

        return array(
            'show_header' => 0,
            'header' => $header,
            'show_text' => 0,
            'text' => '',
        );
    }
    
}