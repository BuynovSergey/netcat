<?php

abstract class nc_messaging_sms_api implements nc_messaging_api {

    /**
     * @var string|null
     */
    protected $api_key;

    /**
     * @var string|null
     */
    protected $login;

    /**
     * @var string|null
     */
    protected $password;

    /**
     * @var string
     */
    protected $sender_id;

    /**
     * @var nc_http|null
     */
    protected $http;

    /**
     * @var bool
     */
    protected $is_api_token_auth_provided = false;

    /**
     * @var bool
     */
    protected $auth_method_provided = false;

    /**
     * @param nc_http|null $http
     */
    public function __construct(nc_http $http = null) {
        if ($http == null) {
            $http = $this->configure_http();
        }

        $this->http = $http;
    }

    /**
     * @param string $login
     * @param string $password
     *
     * @return void
     */
    public final function authorize_by_basic_auth_method($login, $password) {
        if (strlen($login) && strlen($password)) {
            $this->is_api_token_auth_provided = false;
            $this->login = $login;
            $this->password = $password;
            $this->api_key = null;
            $this->auth_method_provided = true;
        }
    }

    /**
     * @param string $api_key
     *
     * @return void
     */
    public final function authorize_by_api_token_auth_method($api_key) {
        if (strlen($api_key)) {
            $this->is_api_token_auth_provided = true;
            $this->api_key = $api_key;
            $this->login = null;
            $this->password = null;
            $this->auth_method_provided = true;
        }
    }

    /**
     * @inheritDoc
     */
    public function provide_authorization(array $credentials) {
        if ($this->api_key) {
            $this->authorize_by_api_token_auth_method($this->api_key);
        }
        else {
            $this->authorize_by_basic_auth_method($this->login, $this->password);
        }
    }

    /**
     * @return string
     */
    public final function get_sender_id() {
        return $this->sender_id;
    }

    /**
     * @param string $sender_id
     */
    public final function set_sender_id($sender_id) {
        $this->sender_id = $sender_id;
    }

    /**
     * Возвращает название экземпляра класса
     *
     * @return string
     */
    public static function to_string() {
        return "";
    }

    /**
     * @param array $parameters
     *
     * @return array
     * @throws nc_messaging_api_exception
     */
    public abstract function get_balance(array $parameters = array());

    /**
     * @param array $response
     *
     * @throws nc_messaging_api_exception
     */
    protected abstract function handle_api_errors(array $response);

    /**
     * @return void
     * @throws nc_messaging_api_exception
     */
    protected final function check_is_auth_method_provided() {
        if (!$this->auth_method_provided) {
            throw new nc_messaging_api_exception(NETCAT_MODULE_MESSAGING_SERVICE_AUTH_PARAMETERS_DID_NOT_PROVIDED_ERROR,
                401);
        }
    }

    /**
     * @return nc_http
     */
    protected function configure_http() {
        return new nc_http();
    }

}
