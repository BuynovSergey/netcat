<?php

/**
 * $Id: quotes.php 6209 2012-02-10 10:28:29Z denis $
 */

/**
 * 
 */
class nc_search_language_corrector_quotes extends nc_search_language_corrector {

    /**
     * Пытается убрать кавычки из запроса
     * @param nc_search_language_corrector_phrase $phrase
     * @return boolean
     */
    public function correct(nc_search_language_corrector_phrase $phrase) {
        if (!nc_search::should('RemovePhrasesOnEmptyResult')) {
            return false;
        }

        $orignal_phrase_text = $phrase_text = $phrase->to_string();
        if (strpos($phrase_text, '"') !== false && !preg_match('/"\S+"/u', $phrase_text)) {
            $phrase_text = preg_replace('/"~[\d\.]+/', '"', $phrase_text); // remove distance search
            if (nc_search_util::is_boolean_query($phrase_text) || preg_match('/[-+]/', $phrase_text)) {
                // there is a a phrase with several words!
                $phrase_text = preg_replace('/"(\S)/u', "($1", $phrase_text);
                $phrase_text = str_replace('"', ")", $phrase_text);
            } else {
                $phrase_text = str_replace('"', "", $phrase_text);
            }
            $message = sprintf(NETCAT_MODULE_SEARCH_CORRECTION_QUOTES, $orignal_phrase_text, $phrase_text);
            $phrase->set_phrase($phrase_text, $message);
            return true;
        }
        return false;
    }

}