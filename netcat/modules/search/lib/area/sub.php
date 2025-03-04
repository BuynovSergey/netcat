<?php

/**
 * 
 */
class nc_search_area_sub extends nc_search_area_part {

    protected $sites = array();
    protected $path = "";
    static protected $date_regexp = "#/(?:19|20)\d{2}(?:/(?:0\d|11|12))?(?:/(?:[012]\d|3[01]))/?#";

    /**
     *
     * @return boolean
     */
    public function has_site() {
        // the path is unambiguous if:
        //  (a) subdivision ID is set
        //  (b) $this->url contains full url, i.e. scheme + domain name + path
        return (bool) ($this->ids || strpos($this->url, "://") || $this->sites);
    }

    /**
     *
     * @param array $sites
     */
    public function set_sites(array $sites) {
        $this->sites = $sites;
    }

    /**
     * Заложена возможность использовать неполные пути на нескольких сайтах.
     * Например, правило "allsites /news/" будет соответствовать множеству разделов
     * "http://site1.com/news/", "http://site2.com/news/" и т.д.
     * @return nc_search_area_site[]
     */
    public function get_sites() {
        if (!$this->sites) {
            if ($this->ids) { // get site by subdivision ID
                foreach ($this->ids as $id) {
                    $site_id = nc_db()->get_var("SELECT `Catalogue_ID` FROM `Subdivision` WHERE `Subdivision_ID`=" . intval($id));
                    $this->sites[] = new nc_search_area_site(array(
                        "ids" => array($site_id),
                        "https" => $this->https
                    ));
                }
            }
            elseif (strpos($this->url, "://")) { // probably a string with "http://"
                $this->sites[] = new nc_search_area_site(array(
                    "url" => parse_url($this->url, PHP_URL_HOST),
                    "https" => (strtolower(substr($this->url, 0, 8)) == 'https://')
                ));
            }
            else {
                $this->sites[] = new nc_search_area_allsites(array());
            }
        }
        return $this->sites;
    }

    /**
     *
     * @return array
     */
    public function get_ids() {
        if ($this->ids === null) {
            $site_ids = array();

            if (strpos($this->url, "://")) { // hostname, huh?!
                $host = parse_url($this->url, PHP_URL_HOST);
                try {
                    $site_settings = nc_Core::get_object()->catalogue->get_by_host_name($host);
                    $site_ids[] = $site_settings['Catalogue_ID'];
                }
                catch (Exception $e) {
                }
            }
            else {
                $sites = $this->get_sites();
                if ($sites) {
                    foreach ($sites as $site) {
                        $site_ids = array_merge($site_ids, $site->get_ids());
                    }
                }
            }

            foreach ($site_ids as $site_id) {
                $resolved_path = nc_resolve_url($this->url, 'GET', $site_id);

                if (isset($resolved_path['folder_id'])) {
                    $this->ids[] = $resolved_path['folder_id'];
                }
            }
        }

        return $this->ids;
    }

    /**
     *
     * @throws nc_search_exception
     * @return string
     */
    protected function get_path() {
        if (!$this->path) {
            if ($this->url) {
                if (strpos($this->url, "://")) {
                    $this->path = parse_url($this->url, PHP_URL_PATH);
                }
                else {
                    $this->path = $this->url;
                }
            }
            elseif ($this->ids) {
                $this->path = (string)nc_folder_path($this->ids[0]);
            }
            else {
                throw new nc_search_exception("Wrong subdivision area: neither ID nor URL specified");
            }
        }
        return $this->path;
    }

    /**
     * Получить полный URL (с http://, именем домена)
     */
    public function get_urls() {
        $path = ltrim($this->get_path(), '/');
        $urls = array();
        foreach ($this->get_sites() as $site) {
            foreach ($site->get_urls() as $site_url) {
                $urls[] = $site_url.$path;
            }
        }
        return $urls;
    }

    /**
     * ВНИМАНИЕ! Пути регистрозависимы!
     * @param string $url
     * @return boolean
     */
    public function matches($url) {
        $domain_matched = false;
        foreach ($this->get_sites() as $site) {
            if ($site->matches($url) && !$site->is_excluded()) {
                $domain_matched = true;
                break;
            }
        }
        if (!$domain_matched) {
            return false;
        }

        $area_path = $this->get_path();
        $checked_path = parse_url($url, PHP_URL_PATH);

        // убрать фрагменты дат из проверяемого пути в случае, если правило задано
        // в виде идентификатора раздела ("sub123")
        if (!$this->url) {
            $checked_path = preg_replace(self::$date_regexp, "/", $checked_path);
        }

        // нелатинские пути
        $checked_path = urldecode($checked_path);

        // Возможно три варианта:
        // (а) Только эта страница
        if (!$this->include_children && !$this->include_descendants) {
            return ($checked_path == $area_path);
        }
        // (б) Этот раздел и прямые потомки (объекты в разделе)
        if ($this->include_children && !$this->include_descendants) {
            if (strpos($checked_path, $area_path) !== 0) {
                return false;
            }
            $remainder = substr($checked_path, strlen($area_path));
            return (strpos($remainder, "/") === false);
        }
        // (в) Этот раздел и все потомки
        return (strpos($checked_path, $area_path) === 0);
    }

    /**
     *
     * @return string
     */
    public function get_string() {
        $ids = $this->get_ids();
        if ($ids) {
            $conditions = array();
            foreach ($ids as $id) { $conditions[] = "sub$id"; }
            return $this->join_multiple_or_conditions($conditions);
        }
        else {
            return $this->path;
        }
    }

    /**
     *
     * @return string
     */
    protected function get_suffix() {
        if (!$this->include_children) {
            return ".";
        }
        if ($this->include_descendants) {
            return "*";
        }
        return "";
    }

    /**
     *
     */
    public function get_sql_condition() {
        $table = "`{$this->document_table_name}`";
        // (а) Только эта страница
        if (!$this->include_children && !$this->include_descendants) {
            $q = $this->get_path_sql_condition();
        }
        // (б) Этот раздел и все потомки
        elseif ($this->include_descendants) {
            if ($this->is_sub_root()) {
                $ids = $this->get_ids();
                $query_parts = array();
                foreach ($ids as $id) {
                    $query_parts[] = "FIND_IN_SET('sub{$id}', $table.`Ancestors`)";
                }
                $q = "(" . join(" OR ", $query_parts) . ")";
            }
            else {
                $q = $this->get_path_sql_condition('LIKE', '%');
            }
        }
        // (в) Этот раздел и прямые потомки (объекты в разделе)
        else {
            $q = $this->is_sub_root() ?
                    "$table.`Subdivision_ID` IN (" . join(', ', $this->get_ids()) . ")" :
                     $this->get_path_sql_condition("RLIKE", "[^/]*$");
        }
        return $q;
    }

    /**
     * Является ли указанный в правиле путь «корнем» раздела?
     * Не являются «„корнем“ раздела»:
     *  — пути, не находящиеся под управлением Netcat
     *  — пути с указаниями фрагментов дат
     */
    protected function is_sub_root() {
        if (!$this->url) { return true; }         // it must be "subXXX" then
        if (!$this->get_ids()) { return false; }  // not a Netcat-managed path
        // has ID, has URL; check for date fragments
        return !preg_match(self::$date_regexp, $this->get_path());
    }

    /**
     * @param string $operator
     * @param string $template
     * @return string
     * @throws nc_search_exception
     */
    protected function get_path_sql_condition($operator = '=', $template = '') {
        $site_cond = array();
        foreach ($this->get_sites() as $site) {
            $site_cond[] = $site->get_sql_condition();
        }
        $q = ($site_cond ? "(" . join(" OR ", $site_cond) . ")" : "1");
        $q .= " AND `{$this->document_table_name}`.`Path` $operator '" .
              nc_search_util::db_escape($this->get_path()) . $template . "'";
        return $q;
    }

    /**
     *
     */
    public function get_field_condition() {
        $ids = $this->get_ids();
        $conditions = array();
        if ($ids) {
            if ($this->include_descendants) { // Этот раздел и все потомки
                foreach ($ids as $id) { $conditions[] = "ancestor:sub$id"; }
            }
            else { // Только эта страница или страница и прямые потомки
                foreach ($ids as $id) { $conditions[] = "sub_id:$id"; }
            }
        }
        else {
            $conditions[] = "path:NOT_SUPPORTED_IN_LUCENE_INDEX___NO_SUB_ID";
        }
        return $this->join_multiple_or_conditions($conditions);
    }

    /**
     *
     */
    public function get_description() {
        $sub_ids = $this->get_ids();
        $sub_id = $sub_ids[0];
        $link = "$GLOBALS[ADMIN_PATH]#subdivision.info($sub_id)";

        if ($sub_id) {
            try {
                $name = nc_Core::get_object()->subdivision->get_by_id($sub_id, "Subdivision_Name");
            }
            catch (Exception $e) {
                $name = "sub$sub_id";
            }
        } else {
            $name = $link = $this->url;
        }

        // (а) Только эта страница
        if (!$this->include_children && !$this->include_descendants) {
            $str = NETCAT_MODULE_SEARCH_ADMIN_RULE_AREA_DESCRIPTION_SUB_ONLY;
        }
        // (б) Этот раздел и все потомки
        elseif ($this->include_descendants) {
            $str = NETCAT_MODULE_SEARCH_ADMIN_RULE_AREA_DESCRIPTION_SUB_DESCENDANTS;
        }
        // (в) Этот раздел и прямые потомки (объекты в разделе)
        else {
            $str = NETCAT_MODULE_SEARCH_ADMIN_RULE_AREA_DESCRIPTION_SUB_CHILDREN;
        }

        return sprintf($str, $link, $name);
    }

}
