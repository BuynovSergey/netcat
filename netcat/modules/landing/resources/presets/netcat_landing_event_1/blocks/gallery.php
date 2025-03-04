<?php

class nc_landing_preset_netcat_landing_event_1_block_gallery extends nc_landing_preset_block {

    protected $component = 'netcat_page_block_gallery';
    protected $default_component_template = 'slider';
    protected $default_infoblock_keyword = 'gallery';
    protected $default_infoblock_name = 'Галерея';


    protected function get_objects_properties($infoblock_id, $settings, array $landing_data) {
        return array(
                array('SlideDescription' => '', 'Slide' => $this->get_image_path('photo1.jpg')),
                array('SlideDescription' => '', 'Slide' => $this->get_image_path('photo2.jpg')),
                array('SlideDescription' => '', 'Slide' => $this->get_image_path('photo3.jpg')),
        );
    }


    protected function get_default_infoblock_settings($subdivision_id, $settings, array $landing_data) {
        return array(
            'show_header' => 0,
            'header' => '',
            'show_text' => 0,
            'text' => '',
            'padding_top' => '60',
            'padding_bottom' => '45',
        );
    }

}