<?php

require_once __DIR__ . "/nc_requests.class.php";
$nc_core = nc_Core::get_object();

$nc_core->register_class_autoload_path('nc_requests_', __DIR__ . "/classes");

$nc_core->event->add_listener(
    nc_Event::AFTER_SUBDIVISION_DELETED,
    array('nc_requests_form_settings_subdivision', 'delete_subdivision_settings')
);

$nc_core->event->add_listener(
    nc_Event::AFTER_INFOBLOCK_DELETED,
    array('nc_requests_form_settings_infoblock', 'delete_infoblock_settings')
);