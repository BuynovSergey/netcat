<?php

require_once __DIR__ . '/nc_airee.class.php';
$nc_core->register_class_autoload_path('nc_airee_', __DIR__ . '/classes/', false);
$nc_core->add_output_processor(function($buffer) {
    return nc_airee::get_instance(nc_core::get_object()->catalogue->id())->replace_resources($buffer);
});
