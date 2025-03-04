<?php

class nc_landing_preset_netcat_landing_event_1_block_cover extends nc_landing_preset_block {

    protected $component = 'netcat_page_block_cover';
    protected $default_component_template = 'image_in_background';
    protected $default_infoblock_keyword = 'cover';
    protected $default_infoblock_name = 'Обложка';


    protected function get_objects_properties($infoblock_id, $settings, array $landing_data) {
        return array(
            array(
                'Image' => $this->get_image_path('cover.jpg'),
                'SmallImage' => $this->get_image_path('photo1.jpg'),
            )
        );
    }


    protected function get_default_infoblock_settings($subdivision_id, $settings, array $landing_data) {
        return array(
            'show_text_before_header' => 1,
            'text_before_header' => 'Спортивное событие года',
            'show_header' => 1,
            'header' => 'Большой весенний веломарафон',
            'show_text' => 1,
            'text' => 'Испытай себя — участвуй в Большом весеннем веломарафоне и победи!',
            'background_image' => array('file' => $this->get_image_path('cover.jpg')),
            'padding_top' => '135',
            'padding_bottom' => '135',
        );
    }


    protected function get_requests_form_infoblock_settings(array $landing_data) {
        return array(
            'default' => array(
                'EmbeddedForm_Header' => "Заявка на участие",
                'EmbeddedForm_TextAfterHeader' => "Заполните короткую форму",
                'EmbeddedForm_SubmitButton_Text' => "Принять участие",
                'EmbeddedForm_SubmitButton_ShowPrice' => false,

                'OpenPopupButton_Text' => "Принять участие",
                'OpenPopupButton_ShowPrice' => false,
            )
        );
    }

}