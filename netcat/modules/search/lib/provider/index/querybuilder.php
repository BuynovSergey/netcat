<?php
/**
 * Helper class which constructs SQL query(-ies).
 */
class nc_search_provider_index_querybuilder {

    const MAX_JOINS = 61; // 61 is maximum number of joins in MySQL

    protected $index_table = "Search_Index";
    protected $document_table = "Search_Document";

    /** @var nc_search_query */
    protected $query;
    /** @var nc_search_provider_index_translator */
    protected $translator;

    protected $joins = array();
    protected $left_joins = array();
    protected $condition_joins = array();
    protected $index_match = "1";
    protected $ranking_terms = array(); // 3-dimensional: [field_name => boost => term[]]

    protected $condition_stack = array(array());
    protected $condition_stack_operators = array("AND");

    protected static $temp_table_count = 0;

    /**
     * @param nc_search_query $query
     * @param nc_search_provider_index_translator $translator
     */
    public function __construct(nc_search_query $query, nc_search_provider_index_translator $translator) {
        $this->query = $query;
        $this->translator = $translator;
    }

    /**
     * @param bool $use_temp_table
     * @return string|array
     */
    public function get_sql_query($use_temp_table) {
        $index = $this->index_table;

        if (!$use_temp_table) {
            foreach ($this->condition_joins as $table_name => $join_condition) {
                $this->add_left_join($table_name);
            }
        }

        // prepare ORDER BY and value for the `Score` column
        $ranking_select = "1 AS `Score` ";
        $order_by = "";

        $sort_field_name = $this->query->get('sort_by');
        if ($sort_field_name == "last_modified") {
            $this->add_join($this->document_table);
            $order_by = "ORDER BY `{$this->document_table}`.`LastModified` DESC";
        }
        elseif ($sort_field_name) { // some other field
            /** @var $sort_fields nc_search_provider_index_field_manager */
            $sort_fields = $this->translator->get_fields('name', $sort_field_name)->where('is_sortable', true);
            if (count($sort_fields)) {
                $order_by = array();
                /** @var $f nc_search_provider_index_field */
                foreach ($sort_fields as $f) {
                    $field_table_name = $f->get_field_table_name();
                    $this->add_left_join($field_table_name);
                    $order_by[] = "`$field_table_name`.`RawData`";
                }
                $order_by = "ORDER BY IFNULL(" . join(", ", $order_by) . ", 0) " .
                    (($this->query->get('sort_direction') == SORT_DESC) ? "DESC" : "ASC");
            }
        }

        if (!$order_by) { // standard sorting by relevance
            $ranking_select = $this->term_ranking_calculation();
            $order_by = "ORDER BY `Score` DESC";
        }

        // Prepare area condition (will need join)
        $area_condition = "";
        $area = $this->query->get('area');
        if ($area) {
            $this->add_join($this->document_table);
            if (!($area instanceof nc_search_area)) { $area = new nc_search_area($area); }
            $area_condition = "AND " . $area->get_sql_condition() . "\n";
        }

        // Compose query
        // SELECT
        $query_string = "SELECT " . ($use_temp_table ? "" : "SQL_CALC_FOUND_ROWS ") .
                        "`$index`.`Document_ID`,\n" .
                        $ranking_select;
        // FROM
        $query_string .= "FROM `$index` FORCE INDEX (`Content`)\n";
        // INNER JOINs
        foreach ($this->joins as $j) { $query_string .= "$j\n"; }
        // LEFT JOINs
        foreach ($this->left_joins as $j) { $query_string .= "$j\n"; }

        // WHERE [index_match_condition]
        // (1) Index match;                  (2) site/path filter
        $query_string .= "WHERE $this->index_match\n$area_condition";
        // (3) Extra conditions
        if ($this->has_conditions() && !$use_temp_table) {
            $query_string .= "AND " . join("\nAND ", $this->condition_stack[0]);
        }

        // ORDER
        $query_string .= "\n$order_by";

        if ($use_temp_table) {
            $t = $this->get_temporary_table_name();
            $queries = array(
                "temp_table" => $t,
                "prefilter" => $query_string,
                "refinement" => "SELECT t.`Document_ID`, t.`Rank`\n" .
                    "FROM `$t` AS t\n" .
                    join("\n", $this->condition_joins) . "\n" .
                    "WHERE t.`Rank` >= IFNULL(@rank_value,0)\n" .
                    "AND " . join("\nAND ", $this->condition_stack[0]) . "\n" .
                    "ORDER BY t.`Rank`\n"
            );
            return $queries;
        }
        else {
            // LIMIT, OFFSET
            $query_string .= "\nLIMIT " . (int)$this->query->get('limit') .
                             "\nOFFSET " . (int)$this->query->get('offset');

            return $query_string;
        }
    }

    /**
     * @param string $table
     */
    protected function add_left_join($table) {
        if (isset($this->joins[$table]) || $table == $this->index_table) { return; }
        $this->left_joins[$table] = "LEFT JOIN `$table` USING (`Document_ID`)";
    }

    /**
     * @param string $table
     */
    protected function add_join($table) {
        if ($table == $this->index_table) { return; }
        if (isset($this->left_joins[$table])) { unset($this->left_joins[$table]); }
        $this->joins[$table] = "JOIN `$table` USING (`Document_ID`)";
    }

    /**
     * @param $operator
     */
    public function begin_group($operator) {
        $this->condition_stack_operators[] = $operator;
        $this->condition_stack[] = array();
    }

    /**
     * @return bool
     */
    public function finish_group() {
        if (count($this->condition_stack) < 2) { return false; }
        $operator = array_pop($this->condition_stack_operators);
        $current_level_conditions = join(" $operator ", array_pop($this->condition_stack));
        if ($current_level_conditions) {
            $this->add_condition("($current_level_conditions)");
        }
    }

    /**
     * @param $table
     */
    public function add_condition_table($table) {
        $this->condition_joins[$table] = "LEFT JOIN `$table` USING (`Document_ID`)";
    }

    /**
     * @param $condition
     */
    public function add_condition($condition) {
        $last = count($this->condition_stack)-1;
        $this->condition_stack[$last][] = $condition;
    }

    /**
     * @return bool
     */
    public function has_conditions() {
        return count($this->condition_stack[0]) > 0;
    }

    /**
     * @param array $table_names
     * @param string|array $regexps
     * @param boolean $negate
     */
    public function add_field_regexps(array $table_names, /*array*/ $regexps, $negate) {
        $operator = $negate ? "NOT REGEXP" : "REGEXP";
        if (count($table_names)==0) { $table_names[] = ''; }
        $regexps = (array)$regexps;
        $conditions = array();
        foreach ($table_names as $table) {
            if (!$table) { $table = $this->index_table; }
            $this->filter_name($table);
            $this->add_condition_table($table);
            foreach ($regexps as $re) {
                $conditions[] = "`$table`.`Content` $operator '$re'";
            }
        }
        $this->add_condition("(" . join(" OR ", $conditions) . ")");
    }

    /**
     * @param $fts_qry
     */
    public function set_index_match($fts_qry) {
        $this->index_match = $this->make_match($this->index_table, $fts_qry);
    }

    /**
     * @param $table
     * @param $fts_qry
     * @return string
     */
    protected function make_match($table, $fts_qry) {
        return "MATCH(`$table`.`Content`) AGAINST ('" .
                nc_search_util::db_escape($fts_qry) . "' IN BOOLEAN MODE)";
    }

    /**
     * @param string $table_name
     * @param string|array $terms
     * @param float $boost
     */
    public function add_term_ranking($table_name, $terms, $boost) {
        foreach ((array)$terms as $term) {
            $this->ranking_terms[strval($table_name)][sprintf("%.2F", $boost)][$term] = 1;
        }
    }

    /**
     * @return string
     */
    protected function term_ranking_calculation() {
        $field_settings = array();

        /** @var $f nc_search_provider_index_field */
        foreach ($this->translator->get_fields() as $f) {
            $field_name = $f->get('name');
            $table_name = $f->get_field_table_name();

            $settings = array(
                "weight" => $f->get('weight'),
                "table" => $table_name,
                "joined" => isset($this->joins[$table_name]) || isset($this->left_joins[$table_name])
            );

            $field_settings[$field_name][] = $field_settings[''][] = $settings;
        }

        $main_scores = array(); // score calculation for the tables that are already joined (1-dim: score[])
        $extra_scores = array(); // score calculation for the tables used only for scoring (2-dim: table => score[])
        $all_scores = array(); // main+extra scores

        foreach ($this->ranking_terms as $field_name => $data) {
            if (!isset($field_settings[$field_name])) { continue; } // unknown field name - SKIP

            foreach ($data as $term_boost => $term_codes) {
                $term_codes = join(' ', array_keys($term_codes));

                foreach ($field_settings[$field_name] as $f) {
                    $table_name = $f["table"];
                    $score = "$f[weight] * $term_boost * IFNULL(MATCH(`$table_name`.`Content`) AGAINST ('$term_codes'), 0)";
                    if ($f["joined"]) {
                        $main_scores[] = $score;
                        $all_scores[] = $score;
                    }
                    else {
                        $join = "LEFT JOIN `$table_name` USING (`Document_ID`)";
                        $extra_scores[$join][] = $score;
                        $all_scores[] = $score;
                    }
                }
            }
        }

        if (!$main_scores && !$extra_scores) { return "1 AS `Score`\n"; }

        $result = "";
        $num_joins = count($this->joins) + count($this->left_joins);
        if ($num_joins + count($extra_scores) <= self::MAX_JOINS) {
            // join to the main table
            $this->left_joins = array_merge($this->left_joins, array_keys($extra_scores));
            $result = "(" . join(" +\n", $all_scores) . ") AS `Score`\n";
        }
        else { // too many joins; use subqueries to retrieve score
            $subqueries = array();
            $chunks = array_chunk($extra_scores, self::MAX_JOINS, true);

            foreach ($chunks as $chunk) {
                $joins = join("\n", array_keys($chunk));
                $chunk_scores = array();
                foreach ($chunk as $table_scores) {
                    $chunk_scores[] = join(" +\n", $table_scores);
                }
                $subqueries[] = "(SELECT " . join(" +\n", $chunk_scores) .
                                "\nFROM `{$this->index_table}` AS `InnerQueryIndex`\n" .
                                 $joins .
                                "\nWHERE `{$this->index_table}`.`Document_ID` = `InnerQueryIndex`.`Document_ID`" .
                                ")";
            }
            $result = "(" . ($main_scores ? "(" . join("+\n", $main_scores) . ") +\n " : "") .
                      "(\n" . join("\n+\n", $subqueries) . "\n)) AS `Score`\n";
        }

        return $result;
    }

    /**
     *
     */
    protected function get_temporary_table_name() {
        return "Search_Index_Temporary" . (self::$temp_table_count++);
    }

    /**
     * @param string $table_name
     * @return string
     */
    protected function filter_name(&$table_name) {
        $table_name = preg_replace("/\W+/", "", $table_name);
        return $table_name;
    }

}