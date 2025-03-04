<?php

class nc_messaging_sms_service extends nc_messaging_service {

    /**
     * @inheritDoc
     * @throws nc_messaging_exception
     */
    public function send_message($recipient_id, $message, array $parameters = array()) {
        try {
            $this->validate_recipient($recipient_id);

            return $this->api->send_message($recipient_id, $message);
        } catch (nc_messaging_exception $e) {
            throw new nc_messaging_exception($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * @throws nc_messaging_exception
     */
    public function send_multi_messages(array $recipient_ids, $message, array $parameters = array()) {
        try {
            if (!$this->api instanceof nc_messaging_api_multi_messaging) {
                throw new nc_messaging_exception(NETCAT_MODULE_MESSAGING_UNSUPPORTED_MULTI_MESSAGING);
            }

            $this->validate_recipient($recipient_ids);

            return $this->api->send_multi_messages($recipient_ids, $message, $parameters);
        } catch (nc_messaging_exception $e) {
            throw new nc_messaging_exception($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * @throws nc_messaging_exception
     * @throws nc_validator_exception
     */
    public function push_flash_call($phone, $confirmation_code) {
        if (!$this->api instanceof nc_messaging_api_flash_call) {
            throw new nc_messaging_exception(NETCAT_MODULE_MESSAGING_UNSUPPORTED_MULTI_MESSAGING);
        }

        $this->validate_recipient($phone);

        return $this->api->push_flash_call($phone, $confirmation_code);
    }

    /**
     * @param array $parameters
     *
     * @return array
     * @throws nc_messaging_api_exception
     */
    public function get_balance(array $parameters = array()) {
        return $this->api->get_balance($parameters);
    }

    /**
     * @inheritDoc
     */
    public function get_status_info($message_id, array $parameters = array()) {
        return $this->api->get_status_info($message_id, $parameters);
    }

    /**
     * Осуществляет проверку номера телефона по принадлежности к стране
     *
     * @param string|array $phone
     *
     * @return void
     * @throws nc_messaging_exception
     */
    protected function validate_recipient($phone) {
        if ($phone == null) {
            throw new nc_messaging_exception(NETCAT_MODULE_MESSAGING_INVALID_PHONE_FORMAT);
        }

        if (is_string($phone)) {
            $this->validate_phone_number_callback($phone);

            return;
        }

        $errors = "";

        foreach ($phone as $ph) {
            try {
                $this->validate_phone_number_callback($ph);
            } catch (nc_messaging_exception $e) {
                $errors .= $e->getMessage() . "</br>";
            }
        }

        if ($errors) {
            throw new nc_messaging_exception($errors);
        }
    }

    /**
     * @param string $phone
     *
     * @return void
     * @throws nc_messaging_exception
     */
    private function validate_phone_number_callback($phone) {
        $normalized_phone = nc_normalize_phone_number($phone);

        // Если номер не был корректно распарсен, значит его формат не валидный
        if (!$normalized_phone) {
            throw new nc_messaging_exception(sprintf("%s: %s", NETCAT_MODULE_MESSAGING_INVALID_PHONE_FORMAT,
                $phone));
        }

        $region = strtolower(nc_get_country_code_by_number($normalized_phone));

        // Несмотря на то, что настройки называются prefixes,
        // проверка осуществляется по ISO 3166-1 кодам стран
        // для обеспечения максимальной совместимости с библиотекой PhoneNumberUtil
        $allowed_regions = isset($this->settings["allowed_prefixes"]) ?
            array_keys($this->settings["allowed_prefixes"]) : array();

        $forbidden_regions = isset($this->settings["forbidden_prefixes"]) ?
            array_keys($this->settings["forbidden_prefixes"]) : array();

        // В случае наличия одинаковых кодов стран в обоих списках доступа, приоритет отдается белому списку доступа
        $diff = array_diff($forbidden_regions, $allowed_regions);

        if ($region && in_array($region, $diff, true)) {
            throw new nc_messaging_exception(sprintf(NETCAT_MODULE_MESSAGING_SERVICE_FORBIDDEN_PREFIX_ERROR,
                $phone));

        }
    }

}
