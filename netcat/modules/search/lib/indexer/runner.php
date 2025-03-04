<?php

/* $Id: runner.php 6209 2012-02-10 10:28:29Z denis $ */

/**
 * 
 */
interface nc_search_indexer_runner {
    public function loop(nc_search_indexer $indexer);
}
