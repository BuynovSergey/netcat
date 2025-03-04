<?php

// новый класс
require_once __DIR__ . "/nc_landing.class.php";
$nc_core = nc_core::get_object();
$nc_core->register_class_autoload_path('nc_landing_', __DIR__ . "/classes");

nc_landing_listener::init();
