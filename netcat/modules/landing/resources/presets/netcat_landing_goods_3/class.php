<?php

class nc_landing_preset_netcat_landing_goods_3 extends nc_landing_preset_goods {
    protected $name = array(
        'ru' => 'Товарная промо-страница 3',
        'en' => 'Goods promo page 3'
    );

    protected $description = array(
        'ru' => 'Стандартный лендинг, который подходит для любого товара. Яркий, лёгкий, красочный и нацеленный на продажу. Основа для новых шаблонов.',
        'en' => 'Standard landing, suitable for any item. Bright, light, colorful and focused on selling. Base for new landing page templates.'
    );

    protected $blocks = array(
        'goods_common_data',
        'cover',
        'offer',
        'description',
        'image',
        'advantages',
        'divider',
        'properties',
        'opinions',
        'form',
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
            $form_header = "Предзаказ кроссовок Nike Air Mag 2016";
        }
        return array(
            'default' => array(
                'Subdivision_VisibleFields' => array('Name', 'Email', 'Item_VariantName', 'Comment', 'Quantity'),
                'StandaloneForm_Header' => $form_header,
                'StandaloneForm_TextAfterHeader' => "Заполните короткую форму и мы свяжемся с вами в течение дня",
                'StandaloneForm_SubmitButton_Text' => "Заказать",
                'StandaloneForm_SubmitButton_ShowPrice' => false,
            )
        );
    }

}
