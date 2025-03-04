<?php

/* $Id: normalize.php 6209 2012-02-10 10:28:29Z denis $ */

/**
 * Нормализация строки: замена сущностей html на соответствующие символы,
 * приведение к UTF NFC
 */
class nc_search_language_filter_normalize extends nc_search_language_filter {

    public function filter(array $terms) {
        $has_normalizer = class_exists("Normalizer", false);

        for ($i = 0, $max = sizeof($terms); $i < $max; $i++) {
            // убрать HTML Entities
            $terms[$i] = html_entity_decode($terms[$i], ENT_QUOTES, 'UTF-8');

            // Приведение к нормальной C-форме UTF
            if ($has_normalizer) { // расширение intl (PHP 5.2+)
                $terms[$i] = Normalizer::normalize($terms[$i], Normalizer::FORM_C);
            }
        }
        return $terms;
    }

}