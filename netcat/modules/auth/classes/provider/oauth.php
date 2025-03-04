<?php

class nc_auth_provider_oauth extends nc_auth_provider {

    /**
     * @var array $scope оставлено для совместимости
     */
    protected $scope = array(
        'Vkontakte' => 'notify,friends,notes,email,offline'
    );

    public function __construct() {
        parent::__construct();
        $this->name = 'oauth';
        $this->fields_map = array(
            'uid'   => 'identifier',
            'name'  => 'firstName',
            'nick'  => array(
                'default'  => 'nickname',
                'fallback' => 'displayName'
            ),
            'photo' => 'photoURL'
        );
    }

    /**
     * @param string $provider
     */
    public function set_default_provider($provider) {
        $provider = $this->convert_provider_name($provider);

        $config = array(
            'enabled'      => true,
            'keys'         => array(
                'id'     => $this->get_provider_app_id($provider),
                'key'    => $this->get_provider_public_key($provider),
                'secret' => $this->get_provider_secret_key($provider)
            ),
            // Twitter, только для приложений из их белого списка, нужны специальные разрешения
            'includeEmail' => true
        );

        if ($this->scope[$provider]) {
            $config['scope'] = $this->scope[$provider];
        }

        $this->set_provider($provider, $config);
    }

    /**
     * @param string $provider
     * @param string|null $key
     * @return mixed
     */
    public function get_provider_settings($provider, $key = null) {
        static $oauth_providers = array();
        if (!$oauth_providers) {
            $oauth_providers = unserialize($this->core->get_settings('ex_oauth_providers', 'auth'));
        }

        if (!$oauth_providers) {
            return false;
        }

        $provider_settings = null;

        foreach ($oauth_providers as $oauth_provider) {
            if ($oauth_provider['provider'] === $provider) {
                $provider_settings = $oauth_provider;
                break;
            }
        }

        if (!$provider_settings || !is_array($provider_settings)) {
            return false;
        }

        if ($key) {
            return $provider_settings[$key];
        }

        return $provider_settings;
    }

    /**
     * @param string $provider
     * @return mixed
     */
    public function get_provider_app_id($provider) {
        return $this->get_provider_settings($provider, 'appid');
    }

    /**
     * @param string $provider
     * @return mixed
     */
    public function get_provider_public_key($provider) {
        return $this->get_provider_settings($provider, 'pubkey');
    }

    /**
     * @param string $provider
     * @return mixed
     */
    public function get_provider_secret_key($provider) {
        return $this->get_provider_settings($provider, 'seckey');
    }

    /**
     * Конвертирует старые имена провайдеров в имена провайдеров, используемых Hybrid_Auth
     * Функция обеспечивает совместимость со старыми вариантами наименования
     *
     * @param string $provider
     * @return string
     */
    public function convert_provider_name($provider) {
        switch ($provider) {
            case 'vk':
                return 'Vkontakte';
            case 'fb':
                return 'Facebook';
        }

        return $provider;
    }

    /**
     * @param string $provider
     * @param null $client_id deprecated, оставлено для совместимости
     * @param null $response_type deprecated, оставлено для совместимости
     * @return string ссылка на авторизацию через выбранного провайдера
     */
    public function make_auth_url($provider, $client_id = null, $response_type = null) {
        return nc_module_path('auth') . '?nc_oauth=' . $provider;
    }
}