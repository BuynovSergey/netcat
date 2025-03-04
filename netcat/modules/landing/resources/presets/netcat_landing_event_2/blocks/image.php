<?php

class nc_landing_preset_netcat_landing_event_2_block_image extends nc_landing_preset_block {

    protected $component = 'netcat_page_block_image';
    protected $default_component_template = 'wide';
    protected $default_infoblock_keyword = 'image';
    protected $default_infoblock_name = 'Изображение';


    protected function get_objects_properties($infoblock_id, $settings, array $landing_data) {
        return array(
                array('SlideDescription' => '', 'Slide' => $this->get_image_path('photo1.jpg')),
        );
    }


    protected function get_default_infoblock_settings($subdivision_id, $settings, array $landing_data) {
        return array(
            'show_header' => 0,
            'header' => '',
            'show_text' => 0,
            'text' => '',
        );
    }

}