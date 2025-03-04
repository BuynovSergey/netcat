<?php

/* $Id: collection.php 5700 2011-11-17 07:33:57Z gaika $ */
/**
 * Работа со ссылками, обрабатываемыми в текущей задаче.
 */
class nc_search_indexer_link_manager {

    static protected $url_parts = array('scheme'=>0,'user'=>0,'pass'=>0,'host'=>0,'port'=>0,'path'=>0,'query'=>0,'fragment'=>0);

    /**
     * Конструктор и вызываемый в нём метод убрать после отказа от поддержки MySQL 4.1.х
     * @param bool
     */
    public function __construct() {
        $this->check_hash_column_type();
    }

    /**
     * Убрать после отказа от поддержки MySQL 4.1.x
     */
    protected function check_hash_column_type() {
        static $hash_column_type_checked = false;
        if (!$hash_column_type_checked) {
            $hash_length = nc_search_util::can_use_binary_columns() ? "20" : "40";
            /** @var nc_db $db */
            $db = nc_core('db');
            $hash_column_definition = $db->get_row("SHOW COLUMNS FROM `Search_Link` LIKE 'Hash'", ARRAY_A);
            if (!strpos($hash_column_definition['Type'], $hash_length)) {
                $db->query("ALTER TABLE `Search_Link` CHANGE `Hash` `Hash` BINARY($hash_length) NOT NULL");
            }
            $hash_column_type_checked = true;
        }
    }


    /**
     * @param string $url
     * @return array|bool
     */
    protected function parse_utf8_url($url) {
        if (mb_check_encoding($url, "ASCII")) { return parse_url($url); }
        $regexp = '~^
                        (
                            (
                                (?P<scheme>\w+)://    # with scheme (e.g. http://)
                                |
                                //                    # without scheme
                            )
                            (?:
                                (?P<user>[^:]+)
                                :
                                (?P<pass>[^@]+)
                                @
                            )?
                            (?P<host>[^/?:#]*)
                            (:(?P<port>\d+))?
                        )?
                        (?P<path>[^?#]*)
                        (\?(?P<query>[^#]*))?
                        (\#(?P<fragment>.*))?
                    ~ux';
        if (is_string($url) && preg_match($regexp, $url, $matches)) {
            foreach ($matches as $key => $value) {
                if (!isset(self::$url_parts[$key]) || empty($value)) { unset($matches[$key]); }
            }
            return $matches;
        }
        return false;
    }


    /**
     *
     * @param array $urls
     * @param string $referrer
     * @return array array with link IDs
     */
    public function add_links(array $urls, $referrer = null) {
        $link_ids = array();
        foreach ($urls as $url) {
            $link_ids[] = $this->add_link($url, $referrer);
        }
        return $link_ids;
    }

    /**
     * Возвращает ID ссылки с указанным URL; создает объект nc_search_indexer_link
     * при необходимости
     * @param string $url
     * @param string  $referrer
     * @return integer Link ID
     */
    public function add_link($url, $referrer = null) {
        $full_url = $this->resolve_link($url, $referrer);

        // считать URL’ы с "www." и без него синонимами (просто выкинуть "www.",
        // к сожалению, нельзя — не у всех правильно настроен сервер)
        $has_www = strpos($full_url, "://www.");
        $full_url_with_www = $has_www ? $full_url : str_replace("://", "://www.", $full_url);
        $full_url_without_www = $has_www ? str_replace("://www.", "://", $full_url) : $full_url;

        $link = new nc_search_indexer_link();

        // search for link with that URL in the database, create new if it's not there
        if (!$link->load_by_url(array($full_url_with_www, $full_url_without_www))) {
            // it's a brand new link
            $link->set("url", $full_url);
            $link->save();

            if (nc_search::will_log(nc_search::LOG_PARSER_DOCUMENT_LINKS)) {
                nc_search::log(nc_search::LOG_PARSER_DOCUMENT_LINKS,
                              "Added link to the queue: ".nc_search_util::decode_url($full_url));
            }
        }

        return $link->get_id();
    }

    /**
     * Получить абсолютный URL
     * @param string $href
     * @param string $referrer
     * @return string
     * @throws nc_search_exception
     */
    protected function resolve_link($href, $referrer = null) {
        $referrer_parts = $this->parse_utf8_url($referrer);

        // Абсолютная ссылка без указания протокола — неправильно обрабатывается
        // функцией parse_url() до PHP 5.4.7
        if (substr($href, 0, 2) == "//") {
            $scheme = isset($referrer_parts['scheme']) ? $referrer_parts['scheme'] : 'http';
            $href = "$scheme:$href";
        }

        $href_parts = $this->parse_utf8_url($href);
        if (!is_array($href_parts)) { $href_parts = array(); } // $href == "#"
        $result_parts = $href_parts;


        if (!isset($href_parts["host"])) { // path with no host name
            if ($referrer == 'http:///') {
                return false;
            }
            if ($referrer == 'http://') {
                return false;
            }
            if (!$referrer_parts || !isset($referrer_parts["host"])) {
                throw new nc_search_exception("Cannot resolve full URL: '$href' (no referrer)");
            }

            foreach (array("scheme", "host", "port", "path") as $p) {
                if (isset($referrer_parts[$p]) && !isset($href_parts[$p])) {
                    $result_parts[$p] = $referrer_parts[$p];
                }
            }

            if ($result_parts["path"][0] != "/") { // relative path
                $referrer_dir = (substr($referrer_parts["path"], -1) == '/') ? // referrer path ends with '/'
                        $referrer_parts["path"] :
                        dirname($referrer_parts["path"])."/";
                $result_parts["path"] = $referrer_dir.$result_parts["path"];
            }
        } // end of "path with no host name"
        // "http://mysite.org" → "http://mysite.org/"
        if (!isset($result_parts["path"])) {
            $result_parts["path"] = "/";
        }

        // get rid of "./", "../"
        if (strpos($result_parts["path"], "./") !== false) {
            $path_fragments = array();
            foreach (explode("/", $result_parts["path"]) as $part) {
                if ($part == '.' || $part == '') {
                    continue;
                }
                if ($part == '..') {
                    array_pop($path_fragments);
                } else {
                    $path_fragments[] = $part;
                }
            }
            $path = join("/", $path_fragments);
            if (substr($href_parts["path"], -1) == '/') {
                $path .= "/";
            }
            if ($path[0] != '/') {
                $path = "/$path";
            }
            $result_parts["path"] = $path;
        }

        // Производится сортировка параметров для того, чтобы не запрашивать страницу
        // дважды, если в ссылках на неё параметры перечислены в разном порядке, например:
        // /sub/?tag=22&curPos=10 и /sub/?curPos=10&tag=22 будут считаться одной страницей
        // Параметр модуля: IndexerNormalizeLinks
        if (isset($result_parts["query"]) && strpos($result_parts["query"], "&") && nc_search::should('IndexerNormalizeLinks')) {
            $params = explode("&", $result_parts["query"]);
            sort($params);
            $result_parts["query"] = join("&", $params);
        }

        // IDN & non-latin paths
        $result_parts["host"] = nc_search_util::encode_host($result_parts["host"]);
        $result_parts["path"] = nc_search_util::encode_path($result_parts["path"]);

        // MySite.ORG == mysite.org
        $result_parts["host"] = strtolower($result_parts["host"]);

        $full_url = strtolower($result_parts["scheme"])."://".
                $result_parts["host"].
                (isset($result_parts["port"]) ? ":$result_parts[port]" : "").
                $result_parts["path"].
                (isset($result_parts["query"]) ? "?$result_parts[query]" : "");

        return $full_url;
    }

    /**
     * Возвращает следующую необработанную ссылку
     * @return nc_search_indexer_link|FALSE
     */
    public function get_next_link() {
        return nc_search_indexer_link::get_first_non_processed();
    }

    /**
     * For logging purposes
     * @return array
     */
    public function get_all_urls() {
        $db = nc_Core::get_object()->db;
        $links = $db->get_col("SELECT `URL` FROM `Search_Link`");
        foreach ($links as $i => $url) {
            $links[$i] = nc_search_util::decode_url($url);
        }
        return $links;
    }

}