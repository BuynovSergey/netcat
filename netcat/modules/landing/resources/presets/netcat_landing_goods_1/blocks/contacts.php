<?php

class nc_landing_preset_netcat_landing_goods_1_block_contacts extends nc_landing_preset_block {

    protected $component = 'netcat_page_block_contact';
    protected $default_component_template = 'plain';
    protected $default_infoblock_keyword = 'contacts';
    protected $default_infoblock_name = 'Контакты';


    protected function get_default_infoblock_settings($subdivision_id, $settings, array $landing_data) {
        return array(
            'show_header' => 1,
            'header' => 'Контактная информация',
            'text' => 'Есть вопросы? Свяжитесь с нами!',
            'show_phone' => 1,
            'show_email' => 1,
            'show_address' => 1,
            'show_work_time' => 1,
            'show_map' => 1,
            'phone' => nc_array_value($landing_data, 'Phone', '8 123 000-55-55'),
            'email' => nc_array_value($landing_data, 'Email', 'hello@netcat.ru'),
            'address' => nc_array_value($landing_data, 'Address', 'Москва, ул. Сущевский вал, 14, офис 401'),
            'work_time' => 'Пн–Пт: 9:00–21:00<br>Сб–Вс: 10:00–17:00',
            'map_address' => nc_array_value($landing_data, 'Address', 'Москва, ул. Сущевский вал, 14, офис 401'),
            'map_zoom' => 16,
        );
    }
    
}