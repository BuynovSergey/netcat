<?php

/* $Id: link.php 6209 2012-02-10 10:28:29Z denis $ */

/**
 *
 */
class nc_search_indexer_link extends nc_search_data_persistent {

    protected $properties = array(
            'id' => null,
            'url' => '',
            'is_processed' => false,
            'is_broken' => false,
    );
    protected $table_name = 'Search_Link';
    protected $mapping = array(
            'id' => 'Link_ID',
            'url' => 'URL',
            'is_processed' => 'Processed',
            'is_broken' => 'Broken',
    );

    /**
     * @param $url
     * @return string
     */
    protected function make_hash_statement($url) {
        $unhex = nc_search_util::can_use_binary_columns() ? "UNHEX" : "";
        return "$unhex(SHA1('" . nc_search_util::db_escape($url) . "'))";
    }

    /**
     * Override prepare_set_clause() to set the Hash field
     */
    protected function prepare_set_clause() {
        $set = parent::prepare_set_clause();
        $set .= ", `Hash` = " . $this->make_hash_statement($this->get('url'));
        return $set;
    }

    /**
     * Load link with the specified URL
     * @param string|array $urls
     * @return nc_search_indexer_link|FALSE
     */
    public function load_by_url($urls) {
        $values = array();
        foreach ((array)$urls as $url) {
            $values[] = $this->make_hash_statement($url);
        }
        $result = $this->select_from_database("SELECT " . $this->get_all_column_names() .
                               "  FROM `$this->table_name`" .
                               " WHERE `Hash` IN (" . join(", ", $values) . ")" .
                               " LIMIT 1");
        return $result;
    }

    /**
     * Get first link with 'is_processed'==false
     * @return nc_search_indexer_link|FALSE
     */
    public static function get_first_non_processed() {
        $link = new self();
        return $link->load_where('is_processed', false);
    }

}