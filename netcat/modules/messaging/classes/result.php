<?php

final class nc_messaging_result {

    /**
     * @var array
     */
    private $raw_data;

    /**
     * @var int|string|null
     */
    private $message_id;

    /**
     * @var array|string
     */
    private $recipients;

    /**
     * @var string
     */
    private $message;

    /**
     * @param array $data
     */
    public function __construct(array $data) {
        $this->raw_data = $data;
    }

    /**
     * @param array $data
     *
     * @return void
     */
    public function map_to_properties(array $data) {
        $this->message_id = nc_array_value($data, "message_id", -1);
        $this->recipients = nc_array_value($data, "recipients", "");
        $this->message = nc_array_value($data, "message", "");
    }

    /**
     * @return int|string|null
     */
    public function get_message_id() {
        return $this->message_id;
    }

    /**
     * @return array|string
     */
    public function get_recipients() {
        return is_array($this->recipients) && count($this->recipients) == 1 ? $this->recipients[0] : $this->recipients;
    }

    /**
     * @return string
     */
    public function get_message() {
        return $this->message;
    }

    /**
     * @return array
     */
    public function get_original_data() {
        return $this->raw_data;
    }

}
