<?php

class nc_landing_preset_netcat_landing_goods_1_block_text_and_button extends nc_landing_preset_block {

    protected $component = 'netcat_page_block_text_with_image';
    protected $default_component_template = 'image_on_right';
    protected $default_infoblock_keyword = 'buy_now';
    protected $default_infoblock_name = 'Описание';

    protected $ignore_user_objects = true;
    protected $ignore_user_infoblock_settings = false;


    protected function get_objects_properties($infoblock_id, $settings, array $landing_data) {
        $item = $landing_data['item'];

        if ($item) {
            $title = 'Преимущества ' . $item['Name'];
            $text =
                'Кратко опишите выгодное преимущество продукта. Это поможет посетителю лучше понять высокое качество товара.
                Также подберите для него качественное изображение.';
            $image = $item['BigImage'] ?: $item['Image'];
        }
        else {
            $title = 'Red and Blue от Cassina';
            $text =
                'Красно-синий стул имеет все элементы, которые отличают произведения входивших в «Стиль» художников:
                стандартная цветовая палитра, геометрические фигуры как основа композиции, сочетание ярко выраженных
                горизонтальных и вертикальных плоскостей. Свою характерную цветовую гамму стул приобрёл в 1923 году,
                когда Ритвельд под влиянием картин Пита Мондриана покрасил его в «основные цвета»:
                красный, жёлтый, синий и чёрный.';
            $image = $this->get_image_path('stoel1.jpg');
        }

        return array(
            array(
                'Title' => $title,
                'Text' => $text,
                'Image' => $image,
            )
        );
    }



    protected function get_requests_form_infoblock_settings(array $landing_data) {
        return array(
            'default' => array(
                'OpenPopupButton_Text' => "Купить",
                'OpenPopupButton_ShowPrice' => true,
            )
        );
    }

}