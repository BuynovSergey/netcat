<?php

abstract class nc_cache_io {

    protected function __construct() {

    }

    //abstract public function read();
    //abstract public function add();
    abstract public function get_size($key);
}
?>