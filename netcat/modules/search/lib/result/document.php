<?php

/* $Id: document.php 6209 2012-02-10 10:28:29Z denis $ */

/**
 * «Облегченная» версия nc_search_document для загрузки результатов поиска
 */
class nc_search_result_document extends nc_search_data_persistent {

    protected $table_name = "Search_Document";
    protected $properties = array(
        'id' => null,
        'site_id' => null,
        'sub_id' => null,
        'contenttype' => 'text/html',
        'path' => null, // только путь, без имени сайта — всегда ИСПОЛЬЗУЙТЕ url!
        'url' => null,
        'language' => null,
        'title' => null,
        'context' => null,
        'content' => null,
        'lastmodified' => null,
        'meta' => array(),
    );

    /**
     * Отображение в БД
     * @var array
     */
    protected $mapping = array(
        'id' => 'Document_ID',
        'site_id' => 'Catalogue_ID',
        'sub_id' => 'Subdivision_ID',
        'path' => 'Path',
        'title' => 'Title',
        'content' => 'Content',
        'contenttype' => 'ContentType',
        'language' => 'Language',
        'lastmodified' => 'LastModified',
        'meta' => 'Meta',
    );

    protected $serialized_properties = array('meta');

    public function get($option) {
        $value = parent::get($option);
        // если у страницы нет заголовка — вернуть вместо него заглушку (иначе может
        // пропасть ссылка на документ в результатах поиска)
        if ($option == 'title' && !strlen(trim($value))) {
            // $value = nc_search_util::decode_path(parent::get('path'));
            $value = NETCAT_MODULE_SEARCH_NO_TITLE;
        }
        return $value;
    }

}
