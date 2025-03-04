<?php

class nc_auth_provider {

    /** @var string $name */
    protected $name;
    /** @var nc_auth $auth */
    protected $auth;
    /** @var nc_Core $core */
    protected $core;
    /** @var array $config */
    protected $config = array();
    /** @var string $provider_id ID of the provider */
    protected $provider_id;
    /** @var string $redirect_to */
    protected $redirect_to;
    /** @var array $fields_map */
    protected $fields_map = array();

    public function __construct() {
        $this->core = nc_Core::get_object();
        $this->auth = nc_auth::get_object();
        $this->config = array(
            'base_url'  => nc_get_scheme() . '://' . $this->core->HTTP_HOST . nc_module_path('auth') . 'endpoint/',
            'providers' => array()
        );
    }

    static public function get_object($name) {
        $class_name = 'nc_auth_provider_' . $name;

        return new $class_name;
    }

    public function enabled() {
        static $ex_enabled = array();
        if (!$ex_enabled) {
            $ex_enabled = unserialize($this->core->get_settings('ex_enabled', 'auth'));
        }

        return $this->core->php_ext('json') && $this->core->php_ext('curl') && $ex_enabled[$this->name];
    }

    /**
     * @param string|null $key
     * @return mixed
     */
    public function get_app_settings($key = null) {
        static $app_settings = array();
        if (!$app_settings) {
            $app_settings = unserialize($this->core->get_settings('ex_apps', 'auth'));
        }

        if ($key) {
            return $app_settings[$this->name][$key];
        }

        return $app_settings[$this->name];
    }

    public function get_app_id() {
        return $this->get_app_settings('app_id');
    }

    public function get_app_key() {
        return $this->get_app_settings('app_key');
    }

    public function make_user($userinfo, $ex_user_id) {
        $login_field_name = nc_Core::get_object()->AUTHORIZE_BY;

        if (is_object($userinfo)) {
            $res = array();
            foreach ($userinfo as $k => $v) {
                $res[$k] = $v;
            }
            $userinfo = $res;
        }

        // соответствие полей
        $mapping = unserialize($this->core->get_settings('ex_fields', 'auth'));
        $mapping = $mapping[$this->name];
        if (!empty($mapping)) {
            foreach ($mapping as $nc_field => $field) {
                $fl[$nc_field] = $userinfo[$field];
            }
        }

        // установка email на основе переданных данных
        if ($this->provider_id !== null && !empty($userinfo['email']) && !isset($fl['Email'])) {
            $fl['Email'] = $userinfo['email'];
        }

        // принудительная установка логина
        // Внимание!!! в качестве идентификатора может быть передан URL (так делает, например, Yahoo)
        if (empty($fl[$login_field_name])) {
            $fl[$login_field_name] = $userinfo['identifier'];
        }

        // группы
        $groups = unserialize($this->core->get_settings('ex_group', 'auth'));
        $groups = $groups[$this->name];

        if (!$groups) {
            $groups = $this->core->get_settings('group', 'auth');
        }

        $add_fields['UserType'] = $this->name;
        $password = md5(mt_rand(6, 100) . time());

        if (!$this->core->NC_UNICODE) {
            $fl = $this->core->utf8->array_utf2win($fl);
        }

        $this->eval_user_code(0, $userinfo, $ex_user_id, 'ex_addaction_prep');
        $this->core->event->execute(nc_Event::BEFORE_USER_CREATED, 0);

        $user_id = $this->core->user->add($fl, $groups, $password, $add_fields);

        $this->core->db->query(
            "INSERT INTO `Auth_ExternalAuth` (User_ID, Service, ExternalUser)
             VALUES ('{$user_id}', '{$this->name}', '{$this->core->db->escape($ex_user_id)}');"
        );

        $this->eval_user_code($user_id, $userinfo, $ex_user_id, 'ex_addaction');
        $this->core->event->execute(nc_Event::AFTER_USER_CREATED, $user_id);

        return $user_id;
    }

    protected function eval_user_code($user_id = 0, $userinfo, $ex_user_id, $field) {
        global $nc_core, $db;
        $user_code_sections = unserialize($this->core->get_settings($field, 'auth'));
        $user_code = $user_code_sections[$this->name];
        if ($user_code) {
            eval($user_code . ';');
        }

        return 0;
    }

    /**
     * @param array $config
     */
    public function set_config(array $config) {
        $this->config = $config;
    }

    /**
     * @return array $config
     */
    public function get_config() {
        return $this->config;
    }

    /**
     * @param string $provider
     * @param array $config
     */
    public function set_provider($provider, array $config) {
        $this->provider_id = $provider;
        $this->config['providers'][$provider] = $config;
    }

    /**
     * @param string $redirect_to
     */
    public function set_redirect_to($redirect_to) {
        $this->redirect_to = $redirect_to;
    }

    /**
     * @return Hybrid_Auth
     */
    public function get_gatekeeper() {
        return new Hybrid_Auth($this->get_config());
    }

    /**
     * Заглушка, оставленная для совместимости со старой версией
     *
     * @param Hybrid_User_Profile $user_profile
     * @return Hybrid_User_Profile
     */
    protected function convert_user_profile(Hybrid_User_Profile $user_profile) {
        foreach ($this->fields_map as $deprecated_field_name => $mapped_field_name) {
            if (is_array($mapped_field_name)) {
                $default_field_name = $user_profile->$mapped_field_name['default'];
                $fallback_field_name = $user_profile->$mapped_field_name['fallback'];
                $user_profile->$deprecated_field_name = $default_field_name ?: $fallback_field_name;
            } else {
                $user_profile->$deprecated_field_name = $user_profile->$mapped_field_name;
            }
        }

        return $user_profile;
    }

    /**
     * @param Hybrid_User_Profile $user_profile
     * @return string|bool
     */
    protected function register(Hybrid_User_Profile $user_profile) {
        if ($user_profile = $this->convert_user_profile($user_profile)) {
            $external_user_id = $this->core->db->escape($user_profile->identifier);
            $user_id = $this->core->db->get_var(
                "SELECT User_ID
                 FROM `Auth_ExternalAuth`
                 WHERE Service = '{$this->name}'
                 AND ExternalUser = '{$external_user_id}';"
            );
            if (!$user_id) {
                $user_id = $this->make_user($user_profile, $external_user_id);
            }

            return $this->core->user->authorize_by_id($user_id, NC_AUTHTYPE_EX);
        }

        return false;
    }

    /**
     * @param array|null $params
     */
    public function process($params = null) {
        try {
            $gatekeeper = $this->get_gatekeeper();
            $should_logout = Hybrid_Auth::storage()->get("hauth_session.{$this->provider_id}.should_logout");

            if ($should_logout) {
                $gatekeeper::logoutAllProviders();
                Hybrid_Auth::storage()->set("hauth_session.{$this->provider_id}.should_logout", 0);
            }

            $adapter = $gatekeeper->authenticate($this->provider_id, $params);
            $this->register($adapter->getUserProfile());

            Hybrid_Auth::storage()->set("hauth_session.{$this->provider_id}.should_logout", 1);

            header('Location: ' . $this->redirect_to);
            exit;
        } catch (Exception $e) {
            trigger_error(__METHOD__ . '(): failed to authenticate given user. An error occurred: ' . $e->getMessage(), E_USER_WARNING);
            echo $this->get_error_message();
        }
    }

    public function get_error_message() {
        return '<div class="tpl-block-message tpl-state-error">'
            . '<div class="tpl-block-title--size_m">' . NETCAT_MODULE_AUTH_AUTHENTICATION_FAILED . '</div>'
            . '<div class="tpl-block-message-content">' . NETCAT_MODULE_AUTH_RETRY  . '</div>'
            . '</div>';
    }
}
