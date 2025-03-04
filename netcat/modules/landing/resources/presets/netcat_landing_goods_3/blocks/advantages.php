<?php

class nc_landing_preset_netcat_landing_goods_3_block_advantages extends nc_landing_preset_block {

    protected $component = 'netcat_page_block_text_column';
    protected $default_component_template = 'plain_one_row';
    protected $default_infoblock_keyword = 'advantages';
    protected $default_infoblock_name = 'Преимущества';

    protected $ignore_user_objects = false;
    protected $ignore_user_infoblock_settings = array('header', 'text');


    protected function get_default_infoblock_settings($subdivision_id, $settings, array $landing_data) {
        $item = $landing_data['item'];

        if ($item) {
            $header = "Еще больше уникальных возможностей с {$item['Name']}";
        }
        else {
            $header = "Назад в будущее с кроссовками Nike Air Mag 2016";
        }

        return array(
            'background_color' => '#ffffff',
            'disable_background_pattern' => 1,
            'show_header' => 1,
            'header' => $header,
            'show_text' => 0,
            'text' => '',
        );
    }

    protected function get_objects_properties($infoblock_id, $settings, array $landing_data) {
        $item = $landing_data['item'];

        /* @todo */

        return array(
            array(
                'Title' => 'Светящиеся подошвы и логотип',
                'Text' => 'Удивлённые и восторженные взгляды в подарок',
                'Icon' => $this->get_image_path('icon1.png'),
            ),
            array(
                'Title' => 'Самозавязывающиеся шнурки',
                'Text' => 'Вы ведь столько раз мечтали о таких в детстве!',
                'Icon' => $this->get_image_path('icon2.png'),
            ),
            array(
                'Title' => 'Комфорт, эргономика и дизайн',
                'Text' => 'Вам будет удобно, как и в обычных Nike. Ну, мы почти в этом уверены',
                'Icon' => $this->get_image_path('icon3.png'),
            ),
        );
    }
    
}