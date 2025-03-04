<?php

/**
 * Запрос к поисковой системе
 */
class nc_search_query extends nc_search_data_persistent {

    protected $table_name = "Search_Query";
    /**
     * Дополнительные параметры запроса
     */
    protected $properties = array(
            'query_string' => '', // поисковая строка, указанная пользователем
            'area' => null, // область поиска (строка)
            'modified_after' => null, // время изменения, не ранее
            'modified_before' => null, // время изменение, не позднее
            'language' => null, // язык запроса и результатов
            'sort_by' => null, // сортировка по полю
            'sort_direction' => SORT_DESC,
            'limit' => 10, // количество результатов
            'offset' => 0, // первый результат (с ноля) [=curPos]
            // какие свойства документа следует загрузить (получить)
            // (может быть полезно, если не нужно загружать весь документ)
            'options_to_fetch' => array(),
            // эти свойства не имеют прямого отношения к собственно запросу,
            // используются при сохранении результата в журнал запросов
            'id' => null,
            'site_id' => null,
            'results_count' => 0,
            'user_ip' => null,
            'user_id' => null,
    );
    /**
     * 
     */
    protected $mapping = array(
            'query_string' => 'QueryString',
            'area' => 'Area',
            'language' => 'Language',
            'id' => 'Query_ID',
            'site_id' => 'Catalogue_ID',
            'results_count' => 'ResultsCount',
            'user_ip' => 'IP',
            'user_id' => 'User_ID',
    );
    /**
     * Шаблоны для экранирования спецсимволов в запросах
     * @see self::escape_disabled_options()
     */
    protected $escape_patterns = array(
            'AllowTermBoost' => '/(\pL)(\^[\d\.]+)/u',
            'AllowProximitySearch' => '/(")(~[\d\.]+)/',
            'AllowWildcardSearch' => '/(.?)([\*\?])/',
            'AllowRangeSearch' => '/(.?)([\[\]{}])/',
            'AllowFuzzySearch' => '/([^"])(~[\d\.]*)/',
            'AllowFieldSearch' => '/(\w+)(:)/',
    );

    /**
     *
     * @param string $string
     */
    public function __construct($string) {
        $this->set('query_string', (string)$string);
    }

    /**
     *
     * @return string
     */
    protected function prepare() {
        $query = $this->escape_special_characters($this->get('query_string'));
        return $query;
    }

    /**
     *
     * @param string $query
     * @return string
     */
    protected function escape_special_characters($query) {
        foreach ($this->escape_patterns as $allow_feature => $pattern) {
            if (!nc_search::should($allow_feature)) {
                $query = preg_replace($pattern, '$1', $query);
            }
        }
        return $query;
    }

    /**
     *
     * @return string
     */
    public function to_string() {
        return $this->prepare();
    }

    /**
     * Возвращает выражение nc_search_query_expression, полученное при парсинге
     * опции 'query_string' данного запроса
     * @return nc_search_query_expression
     */
    public function parse() {
        $parser = new nc_search_query_parser();
        return $parser->parse($this->get('query_string'));
    }

    /**
     * @param nc_search_query_translator $translator
     * @return mixed
     */
    public function translate(nc_search_query_translator $translator) {
        return $translator->translate($this);
    }

}