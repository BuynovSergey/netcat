<?php

class nc_landing_preset_netcat_landing_event_2_block_price_data extends nc_landing_preset_block {

    protected $component = 'netcat_page_block_goods_common_data';
    protected $default_component_template = 0;
    protected $default_infoblock_keyword = 'goods_common_data';
    protected $default_infoblock_name = 'Цена';
    protected $ignore_user_objects = true;

    protected function get_objects_properties($infoblock_id, $settings, array $landing_data) {
        $properties[] = array(
            'Name' => 'Стоимость',
            'VariantName' => '',
            'OriginalPrice' => '1000',
            'OriginalPriceCurrency' => 'рублей',
            'DiscountValue' => '0',
            'DiscountInPercent' => 1,
            'DiscountCurrency' => 'рублей',
            'ItemPrice' => '1000',
            'ItemPriceCurrency' => 'рублей',
        );

        return $properties;
    }

}