<?php

/* $Id: stopword.php 6209 2012-02-10 10:28:29Z denis $ */

/**
 * Стоп-слово
 */
class nc_search_language_stopword extends nc_search_data_persistent {

    protected $properties = array(
            'id' => null,
            'language' => null,
            'word' => null,
    );
    protected $table_name = 'Search_Stopword';
    protected $mapping = array(
            'id' => 'Word_ID',
            '_generate' => true,
    );

}