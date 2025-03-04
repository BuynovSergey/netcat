<?php

class nc_landing_preset_netcat_landing_event_2_block_gallery_2 extends nc_landing_preset_block {

    protected $component = 'netcat_page_block_gallery';
    protected $default_component_template = 'grid';
    protected $default_infoblock_keyword = 'gallery2';
    protected $default_infoblock_name = 'Галерея 2';


    protected function get_objects_properties($infoblock_id, $settings, array $landing_data) {
        return array(
                array('SlideDescription' => '', 'Slide' => $this->get_image_path('photo1.jpg')),
                array('SlideDescription' => '', 'Slide' => $this->get_image_path('photo4.jpg')),
                array('SlideDescription' => '', 'Slide' => $this->get_image_path('photo5.jpg')),
                array('SlideDescription' => '', 'Slide' => $this->get_image_path('photo6.jpg')),
                array('SlideDescription' => '', 'Slide' => $this->get_image_path('photo3.jpg')),
                array('SlideDescription' => '', 'Slide' => $this->get_image_path('photo2.jpg')),
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