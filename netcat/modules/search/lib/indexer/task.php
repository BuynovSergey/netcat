<?php

/**
 * Задача переиндексатора: хранит состояние сессии переиндексации
 */
class nc_search_indexer_task extends nc_search_data_persistent {

    protected $table_name = "Search_Task";
    protected $links;
   // nc_search_indexer_link_manager
    protected $properties = array(
            'id' => null,
            'area' => null, // nc_search_area
            'disallowed' => null, // nc_search_area
            'rule_id' => null, // integer|null
            'token' => null, // для предотвращения конфликтов при неправильном запуске переинедксации "из веба"
            'last_activity' => null, // timestamp (integer)
            'start_time' => null, // timestamp (integer)
            'total_processed' => 0, // number of indexed documents
            'total_checked' => 0, // number of checked links
            'total_not_found' => 0, // number of 'broken' links
            'total_deleted' => 0, // number of deleted documents
            'runner_type' => null, // type of the 'runner' used (see nc_search::INDEXING_* constants)
            'is_idle' => false, // set to true when task is interrupted and not executed at the time
    );
    protected $mapping = array(
            'id' => 'Task_ID',
            '_generate' => true,
    );
    protected $serialized_properties = array("area", "disallowed");

    /**
     *
     */
    public function __construct(array $values = null) {
        $this->links = new nc_search_indexer_link_manager();
        $this->set('start_time', time());
        $this->set('token', rand(-2147483647, 2147483647));
        parent::__construct($values);
    }

    /**
     * 
     */
    public function set($option, $value, $add_new_option = false) {
        if ($option == 'area' && !($value instanceof nc_search_area)) {
            $value = new nc_search_area($value);
        }
        return parent::set($option, $value, $add_new_option);
    }

    /**
     *
     */
    public function increment($option) {
        $this->properties[$option]++;
        return $this;
    }

    /**
     *
     * @param array $urls
     * @param string $referrer
     * @return nc_search_indexer_task
     */
    public function add_links(array $urls, $referrer = null) {
        $urls = array_unique($urls);
        return $this->links->add_links($urls, $referrer);
    }

    /**
     * 
     */
    public function add_link($url, $referrer = null) {
        return $this->links->add_link($url, $referrer);
    }

    /**
     *
     */
    public function get_links_as_string() {
        return $this->links->get_all_urls();
    }

    /**
     * Возвращает следующую необработанную ссылку
     * @return nc_search_indexer_link|null
     */
    public function get_next_link() {
        return $this->links->get_next_link();
    }

    /**
     * @return nc_search_rule|NULL
     */
    public function get_rule() {
        if ($this->get('rule_id')) {
            try {
                $rule = new nc_search_rule();
                return $rule->load($this->get('rule_id'));
            }
            catch (Exception $e) {
                return null;
            }
        }
        return null;
    }

    /**
     * Входит ли данная страница в задачу по индексированию?
     * @param string $url
     * @return boolean
     */
    public function should_index($url) {
        return $this->get('area')->includes($url);
    }

    /**
     * Запрещён ли указанный путь к индексированию?
     * @param string $url
     * @return boolean
     */
    public function is_url_disallowed($url) {
        if (!$this->get('disallowed')) {
            $this->set('disallowed', $this->get_disallowed_areas());
        }
        return $this->get('disallowed')->includes($url);
    }

    /**
     *
     */
    protected function get_disallowed_areas() {
        $disallowed = array();

        // (1) robots.txt
        if (nc_search::should('CrawlerObeyRobotsTxt')) {
            $disallowed = $this->get_robots_txt_area_parts();
        }

        // (2) Settings (ExcludeUrlRegexps)
        $regexps = preg_split("/\s*\n/u", nc_search::get_setting('ExcludeUrlRegexps'), -1, PREG_SPLIT_NO_EMPTY);
        foreach ($regexps as $regexp) {
            $regexp = "@".addcslashes($regexp, "@")."@u";
            $disallowed[] = new nc_search_area_regexp(array('regexp' => $regexp));
        }

        // done
        return new nc_search_area($disallowed);
    }

    /**
     * 
     */
    protected function get_robots_txt_area_parts() {
        $disallowed = array();
        $robots_parser = new nc_search_robots;
        $all_sites_data = nc_core::get_object()->catalogue->get_all();

        foreach ($all_sites_data as $site_id => $site_data) {
            $https = (bool)$site_data['ncHTTPS'];
            $site_area = array(new nc_search_area_site(array(
                'ids' => array($site_id),
                'https' => $https,
            )));
            $robots = $robots_parser->get_directives($site_id);
            $all = array();
            foreach ($robots["allow"] as $url) {
                $all[$url] = true;
            }
            foreach ($robots["disallow"] as $url) {
                $all[$url] = false;
            }
            ksort($all);

            foreach ($all as $url => $is_excluded) {
                $url = rawurldecode($url);
                if (strpos($url, "*") !== false || strpos($url, "$") !== false) {
                    $regexp = preg_quote($url, '@');
                    $regexp = str_replace("\*", ".+", $regexp);
                    $regexp = str_replace("\\$", "$", $regexp);
                    $disallowed[] = new nc_search_area_regexp(array(
                        'regexp' => "@$regexp@u",
                        'is_excluded' => $is_excluded,
                        'sites' => $site_area,
                        'https' => $https,
                    ));
                } else {
                    $disallowed[] = new nc_search_area_page(array(
                        'url' => $url,
                        'is_excluded' => $is_excluded,
                        'include_descendants' => true,
                        'sites' => $site_area,
                        'https' => $https,
                    ));
                }
            }
        }
        return $disallowed;
    }

    /**
     *
     */
    public function save() {
        $this->set('last_activity', time());
        return parent::save();
    }

}
