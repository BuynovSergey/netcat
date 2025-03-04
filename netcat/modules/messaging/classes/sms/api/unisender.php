<?php

class nc_messaging_sms_api_unisender extends nc_messaging_sms_api implements nc_messaging_api_multi_messaging {

    const API_BASE_URL = "https://api.unisender.com/ru/api";
    const SMS_MESSAGES_PER_REQUEST = 150;


    /**
     * @inheritDoc
     */
    public function provide_authorization(array $credentials) {
        $this->authorize_by_api_token_auth_method($credentials["api_key"]);
    }

    /**
     * @inheritDoc
     */
    public function send_message($recipient_id, $message, array $parameters = array()) {
        $parameters = array_merge(
            $this->get_default_request_parameters(),
            array(
                "phone" => $recipient_id,
                "sender" => $this->sender_id,
                "text" => $message,
            )
        );

        try {
            $response = $this->http->make_post_request("/sendSms", $parameters);
            $this->handle_api_errors($response);

            $result = new nc_messaging_result($response);
            $result->map_to_properties(array(
                "message_id" => $response["message_id"],
                "message" => $message,
                "recipients" => $recipient_id,
            ));

            return $result;
        } catch (nc_http_exception $e) {
            $response = $e->get_response();
            $message = nc_array_value($response, "error", "");

            throw new nc_messaging_api_exception($message, $e->getCode(), $e);
        }
    }

    /**
     * @inheritDoc
     */
    public function send_multi_messages(array $recipient_ids, $message, array $parameters = array()) {
        $chunks = array_chunk($recipient_ids, nc_messaging_sms_api_unisender::SMS_MESSAGES_PER_REQUEST);
        $parameters = array_merge(
            $this->get_default_request_parameters(),
            array(
                "sender" => $this->sender_id,
                "text" => $message,
            )
        );

        $result = array();

        foreach ($chunks as $chunk) {
            try {
                $parameters["phone"] = implode(",", $chunk);
                $response = $this->http->make_post_request("/sendSms", $parameters);
                $result["result"] = $response["result"];

                try {
                    $this->handle_api_errors($response);
                } catch (nc_messaging_api_exception $e) {
                    $result["errors"][] = $e->getMessage();
                }
            } catch (nc_http_exception $e) {
                $response = $e->get_response();
                throw new nc_messaging_api_exception(nc_array_value($response, "error", $e->getMessage()),
                    $e->getCode(), $e);
            }
        }

        $messaging_result = new nc_messaging_result($result);
        $messaging_result->map_to_properties(array(
            "message_id" => $result["phone"],
            "message" => $message,
            "recipients" => $recipient_ids,
        ));

        return $messaging_result;
    }

    /**
     * @inheritDoc
     */
    public function get_status_info($message_id, array $parametersIgnored = array()) {
        $parameters = array_merge(
            $this->get_default_request_parameters(),
            array(
                "sms_id" => $message_id,
            )
        );

        try {
            $response = $this->http->make_post_request("/checkSms", $parameters);
            $this->handle_api_errors($response);

            return $response;
        } catch (nc_http_exception $e) {
            throw new nc_messaging_api_exception($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * @inheritDoc
     */
    public function get_balance(array $parameters = array()) {
        throw new InvalidArgumentException("Not supported");
    }

    /**
     * @inheritDoc
     */
    protected function handle_api_errors(array $response) {
        if (isset($response["error"]) || isset($response["code"])) {
            throw new nc_messaging_api_exception($response["error"], 1);
        }
    }

    /**
     * @inheritDoc
     */
    protected function configure_http() {
        return new nc_http(nc_messaging_sms_api_unisender::API_BASE_URL);
    }

    /**
     * @return array
     */
    private function get_default_request_parameters() {
        return array(
            "api_key" => $this->api_key,
            "format" => "json",
        );
    }

}
