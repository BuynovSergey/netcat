<?php

class nc_landing_preset_netcat_landing_goods_1_block_description extends nc_landing_preset_block {

    protected $component = 'netcat_page_block_text';
    protected $default_component_template = 'paragraph';
    protected $default_infoblock_keyword = 'description';
    protected $default_infoblock_name = 'Описание';

    protected $ignore_user_objects = true;
    protected $ignore_user_infoblock_settings = array('header');

    protected function get_objects_properties($infoblock_id, $settings, array $landing_data) {
        $item = $landing_data['item'];

        if ($item) {
            $text = $item['Description'];
        }
        else {
            $text =
                'Красно-синий стул имеет все элементы, которые отличают произведения входивших в «Стиль» художников:
                стандартная цветовая палитра, геометрические фигуры как основа композиции, сочетание ярко выраженных
                горизонтальных и вертикальных плоскостей. Свою характерную цветовую гамму стул приобрёл в 1923 году,
                когда Ритвельд под влиянием картин Пита Мондриана покрасил его в «основные цвета»:
                красный, жёлтый, синий и чёрный.';
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
            $show_header = 0;
            $header = $item['Name'];
        }
        else {
            $show_header = 1;
            $header = "Самый узнаваемый предмет в истории мебельного дизайна";
        }

        return array(
            'show_header' => $show_header,
            'header' => $header,
        );
    }
    
}