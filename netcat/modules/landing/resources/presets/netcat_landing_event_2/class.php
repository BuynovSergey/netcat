<?php

class nc_landing_preset_netcat_landing_event_2 extends nc_landing_preset {
    protected $name = array(
        'ru' => 'Мероприятие',
        'en' => 'Event'
    );

    protected $description = array(
        'ru' => 'Лендинг мероприятия.',
        'en' => 'Event landing page.'
    );

    protected $blocks = array(
        'price_data',
        'cover',
        'countdown',
        'image',
        'gallery_1',
        'gallery_2',
        'text_1',
        'text_2',
        'text_3',
        'participants_1',
        'participants_2',
    );

    /**
     * @param array $landing_data
     * @return array
     */
    protected function get_subdivision_properties(array $landing_data) {
        return array(
            'Subdivision_Name' => 'Промо-страница',
        );
    }


    /**
     * @param array $landing_data
     * @return array
     */
    protected function get_requests_form_subdivision_settings(array $landing_data) {
        return array(
            'default' => array(
                'Subdivision_VisibleFields' => array('Name', 'Email'),
                'StandaloneForm_Header' => 'Успей приобрести билет',
                'StandaloneForm_TextAfterHeader' => '',
                'StandaloneForm_SubmitButton_Text' => 'Записаться',
                'StandaloneForm_SubmitButton_ShowPrice' => false,
            )
        );
    }

    
}
