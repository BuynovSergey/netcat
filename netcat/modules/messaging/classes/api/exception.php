<?php

class nc_messaging_api_exception extends nc_messaging_exception {

    public function __construct($message = "", $code = 0, Exception $previous = null) {
        parent::__construct($message, $code, $previous);
    }

}
