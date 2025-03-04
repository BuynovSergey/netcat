<?php

/* $Id: yo.php 6209 2012-02-10 10:28:29Z denis $ */

/**
 * Ё → Е
 */
class nc_search_language_filter_yo extends nc_search_language_filter {

    public function filter(array $terms) {
        for ($i = 0, $max = sizeof($terms); $i < $max; $i++) {
            // оба варианта (Ёё) на всякий случай — если вдруг кто-то вздумает
            // использовать когда-то другой регистр
            $terms[$i] = strtr($terms[$i], array("Ё" => "Е", "ё" => "е"));
        }
        return $terms;
    }

}