<?php

/**
 * Запускает переиндексацию по правилу в кроне в ближайшее возможное время
 */
if (!class_exists("nc_system")) { die; }

while (@ob_end_clean()); // discard output

    $area = $this->get_input('area');
if (!$area) {
    print "0; // no area";
    die;
}

try {
    nc_search::index_area($area, "now");
    print "1";
} catch (Exception $e) {
    print "0; /* {$e->getMessage()} */";
}

die;