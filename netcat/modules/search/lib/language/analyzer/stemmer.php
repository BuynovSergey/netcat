<?php

/* $Id: stemmer.php 6209 2012-02-10 10:28:29Z denis $ */

/**
 * Base class for a stemmer.
 * Concrete class must implement stem() method
 */
abstract class nc_search_language_analyzer_stemmer extends nc_search_language_analyzer {

    /**
     * Предполагаемая максимальная длина убираемого окончания слова (используется
     * в $this->get_highlight_regexp для того, чтобы не подсвечивать сильно
     * отличающиеся слова, например «вертикально» по запросу со словом «в»)
     * @var int
     */
    protected $max_remainder_length = 5;

    /**
     * @param string $term
     * @return string term after the stemming
     */
    abstract public function stem($term);

    /**
     *
     * @param array $terms
     * @return array
     */
    public function get_base_forms(array $terms) {
        $result = array_map(array($this, 'stem'), $terms);
        return $result;
    }

    /**
     *
     * @param array $terms
     * @return string
     */
    public function get_highlight_regexp(array $terms) {
        $res = array();
        foreach ($this->get_base_forms($terms) as $base) {
            $res[] = $base."[\pL\d]{0,$this->max_remainder_length}";
        }
        return nc_search_util::word_regexp("(".join("|", $res).")", "Si");
    }

}