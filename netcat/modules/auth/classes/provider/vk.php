<?php

class nc_auth_provider_vk extends nc_auth_provider {

    public function __construct() {
        parent::__construct();
        $this->name = 'vk';
        $this->fields_map = array(
            'uid'        => 'identifier',
            'first_name' => 'firstName',
            'last_name'  => 'lastName',
            'nickname'   => array(
                'default'  => 'nickname',
                'fallback' => 'displayName'
            ),
            'photo_big'  => 'photoURL'
        );
        $this->set_provider('Vkontakte', array(
            'enabled' => true,
            'keys'    => array(
                'id'     => $this->get_app_id(),
                'secret' => $this->get_app_key()
            ),
            'scope'   => 'notify,friends,notes,email,offline'
        ));
    }

    public function is_member() {
        $app_id = $this->get_app_id();
        $session = array();
        $member = false;
        $valid_keys = array('expire', 'mid', 'secret', 'sid', 'sig');
        $app_cookie = $_COOKIE['vk_app_' . $app_id];
        if ($app_cookie) {
            $session_data = explode('&', $app_cookie, 10);
            foreach ($session_data as $pair) {
                list($key, $value) = explode('=', $pair, 2);
                if (empty($key) || empty($value) || !in_array($key, $valid_keys, true)) {
                    continue;
                }
                $session[$key] = $value;
            }
            foreach ($valid_keys as $key) {
                if (!isset($session[$key])) {
                    return $member;
                }
            }
            ksort($session);
            $sign = '';
            foreach ($session as $key => $value) {
                if ($key !== 'sig') {
                    $sign .= ($key . '=' . $value);
                }
            }
            $sign .= $this->get_app_key();
            $sign = md5($sign);
            if ($session['sig'] === $sign && $session['expire'] > time()) {
                $member = (int)$session['mid'];
            }
        }

        return $member;
    }
}