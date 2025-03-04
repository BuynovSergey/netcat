<?php

class nc_messaging_sms_api_smsprosto extends nc_messaging_sms_api implements nc_messaging_api_flash_call {

    const API_BASE_URL_1 = "http://api.sms-prosto.ru";
    const API_BASE_URL_2 = "https://ssl.bs00.ru";


    /**
     * @inheritDoc
     *
     * @see https://lk.sms-prosto.ru/help.php?faq=43
     */
    public function send_message($recipient_id, $message, array $parameters = array()) {
        $this->check_is_auth_method_provided();

        $parameters = array_merge(
            $parameters,
            $this->get_default_request_parameters(),
            array(
                "method" => "push_msg",
                "text" => $message,
                "phone" => $recipient_id,
            )
        );

        if ($this->sender_id) {
            $parameters["sender_name"] = $this->sender_id;
        }

        try {
            $response = $this->http->make_get_request("/", $parameters);
            $response = $response["response"];

            $this->handle_api_errors($response);

            $result = new nc_messaging_result($response["data"]);
            $result->map_to_properties(array(
                "message_id" => $response["data"]["id"],
                "message" => $message,
                "recipients" => $recipient_id,
            ));

            return $result;
        } catch (nc_http_exception $e) {
            throw new nc_messaging_api_exception($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * @inheritDoc
     * @see https://lk.sms-prosto.ru/help.php?faq=66
     */
    public function push_flash_call($phone, $confirmation_code) {
        $this->check_is_auth_method_provided();

        $code_length = strlen($confirmation_code);

        if ($code_length > 4 || $code_length < 3) {
            throw new nc_validator_exception(array(
                sprintf(NETCAT_MODULE_MESSAGING_SERVICE_CONFIRMATION_CODE_LENGTH_VALIDATION_ERROR, 3, 4),
            ));
        }

        $parameters = array_merge(
            $this->get_default_request_parameters(),
            array(
                "method" => "push_msg",
                "route" => "pc",
                "phone" => $phone,
                "text" => $confirmation_code,
            )
        );

        if ($this->sender_id) {
            $parameters["sender_name"] = $this->sender_id;
        }

        try {
            $response = $this->http->make_get_request("/", $parameters);
            $response = $response["response"];

            $this->handle_api_errors($response);

            return $response["data"];
        } catch (nc_http_exception $e) {
            throw new nc_messaging_api_exception($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * @inheritDoc
     *
     * @see https://lk.sms-prosto.ru/help.php?faq=48
     */
    public function get_status_info($message_id, array $parameters = array()) {
        $this->check_is_auth_method_provided();

        $parameters = array_merge(
            $parameters,
            $this->get_default_request_parameters(),
            array(
                "method" => "get_msg_report",
                "id" => $message_id,
            )
        );

        try {
            $response = $this->http->make_get_request("/", $parameters);
            $response = $response["response"];

            $this->handle_api_errors($response);

            return $response["data"];
        } catch (nc_http_exception $e) {
            throw new nc_messaging_api_exception($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * @inheritDoc
     *
     * @see https://lk.sms-prosto.ru/help.php?faq=51
     */
    public function get_balance(array $parameters = array()) {
        $this->check_is_auth_method_provided();

        $parameters = array_merge(
            $parameters,
            $this->get_default_request_parameters(),
            array(
                "method" => "get_profile",
            )
        );

        try {
            $response = $this->http->make_get_request("/", $parameters);
            $response = $response["response"];

            $this->handle_api_errors($response);

            return $response["data"];
        } catch (nc_http_exception $e) {
            throw new nc_messaging_api_exception($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * @return nc_http
     */
    protected function configure_http() {
        return new nc_http(self::API_BASE_URL_2);
    }

    /**
     * @param array $response
     *
     * @throws nc_messaging_api_exception
     */
    protected function handle_api_errors(array $response) {
        if ($response["msg"]["err_code"] != "0" || $response["msg"]["type"] == "error") {
            throw new nc_messaging_api_exception($response["msg"]["text"], $response["msg"]["err_code"]);
        }
    }

    /**
     * @return array
     */
    private function get_default_request_parameters() {
        $parameters = array(
            "format" => "json",
        );

        if ($this->is_api_token_auth_provided) {
            $parameters["key"] = $this->api_key;
        }
        else {
            $parameters["email"] = $this->login;
            $parameters["password"] = $this->password;
        }

        return $parameters;
    }


}
