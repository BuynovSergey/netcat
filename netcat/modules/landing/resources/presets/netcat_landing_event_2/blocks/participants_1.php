<?php

class nc_landing_preset_netcat_landing_event_2_block_participants_1 extends nc_landing_preset_block {

    protected $component = 'netcat_page_block_opinion';
    protected $default_component_template = 'slider_cards_on_background';
    protected $default_infoblock_keyword = 'participants1';
    protected $default_infoblock_name = 'Участники 1';


    protected function get_objects_properties($infoblock_id, $settings, array $landing_data) {
        return array(
            array(
                'Text' => 'Музыкант, широко известный в узких кругах, заставляющий терять голову под очарование изысканного звучания саксофона.',
                'AuthorName' => 'Энтони Уайт',
                'AuthorPosition' => 'саксофонист афро-американского джаза',
                'AuthorImage' => $this->get_image_path('avatar2.jpg'),
            ),
            array(
                'Text' => 'Певица, актриса Московского театра, песни в исполнении которой играют переливами свежести и новизны.',
                'AuthorName' => 'Виктория Абрамова',
                'AuthorPosition' => 'легенда вокального джаза',
                'AuthorImage' => $this->get_image_path('avatar3.jpg'),
            ),
            array(
                'Text' => 'Талантливый гитарист, аранжировщик, композитор, руководитель гитарного блюзового трио не устает удивлять своим новаторством и оригинальностью.',
                'AuthorName' => 'Алексей Тэктен',
                'AuthorPosition' => 'блюз-гитарист из Санкт-Петербурга',
                'AuthorImage' => $this->get_image_path('avatar1.jpg'),
            ),
        );
    }


    protected function get_default_infoblock_settings($subdivision_id, $settings, array $landing_data) {
        return array(
            'show_header' => 1,
            'header' => 'Участники',
            'show_text' => 1,
            'text' => 'Для вас выступят звёзды мировой сцены',
        );
    }
    
}