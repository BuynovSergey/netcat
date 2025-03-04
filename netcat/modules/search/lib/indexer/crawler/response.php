<?php

/* $Id: response.php 6209 2012-02-10 10:28:29Z denis $ */

/**
 * HTTP Response
 */
class nc_search_indexer_crawler_response {

    protected $url = '';
    protected $code = 0;
    protected $headers = array();
    protected $body = '';
    protected $is_cancelled = false;

    /**
     *
     * @param array $values
     */
    public function __construct(array $values) {
        foreach (array('url', 'code', 'headers', 'body', 'is_cancelled') as $k) {
            if (isset($values[$k])) {
                $this->$k = $values[$k];
            }
        }
    }

    /**
     *
     * @return integer
     */
    public function get_code() {
        return $this->code;
    }

    /**
     *
     * @param string $header
     * @return string|null
     */
    public function get_header($header) {
        return isset($this->headers[$header]) ? $this->headers[$header] : null;
    }

    /**
     * @return string
     */
    public function get_content_type() {
        // content-type header may contain encoding, e.g. "text/html; charset=utf-8"
        list($content_type) = explode(";", $this->get_header("content-type"));
        return $content_type;
    }

    /**
     *
     */
    public function get_content_length() {
        return $this->get_header("content-length");
    }

    /**
     *
     * @return boolean
     */
    public function has_body() {
        return ((bool) $this->body || (strlen($this->body) > 0));
    }

    /**
     *
     * @return string
     */
    public function get_body() {
        return nc_search_util::convert($this->body, 1);
    }

    /**
     *
     */
    public function get_body_length() {
        return strlen($this->body);
    }

    /**
     *
     * @return string
     */
    public function get_url() {
        $parts = explode('?', $this->url);
        return urldecode($parts[0]) . (isset($parts[1]) ? '?' . $parts[1] : '');
    }

    /**
     *
     */
    public function get_last_modified() {
        $timestamp = strtotime($this->get_header('last-modified'));
        if (!$timestamp) {
            $timestamp = time();
        } // no header obviously
        return strftime("%Y%m%d%H%M%S", $timestamp); // SIC, no delimiters
    }

}
