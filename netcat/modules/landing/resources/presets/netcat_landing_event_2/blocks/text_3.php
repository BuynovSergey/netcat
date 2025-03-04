<?php

class nc_landing_preset_netcat_landing_event_2_block_text_3 extends nc_landing_preset_block {

    protected $component = 'netcat_page_block_text_with_image';
    protected $default_component_template = 'image_on_left';
    protected $default_infoblock_keyword = 'text3';
    protected $default_infoblock_name = 'Текст 3';


    protected function get_objects_properties($infoblock_id, $settings, array $landing_data) {
        return array(
            array(
                'Title' => 'Главное событие весны',
                'Text' =>
                    'На Netcat Music Festival выступят хэдлайнеры фестиваля — легенда 
                    вокального джаза Лео Рамаши, обладатели «Лучший оркестр Европы» Small Jazz, 
                    крупнейший саксофонист афро-американского джаза Энтони Уайт, лидер итальянского 
                    джаз-фламенко Эрнесто Тилисси, Санкт-Петербургское джазовое трио Виктора Абрамова, 
                    звезда кубинского поп-латино Фидель, легенда джаза СССР контрабасист Эстебан 
                    и другие исполнители.',
                'Image' => $this->get_image_path('photo3.jpg'),
            )
        );
    }



    protected function get_requests_form_infoblock_settings(array $landing_data) {
        return array(
            'default' => array(
                'OpenPopupButton_Text' => "Записаться",
                'OpenPopupButton_ShowPrice' => false,
            )
        );
    }

}