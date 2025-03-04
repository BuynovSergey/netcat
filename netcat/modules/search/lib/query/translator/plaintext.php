<?php
/**
 * Выводит структуру запроса (query_string) в текстовом виде (может использоваться
 * для отладки)
 *
 * Пример использования:
 *   $query = new nc_search_query($query_string);
 *   $query->translate(new nc_search_query_expression_dumper);
 */
class nc_search_query_translator_plaintext extends nc_search_query_translator {
    /**
     * @var int
     */
    protected $indent = 0;

    /**
     * @param $string
     */
    protected function print_line($string) {
        print str_repeat("  ", $this->indent) . $string . "\n";
    }

    /**
     * @param nc_search_query_expression_composite $expression
     */
    protected function print_items(nc_search_query_expression_composite $expression) {
        $this->indent++;
        $this->translate_items($expression);
        $this->indent--;
    }

    /**
     * @param nc_search_query_expression $expression
     * @return string
     */
    protected function get_modifiers(nc_search_query_expression $expression) {
        $modifiers = array();

        $getters = preg_grep("/^(?:get|is)_/", get_class_methods($expression));
        foreach ($getters as $getter) {
            if ($getter == "get_value") { continue; }
            $name = str_replace("get_", "", $getter);
            $value = $expression->$getter();
            if (is_scalar($value) && $value != null && strlen($value)) {
                $modifiers[] = "$name=$value";
            }
        }
        if (count($modifiers)) {
            return " (" . join(', ', $modifiers) . ")";
        }

        return "";
    }

    /**
     * @param string $type
     * @param nc_search_query_expression_composite $expression
     */
    protected function print_boolean($type, nc_search_query_expression_composite $expression) {
        $this->print_line("$type (" . $this->get_modifiers($expression));
        $this->print_items($expression);
        $this->print_line(")");
    }

    /**
     * @param nc_search_query_expression_and $expression
     * @return void
     */
    protected function translate_and(nc_search_query_expression_and $expression) {
        $this->print_boolean("AND", $expression);
    }

    /**
     * @param nc_search_query_expression_or $expression
     * @return void
     */
    protected function translate_or(nc_search_query_expression_or $expression) {
        $this->print_boolean("OR", $expression);
    }

    /**
     * @param nc_search_query_expression_not $expression
     * @return void
     */
    protected function translate_not(nc_search_query_expression_not $expression) {
        $this->print_boolean("NOT", $expression);
    }

    /**
     * @param nc_search_query_expression_term $expression
     * @return void
     */
    protected function translate_term(nc_search_query_expression_term $expression) {
        $this->print_line('TERM "' . $expression->get_value() . '"' . $this->get_modifiers($expression));
    }

    /**
     * @param nc_search_query_expression_wildcard $expression
     * @return void
     */
    protected function translate_wildcard(nc_search_query_expression_wildcard $expression) {
        $this->print_line('WILDCARD "' . $expression->get_value() . '"' . $this->get_modifiers($expression));
    }

    /**
     * @param nc_search_query_expression_fuzzy $expression
     * @return void
     */
    protected function translate_fuzzy(nc_search_query_expression_fuzzy $expression) {
        $this->print_line('FUZZY "' . $expression->get_value() . '"' . $this->get_modifiers($expression));
    }

    /**
     * @param nc_search_query_expression_phrase $expression
     * @return void
     */
    protected function translate_phrase(nc_search_query_expression_phrase $expression) {
        $terms = array();
        foreach ($expression->get_items() as $term) { $terms[] = $term->get_value(); }
        $string = 'PHRASE "' . join(" ", $terms) . '"' . $this->get_modifiers($expression);
        $this->print_line($string);
    }

    /**
     * @param nc_search_query_expression_interval $expression
     * @return void
     */
    protected function translate_interval(nc_search_query_expression_interval $expression) {
        $terms = array();
        $brackets = ($expression->get_type() == "inclusive" ? "[]" : "{}");
        foreach ($expression->get_items() as $term) { $terms[] = $term->get_value(); }
        $string = "INTERVAL $brackets[0]" . join("; ", $terms) . $brackets[1] .
                  $this->get_modifiers($expression);
        $this->print_line($string);
    }

    /**
     * @param nc_search_query_expression_empty $expression
     * @return mixed
     */
    protected function translate_empty(nc_search_query_expression_empty $expression) {
        $this->print_line("EMPTY");
    }
}