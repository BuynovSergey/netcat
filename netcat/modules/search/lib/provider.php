<?php

/* $Id: provider.php 6209 2012-02-10 10:28:29Z denis $ */

/**
 * Интерфейс службы поиска (поискового индекса)
 */
interface nc_search_provider {

    /**
     * Метод вызывается однократно после установки модуля (или системы в редакции, 
     * содержащей модуль)
     */
    public function first_run();

    /**
     * Проверка правильности настроек сервера, выводится на странице «Информация»
     * в панели управления модулем. Метод должен вывести сообщения об ошибках
     * (используйте фунцию nc_print_status())
     */
    public function check_environment();

    /**
     * Удаление документа из индекса
     * @param nc_search_document $document
     */
    public function remove_document(nc_search_document $document);

    /**
     * Обработать документ (конкретный класс должен проверить, есть ли документ
     * в индексе, и в зависимости от этого обновить существующий документ или добавить
     * новый)
     * @param nc_search_document $document
     */
    public function process_document(nc_search_document $document);

    /**
     * Запись изменений в индекс
     */
    public function commit();

    /**
     * Оптимизация индекса (запускается после окончания переиндексации)
     */
    public function optimize();

    /**
     * Выполнение запроса
     * @param nc_search_query $query
     * @param boolean $should_highlight
     * @return nc_search_result
     */
    public function find(nc_search_query $query, $should_highlight = true);

    /**
     * Переиндексация области здесь и сейчас
     * @param string $area_string   area string OR rule ID
     * @param integer $run_type  тип запуска
     */
    public function index_area($area_string, $run_type = nc_search::INDEXING_NC_CRON);

    /**
     * Запланировать переиндексацию области в указанное время
     * @param string $area_string   area string, rule ID
     * @param int $timestamp
     */
    public function schedule_indexing($area_string, $timestamp);

    /**
     * Работает ли в данный момент переиндексация?
     * @return mixed   FALSE если индексирование не производится
     */
    public function is_reindexing();

    /**
     * Получить массив с заголовками страниц для autocomplete
     * @param string $input
     * @param string $language
     * @param integer $site_id
     * @return array   элементы массива: array("label" => "Page Title", "url" => "/path/")
     */
    public function suggest_titles($input, $language, $site_id);

    /**
     * Проверить, есть ли слово с индексе.
     * @param string $term
     * @return boolean
     */
    public function has_term($term);

    /**
     * Получить количество документов в индексе
     * @return integer
     */
    public function count_documents();

    /**
     * Получить количество слов в индексе
     * @return integer
     */
    public function count_terms();
}