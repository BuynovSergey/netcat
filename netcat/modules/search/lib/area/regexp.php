<?php

/**
 * $Id: regexp.php 6209 2012-02-10 10:28:29Z denis $
 */

/**
 * 
 */
class nc_search_area_regexp extends nc_search_area_part {

    protected $regexp;
    protected $sites = array();

    public function matches($url) {
        foreach ($this->sites as $site_area) {
            if (!$site_area->matches($url)) {
                return false;
            }
        }
        return preg_match($this->regexp, $url);
    }

    public function get_string() {
        return $this->regexp;
    }

    // ---------------------------------------------------------------------------

    protected function not_implemented() {
        throw new nc_search_exception("Not implemented");
    }

    public function get_urls() {
        $this->not_implemented();
    }

    public function get_sql_condition() {
        $this->not_implemented();
    }

    public function get_field_condition() {
        $this->not_implemented();
    }

    public function get_description() {
        $this->not_implemented();
    }

}