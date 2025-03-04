<?php

class nc_landing_preset_netcat_landing_event_1_block_columns extends nc_landing_preset_block {

    protected $component = 'netcat_page_block_text_column';
    protected $default_component_template = 'cards_on_partial_background';
    protected $default_infoblock_keyword = 'highlights';
    protected $default_infoblock_name = 'Колонки';


    protected function get_objects_properties($infoblock_id, $settings, array $landing_data) {
        return array(
            array(
                'Title' => '5 мая, 10:00',
                'Text' => 'Национальный парк «Хвалынский»',
                'Icon' => $this->get_image_path('icon1.png'),
            ),
            array(
                'Title' => 'Участники со всей страны',
                'Text' => 'Принять участие в велофестивале может любой желающий, достигший 18 лет',
                'Icon' => $this->get_image_path('icon2.png'),
            ),
            array(
                'Title' => '1500 рублей',
                'Text' => 'В стоимость участия входит питание и медпомощь на маршруте',
                'Icon' => $this->get_image_path('icon3.png'),
            ),
        );
    }


    protected function get_default_infoblock_settings($subdivision_id, $settings, array $landing_data) {
        return array(
            'background_color' => '#fff200',
            'disable_background_pattern' => 1,
            'show_header' => 1,
            'header' => 'Приглашаем на самое захватывающее спортивное событие этой весны',
            'show_text' => 0,
            'text' => '',
        );
    }

}