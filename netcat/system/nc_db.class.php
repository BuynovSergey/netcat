<?php

/**
 * Class nc_Db
 *
 * Originally based on ezSQL 2.0, (c) Author: Justin Vincent (justin@visunet.ie),
 * http://php.justinvincent.com  (licensed under LGPL)
 *
 */

define('OBJECT', 'OBJECT');
define('ARRAY_A', 'ARRAY_A');
define('ARRAY_N', 'ARRAY_N');


class nc_Db extends nc_System {

    public $dbh;

    public $show_errors = false;
    public $trace = false;     // same as $debug_all
    public $debug_all = false; // same as $trace
//    public $func_call;
    public $fill_col_info = false;   // if TRUE, $col_info will be filled on each request
    public $groupped_queries;

    public $num_queries = 0;
    public $last_query;
    public $col_info;

    public $result;
    public $rows_affected;
    public $num_rows;
    public $insert_id;
    public $last_result;
    protected $result_output_type;

    public $last_error;
    public $captured_errors = array();
    public $is_error;
    public $errno;

    public $benchmark;

    protected $debug_called = false;
    protected $vardump_called = false;

    /**
     *
     */
    public function __construct() {
        $nc_core = nc_Core::get_object();
        parent::__construct();

        mysqli_report(MYSQLI_REPORT_OFF); // отключение исключений в mysqli_query (PHP 8.1+) для обеспечения обратной совместимости

        $this->quick_connect($nc_core->MYSQL_USER, $nc_core->MYSQL_PASSWORD, $nc_core->MYSQL_DB_NAME, $nc_core->MYSQL_HOST, $nc_core->MYSQL_PORT, $nc_core->MYSQL_SOCKET);

        // set default names
        if ((float)mysqli_get_server_info($this->dbh) >= 4.1) {
            if (!$nc_core->MYSQL_CHARSET) {
                $nc_core->MYSQL_CHARSET = 'cp1251';
            }
            $this->query("SET NAMES '" . $nc_core->MYSQL_CHARSET . "'");
            $this->query("SET sql_mode=''");
            if ($nc_core->MYSQL_TIMEZONE) {
                $this->query("SET TIME_ZONE = '" . $this->escape($nc_core->MYSQL_TIMEZONE) . "'");
            }
        }

        // what to do when with MySQL errors
        $this->show_errors = $nc_core->SHOW_MYSQL_ERRORS == "on";
    }

    /**
     * @param string $dbuser
     * @param string $dbpassword
     * @param string $dbname
     * @param string $dbhost
     * @param string $dbport
     * @param string $dbsocket
     * @return bool
     * @throws Exception
     */
    public function quick_connect($dbuser = '', $dbpassword = '', $dbname = '', $dbhost = 'localhost', $dbport = '', $dbsocket = '') {
        if (!$this->connect($dbuser, $dbpassword, $dbhost, $dbname, $dbport, $dbsocket) || !$this->select($dbname)) {
            // probably system was not installed
            if ($this->check_system_install()) {
                // DB connection error
                nc_set_http_response_code(503);
                throw new Exception("Unable to connect to the database. Check connection settings");
            }
        }

        return true;
    }

    /**
     * Try to connect to mySQL database server
     *
     * @param string $dbuser
     * @param string $dbpassword
     * @param string $dbhost
     * @param string $dbname
     * @param string $dbport
     * @param string $dbsocket
     *
     * @return mysqli
     * @throws Exception
     */
    public function connect($dbuser = '', $dbpassword = '', $dbhost = 'localhost', $dbname = '', $dbport = '', $dbsocket = '') {
        if (!extension_loaded('mysqli')) {
            nc_set_http_response_code(503);
            throw new Exception("The <a href='http://php.net/mysqli'>mysqli</a> extension must be enabled.");
        }

        $dbport = $dbport ?: ini_get('mysqli.default_port');
        $dbsocket = $dbsocket ?: ini_get('mysqli.default_socket');

        return ($this->dbh = @mysqli_connect($dbhost, $dbuser, $dbpassword, $dbname, $dbport, $dbsocket));
    }

    /**
     * Try to select a mySQL database
     *
     * @param string $dbname
     * @return bool
     */
    public function select($dbname = '') {
        if (empty($dbname)) {
            return false;
        }
        return (@mysqli_select_db($this->dbh, $dbname));
    }

    /**
     * Format a mySQL string correctly for safe mySQL insert
     * (no mater if magic quotes are on or not)
     *
     * @param $str
     * @return string
     */
    public function escape($str) {
        return mysqli_real_escape_string($this->dbh, stripslashes($str));
    }

    /**
     * @param $str
     * @return string
     */
    public function prepare($str) {
        return mysqli_real_escape_string($this->dbh, $str);
    }

    /**
     * Return mySQL-specific system date syntax
     * @return string
     */
    public function sysdate() {
        return 'NOW()';
    }

    /**
     * Perform mySQL query and try to determine result value
     *
     * @param string $query
     * @param string $output
     * @param string $index_field    if set, the resulting array will have the value
     *                               of the $index_field as a key
     *                               (use with caution if a get_row() call will follow!)
     * @return bool|int
     */
    public function query($query, $output = OBJECT, $index_field = null) {
        global $MODULE_VARS;

        // Keep track of the time the query took?
        $sql_time = $MODULE_VARS && is_array($MODULE_VARS['default']) &&
                    array_key_exists('NC_DEBUG_SQL_TIME', $MODULE_VARS['default']) &&
                    $MODULE_VARS['default']['NC_DEBUG_SQL_TIME'];

        // Keep track of from where the method was executed?
        $sql_func = $MODULE_VARS && is_array($MODULE_VARS['default']) &&
                    array_key_exists('NC_DEBUG_SQL_FUNC', $MODULE_VARS['default']) &&
                    $MODULE_VARS['default']['NC_DEBUG_SQL_FUNC'];

        if ($sql_time && !class_exists('Benchmark_Timer')) {
            require_once("Benchmark/Timer.php");
        }

        if ($this->benchmark || $sql_time) {
            $timer = new Benchmark_Timer();
            $timer->start();
        }

        // For reg expressions
        $query = trim($query);

        // Initialise return
        $return_val = 0;
        $this->is_error = 0;
        $func = '';
        $this->errno = 0;

        // Flush cached values..
        $this->flush();

        // Log how the function was called
//        $this->func_call = "\$db->query(\"$query\")";

        // Keep track of the last query for debug..
        $this->last_query = $query;

        // Check for injections
        $nc_core = nc_core::get_object();
        if (isset($nc_core->security->sql_filter)) { // система инициализирована
            $query = $nc_core->security->sql_filter->filter($query);
        }

        if (!$query) {
            // Нельзя выполнить пустой запрос
            return 0;
        }

        // Perform the query via std mysqli_query function..
        $this->result = @mysqli_query($this->dbh, $query);

        $this->num_queries++;

        // таймер
        if ($this->benchmark || $sql_time) {
            $timer->stop();
            if ($this->benchmark) { $timer->display(); }
            $sql_time = $timer->timeElapsed();
        }

        if ($sql_func) {
            $backtrace = debug_backtrace();
            $func = ($backtrace[2]['class'] ? $backtrace[2]['class'] . '::' : '') . $backtrace[2]['function'];
        }

        // If there is an error then take note of it..
        if (($str = @mysqli_error($this->dbh))) {
            $this->register_error($str);
            $this->is_error = 1;
            $this->show_errors ? trigger_error($str, E_USER_WARNING) : null;
            $this->errno = mysqli_errno($this->dbh);

            if (/*nc_Core::get_object()->beta ||*/ $this->debug_all || $this->trace) {
                echo "<div style='border: 2pt solid red; margin: 10px; padding:10px; font-size:13px; color:black;'><br/>\n";
                echo "Query: <b>" . $query . "</b><br/>\n";
                echo "Error: <b>" . $str . "</b><br/>\n";
                echo "</div>\n";
            }
        }

        $this->debugMessage($this->num_queries . ". " . $query, $func, $sql_time, $this->is_error ? 'error' : 'ok');

        if ($this->is_error) {
            return false;
        }

        // Query was an insert, delete, update, replace
        if (preg_match("/^(insert|delete|update|replace)\s+/i", $query)) {
            $this->rows_affected = @mysqli_affected_rows($this->dbh);

            // Take note of the insert_id
            // NB: не нужно заменять на nc_preg_match(), поскольку запрос не обязательно
            // является корректной UTF строкой - в этом случае условие не будет выполнено!
            if (preg_match("/^(insert|replace)\s+/i", $query)) {
                $this->insert_id = @mysqli_insert_id($this->dbh);
            }

            // Return number of rows affected
            $return_val = $this->rows_affected;
        }
        // Query was a select
        else {
            // Take note of column info
            if ($this->fill_col_info) {
                $this->col_info = array();
                $i = 0;
                while ($i < @mysqli_num_fields($this->result)) {
                    $this->col_info[$i] = @mysqli_fetch_field($this->result);
                    $i++;
                }
            }
            else {
                $this->col_info = false;
            }

            // mysqli_query returns TRUE for INSERT/UPDATE/DROP queries and FALSE on error
            if (!is_bool($this->result)) {
                // Store Query Results
                $this->result_output_type = $output;

                    if ($output == ARRAY_N) { $fetch_function = 'mysqli_fetch_row'; }
                elseif ($output == ARRAY_A) { $fetch_function = 'mysqli_fetch_assoc'; }
                                       else { $fetch_function = 'mysqli_fetch_object'; }

                // Store results as an objects within main array
                $num_rows = 0;
                while ($row = $fetch_function($this->result)) {
                    $key = ($index_field !== null)
                                ? (is_array($row) ? $row[$index_field] : $row->$index_field)
                                : $num_rows;
                    $this->last_result[$key] = $row;
                    $num_rows++;
                }

                mysqli_free_result($this->result);

                // Log number of rows the query returned
                $this->num_rows = $num_rows;
            }

            // Return number of rows selected
            $return_val = $this->num_rows;
        }

        // If debug ALL queries
        $this->trace || $this->debug_all ? $this->debug() : null;

        if ($this->debug_all) {
            if (preg_match("/(from\s+`?\w+`?)/si", $query, $regs)) {
                $from = preg_replace("/\s+/s", " ", $regs[1]);
                $from = preg_replace("/from /i", "FROM ", $from);
            } else {
                $from = '';
            }
            $this->groupped_queries[$from][$this->num_queries] = $query;
        }

        if ($this->benchmark && $GLOBALS["nccttimer"] instanceof Benchmark_Timer) {
            $GLOBALS["nccttimer"]->setMarker("QRY $this->num_queries<br />");
        }

        return $return_val;
    }

    /**
     * @param $err_str
     */
    public function register_error($err_str) {
        // Keep track of last error
        $this->last_error = $err_str;

        // Capture all errors to an error array no matter what happens
        $this->captured_errors[] = array(
            'error_str' => $err_str,
            'query' => $this->last_query
        );
    }

    /**
     * Turn error handling on
     */
    public function show_errors() {
        $this->show_errors = true;
    }

    /**
     * Turn error handling off
     */
    public function hide_errors() {
        $this->show_errors = false;
    }

    /**
     * Kill cached query results
     */
    public function flush() {
        // Get rid of these
        $this->last_result = null;
        $this->col_info = null;
        $this->last_query = null;
        $this->num_rows = 0;
        $this->result_output_type = null;
    }

    /**
     * Get one variable from the DB
     *
     * @param string|null $query
     * @param int $x
     * @param int $y
     * @return null
     */
    public function get_var($query = null, $x = 0, $y = 0) {

        // Log how the function was called
//        $this->func_call = "\$db->get_var(\"$query\",$x,$y)";

        // If there is a query then perform it if not then use cached results..
        if ($query) {
            $this->query($query, ARRAY_N);
        }

        // Extract var out of cached results based x,y vals
        if (isset($this->last_result[$y])) {
            $row = $this->last_result[$y];

            if ($this->result_output_type == OBJECT) {
                $row = array_values(get_object_vars($row));
            }
            elseif ($this->result_output_type == ARRAY_A) {
                $row = array_values($row);
            }

            if (array_key_exists($x, $row)) {
                return $row[$x];
            }
        }

        return null;
    }

    /**
     * Get one row from the DB
     *
     * @param string|null $query
     * @param string $output
     * @param int $y
     * @return array|null
     */
    public function get_row($query = null, $output = OBJECT, $y = 0) {

        // Log how the function was called
//        $this->func_call = "\$db->get_row(\"$query\",$output,$y)";

        // If there is a query then perform it if not then use cached results..
        if ($query) {
            $this->query($query, $output);
        }
        else {
            $this->execute_previous_query_when_needed($output);
        }

        if (is_array($this->last_result) && array_key_exists($y, $this->last_result)) {
            return $this->convert_results($this->last_result[$y], $this->result_output_type, $output);
        }

        return null;
    }

    /**
     * Function to get 1 column from the cached result set based in X index
     *
     * @param string|null $query
     * @param int $x
     * @param int|null $index_x    If set, this column will be used as array index
     * @return array
     */
    public function get_col($query = null, $x = 0, $index_x = null) {

        // If there is a query then perform it if not then use cached results..
        if ($query) {
            $this->query($query, ARRAY_N);
            $last_result = $this->last_result;
        } else {
            $last_result = array();
            // convert last_result to array with integer keys
            if (!isset($this->last_result)) {
                $this->last_result = array();
            }
            foreach ($this->last_result as $key => $row) {
                if ($row instanceof stdClass) {
                    $row = get_object_vars($row);
                }
                $last_result[$key] = array_values($row);
            }
        }

        $new_array = array();

        $last_result_count = is_array($last_result) ? count($last_result) : 0;

        // Extract the column values
        for ($i = 0, $last = $last_result_count; $i < $last; $i++) {
            if ($index_x !== null) {
                $key = $last_result[$i][$index_x];
            }
            else {
                $key = $i;
            }

            $new_array[$key] = $last_result[$i][$x];
        }

        return $new_array;
    }

    /**
     * Return the query as a result set
     *
     * @param string|null $query
     * @param string $output
     * @param string $index_field    if set, the resulting array will have the value
     *                               of the $index_field as a key
     *                               (use with caution if a get_row() call will follow!)
     * @return array|null
     */
    public function get_results($query = null, $output = OBJECT, $index_field = null) {
        // Log how the function was called
//        $this->func_call = "\$db->get_results(\"$query\", $output)";

        // If there is a query then perform it if not then use cached results..
        if ($query) {
            $this->query($query, $output, $index_field);
        }
        else {
            $this->execute_previous_query_when_needed($output);
        }

        // Return results
        if ($this->result_output_type == $output) {
            return $this->last_result;
        }

        if (is_array($this->last_result)) {
            $result = array();
            foreach ($this->last_result as $row) {
                $result[] = $this->convert_results($row, $this->result_output_type, $output);
            }
            return $result;
        }

        return null;
    }

    /**
     * Function to get column meta data info pertaining to the last query
     *
     * @param string $info_type
     * @param $col_offset
     * @return mixed
     */
    public function get_col_info($info_type = "name", $col_offset = -1) {
        if ($this->col_info === false) {
            // added for compatibility with the previous versions; never used
            $fill_col_info = $this->fill_col_info;
            $this->fill_col_info = true;
            $this->query($this->last_query, $this->result_output_type);
            $this->fill_col_info = $fill_col_info;
        }

        if (is_array($this->col_info)) {
            if ($col_offset == -1) {
                $new_array = array();
                $i = 0;
                foreach ($this->col_info as $col) {
                    $new_array[$i] = $col->{$info_type};
                    $i++;
                }
                return $new_array;
            }
            else {
                return $this->col_info[$col_offset]->{$info_type};
            }
        }

        return null;
    }

    /**
     * Dumps the contents of any input variable to screen in a nicely
     * formatted and easy to understand way - any type: Object, Var or Array
     *
     * @param string $mixed
     */
    public function vardump($mixed = '') {

        echo "<p><table><tr><td bgcolor=ffffff><blockquote><font color=000090>";
        echo "<pre><font face=arial>";

        if (!$this->vardump_called) {
            echo "<font color=800080><b>SQL/DB Error</b></font>\n\n";
        }

        $var_type = gettype($mixed);
        print_r(($mixed ? $mixed : "<font color=red>No Value / False</font>"));
        echo "\n\n<b>Type:</b> " . ucfirst($var_type) . "\n";
        echo "<b>Last Query</b> [$this->num_queries]<b>:</b> " . ($this->last_query ? $this->last_query : "NULL") . "\n";
//        echo "<b>Last Function Call:</b> " . ($this->func_call ? $this->func_call : "None") . "\n";
        echo "<b>Last Rows Returned:</b> " . count($this->last_result) . "\n";
        echo "</font></pre></font></blockquote></td></tr></table>" . $this->donation();
        echo "\n<hr size=1 noshade color=dddddd>";

        $this->vardump_called = true;
    }

    /**
     * Displays the last query string that was sent to the database & a
     * table listing results (if there were any).
     */
    public function debug() {

        echo "<blockquote>";

        if ($this->last_error) {
            echo "<font face=arial size=2 color=000099><b>Last Error --</b> [<font color=000000><b>$this->last_error</b></font>]<p>";
        }

        echo "<font face=arial size=2 color=000099><b>Query</b> [$this->num_queries] <b>--</b> ";
        echo "[<font color=000000><b><xmp>$this->last_query</xmp></b></font>]</font><p>";

        echo "<font face=arial size=2 color=000099><b>Query Result..</b></font>";
        echo "<blockquote>";

        if ($this->col_info || $this->col_info === false) {

            // =====================================================
            // Results top rows

            echo "<table cellpadding=5 cellspacing=1 bgcolor=555555>";
            echo "<tr bgcolor=eeeeee><td nowrap valign=bottom><font color=555599 face=arial size=2><b>(row)</b></font></td>";

            if ($this->col_info) {
                for ($i = 0; $i < count($this->col_info); $i++) {
                    echo "<td nowrap align=left valign=top><font size=1 color=555599 face=arial>{$this->col_info[$i]->type} {$this->col_info[$i]->max_length}</font><br><span style='font-family: arial; font-size: 10pt; font-weight: bold;'>{$this->col_info[$i]->name}</span></td>";
                }
            }
            elseif ($this->last_result) {
                $first_row = reset($this->last_result);
                if (is_object($first_row)) { $first_row = get_object_vars($first_row); }
                foreach (array_keys($first_row) as $name) {
                    echo "<td nowrap align=left valign=top><span style='font-family: arial; font-size: 10pt; font-weight: bold;'>{$name}</span></td>";
                }
            }

            echo "</tr>";

            // ======================================================
            // print main results

            if ($this->last_result) {

                $i = 0;
                foreach ($this->get_results(null, ARRAY_N) as $one_row) {
                    $i++;
                    echo "<tr bgcolor=ffffff><td bgcolor=eeeeee nowrap align=middle><font size=2 color=555599 face=arial>$i</font></td>";

                    foreach ($one_row as $item) {
                        echo "<td nowrap><font face=arial size=2>$item</font></td>";
                    }

                    echo "</tr>";
                }
            } // if last result
            else {
                $colspan = 1 + (is_array($this->col_info) ? count($this->col_info) : 0);
                echo "<tr bgcolor=ffffff><td colspan=$colspan><font face=arial size=2>No Results</font></td></tr>";
            }

            echo "</table>";
        } // if col_info
        else {
            echo "<font face=arial size=2>No Results</font>";
        }

        echo "</blockquote></blockquote><hr noshade color=dddddd size=1>";

        $this->debug_called = true;
    }

    /**
     * Check if the field already exists in table
     *
     * @param $table2check
     * @param $column
     * @return bool
     */
    public function column_exists($table2check, $column) {
        $sql = "SHOW COLUMNS FROM " . $table2check;
        foreach ($this->get_col($sql, 0) as $column_name) {
            if ($column_name == $column) {
                return true;
            }
        }
        return false;
    }

    /**
     * Provides compatibility with the original class:
     * For the calls to get_row() and get_results without $query parameter specified
     * and incompatible $output types (i.e. when original query request had ARRAY_N output
     * and the current request has another output type) it is impossible to use
     * stored results, so we have to execute previous query again.
     *
     * (Actually, there are no such calls in Netcat.)
     *
     * @param $output
     */
    protected function execute_previous_query_when_needed($output) {
        if ($this->last_query && $this->result_output_type == ARRAY_N && $output != ARRAY_N) {
            trigger_error(
                "nc_db: Incompatible \$output types for consecutive calls with absent query string; query executed twice",
                E_USER_WARNING);

            $this->query($this->last_query, $output);
        }
    }

    /**
     * Provides partial compatibility with the original class
     *
     * @param $row
     * @param $source_format
     * @param $output_format
     * @return array|null|object
     */
    protected function convert_results($row, $source_format, $output_format) {
        if ($source_format == $output_format) { return $row; }

        if ($source_format == ARRAY_N) { // sorry; conversion impossible
            $this->register_error("Cannot convert ARRAY_N results to other types");
            return null;
        }

        // convert OBJECT → ARRAY_A or ARRAY_N
        if ($source_format == OBJECT) {
            $row = get_object_vars($row);
            if ($output_format == ARRAY_N) { $row = array_values($row); }
            return $row;
        }

        // convert ARRAY_A → OBJECT
        if ($output_format == OBJECT) {
            return (object)$row;
        }

        // convert ARRAY_A → ARRAY_N
        if ($output_format == ARRAY_N) {
            return array_values($row);
        }

        // should’t get here!
        return null;
    }

}
