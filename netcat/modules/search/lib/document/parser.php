<?php

/* $Id: parser.php 6209 2012-02-10 10:28:29Z denis $ */

/**
 * 
 */
interface nc_search_document_parser extends nc_search_extension {

    /**
     * Загрузить документ.
     * Данный метод должен использоваться перед вызовом остальных методов.
     * (Парсер не создаётся для каждого документа для того, чтобы для подключения
     * парсеров для различных типов документов можно было использовать extension
     * manager — соответственно, парсер должен реализовывать интерфейс nc_search_extension).
     * @param nc_search_indexer_crawler_response $response
     */
    public function load(nc_search_indexer_crawler_response $response);

    /**
     * @return boolean
     */
    public function should_index();

    /**
     * @return nc_search_document
     */
    public function get_document();

    /**
     * @return array  all links
     */
    public function extract_links();

    /**
     * Проверка требований к работе парсера, вывод сообщений о найденных ошибках
     * конфигурации (используется в панели управления модулем)
     * @return null
     */
    public function check_environment();
}
