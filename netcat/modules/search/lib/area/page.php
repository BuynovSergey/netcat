<?php

/**
 * 
 */
class nc_search_area_page extends nc_search_area_sub {

    protected $include_children = false;

    public function get_ids() {
        return array();
    }

    protected function get_path() {
        if (!$this->path) {
            $this->path = (strpos($this->url, "://") ? parse_url($this->url, PHP_URL_PATH) : $this->url);
        }
        return $this->path;
    }

    public function to_string() {
        return $this->get_path();
    }

    public function get_field_condition() {
        return '""';
    }

    public function get_description() {
        $urls = $this->get_urls();
        return sprintf(NETCAT_MODULE_SEARCH_ADMIN_RULE_AREA_DESCRIPTION_PAGE, $urls[0], $this->get_path());
    }

}
