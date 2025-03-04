<?php

interface nc_messaging_api_multi_messaging {

    /**
     * @param array $recipient_ids
     * @param string $message
     * @param array $parameters
     *
     * @return nc_messaging_result
     * @throws nc_messaging_api_exception
     */
    public function send_multi_messages(array $recipient_ids, $message, array $parameters = array());

}
