<?php

class nc_messaging_sms_api_smsc extends nc_messaging_sms_api implements nc_messaging_api_multi_messaging {

    const API_BASE_URL = "https://smsc.ru/rest";


    /**
     * @inheritDoc
     */
    public function provide_authorization(array $credentials) {
        $this->authorize_by_basic_auth_method($credentials["login"], $credentials["password"]);
    }

    /**
     * @inheritDoc
     *
     * @see https://smsc.ru/api/http/send/sms/#menu
     */
    public function send_message($recipient_id, $message, array $parameters = array()) {
        $parameters = array_merge(
            $parameters,
            array(
                "login" => $this->login,
                "psw" => $this->password,
                "phones" => $recipient_id,
                "mes" => $message,
                "charset" => "utf-8",
                "fmt" => 3,
            )
        );

        try {
            $response = $this->http->make_post_request("/send/", $parameters);
            $this->handle_api_errors($response);

            $result = new nc_messaging_result($response);
            $result->map_to_properties(array(
                "message_id" => $response["id"],
                "message" => $message,
                "recipients" => $recipient_id,
            ));

            return $result;
        } catch (nc_http_exception $e) {
            return new nc_messaging_api_exception($e->getMessage(), $e->getCode());
        }
    }

    /**
     * @inheritDoc
     *
     * @see https://smsc.ru/api/http/send/sms/#menu
     */
    public function send_multi_messages(array $recipient_ids, $message, array $parameters = array()) {
        $parameters = array_merge(
            $parameters,
            array(
                "login" => $this->login,
                "psw" => $this->password,
                "phones" => implode(",", $recipient_ids),
                "mes" => $message,
                "charset" => "utf-8",
                "fmt" => 3,
                "op" => 1,
                "err" => 1,
            )
        );

        try {
            $response = $this->http->make_post_request("/send/", $parameters);
            $this->handle_api_errors($response);

            $result = new nc_messaging_result($response);
            $result->map_to_properties(array(
                "message_id" => $response["id"],
                "message" => $message,
                "recipients" => $recipient_ids,
            ));

            return $result;
        } catch (nc_http_exception $e) {
            return new nc_messaging_api_exception($e->getMessage(), $e->getCode());
        }
    }

    /**
     * @inheritDoc
     */
    public function get_status_info($message_id, array $parameters = array()) {
        $parameters = array_merge(
            $parameters,
            array(
                "login" => $this->login,
                "psw" => $this->password,
                "phone" => $parameters["phone"],
                "id" => $message_id,
                "charset" => "utf-8",
                "fmt" => 3,
                "all" => 2,
            )
        );

        try {
            $response = $this->http->make_post_request("/status/", $parameters);
            $this->handle_api_errors($response);

            return $response;
        } catch (nc_http_exception $e) {
            return new nc_messaging_api_exception($e->getMessage(), $e->getCode());
        }
    }

    /**
     * @inheritDoc
     */
    public function get_balance(array $parameters = array()) {
        $parameters = array(
            "login" => $this->login,
            "psw" => $this->password,
            "fmt" => 3,
            "cur" => 1,
        );

        try {
            $response = $this->http->make_post_request("/balance/", $parameters);
            $this->handle_api_errors($response);

            return $response;
        } catch (nc_http_exception $e) {
            return new nc_messaging_api_exception($e->getMessage(), $e->getCode());
        }
    }

    /**
     * @inheritDoc
     */
    protected function configure_http() {
        return new nc_http(nc_messaging_sms_api_smsc::API_BASE_URL);
    }

    /**
     * @inheritDoc
     */
    protected function handle_api_errors(array $response) {
        $error_message = nc_array_value($response, "error", "");

        if ($error_message || isset($error["error_code"])) {
            throw new nc_messaging_api_exception($error_message, $response["error_code"]);
        }
    }

}
