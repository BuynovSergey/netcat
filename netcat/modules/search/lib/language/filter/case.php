<?php

/* $Id: case.php 6209 2012-02-10 10:28:29Z denis $ */

/**
 * Преобразование регистра
 */
class nc_search_language_filter_case extends nc_search_language_filter {

    public function filter(array $terms) {
        for ($i = 0, $max = sizeof($terms); $i < $max; $i++) {
            $terms[$i] = mb_convert_case($terms[$i], nc_search::get_setting('FilterStringCase'), 'UTF-8');
        }
        return $terms;
    }

}