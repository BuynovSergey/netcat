<?php

/**
 * $Id: corrector.php 6209 2012-02-10 10:28:29Z denis $
 */

/**
 * Базовый класс для расширений модуля, обеспечивающих исправление запросов
 * (таких, как spellchecker)
 */
abstract class nc_search_language_corrector implements nc_search_extension {

    protected $context;

    public function __construct(nc_search_context $context) {
        $this->context = $context;
    }

    /**
     * @param nc_search_language_corrector_phrase $phrase
     * @return boolean
     *   TRUE, если запрос был исправлен
     *   FALSE, если запрос не был исправлен
     */
    abstract public function correct(nc_search_language_corrector_phrase $phrase);
}
