<?php

/* $Id: filter.php 6209 2012-02-10 10:28:29Z denis $ */

abstract class nc_search_language_filter implements nc_search_extension {

    protected $context;

    public function __construct(nc_search_context $context) {
        $this->context = $context;
    }

    abstract public function filter(array $terms);
}
