<?php

/* $Id: synonyms.php 6209 2012-02-10 10:28:29Z denis $ */

/**
 * Добавление синонимов
 */
class nc_search_language_filter_synonyms extends nc_search_language_filter {

    static protected $lists = array();

    protected function load_synonyms($language) {
        $query = "SELECT * FROM `%t%` WHERE `Language`='".nc_search_util::db_escape($language)."'";
        self::$lists[$language] = nc_search::load('nc_search_language_synonyms', $query);
    }

    public function filter(array $terms) {
        $language = $this->context->get('language');

        if (!isset(self::$lists[$language])) {
            $this->load_synonyms($language);
        }
        if (!sizeof(self::$lists[$language])) {
            return $terms;
        } // nothing to do

        $result = $terms;
        // this might be quite slow on a big synonym dictionary... place for further optimization
        foreach ($terms as $term) {
            foreach (self::$lists[$language] as $entry) {
                $synonyms = $entry->get('words'); // array
                if (in_array($term, $synonyms)) {
                    $result = array_merge($result, $synonyms);
                    break; // use the first match only
                }
            }
        }

        return $result;
    }

}
