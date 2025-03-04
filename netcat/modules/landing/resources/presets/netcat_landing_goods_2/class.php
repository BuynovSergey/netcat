<?php

class nc_landing_preset_netcat_landing_goods_2 extends nc_landing_preset_goods {
    protected $name = array(
        'ru' => 'Товарная промо-страница 2',
        'en' => 'Goods promo page 2'
    );

    protected $description = array(
        'ru' => 'Стандартный лендинг, который подходит для любого товара. Яркий, лёгкий, красочный и нацеленный на продажу. Основа для новых шаблонов.',
        'en' => 'Standard landing, suitable for any item. Bright, light, colorful and focused on selling. Base for new landing page templates.'
    );

    protected $blocks = array(
        'goods_common_data',
        'header',
        'cover',
        'offer',
        'gallery',
        'advantages',
        'description',
        'properties',
        'contacts',
    );

    protected $ignore_requests_form_subdivision_settings = array('StandaloneForm_Header');


    /**
     * @param array $landing_data
     * @return array
     */
    protected function get_requests_form_subdivision_settings(array $landing_data) {
        $item = $landing_data['item'];
        if ($item) {
            $form_header = "Закажите {$item['Name']}";
        }
        else {
            $form_header = "Закажите NetPhone";
        }
        return array(
            'default' => array(
                'Subdivision_VisibleFields' => array('Name', 'Phone', 'Item_VariantName', 'Quantity'),
                'StandaloneForm_Header' => $form_header,
                'StandaloneForm_TextAfterHeader' => "Заполните короткую форму",
                'StandaloneForm_SubmitButton_Text' => "Купить",
                'StandaloneForm_SubmitButton_ShowPrice' => false,
            )
        );
    }

}
