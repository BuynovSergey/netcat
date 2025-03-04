<?php

class nc_landing_preset_netcat_landing_event_1 extends nc_landing_preset {
    protected $name = array(
        'ru' => 'Спортивное мероприятие',
        'en' => 'Sports event'
    );

    protected $description = array(
        'ru' => 'Лендинг мероприятия.',
        'en' => 'Event landing page.'
    );

    protected $blocks = array(
        'price_data',
        'cover',
        'columns',
        'table',
        'gallery',
        'text',
        'form',
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
                'StandaloneForm_Header' => 'Заявка на участие',
                'StandaloneForm_TextAfterHeader' => "Заполните короткую форму",
                'StandaloneForm_SubmitButton_Text' => "Принять участие",
                'StandaloneForm_SubmitButton_ShowPrice' => false,
            )
        );
    }

    
}
