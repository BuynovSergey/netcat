<?php

class nc_landing_preset_netcat_landing_goods_3_block_cover extends nc_landing_preset_block {

    protected $component = 'netcat_page_block_cover';
    protected $default_component_template = 'image_in_background';
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
            $image1 = $this->get_image_path("image1.jpg");
            $image2 = $this->get_image_path("image2.jpg");
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
            $image = $item['BigImage'] ?: $item['Image'];
        }
        else {
            $text_before_header = "Ограниченный выпуск 21 октября 2016";
            $header = "Кроссовки будущего Nike Air Mag 2016";
            $text = "Сделайте предзаказ до 21 октября 2016 года и сэкономьте 5500 рублей!";
            $show_text = 0;
            $image = $this->get_image_path("image1.jpg");
        }

        return array(
            'background_color' => '#ffffff',
            'background_image' => array('file' => $image),
            'show_text_before_header' => 1,
            'text_before_header' => $text_before_header,
            'show_header' => 1,
            'header' => "<strong>$header</strong>",
            'show_text' => $show_text,
            'text' => $text,
            'padding_top' => 150,
            'padding_bottom' => 150,
        );
    }


    protected function get_requests_form_infoblock_settings(array $landing_data) {
        $item = $landing_data['item'];

        if ($item) {
            $form_header = "Закажите $item[name]";
        }
        else {
            $form_header = "Предзаказ кроссовок Nike Air Mag 2016";
        }

        return array(
            'default' => array(
                'EmbeddedForm_Header' => $form_header,
                'EmbeddedForm_TextAfterHeader' => "Заполните короткую форму и мы свяжемся с вами в течение дня",
                'EmbeddedForm_SubmitButton_Text' => "Заказать",
                'EmbeddedForm_SubmitButton_ShowPrice' => true,
                'EmbeddedForm_SubmitButton_BackgroundColor' => '#ff5633',

                'OpenPopupButton_Text' => "Заказать",
                'OpenPopupButton_ShowPrice' => true,
                'OpenPopupButton_BackgroundColor' => 'transparent',
            )
        );
    }

}