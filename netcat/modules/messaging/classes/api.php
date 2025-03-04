<?php

interface nc_messaging_api {

    /**
     * Предоставляет набор необходимых действий для настройки успешного взаимодействия со сторонним API
     *
     * @param array $credentials
     *
     * @return void
     */
    public function provide_authorization(array $credentials);

    /**
     * @param string|int $recipient_id идентификатор получателя (email, телефон, уникальный id, тег и прочее)
     * @param string $message
     * @param array $parameters список из любых дополнительных, незадекларированных параметров
     *
     * @return nc_messaging_result
     * @throws nc_messaging_api_exception
     */
    public function send_message($recipient_id, $message, array $parameters = array());

    /**
     * @param string|int $message_id
     * @param array $parameters
     *
     * @return array
     * @throws nc_messaging_api_exception
     */
    public function get_status_info($message_id, array $parameters = array());

}
