<?php

abstract class nc_messaging_service implements nc_messaging_api {

    /**
     * @var nc_messaging_service_record
     */
    protected $service_record;

    /**
     * @var array
     */
    protected $settings;

    /**
     * @var nc_messaging_sms_api|nc_messaging_api_multi_messaging|null
     */
    protected $api;


    /**
     * Возвращает структуру данных с настройками сервиса для nc_a2f класса
     * При отсутствии переопределения метода не гарантируется корректная отрисовка формы
     *
     * @return array
     */
    public static function get_settings_mapping() {
        return array();
    }

    /**
     * @param int $length
     *
     * @return string
     */
    public static function generate_confirmation_code($length = 4) {
        $min = pow(10, ($length - 1));
        $max = pow(10, $length) - 1;

        return (string)rand($min, $max);
    }

    /**
     * @param nc_messaging_service_record $service_record
     * @param nc_messaging_api|null $api
     */
    public function __construct(nc_messaging_service_record $service_record, nc_messaging_api $api = null) {
        $this->service_record = $service_record;
        $this->settings = $service_record["settings"];

        if ($api) {
            $this->api = $api;
            $this->configure_api();
        }
    }

    /**
     * @inheritDoc
     */
    public function provide_authorization(array $credentials) {
        isset($credentials["api_key"])
            ? $this->api->authorize_by_api_token_auth_method($credentials["api_key"])
            : $this->api->authorize_by_basic_auth_method($credentials["login"], $credentials["password"]);
    }

    /**
     * @return int
     */
    public function get_service_id() {
        return nc_array_value($this->service_record, "id", -1);
    }

    /**
     * @param string|array $recipient_id
     *
     * @return void
     * @throws nc_messaging_exception
     */
    protected abstract function validate_recipient($recipient_id);

    /**
     * @return void
     */
    protected function configure_api() {
        $this->provide_authorization($this->settings);

        if ($this->settings["sender_id"]) {
            $this->api->set_sender_id($this->settings["sender_id"]);
        }
    }

}
