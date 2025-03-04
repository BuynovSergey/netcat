<?php

class nc_landing_preset_netcat_landing_goods_3_block_goods_common_data extends nc_landing_preset_block {

    protected $component = 'netcat_page_block_goods_common_data';
    protected $default_component_template = 0;
    protected $default_infoblock_keyword = 'goods_common_data';
    protected $default_infoblock_name = 'Свойства товара';
    protected $ignore_user_objects = true;

    protected function get_objects_properties($infoblock_id, $settings, array $landing_data) {
        $properties = array();
        $item = $landing_data['item'];

        if ($item) {
            $netshop = nc_netshop::get_instance($item['Catalogue_ID']);
            $check_stock_units = !$netshop->get_setting('IgnoreStockUnitsValue');

            foreach ($landing_data['item']['_Variants'] as $variant) {
                if ($check_stock_units && $variant['StockUnits'] == '0') {
                    continue;
                }

                $properties[] = array(
                    'Item_Component_ID' => $variant['Class_ID'],
                    'Item_ID' => $variant['Message_ID'],
                    'Name' => $variant['FullName'],
                    'VariantName' => strlen($variant['VariantName']) ? $variant['VariantName'] : $variant['FullName'],
                    'OriginalPrice' => $variant['OriginalPrice'],
                    'OriginalPriceCurrency' => $landing_data['OriginalPriceCurrency'],
                    'DiscountValue' => $variant['ItemDiscount'],
                    'DiscountInPercent' => 0,
                    'DiscountCurrency' => $landing_data['DiscountCurrency'],
                    'ItemPrice' => $variant['ItemPrice'],
                    'ItemPriceCurrency' => $landing_data['ItemPriceCurrency'],
                );
            }
        }
        else {
            $properties[] = array(
                'Name' => 'Nike Air Mag 2016',
                'VariantName' => '',
                'OriginalPrice' => '17400',
                'OriginalPriceCurrency' => 'руб.',
                'DiscountValue' => '5500',
                'DiscountInPercent' => 0,
                'DiscountCurrency' => 'руб.',
                'ItemPrice' => '11900',
                'ItemPriceCurrency' => 'руб.',
            );
        }

        return $properties;
    }

}