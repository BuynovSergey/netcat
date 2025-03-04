<?php

class nc_landing_preset_netcat_landing_event_2_block_participants_2 extends nc_landing_preset_block {

    protected $component = 'netcat_page_block_opinion';
    protected $default_component_template = 'slider_one_card';
    protected $default_infoblock_keyword = 'participants2';
    protected $default_infoblock_name = 'Участники 2';


    protected function get_objects_properties($infoblock_id, $settings, array $landing_data) {
        return array(
            array(
                'Text' => 'Талантливый гитарист, аранжировщик, композитор, руководитель гитарного блюзового трио не устает удивлять своим новаторством и оригинальностью.',
                'AuthorName' => 'Алексей Тэктен',
                'AuthorPosition' => 'блюз-гитарист из Санкт-Петербурга',
                'AuthorImage' => $this->get_image_path('avatar1.jpg'),
            ),
        );
    }


    protected function get_default_infoblock_settings($subdivision_id, $settings, array $landing_data) {
        return array(
            'show_header' => 0,
            'header' => '',
            'show_text' => 0,
            'text' => '',
            'disable_background_pattern' => 1,
        );
    }
    
}