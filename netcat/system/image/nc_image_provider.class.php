<?php

abstract class nc_image_provider {
    protected $nc_core = null;

    public function __construct() {
        $this->nc_core = nc_core::get_object();
    }
}