<?php

class nc_auth_provider_twitter extends nc_auth_provider {

    public function __construct() {
        parent::__construct();
        $this->name = 'twitter';
        $this->fields_map = array(
            'id'                => 'identifier',
            'name'              => 'firstName',
            'profile_image_url' => 'photoURL',
            'screen_name'       => 'displayName'
        );
        $this->set_provider('Twitter', array(
            'enabled' => true,
            'keys'    => array(
                'key'    => $this->get_app_id(),
                'secret' => $this->get_app_key()
            ),
            // только для приложений из их белого списка, нужны специальные разрешения
            'includeEmail' => true
        ));
    }
}