<?php
/**
 *
 */
interface nc_condition_visitor {
    public function accept_condition(nc_condition $condition);
}