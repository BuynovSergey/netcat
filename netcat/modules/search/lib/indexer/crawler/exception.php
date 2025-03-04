<?php

/* $Id: exception.php 6209 2012-02-10 10:28:29Z denis $ */

/**
 * Используется для остановки загрузки неподходящих страниц
 */
class nc_search_indexer_crawler_exception extends nc_search_exception {

    /**
     * @var array
     */
    protected $response;

    public function __construct($msg, array $response) {
        $this->message = $msg;
        $this->response = $response;
    }

    public function get_response() {
        return $this->response;
    }

}
