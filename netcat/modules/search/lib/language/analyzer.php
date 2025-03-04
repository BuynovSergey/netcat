<?php

/* $Id: analyzer.php 6209 2012-02-10 10:28:29Z denis $ */

/**
 * Классы для анализа слов (получение базовой формы и регвыра для подсветки в
 * результатах)
 */
abstract class nc_search_language_analyzer implements nc_search_extension {

    /**
     * @var nc_search_context
     */
    protected $context;

    public function __construct(nc_search_context $context) {
        $this->context = $context;
    }

    abstract public function get_base_forms(array $terms);

    abstract public function get_highlight_regexp(array $terms);

    /**
     * Проверить слово по словарю, если таковой имеется.
     * @param $word
     * @return boolean|array
     *   TRUE, если слово есть в словаре ИЛИ анализатор не имеет словаря
     *   FALSE, если слова нет в словаре И анализатор не имеет функции исправления
     *      (spelling correction) ИЛИ невозможно определить правильную форму слова
     *   ARRAY, если имеются варианты исправления слова (хотя бы один)
     */
    public function check_word($word) {
        return true;
    }

}
