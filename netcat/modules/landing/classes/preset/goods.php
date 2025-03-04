<?php

/**
 * Общий класс для пресетов товарных лендингов
 */

abstract class nc_landing_preset_goods extends nc_landing_preset {
    protected $ignore_requests_form_subdivision_settings = array('StandaloneForm_Header');


    /**
     * @param $component_id
     * @return bool
     */
    public function can_be_used_for_component($component_id) {
        return nc_module_check_by_keyword('netshop') &&
               in_array($component_id, nc_netshop::get_instance()->get_goods_components_ids());
    }

    /**
     * @param array $landing_data
     * @return array
     */
    protected function get_subdivision_properties(array $landing_data) {
        $item = $landing_data['item'];
        if ($item) {
            return array(
                'Subdivision_Name' => $item['FullName'],
                'EnglishName' => $item['Keyword'],
                'Description' => $item['ncDescription'],
                'Keywords' => $item['ncKeywords'],
                'ncSMO_Title' => $item['ncSMO_Title'],
                'ncSMO_Description' => $item['ncSMO_Description'],
                'ncSMO_Image' => $item['ncSMO_Image'],
            );
        }
        else {
            return array(
                'Subdivision_Name' => 'Промо-страница',
            );
        }
    }

    /**
     * Возвращает валюту цены
     * @param nc_netshop_item $item
     * @param $price_field
     * @return string
     */
    protected function get_currency_string(nc_netshop_item $item, $price_field) {
        $netshop = nc_netshop::get_instance($item['Catalogue_ID']);

        $currency_field = $netshop->get_currency_column($price_field);
        if ($currency_field == $price_field) {
            $currency = null; // основная валюта магазина (например, для ItemPrice, OriginalPrice)
        }
        else {
            $currency = $item[$netshop->get_currency_column($price_field)];
        }

        $formatted_price_without_currency = $netshop->format_price($item[$price_field], $currency, true, true);
        $formatted_price_with_currency =    $netshop->format_price($item[$price_field], $currency, true, false);

        $currency_string = str_replace($formatted_price_without_currency, '', $formatted_price_with_currency);
        $currency_string = trim(strip_tags($currency_string));

        return $currency_string;
    }

    /**
     * Возвращает свойства, специфичные для данного пресета
     * @param array $params
     * @return array
     * @throws nc_landing_preset_exception
     */
    protected function get_landing_data(array $params = array()) {
        try {
            $domain = nc_core::get_object()->catalogue->get_by_id($params['site_id'], 'Domain');
        }
        catch (Exception $e) {
            $domain = null;
        }

        $landing_data = array(
            'item' => null,
            'Copyright' => $domain,
            /** @TODO **/
            'Phone' => null,
            'Logo' => null,
            'Email' => null,
            'Address' => null,
        );

        if (nc_array_value($params, 'component_id') && nc_array_value($params, 'object_id')) {
            $item = nc_netshop_item::by_id($params['component_id'], $params['object_id']);
            $landing_data['item'] = $item;
            $landing_data['OriginalPriceCurrency'] = $this->get_currency_string($item, 'OriginalPrice');
            $landing_data['DiscountCurrency'] = $this->get_currency_string($item, 'ItemDiscount');
            $landing_data['ItemPriceCurrency'] = $this->get_currency_string($item, 'ItemPrice');
        }

        return $landing_data;
    }
    
}