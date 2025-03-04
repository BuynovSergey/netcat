<?php

class nc_http_exception extends Exception {

    const DEFAULT_ERROR_MESSAGE = "An error occurred while sending the request";

    private static $CODE_MAPPINGS = array(
        nc_http::HTTP_STATUS_BAD_REQUEST => "Bad Request",
        nc_http::HTTP_STATUS_UNAUTHORIZED => "Unauthorized",
        nc_http::HTTP_STATUS_FORBIDDEN => "Forbidden",
        nc_http::HTTP_STATUS_NOT_FOUND => "Not Found",
        nc_http::HTTP_STATUS_METHOD_NOT_ALLOWED => "Method Not Allowed",
        nc_http::HTTP_STATUS_INTERNAL_SERVER_ERROR => "Internal Server Error",
        nc_http::HTTP_STATUS_NOT_IMPLEMENTED => "Method Not Implemented",
        nc_http::HTTP_STATUS_BAD_GATEWAY => "Bad Gateway",
        "" => self::DEFAULT_ERROR_MESSAGE,
    );

    /**
     * @var array
     */
    private $response;


    /**
     * @param string $message
     * @param int $code
     * @param array $response
     */
    public function __construct($message = "", $code = 0, array $response = array()) {
        $this->response = $response;
        parent::__construct($message ?: $this->get_error_message_by_http_code($code), $code);
    }

    /**
     * @return array
     */
    public function get_response() {
        return $this->response;
    }

    /**
     * @param $code
     *
     * @return string
     */
    private function get_error_message_by_http_code($code) {
        return self::$CODE_MAPPINGS[$code] ?: self::DEFAULT_ERROR_MESSAGE;
    }

}
