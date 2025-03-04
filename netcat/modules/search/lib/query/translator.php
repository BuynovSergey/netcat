<?php
/**
 *
 */
abstract class nc_search_query_translator {

    /**
     * @param nc_search_query $query
     * @return mixed
     */
    public function translate(nc_search_query $query) {
        return $this->dispatch_translate($query->parse());
    }

    /**
     * Диспетчер, вызывающий соответствующий типу выражения метод translate_TYPE()
     * @param nc_search_query_expression $expression
     * @return mixed
     */
    protected function dispatch_translate(nc_search_query_expression $expression) {
        $method = str_replace("nc_search_query_expression_", "translate_", get_class($expression));
        return $this->$method($expression);
    }

    /**
     * Обход подвыражений составного выражения
     * @param nc_search_query_expression_composite $expression
     * @return array
     */
    protected function translate_items(nc_search_query_expression_composite $expression) {
        $result = array();
        foreach ($expression->get_items() as $item) {
            $result[] = $this->dispatch_translate($item);
        }
        return $result;
    }

    /**
     * @param nc_search_query_expression_and $expression
     * @return mixed
     */
    abstract protected function translate_and(nc_search_query_expression_and $expression);

    /**
     * @param nc_search_query_expression_or $expression
     * @return mixed
     */
    abstract protected function translate_or(nc_search_query_expression_or $expression);

    /**
     * @param nc_search_query_expression_not $expression
     * @return mixed
     */
    abstract protected function translate_not(nc_search_query_expression_not $expression);

    /**
     * @param nc_search_query_expression_term $expression
     * @return mixed
     */
    abstract protected function translate_term(nc_search_query_expression_term $expression);

    /**
     * @param nc_search_query_expression_wildcard $expression
     * @return mixed
     */
    abstract protected function translate_wildcard(nc_search_query_expression_wildcard $expression);

    /**
     * @param nc_search_query_expression_fuzzy $expression
     * @return mixed
     */
    abstract protected function translate_fuzzy(nc_search_query_expression_fuzzy $expression);

    /**
     * @param nc_search_query_expression_phrase $expression
     * @return mixed
     */
    abstract protected function translate_phrase(nc_search_query_expression_phrase $expression);

    /**
     * @param nc_search_query_expression_interval $expression
     * @return mixed
     */
    abstract protected function translate_interval(nc_search_query_expression_interval $expression);

    /**
     * @param nc_search_query_expression_empty $expression
     * @return mixed
     */
    abstract protected function translate_empty(nc_search_query_expression_empty $expression);
}