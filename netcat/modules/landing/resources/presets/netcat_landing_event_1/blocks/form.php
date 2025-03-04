<?php

class nc_landing_preset_netcat_landing_event_1_block_form extends nc_landing_preset_block {

    protected $component = 'netcat_page_block_form';
    protected $default_component_template = 'plain';
    protected $default_infoblock_keyword = 'form';
    protected $default_infoblock_name = 'Форма';


    protected function get_default_infoblock_settings($subdivision_id, $settings, array $landing_data) {
        return array(
            'background_color' => '#f0fcff',
            'padding_top' => '45',
            'padding_bottom' => '105',
        );
    }


    protected function get_requests_form_infoblock_settings(array $landing_data) {
        return array(
            'default' => array(
                'EmbeddedForm_SubmitButton_BackgroundColor' => '#fff200',
                'EmbeddedForm_SubmitButton_Text' => "Принять участие",
                'EmbeddedForm_SubmitButton_ShowPrice' => false,
            )
        );
    }

}