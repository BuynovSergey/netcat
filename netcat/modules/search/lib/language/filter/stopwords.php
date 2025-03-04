<?php

/* $Id: stopwords.php 6209 2012-02-10 10:28:29Z denis $ */

/**
 * Фильтр стоп-слов
 */
class nc_search_language_filter_stopwords extends nc_search_language_filter {

    /** @var $stop_list nc_search_data_persistent_collection[] */
    static protected $lists = array();

    /**
     * @param array $terms
     * @return array
     */
    public function filter(array $terms) {
        if (!nc_search::should('RemoveStopwords')) {
            return $terms;
        }

        $language = $this->context->get('language');

        if (!isset(self::$lists[$language])) {
            $query = "SELECT * FROM `%t%` WHERE `Language`='".nc_search_util::db_escape($language)."'";
            self::$lists[$language] = nc_search::load('nc_search_language_stopword', $query, 'word');
        }

        $stop_list = self::$lists[$language];
        if (!count($stop_list)) { return $terms; }

        $result = array();
        foreach ($terms as $term) {
            if (is_array($term)) { // alternative forms
                foreach ($term as $i => $t) {
                    if ($stop_list->has_key($t)) { unset($term[$i]); }
                }
                $terms_left = count($term);
                if ($terms_left == 1) { $result[] = nc_array_value($term, 0); }
                elseif ($terms_left > 1) { $result[] = $term; }
            }
            elseif (!$stop_list->has_key($term)) { // ordinary term
                $result[] = $term;
            }
        }

        return $result;
    }

}
