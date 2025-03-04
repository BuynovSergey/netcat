<?php

class nc_landing_preset_netcat_landing_goods_3_block_description extends nc_landing_preset_block {

    protected $component = 'netcat_page_block_text';
    protected $default_component_template = 'paragraph';
    protected $default_infoblock_keyword = 'description';
    protected $default_infoblock_name = 'Описание';

    protected $ignore_user_objects = true;
    protected $ignore_user_infoblock_settings = array('header');

    
    protected function get_objects_properties($infoblock_id, $settings, array $landing_data) {
        $item = $landing_data['item'];

        if ($item['Description']) {
            $text = $item['Description'];
        }
        else {
            $text =
                'Nike создала эти кроссовки вместе с Майклом Джей Фоксом — тем самым Марти Макфлаем из фильма, 
                который вы совершенно точно смотрели. Светящаяся подошва и вставки, которые можно заряжать — 
                не просто бутафория: Nike Air Mag 2016 созданы для занятий активными видами спорта. 
                Ну, а если вы не занимаетесь спортом, то роль крутого чувака на вечеринке и восхищённые 
                взгляды вам обеспечены. Кроссовки будут выпущены 21 октября 2016 года.';
        }

        return array(
            array(
                'Text' => $text,
            )
        );
    }


    protected function get_default_infoblock_settings($subdivision_id, $settings, array $landing_data) {
        $item = $landing_data['item'];

        if ($item) {
            $header = $item['Name'];
        }
        else {
            $header = "";
        }

        return array(
            'background_color' => '#ffffff',
            'show_header' => 0,
            'header' => $header,
            'padding_top' => 75,
            'padding_bottom' => 150,
        );
    }
    
}