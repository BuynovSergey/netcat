<?php

class nc_landing_preset_netcat_landing_event_2_block_cover extends nc_landing_preset_block {

    protected $component = 'netcat_page_block_cover';
    protected $default_component_template = 'image_in_center';
    protected $default_infoblock_keyword = 'cover';
    protected $default_infoblock_name = 'Обложка';


    protected function get_objects_properties($infoblock_id, $settings, array $landing_data) {
        return array(
            array(
                'Image' => $this->get_image_path('photo1.jpg'),
                'SmallImage' => $this->get_image_path('photo5.jpg'),
            )
        );
    }


    protected function get_default_infoblock_settings($subdivision_id, $settings, array $landing_data) {
        return array(
            'show_text_before_header' => 1,
            'text_before_header' => 'Главное событие весны',
            'show_header' => 1,
            'header' => '15 мая Netcat Music Festival',
            'show_text' => 1,
            'text' =>
                'Первый международный фестиваль звёзд джаза, фанка, мировой музыки, лаунжа и джаз-рока.
                 Невероятно широкая и разнообразная палитра выразительных средств от самых известных музыкантов 
                 мировой сцены, только один день, только на Netсat Music Festival',
            'background_image' => array('file' => $this->get_image_path('photo5.jpg')),
            'show_price' => 0,
        );
    }


    protected function get_requests_form_infoblock_settings(array $landing_data) {
        return array(
            'default' => array(
                'EmbeddedForm_Header' => 'Успей приобрести билет',
                'EmbeddedForm_TextAfterHeader' => '',
                'EmbeddedForm_SubmitButton_Text' => 'Записаться',
                'EmbeddedForm_SubmitButton_ShowPrice' => false,

                'OpenPopupButton_Text' => 'Записаться',
                'OpenPopupButton_ShowPrice' => false,
            )
        );
    }

}