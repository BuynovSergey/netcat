<?php

class nc_auth_provider_fb extends nc_auth_provider {

    public function __construct() {
        parent::__construct();
        $this->name = 'fb';
        $this->fields_map = array(
            'id'      => 'identifier',
            'name'    => 'firstName',
            'picture' => 'photoURL',
        );
        $this->set_provider('Facebook', array(
            'enabled' => true,
            'keys'    => array(
                'id'     => $this->get_app_id(),
                'secret' => $this->get_app_key()
            ),
            'scope'   => array('email', 'public_profile')
        ));
    }
}