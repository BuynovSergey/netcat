<?php

class nc_landing_preset_netcat_landing_goods_2_block_cover extends nc_landing_preset_block {

    protected $component = 'netcat_page_block_cover';
    protected $default_component_template = 'image_on_right';
    protected $default_infoblock_keyword = 'cover';
    protected $default_infoblock_name = 'Обложка';

    protected $ignore_user_objects = true;
    protected $ignore_user_infoblock_settings = array('text_before_header', 'header', 'text');
    protected $ignore_requests_form_infoblock_settings = array('EmbeddedForm_Header');


    protected function get_objects_properties($infoblock_id, $settings, array $landing_data) {
        $item = $landing_data['item'];

        if ($item) {
            $image1 = $item['BigImage'] ?: $item['Image'];
            $image2 = isset($item['Slider']->records) && count($item['Slider']->records) > 1
                ? $item['Slider']->records[1]['Path']
                : $item['Image'];
        }
        else {
            $image1 = $this->get_image_path("phone1.png");
            $image2 = $this->get_image_path("phone2.png");
        }

        return array(
            array(
                'Image' => $image1,
                'SmallImage' => $image2,
            )
        );
    }


    protected function get_default_infoblock_settings($subdivision_id, $settings, array $landing_data) {
        $item = $landing_data['item'];
        if ($item) {
            $text_before_header = $item['Type'];
            $header = $item['Name'];
            $text = $item['Description'];
            $show_text = 0;
        }
        else {
            $text_before_header = "Скоро в продаже";
            $header = "Успейте купить NetPhone";
            $text = "Суперновый NetPhone — выберите свой смартфон!";
            $show_text = 1;
        }

        return array(
            'background_color' => '#2872bf',
            'show_text_before_header' => 1,
            'text_before_header' => $text_before_header,
            'show_header' => 1,
            'header' => $header,
            'show_text' => $show_text,
            'text' => $text,
        );
    }


    protected function get_requests_form_infoblock_settings(array $landing_data) {
        $item = $landing_data['item'];

        if ($item) {
            $item_name = $item['Name'];
        }
        else {
            $item_name = "NetPhone";
        }

        return array(
            'default' => array(
                'EmbeddedForm_Header' => "Закажите $item_name",
                'EmbeddedForm_TextAfterHeader' => "Заполните короткую форму",
                'EmbeddedForm_SubmitButton_Text' => "Купить",
                'EmbeddedForm_SubmitButton_ShowPrice' => false,
                'EmbeddedForm_SubmitButton_BackgroundColor' => '#ff5633',

                'OpenPopupButton_Text' => "Купить",
                'OpenPopupButton_ShowPrice' => false,
                'OpenPopupButton_BackgroundColor' => '#ff5633',
            )
        );
    }

}