<?php

/**
 * $Id: phrase.php 6209 2012-02-10 10:28:29Z denis $
 */

/**
 * Фраза для обработки корректором. Обеспечивает возможность корректировать
 * отдельные слова в фразе.
 * НЕ ПУТАТЬ С nc_search_language_corrector_quotes!!!
 */
class nc_search_language_corrector_phrase {

    protected $terms = array();
    protected $original_phrase = '';
    protected $phrase = '';
    protected $is_corrected = false;
    protected $suggestion;

    /**
     *
     */
    public function __construct($phrase) {
        $this->original_phrase = $this->phrase = $phrase;
    }

    /**
     * Получить фрагменты запроса для последующего анализа.
     * Отдельным фрагментом является отрицание или слово (логические операторы AND, OR
     * также будут элементами!)
     * @return array
     */
    public function get_terms() {
        if (!$this->terms) {
            $query_operators = "(?:\s*(?:\+|~[\d.]+|\^[\d.]+|\w+:|\(|\)|NOT\b|AND\b|OR\b|[\[\{].+?\sTO\s.+?[\]\}]))";

            preg_match_all("/(
              (?:\s*(?:-|\bNOT\s+)\"[^\"]+\") |     # отрицание в кавычках (не учитываются экранированные кавычки!)
              (?:\s*(?:-|\bNOT\s+)\([^\(]+\)) |     # отрицание в скобках (не учитываются вложенные скобки!)
              (?:\s*(?:-|\bNOT\s+)\S+)        |     # отрицание одного термина
              $query_operators                |     # спецсимволы
              [\p{L}\d`;:'\",.{}\[\]<>~\*\?]+       # какое-то [слово?; возможно в неправильной раскладке - кроме заглавной Ё]
            )/xui", $this->phrase, $matches);       # u здесь не только для красоты

            foreach ($matches[1] as $term) {
                $this->terms[] = new nc_search_language_corrector_phrase_term(array(
                                'term' => $term,
                                'is_ignored' => (preg_match("/^-|$query_operators/i", $term) || substr($term, -1) == "~"),
                        ));
            }
        }
        return $this->terms;
    }

    /**
     * Выбрать все слова, которые теоретически могут быть исправлены
     * @return array
     */
    public function get_not_corrected_terms() {
        $not_corrected_terms = array();
        foreach ($this->get_terms() as $term) {
            if (!$term->get('is_ignored') && !$term->get('corrected_term')) {
                $not_corrected_terms[] = $term;
            }
        }
        return $not_corrected_terms;
    }

    /**
     * 
     * @return string
     */
    public function to_string() {
        if ($this->is_corrected() && $this->terms) {
            $phrase = "";
            foreach ($this->terms as $term) {
                $phrase .= $term->to_string();
            }
            return trim($phrase);
        } else {
            return $this->phrase;
        }
    }

    /**
     *
     * @param string $new_phrase
     * @param string $suggestion 
     */
    public function set_phrase($new_phrase, $suggestion = null) {
        $this->is_corrected = true;
        $this->terms = array();
        $this->phrase = $new_phrase;
        $this->suggestion = $suggestion;
    }

    /**
     * 
     * @return boolean
     */
    public function is_corrected() {
        if ($this->is_corrected) {
            return $this->is_corrected;
        }
        foreach ($this->terms as $term) {
            if ($term->get('corrected_term') != '') {
                return true;
            }
        }
        return false;
    }

    /**
     *
     * @param boolean $value
     */
    public function set_is_corrected($value) {
        $this->is_corrected = $value;
    }

    /**
     * 
     * @return string
     */
    public function get_suggestion() {
        if (!$this->suggestion && $this->is_corrected()) {
            $this->suggestion = sprintf(NETCAT_MODULE_SEARCH_CORRECTION_GENERIC,
                            nc_search_util::convert($this->original_phrase),
                            nc_search_util::convert($this->to_string()));
        }
        return $this->suggestion;
    }

}