<?php

/*
 * Функции для работы с многобайтовыми строками
 */

function nc_preg_split() {
    $nc_core = nc_Core::get_object();
    $args = func_get_args();

    if ($nc_core->NC_UNICODE) {
        $args[0] .= "u";
    }

    return call_user_func_array('preg_split', $args);
}

function nc_preg_match($pattern, $subject, array &$matches = null, $flags = null, $offset = null) {
    $nc_core = nc_Core::get_object();

    if ($nc_core->NC_UNICODE) {
        $pattern .= "u";
    }
    return preg_match($pattern, $subject, $matches, $flags, $offset);
}

function nc_preg_match_all($pattern, $subject, array &$matches = null, $flags = null, $offset = null) {
    $nc_core = nc_Core::get_object();

    if ($nc_core->NC_UNICODE) {
        $pattern .= "u";
    }
    return preg_match_all($pattern, $subject, $matches, $flags, $offset);
}

function nc_preg_replace($pattern, $replacement, $subject, $limit = -1, &$count = null) {
    $nc_core = nc_Core::get_object();

    if ($nc_core->NC_UNICODE) {
        if (is_array($pattern) && !empty($pattern)) {
            foreach ($pattern as $k => $v) {
                $pattern[$k] .= "u";
            }
        }
        else {
            $pattern .= "u";
        }
    }
    return preg_replace($pattern, $replacement, $subject, $limit, $count);
}

function nc_preg_replace_callback($pattern, $callback, $subject, $limit = -1, &$count = null) {
    $nc_core = nc_Core::get_object();

    if ($nc_core->NC_UNICODE) {
        if (is_array($pattern) && !empty($pattern)) {
            foreach ($pattern as $k => $v) {
                $pattern[$k] .= "u";
            }
        }
        else {
            $pattern .= "u";
        }
    }

    return preg_replace_callback($pattern, $callback, $subject, $limit, $count);
}

function nc_preg_grep() {
    $nc_core = nc_Core::get_object();
    $args = func_get_args();

    if ($nc_core->NC_UNICODE) {
        $args[0] .= "u";
    }

    return call_user_func_array('preg_grep', $args);
}

/**
 * Аналог strlen
 *
 * @param $string
 * @return int длина строки
 */
function nc_strlen($string) {
    $nc_core = nc_Core::get_object();

    if (!$nc_core->NC_UNICODE) {
        return strlen($string);
    }

    if ($nc_core->utf8->mbstring_ext()) {
        return mb_strlen($string);
    }
    else {
        return strlen(utf8_decode($string));
    }
}

/**
 * Аналог substr
 *
 * @param $string
 * @param $start
 * @param int|null $length
 * @return string
 */
function nc_substr($string, $start, $length = null) {
    $nc_core = nc_Core::get_object();

    if (!$nc_core->NC_UNICODE) {
        return $length != null ? substr($string, $start, $length) : substr($string, $start);
    }

    if ($nc_core->utf8->mbstring_ext()) {
        return $length != null ? mb_substr($string, $start, $length) : mb_substr($string, $start);
    }
    else {
        preg_match_all("/./su", $string, $ar);

        if ($length != null) {
            return join("", array_slice($ar[0], $start, $length));
        }
        else {
            return join("", array_slice($ar[0], $start));
        }
    }
}

/**
 * Аналог strpos
 *
 * @param $haystack
 * @param $needle
 * @param int|null $offset
 * @return bool|int
 */
function nc_strpos($haystack, $needle, $offset = null) {
    $nc_core = nc_Core::get_object();

    if (!$nc_core->NC_UNICODE) {
        return strpos($haystack, $needle, $offset);
    };

    if ($nc_core->utf8->mbstring_ext()) {
        return mb_strpos($haystack, $needle, $offset);
    }

    $comp = 0;

    while (!isset($length) || $length < $offset) {
        $pos = strpos($haystack, $needle, $offset + $comp);
        if ($pos === false) {
            return false;
        }
        $length = nc_strlen(substr($haystack, 0, $pos));
        if ($length < $offset) {
            $comp = $pos - $length;
        }
    }

    return $length;
}

/**
 * @param $haystack
 * @param $needle
 * @param int|null $offset
 * @return int|bool
 */
function nc_stripos($haystack, $needle, $offset = null) {
    if (nc_core('NC_UNICODE')) {
        return nc_strpos(nc_strtoupper($haystack), nc_strtoupper($needle), $offset);
    }
    else {
        return stripos($haystack, $needle, $offset);
    }
}

/**
 * Аналог strpos
 *
 * @param $haystack
 * @param $needle
 * @param null|int $offset
 * @return int or false
 */
function nc_strrpos($haystack, $needle, $offset = null) {
    $nc_core = nc_Core::get_object();

    if (!$nc_core->NC_UNICODE) {
        return strrpos($haystack, $needle, $offset);
    }

    if ($nc_core->utf8->mbstring_ext()) {
        return mb_strrpos($haystack, $needle, $offset);
    }

    $pos = strrpos($haystack, $needle, $offset);

    if ($pos === false) {
        return false;
    }

    return nc_strlen(substr($haystack, 0, $pos));
}

/**
 * Аналог strtoupper
 *
 * @param $string
 * @return string
 */
function nc_strtoupper($string) {
    $nc_core = nc_Core::get_object();
    if ($nc_core->utf8->mbstring_ext()) {
        return mb_convert_case($string, MB_CASE_UPPER, $nc_core->get_variable('NC_CHARSET'));
    }
    else {
        return strtoupper($string);
    }
}

/**
 * Аналог strtolower
 *
 * @param $string
 * @return string
 */
function nc_strtolower($string) {
    $nc_core = nc_Core::get_object();
    if ($nc_core->utf8->mbstring_ext()) {
        return mb_convert_case($string, MB_CASE_LOWER, $nc_core->get_variable('NC_CHARSET'));
    }
    else {
        return strtolower($string);
    }
}

/**
 * Аналог ucfirst
 *
 * @param string $string
 * @return string
 */
function nc_ucfirst($string) {
    $nc_core = nc_Core::get_object();
    if ($nc_core->utf8->mbstring_ext()) {
        $encoding = $nc_core->get_variable('NC_CHARSET');
        return mb_convert_case(mb_substr($string, 0, 1, $encoding), MB_CASE_UPPER, $encoding) .
               mb_substr($string, 1);
    }
    else {
        return ucfirst($string);
    }
}

/**
 * Аналог lcfirst
 *
 * @param string $string
 * @return string
 */
function nc_lcfirst($string) {
    $nc_core = nc_Core::get_object();
    if ($nc_core->utf8->mbstring_ext()) {
        $encoding = $nc_core->get_variable('NC_CHARSET');
        return mb_convert_case(mb_substr($string, 0, 1, $encoding), MB_CASE_LOWER, $encoding) .
               mb_substr($string, 1);
    }
    else {
        return lcfirst($string);
    }
}
