<?php

/**
 * 
 */
class nc_search_area_allsites extends nc_search_area_site {

    protected static $site_names_cache = array();
    protected $site_names = array();

    protected function get_domain_names() {
        if (!$this->site_names) {
            if (!self::$site_names_cache) {
                $all_domains = array();
                $site_settings = nc_Core::get_object()->catalogue->get_all();
                foreach ($site_settings as $s) {
                    if ($s["Domain"]) {
                        $all_domains[] = trim("$s[Domain]\n$s[Mirrors]");
                    }
                }
                $all_domains = strtolower(join("\n", $all_domains));
                self::$site_names_cache = preg_split("/\s+/u", $all_domains);
            }
            $this->site_names = self::$site_names_cache;
        }
        return $this->site_names;
    }

    public function get_ids() {
        if (!$this->ids) {
            $this->ids = array();
            $all_sites = nc_Core::get_object()->catalogue->get_all();
            foreach ($all_sites as $s) { $this->ids[] = $s['Catalogue_ID']; }
        }
        return $this->ids;
    }

    public function get_string() {
        return "allsites";
    }

    public function get_urls() {
        $res = array();
        $site_settings = nc_Core::get_object()->catalogue->get_all();
        foreach ($site_settings as $s) {
            if ($s["Domain"]) {
                $scheme = $s['ncHTTPS'] === '1' ? 'https' : 'http';
                $res[] = "{$scheme}://{$s['Domain']}/";
            }
        }
        return $res;
    }

    public function get_sql_condition() {
        return "1";
    }

    public function get_field_condition() {
        return '""';
    }

    /**
     *
     */
    public function get_description() {
        return NETCAT_MODULE_SEARCH_ADMIN_RULE_AREA_DESCRIPTION_ALLSITES;
    }

}
