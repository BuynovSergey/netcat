<?php

/**
 * $Id: term.php 6209 2012-02-10 10:28:29Z denis $
 */

/**
 * 
 */
class nc_search_language_corrector_phrase_term extends nc_search_data {

    protected $properties = array(
            'term' => '',
            'corrected_term' => '',
            'is_incorrect' => false,
            'is_ignored' => false, // если не термин является не словом, а спец-символом в запросе (или выражением отрицания)
    );

    /**
     * Возвращает исправленное (или оригинальное) слово
     * @return string
     */
    public function to_string() {
        // пробел понадобится в phrase->get_phrase()
        $space = ($this->get('is_ignored')) ? '' : ' ';
        if (strlen($this->get('corrected_term'))) {
            return $space.$this->get('corrected_term');
        }
        return $space.$this->get('term');
    }

    /**
     * 
     */
    public function set_corrected($corrected_term) {
        return $this->set('corrected_term', $corrected_term)->set('is_incorrect', false);
    }

}