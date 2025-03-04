<?php

require_once(dirname(__FILE__) . "/nc_search.class.php");
nc_search::init();

$nc_event = nc_core::get_object()->event;

$nc_event->add_listener(
    nc_event::AFTER_SITE_CREATED,
    function ($site_id) {
        nc_search::create_search_result_subdivision($site_id);
        nc_search::process_site_indexing($site_id);
    }
);

$nc_event->add_listener(
    nc_event::AFTER_SITE_IMPORTED,
    function ($site_id) {
        nc_search::process_site_indexing($site_id);
    }
);
