<?php

class nc_landing_preset_netcat_landing_goods_2_block_header extends nc_landing_preset_block {

    protected $component = 'netcat_page_block_header';
    protected $default_component_template = 'plain';
    protected $default_infoblock_keyword = 'header';
    protected $default_infoblock_name = 'Заголовок';


    protected function get_objects_properties($infoblock_id, $settings, array $landing_data) {
        return array(
            array(
                'Phone' => nc_array_value($landing_data, 'Phone', '8 123 000-55-55'),
                'WorkTime' => 'Ежедневно с 9:00 до 21:00',
                'Logo' => nc_array_value($landing_data, 'Logo', $this->get_image_path('logo.png')),
            )
        );
    }

    protected function get_requests_form_infoblock_settings(array $landing_data) {
        return array(
            'default' => array(
                'OpenPopupButton_Text' => "Купить",
                'OpenPopupButton_ShowPrice' => false,
            )
        );
    }

}