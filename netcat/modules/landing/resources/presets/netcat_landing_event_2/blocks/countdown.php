<?php

class nc_landing_preset_netcat_landing_event_2_block_countdown extends nc_landing_preset_block {

    protected $component = 'netcat_page_block_countdown';
    protected $default_component_template = 'plain';
    protected $default_infoblock_keyword = 'countdown';
    protected $default_infoblock_name = 'Обратный отсчёт';

    protected function get_default_infoblock_settings($subdivision_id, $settings, array $landing_data) {
        return array(
            'show_header' => 1,
            'header' => 'Покупайте билеты на лучшие места',
            'show_text' => 1,
            'text' => 'До фестиваля осталось',
        );
    }

    protected function get_objects_properties($infoblock_id, $settings, array $landing_data) {
        return array(
            array(
                'Deadline' => strftime("%Y-%m-%d 00:00:00", strtotime("+7 days")),
                'EndText' => 'Сожалеем, мероприятие завершилось',
            )
        );
    }
    
}