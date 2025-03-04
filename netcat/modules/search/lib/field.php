<?php

/* $Id: field.php 6209 2012-02-10 10:28:29Z denis $ */

/**
 * Поле документа (прежде всего HTML-страницы)
 *
 * ВНИМАНИЕ! Устанавливать значение необходимо через $owner_document->set_field_value() !!!
 */
class nc_search_field extends nc_search_data_persistent {
    const TYPE_STRING = 0;
    const TYPE_INTEGER = 1;

    protected $properties = array(
            'name' => null,
            'value' => null,
            'weight' => 1,
            'type' => self::TYPE_STRING, // тип поля
            'is_stored' => false, // хранится начальное значение в индексе
            'is_retrievable' => false, // начальное значение можно получить в результатах запроса
            'is_indexed' => true, // участвует в поиске (можно сделать запрос)
            'is_normalized' => true, // анализируется (разбивка на токены, морфоанализ)
            'is_searchable' => false, // allow field search queries? (*пользователь* может сделать запрос) --- НЕ ИСПОЛЬЗУЕТСЯ
            'is_sortable' => false, // allow field sort queries?
            'query' => null, // (xpath | tagName | regexp) to extract content
            'query_scope' => 'document', // [content|document]
            'query_use_first_matched' => false, // применять правила только до тех пор, пока не будет получен результат
            // (think 'content' field: '<!-- index -->' OR body)
            'filter_content' => null, // query, фильтрующий контент (think '<!-- noindex -->')
            'remove_from_parent' => true, // remove from HTML document upon extraction
    );
    protected $table_name = "Search_Field";
    protected $primary_key = 'name';
    protected $mapping = array('_generate' => true);
    protected $mapping_exclude = array('value');

}