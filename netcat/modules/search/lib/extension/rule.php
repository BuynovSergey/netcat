<?php

/* $Id: rule.php 6209 2012-02-10 10:28:29Z denis $ */

/**
 *
 */
class nc_search_extension_rule extends nc_search_data_persistent {

    protected $table_name = "Search_Extension";
    protected $properties = array(
            'id' => null,
            'extension_interface' => null,
            'extension_class' => null,
            'search_provider' => null,
            'action' => null,
            'language' => null,
            'content_type' => null,
            'priority' => 127,
            'enabled' => true,
    );
    protected $mapping = array(
            'id' => 'Rule_ID',
            'enabled' => 'Checked',
            '_generate' => true
    );

}
