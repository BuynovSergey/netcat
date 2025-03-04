<?php

class nc_Utf8 extends nc_System {

    protected $UTFConverter;
    protected $_mbstring;
    // установлено расширение
    protected $_func_overload;

    public function __construct() {
        // load parent constructor
        parent::__construct();

        global $UTFConverter;

        $this->UTFConverter = $UTFConverter;

        if (($this->_mbstring = extension_loaded("mbstring"))) {
            $this->_func_overload = ini_get('mbstring.func_overload') & 2;
        }
    }

    // cp1251 to utf
    public function win2utf($str) {
        // system superior object
        $nc_core = nc_Core::get_object();

        if (extension_loaded("mbstring")) {
            return mb_convert_encoding($str, "UTF-8", "cp1251");
        }
        if (extension_loaded("iconv")) {
            return iconv("cp1251", "UTF-8", $str);
        }

        if (!$this->UTFConverter) {
            require_once($nc_core->get_variable("INCLUDE_FOLDER")."lib/utf8/utf8.class.php");
            $this->UTFConverter = new utf8(CP1251);
        }

        return $this->UTFConverter->strToUtf8($str);
    }

    // utf to cp1251
    public function utf2win($str) {
        // system superior object
        $nc_core = nc_Core::get_object();

        if (extension_loaded("mbstring")) {
            return mb_convert_encoding($str, "cp1251", "UTF-8");
        }
        if (extension_loaded("iconv")) {
            return iconv("UTF-8", "cp1251", $str);
        }

        if (!$this->UTFConverter) {
            require_once($nc_core->get_variable("INCLUDE_FOLDER")."lib/utf8/utf8.class.php");
            $this->UTFConverter = new utf8(CP1251);
        }

        return $this->UTFConverter->utf8ToStr($str);
    }

    public function array_utf2win($input) {
        if (!is_array($input)) {
            return $this->utf2win($input);
        }
        $output = array();

        foreach ($input as $k => $v) {
            $output[$this->utf2win($k)] = is_scalar($v) ? $this->utf2win($v) : $this->array_utf2win((array)$v);
        }

        return $output;
    }

    public function array_win2utf($input) {
        if (!is_array($input)) {
            return $this->win2utf($input);
        }
        $output = array();

        foreach ($input as $k => $v) {
            $output[$this->win2utf($k)] = is_scalar($v) ? $this->win2utf($v) : $this->array_win2utf((array)$v);
        }

        return $output;
    }

    public function mbstring_ext() {
        return $this->_mbstring;
    }

    public function func_overload() {
        return $this->_func_overload;
    }

    public function conv($from, $to, $text) {
        if (strtolower($from) == strtolower($to)) return $text;
        if ($from == 'utf-8' && $to == 'windows-1251')
                return $this->array_utf2win($text);
        if ($from == 'windows-1251' && $to == 'utf-8')
                return $this->array_win2utf($text);

        if (!is_array($text)) {
            $output = iconv($from, $to, $text);
        } else {
            $output = array();
            foreach ($text as $k => $v) {
                $output[$this->conv($from, $to, $k)] = $this->conv($from, $to, $v);
            }
        }

        return $output;
    }

    /**
     * Преобразует строку в UTF8 в верхний регистр
     * @param $string
     * @return string
     */
    public function uppercase($string) {
        if ($this->mbstring_ext()) {
            return mb_convert_case($string, MB_CASE_UPPER, 'UTF-8');
        }
        return $string;
    }

    /**
     * Преобразует строку в UTF8 в нижний регистр
     * @param $string
     * @return string
     */
    public function lowercase($string) {
        if ($this->mbstring_ext()) {
            return mb_convert_case($string, MB_CASE_LOWER, 'UTF-8');
        }
        return $string;
    }

}