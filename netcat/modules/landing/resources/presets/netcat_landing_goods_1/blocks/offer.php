<?php

class nc_landing_preset_netcat_landing_goods_1_block_offer extends nc_landing_preset_block {

    protected $component = 'netcat_page_block_countdown';
    protected $default_component_template = 'plain';
    protected $default_infoblock_keyword = 'offer';
    protected $default_infoblock_name = 'Акция';

    protected $ignore_user_objects = true;
    protected $ignore_user_infoblock_settings = array('text');


    protected function get_default_infoblock_settings($subdivision_id, $settings, array $landing_data) {
        $item = $landing_data['item'];

        if ($item) {
            $text = "Успей купить {$item['Name']}!";
        }
        else {
            $text = "Успей купить стул Red and Blue по уникальной цене!";
        }

        return array(
            'show_header' => 1,
            'header' => "Предложение ограничено",
            'show_text' => 1,
            'text' => $text,
        );
    }

    protected function get_objects_properties($infoblock_id, $settings, array $landing_data) {
        return array(
            array(
                'Deadline' => strftime("%Y-%m-%d 00:00:00", strtotime("+7 days")),
                'EndText' => 'Сожалеем, акция завершилась',
            )
        );
    }
    
}