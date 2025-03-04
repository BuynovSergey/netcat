<?php

/**
 * 
 */
class nc_search_area_site extends nc_search_area_part {

    protected $site_domains = array();

    public function matches($url) {
        $checked_scheme = strtolower(parse_url($url, PHP_URL_SCHEME));
        if (($this->https && $checked_scheme != 'https') || (!$this->https && $checked_scheme != 'http')) {
            return false;
        }

        $checked_domain = strtolower(parse_url($url, PHP_URL_HOST));
        // рассматривать домены с префиксом www. и без него как одинаковые
        $checked_domain_without_www = (strpos($checked_domain, "www.") === 0) ? substr($checked_domain, 4) : $checked_domain;

        foreach ($this->get_domain_names() as $domain) {
            if ($domain == $checked_domain || $domain == $checked_domain_without_www) {
                return true;
            }
        }
        return false;
    }

    public function get_first_id() {
        $ids = $this->get_ids();
        return $ids[0];
    }

    public function get_ids() {
        if (!$this->ids) {
            $site_settings = nc_Core::get_object()->catalogue->get_by_host_name($this->url);
            $this->ids = array($site_settings["Catalogue_ID"]);
        }
        return $this->ids;
    }

    public function get_string() {
        return "site{$this->get_first_id()}";
    }

    protected function get_domain_names() {
        if (!$this->site_domains) {
            /* @throws Exception */
            try {
                $site_settings = nc_Core::get_object()->catalogue->get_by_id($this->get_first_id());
            }
            catch (Exception $e) {
                throw new nc_search_exception("Cannot get settings for the site with ID={$this->get_first_id()}");
            }

            if ($site_settings["Domain"]) {
                $all_domains = trim(nc_strtolower($site_settings["Domain"]."\n".$site_settings["Mirrors"]));
                $this->site_domains = preg_split("/\s+/u", $all_domains);
            }
            else if (getenv("HTTP_HOST")) { // FALLBACK
                $this->site_domains = array(getenv("HTTP_HOST"));
            }
            else { // we're desperate... but will provide a name anyway
                $this->site_domains = array("localhost");
            }
        }
        return $this->site_domains;
    }

    public function get_urls() {
        $domains = $this->get_domain_names();
        $scheme = ($this->https ? "https" : "http");
        return array("$scheme://$domains[0]/");
    }

    public function get_sql_condition() {
        return "`{$this->document_table_name}`.`Catalogue_ID` = ".$this->get_first_id();
    }

    public function get_field_condition() {
        return "site_id:".$this->get_first_id();
    }

    public function get_description() {
        $site_id = $this->get_first_id();
        $link = "$GLOBALS[ADMIN_PATH]#site.map($site_id)";
        try {
            $name = nc_Core::get_object()->catalogue->get_by_id($site_id, "Catalogue_Name");
        } catch (Exception $e) {
            $name = "site$site_id";
        }
        return sprintf(NETCAT_MODULE_SEARCH_ADMIN_RULE_AREA_DESCRIPTION_SITE, $link, $name);
    }

}
