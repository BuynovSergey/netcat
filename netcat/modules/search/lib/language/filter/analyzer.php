<?php

/* $Id: analyzer.php 6209 2012-02-10 10:28:29Z denis $ */

/**
 * Заменяет слова на их базовые формы
 */
class nc_search_language_filter_analyzer extends nc_search_language_filter {

    /**
     * @var array of nc_search_language_analyzer
     */
    static protected $analyzers = array();

    /**
     * @return nc_search_extension_chain
     */
    protected function get_analyzers() {
        $index = $this->context->get('language')."__".$this->context->get('action');
        if (!isset(self::$analyzers[$index])) {
            self::$analyzers[$index] = nc_search_extension_manager::get('nc_search_language_analyzer', $this->context);
        }
        return self::$analyzers[$index];
    }

    /**
     *
     * @param array $terms
     * @return array
     */
    public function filter(array $terms) {
        return $this->get_analyzers()->apply('get_base_forms', $terms);
    }

}