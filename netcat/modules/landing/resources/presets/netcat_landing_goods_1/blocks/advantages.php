<?php

class nc_landing_preset_netcat_landing_goods_1_block_advantages extends nc_landing_preset_block {

    protected $component = 'netcat_page_block_text_column';
    protected $default_component_template = 'cards_on_partial_background';
    protected $default_infoblock_keyword = 'advantages';
    protected $default_infoblock_name = 'Преимущества';

    protected $ignore_user_objects = false;
    protected $ignore_user_infoblock_settings = array('header', 'text');

    
    protected function get_default_infoblock_settings($subdivision_id, $settings, array $landing_data) {
        $item = $landing_data['item'];

        if ($item) {
            $header = "Преимущества {$item['Name']}";
            $text = "{$item['Name']} — отличный выбор";
        }
        else {
            $header = "Red and Blue — это уникальный дизайн";
            $text = "Стул Red and Blue от Cassina идеально подчеркнёт индивидуальность вашего интерьера";
        }

        return array(
            'show_header' => 1,
            'header' => $header,
            'show_text' => 1,
            'text' => $text,
        );
    }

    protected function get_objects_properties($infoblock_id, $settings, array $landing_data) {
        $item = $landing_data['item'];

        /* @todo */

        return array(
            array(
                'Title' => 'Сделано с любовью',
                'Text' => 'Всё наше внимание и забота для вас',
                'Icon' => $this->get_image_path('icon_star.png'),
            ),
            array(
                'Title' => 'Уникальный дизайн',
                'Text' => 'Неотразимое исполнение для ценителей качества',
                'Icon' => $this->get_image_path('icon_cup.png'),
            ),
            array(
                'Title' => 'Лучший выбор',
                'Text' => 'Вы нашли то, что так долго искали',
                'Icon' => $this->get_image_path('icon_heart.png'),
            ),
        );
    }
    
}