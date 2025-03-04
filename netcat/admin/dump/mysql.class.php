<?php

/**
 * Backup of mysql database
 */

define('HAR_LOCK_TABLE', 1);
define('HAR_FULL_SYNTAX', 2);
define('HAR_DROP_TABLE', 4);
define('HAR_NO_STRUCT', 8);
define('HAR_NO_DATA', 16);
define('HAR_ALL_OPTIONS', HAR_LOCK_TABLE | HAR_FULL_SYNTAX | HAR_DROP_TABLE);

define('HAR_ALL_DB', 1);
define('HAR_ALL_TABLES', 1);

define('OS_Unix', 'u');
define('OS_Windows', 'w');
define('OS_Mac', 'm');

class MYSQL_DUMP {

    protected $dbhost = '';
    protected $dbuser = '';
    protected $dbpwd = '';
    protected $database;
    protected $dbport = '';
    protected $dbsocket = '';
    protected $charset;
    protected $tables;
    protected $conn;
    protected $result;
    protected $error = '';
    protected $OS_FullName;
    protected $lineEnd;
    protected $OS_local = '';

    /**
     * Class Object
     *
     * @param string $host
     * @param string $user
     * @param string $dbpwd
     * @param string $port
     * @param string $socket
     * @param string $charset
     */
    public function __construct($host = '', $user = '', $dbpwd = '', $port = '', $socket = '', $charset = '') {
        $this->setDBHost($host, $user, $dbpwd, $port, $socket, $charset);

        $this->OS_FullName = array(OS_Unix => 'UNIX', OS_Windows => 'WINDOWS', OS_Mac => 'MACOS');
        $this->lineEnd = array(OS_Unix => "\n", OS_Mac => "\r", OS_Windows => "\r\n");

        $this->OS_local = OS_Unix;
        if (stripos(PHP_OS, 'WIN') === 0) {
            $this->OS_local = OS_Windows;
        } elseif (stripos(PHP_OS, 'MAC') === 0) {
            $this->OS_local = OS_Mac;
        }
    }

    /**
     * Set the database connection parameters
     *
     * @param $host
     * @param $user
     * @param $dbpwd
     * @param $port
     * @param $socket
     * @param $charset
     */
    public function setDBHost($host, $user, $dbpwd, $port, $socket, $charset) {
        $this->dbhost = $host;
        $this->dbuser = $user;
        $this->dbpwd = $dbpwd;
        $this->dbport = $port ?: ini_get('mysqli.default_port');
        $this->dbsocket = $socket ?: ini_get('mysqli.default_socket');
        $this->charset = $charset;
    }

    /**
     * Return last error
     *
     * @return String
     */
    public function error() {
        return $this->error;
    }

    public function dump_to_file($file, $database = HAR_ALL_DB, $tables = HAR_ALL_TABLES, $options = HAR_ALL_OPTIONS) {
        return $this->dumpDB($database, $tables, $options, $file);
    }

    /**
     * Take backup of the database
     *
     * @param Mixed $database (It can be string separated by comma (,) or single database name on an array of database names
     * @param Mixed $tables (It can be string separated by comma (,) or single table name on an array of table names
     * @param Int $options
     * @param String $file sql dump filename
     * @return String SQL Commands
     */
    public function dumpDB($database = HAR_ALL_DB, $tables = HAR_ALL_TABLES, $options = HAR_ALL_OPTIONS, $file = '') {
        global $TMP_FOLDER;
        set_time_limit(0);
        $this->_connect();

        if (empty($database)) {
            $this->error = 'Specify the database.';
            return false;
        }

        if (empty($tables)) {
            $this->error = 'Specify the tables.';
            return false;
        }

        if ($database == HAR_ALL_DB) {
            $sql = 'SHOW DATABASES';
            $this->result = @mysqli_query($this->conn, $sql);
            if (mysqli_error($this->conn) !== '') {
                $this->error = 'Error: ' . mysqli_error($this->conn);
                return false;
            }

            while ($row = mysqli_fetch_array($this->result, MYSQLI_NUM)) {
                $this->database[] = $row[0];
            }
        } elseif (is_string($database)) {
            $this->database = @explode(',', $database);
        }

        $tmpFileName = $TMP_FOLDER . 'sql_tmp_' . md5(uniqid('', true)) . 'dat';
        $tmpFile = fopen($tmpFileName, 'w+');

        $lineEnd = $this->lineEnd[$this->OS_local];

        fwrite($tmpFile, '# MySql Dump' . $lineEnd);
        fwrite($tmpFile, '# Host: ' . $this->dbhost . $lineEnd);
        fwrite($tmpFile, '# Time: ' . date('Y.m.d H:i:s') . $lineEnd);

        $sql = 'SELECT VERSION()';
        $this->result = mysqli_query($this->conn, $sql);
        $row = mysqli_fetch_array($this->result, MYSQLI_NUM);
        fwrite($tmpFile, '# Server version ' . $row[0] . $lineEnd);
        fwrite($tmpFile, '# -------------------------------------------------' . $lineEnd . $lineEnd);

        fwrite($tmpFile, "SET NAMES '{$this->charset}';" . $lineEnd . $lineEnd);

        for ($i = 0; $i < count($this->database); $i++) {
            if (count($this->database) > 1) {
                fwrite($tmpFile, "USE `{$this->database[$i]}`;" . $lineEnd . $lineEnd);
            }

            $this->result = @mysqli_query($this->conn, "USE `{$this->database[$i]}`");

            if (mysqli_error($this->conn) !== '') {
                $this->error = 'Error: ' . mysqli_error($this->conn);
                return false;
            }

            mysqli_query($this->conn, "SET NAMES '" . $this->charset . "'");

            $this->tables = array();
            if ($tables == HAR_ALL_TABLES) {
                $sql = 'SHOW TABLES';
                $this->result = @mysqli_query($this->conn, $sql);
                if (mysqli_error($this->conn) !== '') {
                    $this->error = 'Error: ' . mysqli_error($this->conn);
                    return false;
                }

                while ($row = mysqli_fetch_array($this->result, MYSQLI_NUM)) {
                    $this->tables[] = $row[0];
                }
            } elseif (is_string($tables)) {
                $this->tables = @explode(',', $tables);
            }
            for ($j = 0; $j < count($this->tables); $j++) {
                if (($options & HAR_NO_STRUCT) != HAR_NO_STRUCT) {
                    $sql = "SHOW CREATE TABLE `{$this->tables[$j]}`";
                    $this->result = @mysqli_query($this->conn, $sql);
                    if (mysqli_error($this->conn) !== '') {
                        $this->error = 'Error: ' . mysqli_error($this->conn);
                        return false;
                    }
                    $row = mysqli_fetch_array($this->result, MYSQLI_NUM);


                    fwrite($tmpFile, ' #' . $lineEnd);
                    fwrite($tmpFile, " # Table structure for table '{$this->tables[$j]}'" . $lineEnd);
                    fwrite($tmpFile, ' #' . $lineEnd . $lineEnd);

                    if (($options & HAR_DROP_TABLE) == HAR_DROP_TABLE) {
                        fwrite($tmpFile, "DROP TABLE IF EXISTS `{$this->tables[$j]}`;" . $lineEnd);
                    }
                    fwrite($tmpFile, $row[1] . ';' . $lineEnd . $lineEnd . $lineEnd);
                }

                if (($options & HAR_NO_DATA) != HAR_NO_DATA) {
                    fwrite($tmpFile, ' #' . $lineEnd);
                    fwrite($tmpFile, " # Dumping data for table '{$this->tables[$j]}'" . $lineEnd);
                    fwrite($tmpFile, ' #' . $lineEnd . $lineEnd);

                    if (($options & HAR_LOCK_TABLE) == HAR_LOCK_TABLE) {
                        fwrite($tmpFile, "LOCK TABLES `{$this->tables[$j]}` WRITE;" . $lineEnd);
                    }

                    $temp_sql = "INSERT INTO `{$this->tables[$j]}`";
                    if (($options & HAR_FULL_SYNTAX == HAR_FULL_SYNTAX)) {
                        $sql = 'SHOW COLUMNS FROM ' . $this->tables[$j];
                        $this->result = @mysqli_query($this->conn, $sql);
                        if (mysqli_error($this->conn) !== '') {
                            $this->error = 'Error : ' . mysqli_error($this->conn);
                            return false;
                        }
                        $fields = array();
                        $fields_null = array();
                        while ($row = mysqli_fetch_array($this->result, MYSQLI_NUM)) {
                            $fields[] = $row[0];
                            $fields_null[] = $row[2];
                        }
                        $temp_sql .= ' (`' . @implode('`,`', $fields) . '`)';
                    }

                    $this->result = @mysqli_query($this->conn, 'SELECT * FROM ' . $this->tables[$j]);
                    if (mysqli_error($this->conn) !== '') {
                        $this->error = 'Error: ' . mysqli_error($this->conn);
                        return false;
                    }
                    while ($row = mysqli_fetch_array($this->result, MYSQLI_NUM)) {
                        foreach ($row as $key => $value) {
                            $row[$key] = mysqli_real_escape_string($this->conn, $value);
                        }

                        fwrite($tmpFile, $temp_sql . ' VALUES (');
                        foreach ($row as $key => $value) {
                            fwrite($tmpFile, $key != 0 ? ',' : '');
                            if ($fields_null[$key] === 'YES' && !$row[$key]) {
                                fwrite($tmpFile, 'NULL');
                            } else {
                                fwrite($tmpFile, '"' . $row[$key] . '"');
                            }
                        }
                        fwrite($tmpFile, ');' . $lineEnd);
                    }
                    if (($options & HAR_LOCK_TABLE) == HAR_LOCK_TABLE) {
                        fwrite($tmpFile, 'UNLOCK TABLES;' . $lineEnd);
                    }

                }

                fwrite($tmpFile, $lineEnd . $lineEnd);
            }
        }

        fclose($tmpFile);

        if ($file) {
            $result = null;
            rename($tmpFileName, $file);
        } else {
            $result = file_get_contents($tmpFileName);
            unlink($tmpFileName);
        }

        return $result;
    }

    /**
     * Save the sql file on server
     *
     * @param String $sql
     * @param String $sqlfile
     * @return Boolean
     */
    public function save_sql($sql, $sqlfile = '') {
        if (empty($sqlfile)) {
            $sqlfile = @implode('_', $this->database) . '.sql';
        }
        $fp = @fopen($sqlfile, 'wb');
        if (!is_resource($fp)) {
            $this->error = 'Error: Unable to save file.';
            return false;
        }
        @fwrite($fp, $sql);
        @fclose($fp);
        return true;
    }

    /**
     * force to download the sql file
     *
     * @param String $sql
     * @param String $sqlfile
     * @return Boolean
     */
    public function download_sql($sql, $sqlfile = '') {
        if (empty($sqlfile)) {
            $sqlfile = @implode('_', $this->database) . '.sql';
        }
        @header('Cache-Control: '); // leave blank to avoid IE errors
        @header('Pragma: '); // leave blank to avoid IE errors
        @header('Content-type: application/octet-stream');
        @header('Content-type: application/octet-stream');
        @header('Content-Disposition: attachment; filename=' . $sqlfile);
        echo $sql;
    }

    /**
     * Restore the backup file
     *
     * @param String $sqlfile
     * @param string $dbname
     * @return Boolean
     */
    public function restoreDB($sqlfile, $dbname) {
        $this->error = '';
        $this->_connect();

        if (!is_file($sqlfile)) {
            $this->error = 'Error: Not a valid file.';
            return false;
        }

        mysqli_select_db($this->conn, $dbname);

        $file_size = filesize($sqlfile);
        if (!$file_size) {
            $this->error = 'Sql File is empty.';
            return false;
        }

        $fp = fopen($sqlfile, 'r');
        $sql = '';

        while (!feof($fp)) {
            $line = fgets($fp);
            $sql .= trim($line);
            if (empty($sql)) {
                $sql = "";
                continue;
            }

            if (preg_match("/^[#-].*+\r?\n?/i", trim($line))) {
                $sql = '';
                continue;
            }

            if (!preg_match("/;[\r\n]+/", $line)) {
                continue;
            }

            mysqli_query($this->conn, $sql);
            if (mysqli_error($this->conn) !== '') {
                $this->error .= '<br>' . mysqli_error($this->conn);
            }

            $sql = '';
        }
        fclose($fp);
        if (!empty($this->error)) {
            return false;
        }
        return true;
    }

    public function _connect() {
        if (!($this->conn instanceof mysqli)) {
            $this->conn = @mysqli_connect($this->dbhost, $this->dbuser, $this->dbpwd, '', $this->dbport, $this->dbsocket);
        }
        if (!($this->conn instanceof mysqli)) {
            $this->error = mysqli_connect_error();
            return false;
        }

        mysqli_query($this->conn, "SET NAMES '{$this->charset}'");
        mysqli_query($this->conn, 'SET SQL_QUOTE_SHOW_CREATE = 1');
        mysqli_query($this->conn, 'SET sql_mode = ""');

        return $this->conn;
    }

}

# End of Backup of mysql database

?>