<?php

class nc_landing_preset_netcat_landing_goods_2_block_description extends nc_landing_preset_block {

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
                'Дизайн новинки NetPhone на высоте: сочетание стекла и металла. 
                Большой и яркий экран 5,5 дюймов и разрешение Full HD. 
                Экраны защищены прочным стеклом с закруглёнными краями и небольшими рамками, 
                обеспечивают прекрасную яркость и контрастность в различных условиях освещения. 
                Высокая читаемость на солнце, комфортная яркость в темноте, идеальный 
                чёрный цвет и глубокое изображение.';
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
            $header = "NetPhone";
        }

        return array(
            'background_color' => '#2872bf',
            'show_header' => 0,
            'header' => $header,
        );
    }
    
}