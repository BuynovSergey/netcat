<?php

class nc_messaging_sms_api_smsru extends nc_messaging_sms_api implements nc_messaging_api_multi_messaging {

    const API_BASE_URL = "https://sms.ru/sms";
    const SMS_MESSAGES_PER_REQUEST = 100;


    /**
     * @inheritDoc
     */
    public function send_message($recipient_id, $message, array $parameters = array()) {
        $this->check_is_auth_method_provided();

        $parameters = array_merge(
            $parameters,
            $this->get_default_request_parameters(),
            array(
                "to" => $recipient_id,
                "msg" => $message,
            )
        );

        if ($this->sender_id) {
            $parameters["from"] = $this->sender_id;
        }

        try {
            $response = $this->http->make_get_request("/send", $parameters);
            $this->handle_api_errors($response);

            $result = new nc_messaging_result($response);
            $result->map_to_properties(array(
                "message_id" => $response["sms"][0]["sms_id"],
                "message" => $message,
                "recipients" => $recipient_id,
            ));

            return $result;
        } catch (nc_http_exception $e) {
            return new nc_messaging_api_exception($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * @inheritDoc
     */
    public function send_multi_messages(array $recipient_ids, $message, array $parameters = array()) {
        $this->check_is_auth_method_provided();

        $parameters = array_merge(
            $parameters,
            $this->get_default_request_parameters(),
            array(
                "msg" => $message,
            )
        );

        if ($this->sender_id) {
            $parameters["from"] = $this->sender_id;
        }

        $chunks = array_chunk($recipient_ids, nc_messaging_sms_api_smsru::SMS_MESSAGES_PER_REQUEST);
        $result = array();

        foreach ($chunks as $recipient_ids) {
            $parameters["to"] = implode(",", $recipient_ids);

            try {
                $response = $this->http->make_post_request("/send", $parameters);
                $result["status"] = $response["status"];
                $result["status_code"] = $response["status_code"];
                $result["sms"] = $response["sms"];

                try {
                    $this->handle_api_errors($response);
                } catch (nc_messaging_api_exception $e) {
                    $result["errors"][] = $e->getMessage();
                }

            } catch (nc_http_exception $e) {
                throw new nc_messaging_api_exception($e->getMessage(), $e->getCode(), $e);
            }
        }

        $messaging_result = new nc_messaging_result($result);
        $messaging_result->map_to_properties(array(
            "message_id" => $result["sms"],
            "message" => $message,
            "recipients" => $recipient_ids,
        ));

        return $messaging_result;
    }

    /**
     * @inheritDoc
     */
    public function get_status_info($message_id, array $parameters = array()) {
        $this->check_is_auth_method_provided();

        $parameters = array_merge(
            $parameters,
            $this->get_default_request_parameters(),
            array("sms_id" => $message_id)
        );

        try {
            $response = $this->http->make_get_request("/status", $parameters);
            $this->handle_api_errors($response);

            return $response;
        } catch (nc_http_exception $e) {
            return new nc_messaging_api_exception($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * @inheritDoc
     */
    public function get_balance(array $parameters = array()) {
        $this->check_is_auth_method_provided();

        try {
            $response = $this->http->make_get_request("/balance", $this->get_default_request_parameters());
            $this->handle_api_errors($response);

            return $response;
        } catch (nc_http_exception $e) {
            return new nc_messaging_api_exception($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * @inheritDoc
     */
    protected function handle_api_errors(array $response) {
        if ($response["status"] != "OK" || $response["status_code"] != 100) {
            $errors_by_each_number = array();

            if (isset($response["sms"])) {
                foreach ($response["sms"] as $number => $info) {
                    if ($info["status"] != "OK" || $info["status_code"] != 100) {
                        $errors_by_each_number[] = sprintf("%s:%s", $number, nc_array_value($info, "status_text"));
                    }
                }
            }

            throw new nc_messaging_api_exception(nc_array_value($response, "status_text",
                implode(",", $errors_by_each_number)), $response["status_code"]);
        }
    }

    /**
     * @inheritDoc
     */
    protected function configure_http() {
        return new nc_http(nc_messaging_sms_api_smsru::API_BASE_URL);
    }

    /**
     * @return array
     */
    private function get_default_request_parameters() {
        $result = array(
            "json" => 1,
        );

        if ($this->is_api_token_auth_provided) {
            $result["api_id"] = $this->api_key;
        }
        else {
            $result["login"] = $this->login;
            $result["password"] = $this->password;
        }

        return $result;
    }

}
