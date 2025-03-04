<?php

abstract class nc_routing_pattern_part_keyword extends nc_routing_pattern_part {

    /**
     * @return string
     */
    protected function get_keyword_regexp() {
        return '/^([\w' . NETCAT_RUALPHABET . '-]+)/i';
    }

    /**
     * @return string
     */
    protected function get_keyword_delimiter() {
        return nc_core('NC_UNICODE') ? '/([^\pL\d])/' : '/([^a-zA-Zа-яА-ЯёЁ\d])/';
    }

    /**
     * Разбивает ключевое слово на более мелкие части,
     * которые теоретически также могут быть ключевыми словами.
     * Разделителями ключевого слова могут выступать любые не-буквы и
     * не-числа (@see self::get_keyword_delimiter()).
     * Варианты в результирующем массиве располагаются по убыванию длины.
     *
     * @param $max_keyword
     * @return array
     */
    protected function get_possible_keywords($max_keyword) {
        $keyword_parts = nc_preg_split($this->get_keyword_delimiter(), $max_keyword, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
        $possible_keywords = array();

        while (sizeof($keyword_parts)) {
            $possible_keywords[] = join('', $keyword_parts);
            array_pop($keyword_parts);
        }

        return $possible_keywords;
    }

}