<?php

class nc_landing_preset_netcat_landing_empty extends nc_landing_preset {
    protected $name = array(
        'ru' => 'Пустая страница',
        'en' => 'Empty page'
    );

    protected $description = array(
        'ru' => 'Основа для любого лендинга.',
        'en' => 'Foundation for any landing page.'
    );

    protected $blocks = array(
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

}
