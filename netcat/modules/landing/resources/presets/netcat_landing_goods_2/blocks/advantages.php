<?php

class nc_landing_preset_netcat_landing_goods_2_block_advantages extends nc_landing_preset_block {

    protected $component = 'netcat_page_block_text_column';
    protected $default_component_template = 'cards_on_partial_background';
    protected $default_infoblock_keyword = 'advantages';
    protected $default_infoblock_name = 'Преимущества';

    protected $ignore_user_objects = false;
    protected $ignore_user_infoblock_settings = array('header', 'text');


    protected function get_default_infoblock_settings($subdivision_id, $settings, array $landing_data) {
        $item = $landing_data['item'];

        if ($item) {
            $text = "Еще больше уникальных возможностей с {$item['Name']}";
        }
        else {
            $text = "Еще больше уникальных возможностей с NetPhone";
        }

        return array(
            'background_color' => '#2872bf',
            'disable_background_pattern' => 1,
            'show_header' => 1,
            'header' => "Особенности новинки",
            'show_text' => 1,
            'text' => $text,
        );
    }

    protected function get_objects_properties($infoblock_id, $settings, array $landing_data) {
        $item = $landing_data['item'];

        /* @todo */

        return array(
            array(
                'Title' => 'Стильный дизайн',
                'Text' => 'Тонкий, лёгкий корпус из стекла и металла&nbsp;– воплощение безупречного стиля. Оцените, как роскошно NetPhone смотрится и как удобно лежит в руке.',
                'Icon' => $this->get_image_path('icon_heart.png'),
            ),
            array(
                'Title' => 'Отличные фотографии',
                'Text' => '13-мегапиксельная камера делает яркие, чёткие снимки с высокой детализацией даже при слабом освещении.',
                'Icon' => $this->get_image_path('icon_cup.png'),
            ),
            array(
                'Title' => 'Высокая производительность',
                'Text' => 'Если нет возможности подзарядки, то поможет функция максимального энергосбережения и топовый режим батареи.',
                'Icon' => $this->get_image_path('icon_star.png'),
            ),
        );
    }
    
}