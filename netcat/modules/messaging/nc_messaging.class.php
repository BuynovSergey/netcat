<?php


class nc_messaging {

    /**
     * @var nc_messaging[]
     */
    private static $instances;

    /**
     * @var int
     */
    private $site_id;

    /**
     * @param int|null $site_id
     */
    public function __construct($site_id = null) {
        if (!$site_id) {
            $site_id = nc_core::get_object()->catalogue->get_current("Catalogue_ID");
        }

        $this->site_id = $site_id;
    }

    /**
     * @param int|null $site_id
     *
     * @return nc_messaging
     */
    public static function get_instance($site_id = null) {
        if (!$site_id) {
            $site_id = nc_core::get_object()->catalogue->get_current("Catalogue_ID");
        }

        if (!isset(self::$instances[$site_id])) {
            self::$instances[$site_id] = new self($site_id);
        }

        return self::$instances[$site_id];
    }

    /**
     * Можно ли отправить сообщение указанного типа
     *
     * @param string $type nc_messaging_settings::SERVICE_TYPE_*
     * @return bool
     */
    public function can_send($type = null) {
        $settings = $this->get_settings_provider();
        if (!$settings->is_messaging_enabled()) {
            return false;
        }
        if (!$settings->get_messaging_service_id($type)) {
            return false;
        }
        return true;
    }

    /**
     * @return nc_messaging_settings
     */
    public function get_settings_provider() {
        return nc_messaging_settings::get_instance($this->site_id);
    }

    /**
     * Отправить входящий звонок
     *
     * @param string $phone
     * @param string $confirmation_code 4х или менее значный код, цифры которого соответствуют последним цифрам
     *     входящего звонка
     * @param int $service_id
     *
     * @return array
     * @throws nc_messaging_exception
     */
    public function send_flash_call($phone, $confirmation_code, $service_id) {
        $service = nc_messaging_service_factory::create_service_by_id($service_id);

        if (!$service instanceof nc_messaging_api_flash_call) {
            throw new nc_messaging_exception("Сервис не поддерживает функцию отправки входящего звонка");
        }

        try {
            return $service->push_flash_call($phone, $confirmation_code);
        } catch (nc_messaging_api_exception $e) {
            throw new nc_messaging_exception($e->getMessage());
        } catch (nc_validator_exception $e) {
            throw new nc_messaging_exception($e->getMessage());
        }
    }

    /**
     * @param string|array $recipient_ids
     * @param string $message
     * @param int|null $service_id если null, используется служба по умолчанию
     * @param array|null $parameters
     *
     * @return nc_messaging_result
     * @throws nc_messaging_exception
     */
    public function send_message($recipient_ids, $message, $service_id = null, array $parameters = null) {
        if (!$service_id) {
            $service_id = $this->get_settings_provider()->get_messaging_service_id();
        }

        $recipients_normalized = $this->normalize_recipient_ids($recipient_ids);
        $logger = new nc_messaging_log_record();
        $log_data = array(
            "site_id" => $this->site_id,
            "event_type" => nc_array_value($parameters, "event_type", nc_messaging_log_record::EVENT_TYPE_SYSTEM),
        );

        $service = null;

        try {
            if (!$this->get_settings_provider()->is_messaging_enabled()) {
                throw new nc_messaging_exception(NETCAT_MODULE_MESSAGING_DISABLED_ERROR);
            }

            /** @var nc_messaging_service|nc_messaging_api_multi_messaging $service */
            $service = nc_messaging_service_factory::create_service_by_id($service_id);

            if ($service instanceof nc_messaging_sms_service) {
                $recipients_normalized = $this->normalize_phones($recipients_normalized);
            }

            if (!$recipients_normalized) {
                throw new nc_messaging_exception(NETCAT_MODULE_MESSAGING_INVALID_PHONE_FORMAT);
            }

            if (is_array($recipients_normalized)) {
                $result = $service->send_multi_messages($recipients_normalized, $message, $parameters ?: array());
            }
            else {
                $result = $service->send_message($recipients_normalized, $message, $parameters ?: array());
            }

            $logger->set_values(array_merge($log_data, array(
                "service_id" => $service->get_service_id(),
                "message_id" => $result->get_message_id(),
                "recipients" => is_array($recipients_normalized) ? implode(",", $recipients_normalized) :
                    $recipients_normalized,
                "log_message" => NETCAT_MODULE_MESSAGING_MESSAGE_SENT_SUCCESS,
                "message" => $message,
                "messaging_type" => $service instanceof nc_messaging_sms_service ?
                    nc_messaging_service_record::MESSAGING_TYPE_SMS :
                    nc_messaging_service_record::MESSAGING_TYPE_MESSENGER,
                "details" => $result->get_original_data(),
            )));

            $logger->save();

            return $result;
        } catch (Exception $e) {
            $logger->set_values(array_merge($log_data, array(
                "service_id" => $service !== null ? $service->get_service_id() : 0,
                "log_message" => $e->getMessage(),
                "recipients" => is_array($recipients_normalized) ? implode(",", $recipients_normalized) :
                    $recipients_normalized,
                "message" => $message,
                "messaging_type" => $service instanceof nc_messaging_sms_service ?
                    nc_messaging_service_record::MESSAGING_TYPE_SMS :
                    nc_messaging_service_record::MESSAGING_TYPE_MESSENGER,
                "status" => nc_messaging_log_record::STATUS_ERROR,
            )));

            try {
                $logger->save();
            } catch (nc_record_exception $ignored) {
            }

            throw new nc_messaging_exception($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * @param string|array $recipient_ids
     * @param string $message
     * @param array|null $parameters
     *
     * @return nc_messaging_result
     * @throws nc_messaging_exception
     */
    public function send_auth_message($recipient_ids, $message, array $parameters = array()) {
        $service_id = $this->get_settings_provider()->get_messaging_service_id(nc_messaging_settings::SERVICE_TYPE_AUTH);

        return $this->send_message($recipient_ids, $message, $service_id,
            array_merge(array("event_type" => nc_messaging_settings::SERVICE_TYPE_AUTH), $parameters));
    }

    /**
     * @param string|array $recipient_ids
     * @param string $message
     * @param array|null $parameters
     *
     * @return nc_messaging_result
     * @throws nc_messaging_exception
     */
    public function send_security_message($recipient_ids, $message, array $parameters = array()) {
        $service_id = $this->get_settings_provider()
            ->get_messaging_service_id(nc_messaging_settings::SERVICE_TYPE_SECURITY);

        return $this->send_message($recipient_ids, $message, $service_id,
            array_merge(array("event_type" => nc_messaging_settings::SERVICE_TYPE_SECURITY), $parameters));
    }

    /**
     * @param string|array $recipient_ids
     * @param string $message
     * @param array|null $parameters
     *
     * @return nc_messaging_result
     * @throws nc_messaging_exception
     */
    public function send_netshop_message($recipient_ids, $message, array $parameters = array()) {
        $service_id = $this->get_settings_provider()
            ->get_messaging_service_id(nc_messaging_settings::SERVICE_TYPE_NETSHOP);

        return $this->send_message($recipient_ids, $message, $service_id,
            array_merge(array("event_type" => nc_messaging_settings::SERVICE_TYPE_NETSHOP), $parameters));
    }

    /**
     * @param string|array $recipient_ids
     * @param string $message
     * @param array|null $parameters
     *
     * @return nc_messaging_result
     * @throws nc_messaging_exception
     */
    public function send_requests_message($recipient_ids, $message, array $parameters = array()) {
        $service_id = $this->get_settings_provider()
            ->get_messaging_service_id(nc_messaging_settings::SERVICE_TYPE_REQUESTS);

        return $this->send_message($recipient_ids, $message, $service_id,
            array_merge(array("event_type" => nc_messaging_settings::SERVICE_TYPE_REQUESTS), $parameters));
    }

    /**
     * Приводит к строке, если идентификатор один
     * Обрабатывает массив, разделяя каждый через разделитель запятую, если идентификаторов несколько
     * Удаляет дублирующиеся позиции, пустые значения
     *
     * @param array|string $recipient_ids
     *
     * @return array|string
     */
    private function normalize_recipient_ids($recipient_ids) {
        if (is_string($recipient_ids)) {
            $recipient_ids = explode(",", $recipient_ids);
        }

        $result = array();

        foreach ($recipient_ids as $id) {
            foreach (explode(",", $id) as $_id) {
                $result[] = trim($_id);
            }
        }

        $result = array_values(array_filter(array_unique($result)));

        return (count($result) == 1) ? $result[0] : $result;
    }

    /**
     * @param array|string $recipient_ids
     *
     * @return array|string|null
     */
    private function normalize_phones($recipient_ids) {
        $normalize_callback = function ($phone) {
            return nc_normalize_phone_number(trim($phone));
        };

        if (is_string($recipient_ids)) {
            $recipient_ids = explode(",", $recipient_ids);
        }

        $result = array_values(array_unique(array_filter(array_map($normalize_callback, $recipient_ids))));

        return count($result) == 1 ? $result[0] : $result;
    }

}
