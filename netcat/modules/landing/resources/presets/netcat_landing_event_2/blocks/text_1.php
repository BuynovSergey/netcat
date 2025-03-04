<?php

class nc_landing_preset_netcat_landing_event_2_block_text_1 extends nc_landing_preset_block {

    protected $component = 'netcat_page_block_text';
    protected $default_component_template = 'paragraph';
    protected $default_infoblock_keyword = 'text1';
    protected $default_infoblock_name = 'Текст 1';


    protected function get_objects_properties($infoblock_id, $settings, array $landing_data) {
        return array(
                array('Text' =>
                    'На Netcat Music Festival выступят хэдлайнеры фестиваля — легенда 
                    вокального джаза Лео Рамаши, обладатели «Лучший оркестр Европы» Small Jazz, 
                    крупнейший саксофонист афро-американского джаза Энтони Уайт, лидер итальянского 
                    джаз-фламенко Эрнесто Тилисси, Санкт-Петербургское джазовое трио Виктора Абрамова, 
                    звезда кубинского поп-латино Фидель, легенда джаза СССР контрабасист Эстебан 
                    и другие исполнители.'
                ),
        );
    }


    protected function get_default_infoblock_settings($subdivision_id, $settings, array $landing_data) {
        return array(
            'show_header' => 0,
            'header' => '',
        );
    }

}