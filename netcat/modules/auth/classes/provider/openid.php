<?php

class nc_auth_provider_openid extends nc_auth_provider {

    public function __construct() {
        parent::__construct();
        $this->name = 'openid';
        $this->fields_map = array(
            'fullname' => 'displayName',
            'nickname' => array(
                'default'  => 'nickname',
                'fallback' => 'displayName'
            )
        );
        $this->set_provider('OpenID', array(
            'enabled' => true
        ));
    }
}