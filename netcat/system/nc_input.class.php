<?php

class nc_Input extends nc_System {

    private $_variables;
    private $_GET = array();
    private $_POST = array();
    private $_COOKIE = array();
    private $_FILES = array();
    private $_SESSION = array();

    public function __construct() {
        parent::__construct();

        $this->_variables = array(
                "_REQUEST", "_SERVER", "_ENV", "_FILES", "_GET", "_POST", "_COOKIE", "_SESSION", "GLOBALS",
                "ADMIN_AUTHTIME", "ADMIN_AUTHTYPE", "ADMIN_LANGUAGE",
                "AUTHORIZATION_TYPE", "AUTHORIZE_BY", "AUTH_USER_GROUP", "AUTH_USER_ID",
                "DIRCHMOD", "FILECHMOD", "CHARSET", "SOURCE_CHARSET",
                "DOCUMENT_ROOT", "DOC_DOMAIN", "DOMAIN_NAME", "EDIT_DOMAIN", "HTTP_HOST",
                "HTTP_IMAGES_PATH", "HTTP_FILES_PATH", "HTTP_DUMP_PATH", "HTTP_ROOT_PATH", "HTTP_TMP_PATH", "HTTP_CACHE_PATH", "HTTP_TRASH_PATH",
                "ADMIN_PATH", "ADMIN_TEMPLATE", "NC_JQUERY_PATH", "ASSET_PATH",
                "NC_CHARSET", "NC_UNICODE", "NC_ADMIN_HTTPS",
                "MYSQL_CHARSET", "MYSQL_DB_NAME", "MYSQL_HOST", "MYSQL_PASSWORD", "MYSQL_USER", "MYSQL_PORT", "MYSQL_SOCKET", "MYSQL_ENCRYPT", "MYSQL_TIMEZONE", "SHOW_MYSQL_ERRORS",
                "SYSTEM_FOLDER", "SUB_FOLDER", "NETCAT_ROOT_FOLDER", "ROOT_FOLDER", "TMP_FOLDER", "FILES_FOLDER",
                "MODULE_FOLDER", "INCLUDE_FOLDER", "ADMIN_TEMPLATE_FOLDER", "DUMP_FOLDER", "CACHE_FOLDER", "TRASH_FOLDER", "ADMIN_FOLDER",
                "HTTP_TEMPLATE_PATH", "HTTP_TEMPLATE_CACHE_PATH", "TEMPLATE_FOLDER", "CLASS_TEMPLATE_FOLDER", "WIDGET_TEMPLATE_FOLDER",
                "JQUERY_FOLDER", "ASSET_FOLDER", "MODULE_TEMPLATE_FOLDER",
                "PHP_TYPE", "REDIRECT_STATUS", "NC_REDIRECT_DISABLED", "NC_DEPRECATED_DISABLED", "BBCODE", "AJAX_SAVER", "DEVELOPER_NAME", "DEVELOPER_URL", "nc_core", "use_gzip_compression");

        $this->prepare_extract();
    }

    public function clear_system_vars($result) {
        foreach ($this->_variables as $var) {
            unset($result[$var]);
        }
        return $result;
    }

    public function prepare_extract($reset = false) {
        $nc_core = nc_Core::get_object();
        static $result = array();

        $nc_core->REQUEST_URI = isset($_GET['REQUEST_URI']) ? $_GET['REQUEST_URI']
                                                            : (isset($_POST['REQUEST_URI']) ? $_POST['REQUEST_URI']
                                                                                            : (isset($_ENV['REQUEST_URI']) ? $_ENV['REQUEST_URI']
                                                                                                                           : getenv("REQUEST_URI")));

        if (nc_substr($nc_core->REQUEST_URI, 0, 1) != "/") {
            $nc_core->REQUEST_URI = "/" . $nc_core->REQUEST_URI;
        }

        $nc_core->REQUEST_URI = trim($nc_core->REQUEST_URI);

        require_once __DIR__ . '/../require/s_common.inc.php';
        $url = nc_get_scheme() . "://" . getenv("HTTP_HOST") . $nc_core->REQUEST_URI;
        $parsed_url = @parse_url($url);

        if (is_array($parsed_url) && array_key_exists('query', $parsed_url) && $parsed_url['query']) {
            parse_str($parsed_url['query'], $parsed_query_arr);
            $parsed_query_arr = $this->clear_system_vars($parsed_query_arr);
            $_GET = $parsed_query_arr ? $parsed_query_arr : array();
        }

        if (!empty($result) && !$reset) {
            return $result;
        }

        $superglobals = array(
            "_COOKIE" => $_COOKIE,
            "_GET"    => $_GET,
            "_POST"   => $_POST,
            "_FILES"  => $_FILES,
            "_ENV"    => $_ENV,
            "_SERVER" => $_SERVER
        );

        // nc_input_token идентифицирует запрос после редиректа (см. метод mark_input_as_escaped() и nc_security_filter::reload())
        if (isset($superglobals['_POST']['nc_input_token'])) {
            $nc_input_token = $superglobals['_POST']['nc_input_token'];
        } else if (isset($superglobals['_GET']['nc_input_token'])) {
            $nc_input_token = $superglobals['_GET']['nc_input_token'];
        } else {
            $nc_input_token = null;
        }

        if (!empty($_SESSION['nc_input_escaped'][$nc_input_token])) {
            $already_escaped = $_SESSION['nc_input_escaped'][$nc_input_token];
        } else {
            $already_escaped = array();
        }

        $are_magic_quotes_enabled = function_exists('get_magic_quotes_gpc') && get_magic_quotes_gpc();

        foreach ($superglobals as $key => $super_array) {
            if ($are_magic_quotes_enabled) {
                $extracted_values = $super_array;
            } else {
                // Netcat эмулирует magic quotes для переменных, импортированных в глобальную область видимости.
                // Например, для параметра x:
                // в $_GET[x], $nc_core->input->fetch_get('x') — значение в том виде, как было передано (без экранирования)
                // в $GLOBALS['x'] — с экранированием слешами (эмуляция magic quotes)
                $extracted_values = $this->recursive_add_slashes($super_array, '$' . $key, $already_escaped);
            }

            $result = array_merge($result, $extracted_values);
            $this->$key = $this->prepare_superglobals($super_array);
        }

        $result = $this->clear_system_vars($result);

        $result['REQUEST_URI'] = str_replace('$', '', htmlspecialchars(nc_array_value($result, 'REQUEST_URI')));
        $result['REQUEST_METHOD'] = str_replace('$', '', htmlspecialchars(nc_array_value($result, 'REQUEST_METHOD')));

        foreach ($this->_variables as $var) {
            if ((array_key_exists($var, $superglobals) || $this->in_superglobal($var)) && !in_array($var, array('HTTP_HOST', 'DOCUMENT_ROOT', 'REDIRECT_STATUS'))) {
                continue;
            }
            global $$var;
            $nc_core->set_variable($var, $$var);
        }

        if (!$nc_core->NC_CHARSET){
            $nc_core->NC_CHARSET = 'windows-1251';
        }

        if (!$nc_core->NC_JQUERY_PATH) {
            $nc_core->NC_JQUERY_PATH = $nc_core->SUB_FOLDER . $nc_core->HTTP_TEMPLATE_PATH . 'jquery/jquery.min.js';
        }

        if (!$nc_core->ASSET_PATH) {
            $nc_core->ASSET_PATH = $nc_core->SUB_FOLDER . $nc_core->HTTP_TEMPLATE_PATH . 'asset/';
        }

        if (!$nc_core->ASSET_FOLDER) {
            $nc_core->ASSET_FOLDER = $nc_core->DOCUMENT_ROOT . $nc_core->ASSET_PATH;
        }

        if (!$nc_core->HTTP_TEMPLATE_CACHE_PATH) {
            $nc_core->HTTP_TEMPLATE_CACHE_PATH = $nc_core->HTTP_TEMPLATE_PATH;
        }

        $nc_core->MYSQL_ENCRYPT = strtoupper($nc_core->MYSQL_ENCRYPT);

        if (!$nc_core->MYSQL_ENCRYPT || !in_array($nc_core->MYSQL_ENCRYPT, array('PASSWORD', 'OLD_PASSWORD', 'MD5', 'SHA', 'SHA1'))) {
            $nc_core->MYSQL_ENCRYPT = 'PASSWORD';
        }

        if (!$nc_core->NC_UNICODE && ($_POST["NC_HTTP_REQUEST"] || $_SERVER['HTTP_X_REQUESTED_WITH'])) {
            $result = $nc_core->utf8->array_utf2win($result);
            foreach ($superglobals as $key => $super_array) {
                $this->$key = $nc_core->utf8->array_utf2win($this->$key);
            }
        }

        return $result;
    }

    public function recursive_add_slashes($input, $parent_source = null, array $already_escaped = array()) {
        if (!is_array($input)) {
            return addslashes($input);
        }
        $output = array();

        foreach ($input as $key => $value) {
            $value_source = $parent_source ? $parent_source . "[$key]" : '$' . $key;

            if (is_array($value)) {
                $output[$key] = $this->recursive_add_slashes($value, $value_source, $already_escaped);
            } elseif (isset($already_escaped[$value_source]) && $already_escaped[$value_source] === $value) {
                $output[$key] = $value;
            } else {
                $output[$key] = addslashes($value);
            }
        }
        return $output;
    }

    public function prepare_superglobals($array) {
        if (function_exists('get_magic_quotes_gpc') && get_magic_quotes_gpc()) {
            return $this->recursive_stripcslashes($array);
        }

        return $array;
    }

    public function recursive_stripcslashes($input) {
        if (!is_array($input)) {
            return stripcslashes($input);
        }
        $output = array();

        foreach ($input as $k => $v) {
            $output[$k] = is_array($v) ? $this->recursive_stripcslashes($v) : stripcslashes($v);
        }

        return $output;
    }


    public function recursive_striptags($input) {
        if (!is_array($input)) {
            return strip_tags($input);
        }
        $output = array();

        foreach ($input as $k => $v) {
            $output[$k] = is_array($v) ? $this->recursive_striptags($v) : strip_tags($v);
        }

        return $output;
    }

    public function recursive_striptags_escape($input) {
        $db = nc_Core::get_object()->db;
        if (!is_array($input)) {
            return $db->escape(strip_tags($input));
        }
        $output = array();

        foreach ($input as $k => $v) {
            $output[$k] = is_array($v) ? $this->recursive_striptags_escape($v) : $db->escape(strip_tags($v));
        }

        return $output;
    }

    public function in_superglobal($var) {
        $superglobals = array("_GET" => $_GET, "_POST" => $_POST, "_COOKIE" => $_COOKIE, "_FILES" => $_FILES, "_ENV" => $_ENV, "_SERVER" => $_SERVER);
        foreach ($superglobals as $v) {
            return array_key_exists($var, $v);
        }
    }

    /**
     * @param string $item
     * @return bool|array|mixed
     */
    public function fetch_get($item = "") {

        if (empty($this->_GET))
            return false;

        if ($item) {
            return array_key_exists($item, $this->_GET) ? $this->_GET[$item] : null;
        } else {
            return $this->_GET;
        }
    }

    /**
     * @param string $item
     * @return bool|array|mixed
     */
    public function fetch_post($item = "") {

        if (empty($this->_POST))
            return false;

        if ($item) {
            return array_key_exists($item, $this->_POST) ? $this->_POST[$item] : null;
        } else {
            return $this->_POST;
        }
    }

    /**
     * @param string $item
     * @return bool|array|mixed
     */
    public function fetch_cookie($item = "") {

        if (empty($this->_COOKIE))
            return false;

        if ($item) {
            return array_key_exists($item, $this->_COOKIE) ? $this->_COOKIE[$item] : null;
        } else {
            return $this->_COOKIE;
        }
    }

    /**
     * @param string $item
     * @return bool|array|mixed
     */
    public function fetch_session($item = "") {

        if (empty($this->_SESSION))
            return false;

        if ($item) {
            return array_key_exists($item, $this->_SESSION) ? $this->_SESSION[$item] : null;
        } else {
            return $this->_SESSION;
        }
    }

    /**
     * @param string $item
     * @return bool|array|mixed
     */
    public function fetch_files($item = "") {

        if (empty($this->_FILES))
            return false;

        if ($item) {
            return array_key_exists($item, $this->_FILES) ? $this->_FILES[$item] : null;
        } else {
            return $this->_FILES;
        }
    }

    /**
     * @param string $item
     * @return bool|array|mixed
     */
    public function fetch_get_post($item = "") {

        if (empty($this->_GET) && empty($this->_POST))
            return false;

        if ($item) {
            return array_key_exists($item, $this->_GET) ? $this->_GET[$item] : (array_key_exists($item, $this->_POST) ? $this->_POST[$item] : null);
        } else {
            return array_merge($this->_POST, $this->_GET);
        }
    }


    /**
     * @param string $item
     * @return bool|array|mixed
     */
    public function fetch_post_get($item = "") {

        if (empty($this->_GET) && empty($this->_POST)) {
            return false;
        }

        if ($item) {
            return array_key_exists($item, $this->_POST)
                ? $this->_POST[$item]
                : (array_key_exists($item, $this->_GET) ? $this->_GET[$item] : null);
        }
        else {
            return array_merge($this->_GET, $this->_POST);
        }
    }

    /**
     * @param $variable
     * @param $key
     * @param $value
     */
    public function set($variable, $key, $value) {
        $this->{$variable}[$key] = $value;
    }

    /**
     * Помечает в $_SESSION, что входящий параметр экранирован слешами.
     * Используется для того, чтобы избежать двойного экранирования в nc_input::recursive_add_slashes()
     * при работе фильтров nc_security_filter_* в режиме переадресации с экранированием.
     *
     * @param string $input_token значение входящего параметра nc_input_token
     * @param string $input_source источник значения в виде '$_GET[x][y]'
     * @param string $escaped_value значение после экранирования
     */
    public function mark_input_as_escaped($input_token, $input_source, $escaped_value) {
        $_SESSION['nc_input_escaped'][$input_token][$input_source] = $escaped_value;
    }

    /**
     * Проверяет, было ли значение экранировано при предыдущем запросе
     * @param string $input_source источник значения в виде '$_GET[x][y]'
     * @return bool
     */
    public function is_input_escaped($input_source) {
        $input_token = $this->fetch_post_get('nc_input_token');
        return $input_token && isset($_SESSION['nc_input_escaped'][$input_token][$input_source]);
    }

    /**
     * Рекурсивно применяет htmlspecialchars()
     * @param $input
     * @return mixed
     */
    public function filter_html($input) {
        if ($input) {
            $escape = function($i) {
                return htmlspecialchars($i, 0, nc_core::get_object()->NC_CHARSET, false);
            };
            $input = filter_var($input, FILTER_CALLBACK, array('options' => $escape));
        }
        return $input;
    }

    /**
     * Возвращает значения из _POST с применённым htmlspecialchars()
     * @param $item
     * @return mixed
     */
    public function fetch_post_text($item = "") {
        return $this->filter_html($this->fetch_post($item));
    }

    /**
     * Возвращает значения из _GET с применённым htmlspecialchars()
     * @param $item
     * @return mixed
     */
    public function fetch_get_text($item = "") {
        return $this->filter_html($this->fetch_get($item));
    }

}