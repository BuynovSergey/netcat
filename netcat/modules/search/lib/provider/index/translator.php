<?php
/**
 * Транслятор результата парсинга поискового запроса (nc_search_query_expression)
 * в SQL-запрос.
 *
 * Сокращения:
 *  FTS = Full-Text Search
 *  FTS query = строка, сформированная по правилам для полнотекстовых запросов
 *     (MATCH () AGAINST ('$fts_query' IN BOOLEAN MODE)
 */
class nc_search_provider_index_translator extends nc_search_query_translator {

    /** @var nc_search_provider_index */
    protected $provider;

    /** @var nc_search_provider_index_querybuilder */
    protected $query_builder;

    /** @var array */
    protected $stack;

    /** @var array */
    protected $unknown_required_terms;

    /** @var nc_search_extension_chain */
    protected $text_filters;

    /** @var string */
    protected $term_table_name = "Search_Index_Term";
    protected $index_table_name = "Search_Index";

    /** @var bool */
    protected $can_skip_fts_query = false;

    /**
     * В случаях, у запроса имеются дополнительные условия, которые невозможно
     * проверить при помощи базовых возможностей "MATCH() AGAINST()", требуется
     * дублировать условия базового запроса в качестве отдельных условий:
     *  — в запросе есть фраза «внутри» OR;
     *  — в запросе есть выражение с указанием поля «внутри» OR.
     */
    /** @var bool */
    protected $implicit_field_match = false;

    /**
     * @param nc_search_provider_index $provider
     */
    public function __construct(nc_search_provider_index $provider) {
        $this->provider = $provider;
    }

    /**
     * @param nc_search_query $query
     * @return string|null
     */
    public function translate(nc_search_query $query) {
        $root = $query->parse();

        // empty query?
        if ($root instanceof nc_search_query_expression_empty) {
            return null;
        }
        // queries with only excluded terms are forbidden
        if ($root->is_excluded() || $root instanceof nc_search_query_expression_not) {
            return null;
        }

        // initialize variables used for a translation of the query
        $this->query_builder = $builder = new nc_search_provider_index_querybuilder($query, $this);
        $this->stack = array();
        $this->unknown_required_terms = array();
        $this->can_skip_fts_query = false;
        $this->implicit_field_match = $this->expression_requires_implicit_match($root);

        // get language filters chain to use in this.translate_term() etc.
        $language = $query->get('language');
        if (!$language) { $language = nc_Core::get_object()->lang->detect_lang(); }
        $query_context = new nc_search_context(array(
                                "search_provider" => get_class($this->provider),
                                "language" => $language,
                                "action" => "searching"
                            ));
        $this->text_filters = nc_search_extension_manager::get('nc_search_language_filter', $query_context)
                                ->stop_on(array());

        // Ready to go!
        // translate the expression tree
        $index_query = $this->dispatch_translate($root);

        if (!$this->can_skip_fts_query) { // interval search at the root for a numeric field
            // index query is required almost always
            if (!strlen($index_query)) { return null; } // e.g. if query string consists of stop words
            if ($index_query == "____" || $index_query == "(____)") { // empty query
                return null;
            }
        }
        // set query for the combined index in the query builder
        if ($index_query && !$root->get_field()) { $builder->set_index_match($index_query); }

        // there are required terms that are not in the index, so don’t make a query
        if ($this->unknown_required_terms) { return null; }

        // использовать временную таблицу для уточняющих запросов при поиске фраз?
        $use_temp_table = !nc_search::get_setting('DatabaseIndex_AlwaysGetTotalCount') &&
                          $this->query_builder->has_conditions() &&
                          $this->expression_has_phrase($root);

        // return SQL query string
        $result = $builder->get_sql_query($use_temp_table);
        return $result;
    }

    /**
     * @param nc_search_query_expression $expression
     * @param bool $is_root
     * @param bool $is_inside_or
     * @return bool
     */
    public function expression_requires_implicit_match(nc_search_query_expression $expression, $is_root = true, $is_inside_or = false) {
        if ($expression instanceof nc_search_query_expression_or)  { $is_inside_or = true; }

        if ($is_inside_or) {
            if (!$is_root && $expression->get_field()) { return true; }
            if ($expression instanceof nc_search_query_expression_phrase) { return true; }
        }

        if ($expression instanceof nc_search_query_expression_composite) {
            foreach ($expression->get_items() as $item) {
                if ($this->expression_requires_implicit_match($item, false, $is_inside_or)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @param nc_search_query_expression $expression
     * @return bool
     */
    public function expression_has_phrase(nc_search_query_expression $expression) {
        if ($expression instanceof nc_search_query_expression_phrase) { return true; }
        if ($expression instanceof nc_search_query_expression_composite) {
            foreach ($expression->get_items() as $item) {
                if ($this->expression_has_phrase($item)) { return true; } // including nc_search_query_expression_phrase
            }
        }
        return false;
    }

    /**
     * @param string|null $where_option  if not specified, returns all fields
     * @param string|null $where_value
     * @return nc_search_provider_index_field_manager
     */
    public function get_fields($where_option=null, $where_value=null) {
        $fields = $this->provider->get_index_fields();
        if ($where_option) { return $fields->where($where_option, $where_value); }
        return $fields;
    }

    /**
     * @param nc_search_query_expression $expression
     * @return array
     */
    protected function get_table_names(nc_search_query_expression $expression) {
        $field_name = $expression->get_field();
        if ($field_name) {
            return $this->get_fields('name', $field_name)->each('get_field_table_name');
        }
        elseif ($this->implicit_field_match) {
            return array($this->index_table_name);
        }
        return array();
    }

    /**
     * @param nc_search_query_expression $expression
     * @param string $fts_query
     */
    protected function add_field_matches(nc_search_query_expression $expression, $fts_query) {
        $has_field = $expression->get_field();

        if (!$has_field && !$this->implicit_field_match) { return; } // --- EXIT ---

        $table_names = $this->get_table_names($expression);
        $is_inside_or = $this->is_inside('or');

        // prefetch Document_IDs -- this is the fastest way to do this
        // type of queries (other ways are subqueries, which are quite slow,
        // and FTS match, which won’t use FTS index and therefore slow too)
        $id_conditions = array();
        $db  = $this->get_db();
        foreach ($table_names as $table_name) {
            if (!$has_field && !$is_inside_or) { // i.e. regular term which is not optional
                // this condition is already in the FTS query
                $id_conditions[] = "1";
            }
            else {
                $query = "SELECT `Document_ID` FROM `$table_name` WHERE MATCH(`Content`) AGAINST ('$fts_query' IN BOOLEAN MODE)";
                $ids = $db->get_col($query);
                $ids = $ids ? join(",", $ids) : "0";
                $id_conditions[] = "`{$this->index_table_name}`.`Document_ID` IN ($ids)";
                $this->query_builder->add_condition_table($this->index_table_name);
            }
        }
        $condition = ($id_conditions ? "(" . join(" OR ", $id_conditions) . ")" : "0");
        $this->query_builder->add_condition($condition);
    }

    /**
     * @param nc_search_query_expression_composite $expression
     * @return array
     */
    protected function translate_items(nc_search_query_expression_composite $expression) {
        $type_of_expression = str_replace("nc_search_query_expression_", "", get_class($expression));
        $this->stack[] = $type_of_expression;
        $result = parent::translate_items($expression);
        $result = array_filter($result, 'strlen'); // remove empty entries
        array_pop($this->stack);
        return $result;
    }

    /**
     * @param nc_search_query_expression_and $expression
     * @return string
     */
    protected function translate_and(nc_search_query_expression_and $expression) {
        $this->query_builder->begin_group("AND");
        $items = $this->translate_items($expression); // детишки в виде текста FTS-boolean запроса
        $fts_query = ""; // FTS-boolean запрос для объединённого индекса
        if ($items) {
            $glue = $this->is_inside("not") || $expression->is_excluded() ? " " : " +";
            $b = $this->get_brackets(!$this->is_root()); // brackets
            $fts_query = ($expression->is_excluded() ? "-" : "") .
                         $b[0] . trim($glue) . join($glue, $items) . $b[1];
            $fts_query = str_replace("+-", "-", $fts_query); // "AND NOT" → "-"
        }
        $this->query_builder->finish_group();

        return $fts_query;
    }

    /**
     * @param nc_search_query_expression_or $expression
     * @return mixed
     */
    protected function translate_or(nc_search_query_expression_or $expression) {
        $this->query_builder->begin_group("OR");
        $translated_items = $this->translate_items($expression);
        $fts_query = "";
        if ($translated_items) {
            $b = $this->get_brackets(!$this->is_root()); // brackets
            $fts_query = ($expression->is_excluded() ? "-" : "") .
                         $b[0] . join(" ", $translated_items) . $b[1];
        }
        $this->query_builder->finish_group();
        return $fts_query;
    }

    /**
     * @param nc_search_query_expression_not $expression
     * @return mixed
     */
    protected function translate_not(nc_search_query_expression_not $expression) {
        $fts_query = "-" . join(' ', $this->translate_items($expression)); // not must have only one operand
        $this->add_field_matches($expression, $fts_query);
        return $fts_query;
    }

    /**
     * 1) Обрабатвает термин текстовыми фильтрами, преобразовывает полученные
     *    формы в коды терминов.
     * 2) Добавляет запрос на ранжирование по этому термину (если необходимо).
     * 3) Возвращает массив с кодами, соответствующими всем формам термина (базовые
     *    формы, синонимы).
     *
     * Возвращает массив (zero-based) с кодами всех форм, соответствующих термину.
     * Если термин является стоп-словом, возвращает массив с единственным элементом "" (пустая строка).
     * Если в индексе нет ни одной формы термина, возвращает массив с элементом "____".
     *
     * Таким образом, в возвращаемом массиве всегда должен быть по крайней мере один
     * элемент.
     *
     * @param nc_search_query_expression_term $expression
     * @return array
     */
    protected function process_term(nc_search_query_expression_term $expression) {
        $string = $expression->get_value();

        // convert term to base forms
        $base_forms = $this->text_filters->apply('filter', array($string));

        if (!count($base_forms)) { // it's a stop-word obviously
            return array("");
        }

        // get codes
        $codes = array_unique($this->provider->get_term_codes($base_forms, false));

        // add the term to the ranking if it is not excluded
        $is_excluded = $this->is_inside("not") || $expression->is_excluded();

        // check whether there is at least one code
        if (count($codes) == 0) {
            // is this term required for all documents?
            $is_required = !$is_excluded && !$this->is_inside("or") &&
                            (
                                $this->is_root() ||
                                $expression->is_required() ||
                                $this->parent_is("phrase") ||
                                $this->parent_is("and")
                            );

            if ($is_required) {
                // this query won't produce any results anyway, so we could
                // spare a database request later
                $this->unknown_required_terms[] = $string;
            }

            $codes[] = "____"; // dummy "non-existent term" code
        }
        elseif (!$is_excluded) {
            $this->query_builder->add_term_ranking($expression->get_field(), $codes, $expression->get_boost());
        }

        return $codes;
    }

    /**
     * @param nc_search_query_expression_term $expression
     * @return string
     */
    protected function translate_term(nc_search_query_expression_term $expression) {
        $codes = $this->process_term($expression);

        $sign = ($expression->is_required() ? "+" :
                ($expression->is_excluded() ? "-" :
                ""));

        $fts_query = "";

        if (count($codes) == 1) { // only one base form
            if ($codes[0] != '') { $fts_query = $sign . $codes[0]; }
        }
        else {
            // boolean FTS query "-(term1 term2)" is equivalent to "-term1 -term2"
            // boolean FTS query "+(term1 term2)" is equivalent to "(term1 term2)"
            $fts_query = "$sign(" . join(" ", $codes) . ")";
        }

        // if it is a root term with a field modifier, add it to the field conditions
        if ($this->is_root() || $this->implicit_field_match || $expression->get_field()) {
            $this->add_field_matches($expression, $fts_query);
        }

        return $fts_query;
    }

    /**
     * @param nc_search_query_expression_wildcard $expression
     * @return string
     */
    protected function translate_wildcard(nc_search_query_expression_wildcard $expression) {
        $wildcard = mb_convert_case($expression->get_value(), nc_search::get_setting("FilterStringCase"), 'UTF-8');
        $terms = $this->get_codes_by_wildcard($wildcard);
        $fts_query = ($expression->is_excluded() ? "-" : "") . "(" . join(" ", $terms) . ")";
        $this->add_field_matches($expression, $fts_query);
        return $fts_query;
    }

    /**
     * @param nc_search_query_expression_fuzzy $expression
     * @return string
     */
    protected function translate_fuzzy(nc_search_query_expression_fuzzy $expression) {
        $term1 = mb_convert_case($expression->get_value(), nc_search::get_setting("FilterStringCase"), 'UTF-8');
        $similar_terms = $this->get_similar_terms($term1, $expression->get_similarity());
        $fts_query = ($expression->is_excluded() ? "-" : "") . "(" . join(" ", $similar_terms) . ")";

        if ($fts_query == "(____)" || $fts_query == "-(____)") { return ''; }

        $this->add_field_matches($expression, $fts_query);
        $this->query_builder->add_term_ranking($expression->get_field(), $similar_terms, 1);

        return $fts_query;
    }

    /**
     * @param nc_search_query_expression_phrase $expression
     * @return string
     */
    protected function translate_phrase(nc_search_query_expression_phrase $expression) {
        $this->stack[] = "phrase";

        // Given a phrase "one two", where "two" has forms of "two1" and "two2":
        //   - added to the main FTS condition: ((+one +two1) (+one +two2))
        //     (this query is used to fetch rows which contain these words using a FTS index)
        //   - added to the query: REGEXP condition(s) to fetch only rows where these terms
        //     come in sequence (REGEXP is used because there could be several
        //     variants of the same term in the index in the form "one two1|two2")
        $term_combinations = array();
        $regexps = array();

        /** @var $item nc_search_query_expression_term */
        foreach ($expression->get_items() as $item) { // "for each term in the phrase"
            $item_codes = $this->process_term($item);
            if ($item_codes[0]) { // not a stop word
                $brackets = $this->get_brackets(count($item_codes) > 1);
                $regexps[] = $brackets[0] . join("|", array_filter($item_codes)) . $brackets[1];
            }
            if (count($term_combinations) == 0) { // it's a first (meaningful) term
                $term_combinations = $item_codes;
            }
            else { // produce all possible combinations
                $previous_state = $term_combinations;
                $term_combinations = array();
                foreach ($previous_state as $p) {
                    foreach ($item_codes as $c) {
                        $not_empty = strlen($c) > 0; // might be a stop word
                        $term_combinations[] = $p . ($not_empty ? " $c" : "");
                    }
                }
            }
        }

        // there can be a space in the beginning if the first term is stop word
        if ($term_combinations && $term_combinations[0][0] == " ") {
            $term_combinations = array_map('trim', $term_combinations);
        }

        // all terms in the phrase are stop words?!
        if (!$term_combinations || !$term_combinations[0]) { return ""; }

        // make FTS query to pre-filter results before running regexp match
        $fts_query = "";
        if (!$expression->is_excluded()) {
            $brackets = $this->get_brackets(count($term_combinations) > 1);
            $fts_query = "$brackets[0](+" . join(")(+", $term_combinations) . ")$brackets[1]";
            $fts_query = strtr($fts_query, array(" " => " +", ")(" => ") ("));
        }

        // add exact sequence condition
        $table_names = $this->get_table_names($expression);
        // '|' is not redundant: it prevents from matching a partial code
        $term_boundary = '(\\\\|[^ /]+)? ';
        $skip_regexps = false;
        $num_regexps = count($regexps);
        if ($expression->get_distance()) { // it's a "proximity search"
            if ($num_regexps < 2 || $num_regexps > (int)nc_search::get_setting("DatabaseIndex_MaxProximityTerms")) {
                // the proximity phrase is too long (or too short)!
                $skip_regexps = true;
            }
            else {
                // get all possible permutations
                $skipped_term = '[^ /]+ ';
                $distance = min((int)nc_search::get_setting("DatabaseIndex_MaxProximityDistance"),
                                $expression->get_distance());
                $gaps = $distance == 1 ? $term_boundary :
                                        "$term_boundary($skipped_term){0,$distance}";
                $regexps = $this->permute($regexps, $gaps);
            }
        }
        else { // ordinary phrase, distance=0
            if ($num_regexps > 1) {
                $regexps = join($term_boundary, $regexps);
            }
            else {
                $skip_regexps = true;
            }
        }

        if (!$skip_regexps) {
            $this->query_builder->add_field_regexps($table_names,
                                                    $regexps,
                                                    $expression->is_excluded());
        }
        elseif ($expression->get_field()) {
            $this->add_field_matches($expression, $fts_query);
        }

        // remove "phrase" from the stack
        array_pop($this->stack);
        // done
        return $fts_query;
    }

    /**
     * @param nc_search_query_expression_interval $expression
     * @return string
     */
    protected function translate_interval(nc_search_query_expression_interval $expression) {
        /** @var $begin nc_search_query_expression_term */
        /** @var $end nc_search_query_expression_term */
        list($begin, $end) = $expression->get_items();
        $value1 = $begin->get_value();
        $value2 = $end->get_value();
        $field_name = $expression->get_field();
        $builder = $this->query_builder;
        $fts_query = "";

        if ($field_name) {
            /** @var $f nc_search_provider_index_field */
            $range_conditions = array();
            if ($this->is_root()) { $this->can_skip_fts_query = true; }

            foreach ($this->get_fields('name', $field_name) as $f) {
                $table_name = $f->get_field_table_name();

                $is_stored_numeric_field =
                    $f->get('type') == nc_search_provider_index_field::TYPE_INTEGER &&
                   ($f->get('is_sortable') || $f->get('is_stored'));

                if ($is_stored_numeric_field) {
                    $n1 = $this->escape_number($value1);
                    $n2 = $this->escape_number($value2);
                    $range_conditions[] = "`$table_name`.`RawData` BETWEEN $n1 AND $n2" .
                                           ($expression->is_exclusive() ? " AND `$table_name`.`RawData` NOT IN ($n1, $n2)" : "");
                    $this->query_builder->add_condition_table($table_name);
                }
                else { // expand the query
                    if (!$fts_query) { // load once in the foreach loop
                        $terms = $this->get_codes_by_interval($value1, $value2, $expression->is_exclusive());
                        $fts_query = "(" .join(" ", $terms) . ")";
                        $builder->add_term_ranking($field_name, $terms, 1);
                    }

                    // preselect IDs for the field match -- this is the fastest way to do
                    // this type of query (alternative is a subquery with FORCE INDEX, but
                    // MySQL will transform it to a slow correlated subquery)
                    $query = "SELECT `Document_ID` FROM `$table_name` WHERE MATCH(`Content`) AGAINST ('$fts_query' IN BOOLEAN MODE)";
                    $ids = $this->get_db()->get_col($query);
                    $ids = $ids ? join(",", $ids) : "0";
                    $range_conditions[] = "`{$this->index_table_name}`.`Document_ID` IN ($ids)";
                }
            } // of "foreach field with that name"
            if ($range_conditions) {
                $builder->add_condition("(" . join(" OR ", $range_conditions) . ")");
            }
            else {
                $builder->add_condition("0");
            }
            return ""; // no need to match the compound index
        }
        else { // no field name (global match)
            $terms = $this->get_codes_by_interval($value1, $value2, $expression->is_exclusive());
            $fts_query = "(" .join(" ", $terms) . ")";
            $this->add_field_matches($expression, $fts_query);
            $builder->add_term_ranking($field_name, $terms, 1);
        }

        return $fts_query;
    }

    /**
     * @param nc_search_query_expression_empty $expression
     * @return string
     */
    protected function translate_empty(nc_search_query_expression_empty $expression) {
        return "";
    }

    // -------------------------------------------------------------------------
    /* -- Методы для упрощения работы со свойством $this->stack -- */
    protected function is_inside($type) {
        $last = sizeof($this->stack);
        while ($last) {
            if ($this->stack[--$last] == $type) { return true; }
        }
        return false;
    }

    protected function parent_is($type) {
        $last = sizeof($this->stack);
        return ($last && $this->stack[$last-1] == $type);
    }

    protected function is_root() {
        return (sizeof($this->stack) == 0);
    }

    // -------------------------------------------------------------------------
    protected $round_brackets = array("(", ")");
    protected $no_brackets = array("", "");
    protected function get_brackets($condition) {
        return ($condition ? $this->round_brackets : $this->no_brackets);
    }
    // -------------------------------------------------------------------------

    /**
     * @return array
     */
    public function get_unknown_terms() {
        return $this->unknown_required_terms;
    }

    // -------------------------------------------------------------------------
    /**
     * Returns permutation map.
     * Based on the code from the "PHP Cookbook" by David Sklar and Adam Trachtenberg
     * http://commons.oreilly.com/wiki/index.php/PHP_Cookbook/Arrays#Finding_All_Permutations_of_an_Array
     * @param $num_elements
     * @return array
     */
    protected function get_permutation_map($num_elements) {
        $size = $num_elements-1;
        $p = range(0, $size);
        $map = array($p);
        while (true) {
            // slide down the array looking for where we're smaller than the next guy
            for ($i = $size - 1; $i >= 0 && $p[$i] >= $p[$i+1]; --$i) { }

            // if this doesn't occur, we've finished our permutations
            // the array is reversed: (1, 2, 3, 4) => (4, 3, 2, 1)
            if ($i == -1) { break; }

            // slide down the array looking for a bigger number than what we found before
            for ($j = $size; $p[$j] <= $p[$i]; --$j) { }

            // swap them
            $tmp = $p[$i]; $p[$i] = $p[$j]; $p[$j] = $tmp;

            // now reverse the elements in between by swapping the ends
            for (++$i, $j = $size; $i < $j; ++$i, --$j) {
                 $tmp = $p[$i]; $p[$i] = $p[$j]; $p[$j] = $tmp;
            }

            $map[] = $p;
        }
        return $map;
    }

    /**
     * @param array $input original elements
     * @param string $glue
     * @return array strings with all possible combinations of the original elements, divided by $glue
     */
    protected function permute(array $input, $glue) {
        $map = $this->get_permutation_map(sizeof($input));
        $output = array();
        foreach ($map as $variant) {
            $row = array();
            foreach ($variant as $index) { $row[] = $input[$index]; }
            $output[] = join($glue, $row);
        }
        return $output;
    }


    /**
     * @param $value1
     * @param $value2
     * @param $exclusive
     * @return array
     */
    protected function get_codes_by_interval($value1, $value2, $exclusive) {
        $case = nc_search::get_setting('FilterStringCase');
        $value1 = nc_search_util::db_escape(mb_convert_case($value1, $case, 'UTF-8'));
        $value2 = nc_search_util::db_escape(mb_convert_case($value2, $case, 'UTF-8'));

        $query = "SELECT `Code`
                    FROM `{$this->term_table_name}`
                   WHERE `Term` BETWEEN '$value1' AND '$value2'" .
                   ($exclusive ? " AND `Term` NOT IN ('$value1', '$value2')" : "") .
                 " LIMIT " . (int)nc_search::get_setting("DatabaseIndex_MaxRewriteTerms");

        $codes = $this->get_db()->get_col($query);
        if (!$codes) { $codes = array("____"); }
        return $codes;
    }


    /**
     * @param string $wildcard
     * @return array codes of the terms matching the wildcard (array("____") if no similar terms were found)
     */
    protected function get_codes_by_wildcard($wildcard) {
        $max_results = (int)nc_search::get_setting("DatabaseIndex_MaxRewriteTerms");
        $sql_wildcard = strtr($wildcard, array("*" => "%", "?" => "_"));
        $sql_wildcard = nc_search_util::db_escape($sql_wildcard);

        $query = "SELECT `Code`
                    FROM `{$this->term_table_name}`
                   WHERE `Term` LIKE '$sql_wildcard'
                   LIMIT $max_results";

        $codes = $this->get_db()->get_col($query);
        if (!$codes) { $codes = array("____"); }
        return $codes;
    }

    /**
     * @param string $term1
     * @param float $min_similarity
     * @return array of similar term codes (array("____") if no similar terms were found)
     */
    protected function get_similar_terms($term1, $min_similarity) {
        $max_candidates = (int)nc_search::get_setting("DatabaseIndex_MaxSimilarityCandidates");
        $max_results = (int)nc_search::get_setting("DatabaseIndex_MaxRewriteTerms");
        $use_utf_levenshtein = (bool)nc_search::get_setting("DatabaseIndex_UseUtf8Levenshtein");

        $term_length = mb_strlen($term1, 'UTF-8');
        $max_distance = intval((1 - $min_similarity) * $term_length); // == floor()
        $min_length = $term_length - $max_distance;
        $max_length = $term_length + $max_distance;

        // проверять совпадение в PHP до 10 раз быстрее, чем делать это хранимой
        // функцией в MySQL
        $query = "SELECT `Term`, `Code`
                    FROM `{$this->term_table_name}`
                   WHERE `Length` BETWEEN $min_length AND $max_length
                   LIMIT $max_candidates";

        $terms = $this->get_db()->get_results($query, ARRAY_A);
        $similar = array();
        if ($terms) {
            foreach ($terms as $row) {
                // Функция levenshtein() не UTF-8-aware и производит неправильные
                // результаты в случае, если есть замена однобайтовой буквы на
                // многобайтовую, например levenshtein("Z", "Я") == 2, а не 1.
                // Но всё же используется именно эта функция, поскольку она более чем
                // в два раза быстрее кода на PHP, а в этом цикле может обрабатываться
                // большое количество ($this->max_similarity_candidates) терминов
                $distance = $use_utf_levenshtein
                                ? $this->levenshtein_utf8($term1, $row['Term'])
                                : levenshtein($term1, $row['Term']);
                $terms_similarity = 1 - $distance / min($term_length, mb_strlen($row['Term'], 'UTF-8'));
                if ($terms_similarity >= $min_similarity) { $similar[] = $row['Code']; }
                if (sizeof($similar) >= $max_results) { break; }
            }
        }
        if (!sizeof($similar)) { $similar[] = "____"; } // haven't found any similar terms!
        return $similar;
    }

    /**
     * based on https://gist.github.com/santhoshtr/1710925
     * @param $str1
     * @param $str2
     * @return int
     */
    protected function levenshtein_utf8($str1, $str2) {
        $length1 = mb_strlen($str1, 'UTF-8');
        $length2 = mb_strlen($str2, 'UTF-8');
        if ($length1 < $length2) { return $this->levenshtein_utf8($str2, $str1); }
        if ($length1 == 0 ) { return $length2; }
        if ($str1 == $str2) { return 0; }
        $prev_row = range(0, $length2);
        for ($i = 0; $i < $length1; $i++ ) {
            $current_row = array();
            $current_row[0] = $i + 1;
            $c1 = mb_substr($str1, $i, 1, 'UTF-8') ;
            for ($j = 0; $j < $length2; $j++ ) {
                $c2 = mb_substr($str2, $j, 1, 'UTF-8');
                $insertions = $prev_row[$j+1] + 1;
                $deletions = $current_row[$j] + 1;
                $substitutions = $prev_row[$j] + (($c1 != $c2) ? 1 : 0);
                $current_row[] = min($insertions, $deletions, $substitutions);
            }
            $prev_row = $current_row;
        }
        return $prev_row[$length2];
    }

    /**
     * @return nc_Db
     */
    protected function get_db() {
        return nc_Core::get_object()->db;
    }

    /**
     * @param $value
     * @return int|string
     */
    protected function escape_number($value) {
        if (!is_numeric($value)) { $value = "'" . nc_search_util::db_escape($value) . "'"; }
        return $value;
    }

}