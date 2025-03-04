<?php

class nc_routing_request {

    /** @var int */
    protected $site_id;
    /** @var array */
    protected $url_parts;
    /** @var string */
    protected $method;

    /**
     * @param int $site_id
     * @param string $method
     * @param array $url_parts  parsed URL
     *      (result of nc_url::get_parsed_url() or parse_url(), i.e.
     *      array with following keys: host, port, user, pass, path, query)
     */
    public function __construct($site_id, $method, array $url_parts) {
        $this->site_id = (int)$site_id;
        $this->method = $method;
        $this->url_parts = $url_parts;
    }

    /**
     * @return int
     */
    public function get_site_id() {
        return $this->site_id;
    }

    /**
     * @return string
     */
    public function get_method() {
        return $this->method;
    }

    /**
     * @return string
     */
    public function get_path() {
        return nc_array_value($this->url_parts, 'path');
    }

}