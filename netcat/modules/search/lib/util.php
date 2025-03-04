<?php

/* $Id: util.php 8477 2012-11-28 15:37:40Z aix $ */

/**
 *
 */
class nc_search_util {

    static protected $locale_category = LC_ALL;
    static protected $original_locale;
    static protected $idn;
    static protected $idn_cache = array();

    /**
     * Локаль важна для корректного преобразования регистра и т.п.
     * (Вся обработка строк производится в UTF)
     *
     * (В неткете 4.2 устанавливается подходящая локаль только при подключении
     * соответствующего языкового файла в UTF-версии)
     * @param string $language  код языка, напр. 'ru'
     */
    static public function set_utf_locale($language) {
        if (self::$original_locale == null) {
            self::$original_locale = setlocale(self::$locale_category, "0");
            // раз original_locale не установлена, значит этим методом не пользовались
            // "According to MSDN, Windows setlocale()'s implementation does not support UTF-8 encoding",
            // так что под виндой это бессмысленно:
            setlocale(self::$locale_category, "$language.UTF8", "$language.UTF-8", "en_US.UTF8", "en_US.UTF-8");
        }
    }

    /**
     * Восстановить локаль, которая была до вызова set_utf_locale()
     */
    static public function restore_locale() {
        if (self::$original_locale != null) {
            setlocale(self::$locale_category, self::$original_locale);
            self::$original_locale = null;
        }
    }

    /**
     * Make regexp that matches the word (looks for word boundaries)
     * Definition of word boundaries is simplified to "neither a letter nor a digit".
     * @param string $regexp
     * @param string $flags
     * @param string $delimiter
     * @return string
     */
    static public function word_regexp($regexp, $flags = "", $delimiter = '/') {
        return $delimiter."(?:^|(?<![\pL\d]))".$regexp."(?:(?![\pL\d])|$)".$delimiter.$flags."u";
    }

    /**
     *
     * @param mixed $value
     * @return string
     */
    static public function db_escape($value) {
        return nc_Core::get_object()->db->escape($value);
    }

    /**
     * Получить часть URL - путь и запрос (path, query)
     * @param string $url
     * @return string
     */
    public static function get_url_path($url) {
        $parts = parse_url($url);
        return $parts["path"].(isset($parts["query"]) ? "?$parts[query]" : "");
    }

    /**
     * 62 seconds -> 1 h 2 min
     */
    public static function format_seconds($seconds) {
        $minutes = ceil($seconds / 60);
        if ($minutes < 60) {
            return sprintf(NETCAT_MODULE_SEARCH_ADMIN_MINUTES, $minutes);
        }
        return sprintf(NETCAT_MODULE_SEARCH_ADMIN_HOURS_MINUTES, floor($minutes / 60), $minutes % 60);
    }

    /**
     *
     */
    public static function format_time($timestamp) {
        if (!is_numeric($timestamp)) {
            $timestamp = strtotime($timestamp);
        }
        return strftime(NETCAT_MODULE_SEARCH_DATETIME_FORMAT, $timestamp);
    }

    /**
     *
     */
    public static function sql_datetime($timestamp = null) {
        if (!$timestamp) {
            $timestamp = time();
        }
        return strftime("%Y-%m-%d %H:%M:%S", $timestamp);
    }

    /**
     *
     */
    public static function get_nth_key(array $array, $number) {
        $i = 0;
        foreach ($array as $key => $v) { // предполагается, что функция обычно будет использоваться с $number == 0
            if ($i++ == $number) {
                return $key;
            }
        }
        return null;
    }

    /**
     *
     * @param string $query
     * @return boolean Возвращает истину, если в запросе есть логические операторы
     */
    public static function is_boolean_query($query) {
        return (bool) preg_match("/\b(?:and|or|not)\b|(?:\|\||&&|!)/i", (string)$query);
    }

    /**
     *
     * @return Net_IDNA2
     */
    protected static function get_idn_converter() {
        if (!self::$idn) {
            require_once 'Net/IDNA2.php'; // netcat/require/lib
            self::$idn = new Net_IDNA2;
        }
        return self::$idn;
    }

    /**
     *
     * @param string $host  ONLY the host name, e.g. "испытание.рф"
     * @return string
     */
    public static function encode_host($host) {
        if (!preg_match("/[^\w\-\.]/", $host)) {
            return $host;
        }
        $host = trim($host, " \t\n\r");
        if (!isset(self::$idn_cache[$host])) {
            try {
                self::$idn_cache[$host] = self::get_idn_converter()->encode($host);
            } catch (Net_IDNA2_Exception $e) {
                trigger_error("Cannot convert host name '$host' to punycode: {$e->getMessage()}", E_USER_WARNING);
                return $host;
	    } catch (UnexpectedValueException $e) {
		trigger_error("Cannot convert host name '$host' to punycode: {$e->getMessage()}", E_USER_WARNING);
		return $host;
	    }
        }
        return self::$idn_cache[$host];
    }

    /**
     *
     */
    public static function encode_path($path) {
        return preg_replace_callback('![^\x20-\x7E]!Su', array('nc_search_util', 'urlencode_callback'), $path);
    }

    /**
     *
     * @param array $matches
     * @return string
     */
    protected static function urlencode_callback(array $matches) {
        return urlencode($matches[0]);
    }

    /**
     *
     * @param string $url  full url with a protocol name, e.g. "http://испытание.рф/путь"
     * @return string
     */
    public static function encode_url($url) {
        if (!preg_match("/[^\x20-\x7E]/Su", $url)) {
            return $url;
        }

        $url_parts = parse_url($url);

        $url = $url_parts["scheme"]."://".
                self::encode_host($url_parts["host"]).
                (isset($url_parts["port"]) ? ":".$url_parts["port"] : "").
                (isset($url_parts["path"]) ? self::encode_path($url_parts["path"]) : "").
                (isset($url_parts["query"]) ? "?".self::encode_path($url_parts["query"]) : "").
                (isset($url_parts["fragment"]) ? "#".$url_parts["fragment"] : "");

        return $url;
    }

    /**
     *
     * @param string $host  ONLY the host name, e.g. "XN----7SBCPNF2DL2EYA.XN--P1AI"
     * @return string
     */
    public static function decode_host($host) {
        if (stripos($host, "xn--") === false) {
            return $host;
        }
        if (!isset(self::$idn_cache[$host])) {
            try {
                self::$idn_cache[$host] = self::get_idn_converter()->decode(strtolower($host));
            } catch (Net_IDNA2_Exception $e) {
                trigger_error("Cannot convert host name '$host' from punycode: {$e->getMessage()}", E_USER_WARNING);
                return $host;
            }
        }
        return self::$idn_cache[$host];
    }

    /**
     *
     */
    public static function decode_path($path) {
        return urldecode($path);
    }

    /**
     *
     * @param string $url  full URL (with scheme, hostname)
     * @return string
     */
    public static function decode_url($url) {
        if (strpos($url, "%") === false && stripos($url, "xn--") === false) {
            return $url;
        }

        $url_parts = parse_url($url);

        $url = $url_parts["scheme"]."://".
                self::decode_host($url_parts["host"]).
                (isset($url_parts["port"]) ? ":".$url_parts["port"] : "").
                (isset($url_parts["path"]) ? self::decode_path($url_parts["path"]) : "").
                (isset($url_parts["query"]) ? "?".$url_parts["query"] : "").
                (isset($url_parts["fragment"]) ? "#".$url_parts["fragment"] : "");

        return $url;
    }

    public static function convert($text, $to_unicode = false) {
        $nc_core = nc_Core::get_object();
        if ($nc_core->NC_UNICODE) return $text;
        $method = 'array_'.($to_unicode ? 'win2utf' : 'utf2win');
        return $nc_core->utf8->$method($text);
    }

    /**
     *
     */
    public static function can_use_binary_columns() {
        /**
         * До версии MySQL 5.0.3 возникают проблемы при использовании binary
         * полей: binary автоматически превращается в varbinary из-за наличия
         * колонки типа text в этой же таблице, а из значения varbinary удаляются
         * пробелы на конце.
         * http://dev.mysql.com/doc/refman/4.1/en/silent-column-changes.html
         * http://bugs.mysql.com/bug.php?id=14189
         *
         * (Удалить этот метод, когда будет убрана поддержка MySQL старых версий;
         * при этом в обновлении выполнить смену колонки Search_Link.Hash на
         * тип BINARY(20).)
         *
         * @see nc_search_link::make_hash_statement()
         * @see nc_search_link_manager::check_hash_column_type()
        */
        static $result;
        if ($result === null) {
            $result = (-1 == version_compare('5.0.2', nc_core('db')->get_var('SELECT VERSION()')));
        }
        return $result;
    }

}
