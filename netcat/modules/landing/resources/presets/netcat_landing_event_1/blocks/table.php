<?php

class nc_landing_preset_netcat_landing_event_1_block_table extends nc_landing_preset_block {

    protected $component = 'netcat_page_block_table';
    protected $default_component_template = 'table';
    protected $default_infoblock_keyword = 'schedule';
    protected $default_infoblock_name = 'Программа мероприятия';


    protected function get_objects_properties($infoblock_id, $settings, array $landing_data) {
        return array(
                array('Name' => '10:00', 'Value' => 'Сбор и регистрация участников'),
                array('Name' => '11:00', 'Value' => 'Старт'),
                array('Name' => '16:00', 'Value' => 'Финиш'),
                array('Name' => '17:00', 'Value' => 'Награждение участников'),
        );
    }


    protected function get_default_infoblock_settings($subdivision_id, $settings, array $landing_data) {
        return array(
            'show_header' => 1,
            'header' => 'Программа веломарафона',
            'show_text' => 0,
            'text' => '',
            'padding_top' => '90',
            'padding_bottom' => '90',
        );
    }

}