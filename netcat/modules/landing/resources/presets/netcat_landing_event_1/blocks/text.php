<?php

class nc_landing_preset_netcat_landing_event_1_block_text extends nc_landing_preset_block {

    protected $component = 'netcat_page_block_title';
    protected $default_component_template = 'title';
    protected $default_infoblock_keyword = 'description';
    protected $default_infoblock_name = 'Текст';


    protected function get_objects_properties($infoblock_id, $settings, array $landing_data) {
        return array(
                array('Text' => 'Стоимость участия — 1500 руб.'),
        );
    }


    protected function get_default_infoblock_settings($subdivision_id, $settings, array $landing_data) {
        return array(
            'background_color' => '#f0fcff',
            'show_header' => 1,
            'header' => 'Участвуйте в Большом весеннем веломарафоне',
            'padding_top' => '75',
            'padding_bottom' => '15',
        );
    }

}