<?php
/**
 *
 */
class nc_search_query_parser {

    /**
     * @var string "AND", "OR"
     */
    protected $default_operator;

    /**
     * @var string   Какие символы могут быть частью терминов (зависит от настройки IgnoreNumbers)
     */
    protected $term_chars = "\pL\d";

    /**
     * @var boolean
     */
    protected $ignore_numbers = false;

    /**
     * @param string $default_operator  "AND"|"OR" (case-sensitive); defaults
     *   to the 'DefaultBooleanOperator' setting.
     * @param bool $ignore_numbers    defaults to 'IgnoreNumbers'
     */
    public function __construct($default_operator = null, $ignore_numbers = null) {
        if (!$default_operator || ($default_operator != "AND" && $default_operator != "OR")) {
            $default_operator = nc_search::get_setting("DefaultBooleanOperator");
        }
        $this->default_operator = $default_operator;

        if ($ignore_numbers === null) { $ignore_numbers = nc_search::should('IgnoreNumbers'); }
        if ($ignore_numbers) { $this->term_chars = "\pL"; }
        $this->ignore_numbers = $ignore_numbers;
    }

    /**
     *
     * @param string $query_string
     * @param boolean $is_recursive_call
     * @return nc_search_query_expression
     */
    public function parse($query_string, $is_recursive_call = false) {
        if (!$is_recursive_call) {
            // change string encoding to UTF-8 or ensure it's not broken if it is
            // already UTF-8
            $query_string = mb_convert_encoding($query_string, 'UTF-8', nc_Core::get_object()->NC_CHARSET);
        }

        /*
         * LEXEMES
         *
         * simple/terminal:
         *   term
         *   wildcard*
         *   wildcard?
         *
         * group (inside):
         *   (a b)   -- essentially "a AND b" or "a OR b"
         *   "a b"
         *
         * group (left and right)
         *   AND  &&
         *   OR   ||
         *   [a TO b]
         *   {a TO b}
         *
         * (implicit AND or OR)
         *
         * wrap following expression:
         *   NOT  !
         *
         * modify next expression:
         *   field_name:
         *   +
         *   -    (must be preceded with a whitespace if not at the beginning of the string)
         *
         * modify previous expression:
         *   ^2
         *   ~0.5  (for term: fuzzy search)     --- extracted with the preceding term
         *   ~2    (for phrase: proximity search)
         *
         * special rules:
         *   - terms with both letters and numbers are considered a phrase:
         *       x123y567z → phrase("x 123 y 567 z")
         *       inside quotes: "price usd50" → phrase("price usd 50")
         *   - decimal fractions are considered a phrase:
         *       0.123 → phrase("0 123")
         *       "price 0.12" → phrase("price 0 12")
         */

        $query_remainder = $query_string; // part of the query string that is not parsed yet
        $root = null; // result of the parsing
        $previous = null; // previous expression
        $operator = $this->default_operator; // joining operator ("AND", "OR")

        $previous_was_group = false;
        $next_not = $next_required = $next_excluded = false; // modifiers for the upcoming token
        $next_field_name = null; // field name modifier

        while (true) {
            $expression = null;

            $token = $this->remove_next_token($query_remainder);
            if ($token === null) { break; }

            // ----- make sense of the received token:

            if ($token == "(") { // start of the group?
                $expression = $this->remove_group($query_remainder); //may return null if parentheses are not balanced
                if ($expression) { $previous_was_group = true; }
            }
            elseif ($token == '"') { // phrase?
                $expression = $this->remove_phrase($query_remainder); // may return null if not a phrase
            }
            elseif (($token == "[" || $token == "{") && nc_search::should('AllowRangeSearch')) {
                // can be an interval
                $expression = $this->remove_interval($query_remainder, $token); // may return null if not an interval
            }
            elseif (substr($token, -1) == ":" && nc_search::should('AllowFieldSearch')) {
                // field name!
                $next_field_name = substr($token, 0, -1);
            }
            elseif ($token == "+") { // "required" sign (not same as AND if default operator is OR)
                $next_required = true;
            }
            elseif (($token == "-" && !$previous) || (strlen($token) > 1 && trim($token) == "-")) {
                // (a) "excluded" sign at the beginning of the query (not same as NOT if default operator is OR)
                // (b) "excluded" sign elsewhere (separated by the space)
                $next_excluded = true;
            }
            elseif ($token == "!" || $token == "NOT") { // boolean operators are case-sensitive
                $next_not = true; // wrap next item inside NOT
            }
            elseif ($token == "&&" || $token == "AND") {
                $operator = "AND";
            }
            elseif ($token == "||" || $token == "OR") {
                $operator = "OR";
            }
            elseif (strpos($token, "~") > 0 && preg_match("/^[{$this->term_chars}]+~/u", $token)) {
                // fuzzy search
                list($term, $similarity) = explode("~", $token); // decimal value ("0.5")
                if (nc_search::should('AllowFuzzySearch')) {
                    $expression = new nc_search_query_expression_fuzzy($term, $similarity);
                }
                else {
                    $expression = new nc_search_query_expression_term($term);
                }
            }
            elseif ($token[0] == "~" && nc_search::should('AllowProximitySearch')) {
                // phrase word distance option
                $value = substr($token, 1);  // integer value
                if ($previous instanceof nc_search_query_expression_phrase) {
                    $previous->set_distance($value);
                }
                // no fallback, throw the token out
            }
            elseif ($token[0] == "^" && nc_search::should('AllowTermBoost')) {
                // term and phrase boost
                $value = substr($token, 1);   // integer or decimal value
                if ($previous instanceof nc_search_query_expression_term || $previous instanceof nc_search_query_expression_phrase) {
                    $previous->set_boost($value);
                }
                // no fallback, just discard (complicated: decimal value can result in two terms)
            }
            elseif ((strpos($token, "*") || strpos($token, "?")) && nc_search::should('AllowWildcardSearch')) {
                // wildcard; can't be the first symbol
                $expression = new nc_search_query_expression_wildcard($token);
            }
            elseif ($this->ignore_numbers && preg_match("/\d/", $token)) {
                // reset field flag (e.g.: <price:50 term>)
                $next_field_name = null;
            }
            elseif (ctype_digit($token) && preg_match("/^\.(\d+)\b/", $query_remainder, $match)) {
                // special case: decimal fractions
                $fraction = $match[1];
                $query_remainder = substr($query_remainder, strlen($fraction)+1);
                $expression = new nc_search_query_expression_phrase(array($token, $fraction));
                // TODO? можно помечать такие фразы, чтобы транслировать их в FTS-фразы, а не в REGEXP-выражения
            }
            elseif (preg_match("/^[{$this->term_chars}]+$/u", $token)) {
                // special case: treat terms with both letters and numbers as a phrase
                if (preg_match("/\d/", $token)) {
                    $parts = preg_split("/(\d+)/", $token, -1, PREG_SPLIT_DELIM_CAPTURE|PREG_SPLIT_NO_EMPTY);
                    $expression = sizeof($parts) == 1
                                    ? new nc_search_query_expression_term($parts[0])
                                    : new nc_search_query_expression_phrase($parts);
                }
                else {
                    $expression = new nc_search_query_expression_term($token);
                }
            }
            else { // discard unknown tokens
                continue;
            }

            // -----
            // process next token if current token didn't produce an expression
            if (!$expression) { continue; }
            // -----

            // set expression flags / options
            $expression->set_field($next_field_name)
                       ->set_required($next_required)
                       ->set_excluded($next_excluded);

            // reset flags
            $next_field_name = null;
            $next_required = $next_excluded = false;

            if ($next_not) { // wrap inside NOT()
                $expression = new nc_search_query_expression_not($expression);
                $next_not = false;
            }

            // store expression in the $root tree
            if ($root == null) { // first item
                $root = $expression;
            }
            else { // not a first item
                if ($root instanceof nc_search_query_expression_or) {
                    if ($operator == "OR") { // OR+OR=OR
                        $root->add_item($expression);
                    }
                    elseif ($previous_was_group) { // (one OR two) AND three
                        $root = $this->create_boolean($operator, $root, $expression);
                    }
                    else { // replace last item in OR with an AND expression
                        // (t1 OR t2 AND t3) → OR(t1, AND(t2, t3))
                        // (t1 OR t2 AND t3 AND t4) → OR(t1, AND(t2, t3, t4))
                        $root->conjunct_last($expression);
                    }
                }
                elseif ($root instanceof nc_search_query_expression_and && $operator == "AND") {
                    $root->add_item($expression); // AND+AND=AND
                }
                else { // (root=AND && operator=OR) --or-- (root is not boolean)
                    // (t1 AND t2 OR t3) → OR(AND(t1, t2), t3)
                    $root = $this->create_boolean($operator, $root, $expression);
                }

                // reset flag
                $previous_was_group = false;
            }

            // reset $operator:
            $operator = $this->default_operator;
            // remember previous expression:
            $previous = $expression;

        } // of "while tokens are coming"

        return $root ? $root : new nc_search_query_expression_empty;
    }

    /**
     * Получает первый в строке токен, убирает его из переданной строки
     * @param string $string  NB: by ref
     * @return string|null
     */
    protected function remove_next_token(&$string) {
        if (!strlen($string)) { return null; }

        $term = $this->term_chars;
        $parts = preg_split("/(                    # capture all ‘delimiters’
                                \s+\-              # exclude expression (must be separated by a space)
                                | \s+              # whitespace
                                | &&               # alternative for AND
                                | \|\|             # alternative for OR
                                | \^\d+(?:\.\d+)?  # term boost? (integer or decimal)
                                | [$term]+~(?:0\.\d+)?   # fuzzy search -> returned with the term: ‘term~0.5’
                                | ~\d+             # proximity search?
                                | \w+:             # field search
                                | [\[\]{}()\"!+\-] # (can’t insert comments inside [], sorry)
                                | [^$term\*\?]+    # anything not term-like
                             )/Sux",
                            $string,
                            2,
                            PREG_SPLIT_DELIM_CAPTURE|PREG_SPLIT_NO_EMPTY);
        $token = array_shift($parts);
        $string = join('', $parts);

        if (ctype_space($token) || $token == '') { // skip whitespace and empty tokens right away
            // space and non-term characters couldn't be removed by excluding it
            // from the capture group of the split regexp above because otherwise
            // it would be impossible to do join(''); and the join is required
            // because $limit parameter doesn't work as intended when
            // PREG_SPLIT_DELIM_CAPTURE flag is set
            return $this->remove_next_token($string);
        }

        return $token;
    }


    /**
     * Убирает из строки (без открывающейся скобки) группу, возвращает распарсенную
     * группу. Если закрывающейся скобки нет, возвращает NULL.
     * @param string $string  NB: by ref
     * @return nc_search_query_expression|null
     */
    protected function remove_group(&$string) {
        $num_opened_brackets = 1;
        $length = strlen($string);
        $found_closing_bracket = false;
        // search for the group end
        for ($pos = 0; $pos < $length; $pos++) {
            if ($string[$pos] == "\\") { // simple case of character escaping
                $pos++;
                continue;
            }

            if ($string[$pos] == "(") {
                $num_opened_brackets++;
            }
            elseif ($string[$pos] == ")") {
                $num_opened_brackets--;
                if ($num_opened_brackets == 0) {
                    $found_closing_bracket = true;
                    break;
                }
            }
        } // end of "for each character in the string"

        if ($found_closing_bracket && $pos > 1) {
            $group_string = substr($string, 0, $pos);
            $string = substr($string, $pos);
            return $this->parse($group_string, true);
        }

        return null;
    }


    /**
     * Убирает из строки (без открывающей кавычки) фразу. Если нет закрывающей
     * кавычки, возвращает NULL.
     * @param $string
     * @return nc_search_query_expression_phrase|null
     */
    protected function remove_phrase(&$string) {
        if (preg_match('/^([^"]+)"(.*)/', $string, $matches)) {
            $string = $matches[2]; // remove the phrase from the $string
            // remove numbers if needed
            $splitter = ($this->ignore_numbers) ? "/\PL+/u" : "/(\d+)|\PL/u"; // not \PL+!
            $parts = preg_split($splitter, $matches[1], -1, PREG_SPLIT_DELIM_CAPTURE|PREG_SPLIT_NO_EMPTY);
            return new nc_search_query_expression_phrase($parts);
        }
        return null;
    }

    /**
     * Убирает из строки интервал и возвращает его в виде nc_search_query_interval.
     * Если в начале строки нет интервала (без открывающейся скобки), возвращает NULL.
     * @param $string
     * @param $opening_bracket
     * @return nc_search_query_expression_interval|null
     */
    protected function remove_interval(&$string, $opening_bracket) {
        if ($opening_bracket == "{") {
            $closing_bracket = "\}";
            $interval_type = "exclusive";
        }
        else {
            $closing_bracket = "\]";
            $interval_type = "inclusive";
        }
        $word = "[{$this->term_chars}\.]+";
        if (preg_match("/^($word)\s+TO\s+($word)$closing_bracket(.*)$/u", $string, $matches)) {
            $string = $matches[3];
            return new nc_search_query_expression_interval($matches[1], $matches[2], $interval_type);
        }
        return null;
    }

    /**
     * @return nc_search_query_expression_and|nc_search_query_expression_or
     */
    protected function create_boolean() {
        $args = func_get_args();
        $class = "nc_search_query_expression_" . (array_shift($args) == "AND" ? "and" : "or");
        /** @var $expression nc_search_query_expression_composite */
        $expression = new $class();
        foreach ($args as $item) { $expression->add_item($item); }
        return $expression;
    }

}