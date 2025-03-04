<?php

/* $Id: minlength.php 6209 2012-02-10 10:28:29Z denis $ */

/**
 * Длина слова
 */
class nc_search_language_filter_minlength extends nc_search_language_filter {

    public function filter(array $terms) {
        $min_length = nc_search::get_setting('MinWordLength');
        if ($min_length < 2) {
            return $terms;
        }

        $result = array();
        for ($i = 0, $max = sizeof($terms); $i < $max; $i++) {
            if (mb_strlen($terms[$i], 'UTF-8') >= $min_length) {
                $result[] = $terms[$i];
            }
        }
        return $result;
    }

}