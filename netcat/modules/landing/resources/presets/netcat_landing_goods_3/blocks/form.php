<?php

class nc_landing_preset_netcat_landing_goods_3_block_form extends nc_landing_preset_block {

    protected $component = 'netcat_page_block_cover';
    protected $default_component_template = 'image_on_left';
    protected $default_infoblock_keyword = 'form';
    protected $default_infoblock_name = 'Обложка с формой';

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
            $image1 = $this->get_image_path("image4.png");
            $image2 = $this->get_image_path("image3.png");
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
            $header = "Закажите {$item['Name']} сегодня!";

            if ($item['ItemDiscount']) {
                $text = "И сэкономьте {$item['ItemDiscount']} {$landing_data['DiscountCurrency']}!";
                $show_text = 1;
            }
            else {
                $text = '';
                $show_text = 0;
            }
        }
        else {
            $header = "Сделайте предзаказ Nike Air Mag 2016";
            $text = "И сэкономьте 5500 рублей сейчас!";
            $show_text = 1;
        }

        return array(
            'show_text_before_header' => 0,
            'text_before_header' => '',
            'show_header' => 1,
            'header' => $header,
            'show_text' => $show_text,
            'text' => $text,
            'background_color' => '#774f8f',
            'background_pattern' => 0,
            'padding_top' => 75,
            'padding_bottom' => 90,
        );
    }


    protected function get_requests_form_infoblock_settings(array $landing_data) {
        $item = $landing_data['item'];

        if ($item) {
            $form_header = "Закажите {$item['Name']}";
        }
        else {
            $form_header = "Предзаказ кроссовок Nike Air Mag 2016";
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
            )
        );
    }

}