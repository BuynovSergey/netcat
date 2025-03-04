<?php

/**
 * Точки входа:
 *   nc_search_indexer::index_area(): 
 *     создает nc_search_indexer_task, nc_search_indexer_runner,
 *     runner отвечает за вызов next() в цикле.
 *   
 */
class nc_search_indexer {
    const TASK_STEP_SKIPPED = 1;
    const TASK_STEP_FINISHED = 2;
    const TASK_FINISHED = 3;

    /**
     * HTTP Client
     * @var nc_search_indexer_crawler
     */
    protected $crawler;
    /**
     * Strategy to process the task
     * @var nc_search_indexer_runner
     */
    protected $runner;
    /**
     * Stores the current state of the indexing process
     * @var nc_search_indexer_task
     */
    protected $task;
    /**
     * Indexing area (copied from the task)
     * @var nc_search_area
     */
    protected $area;
    /**
     * An index / search provider
     * @var nc_search_provider
     */
    protected $index;
    /**
     * Key: content-type, value: parser object
     * @var array
     */
    protected $parsers = array();
    /**
     * 
     */
    protected $referrer_cache = array();

    /**
     * Запуск переиндексации указанной области
     */
    static public function index_area($area_string, $runner_type = nc_search::INDEXING_NC_CRON) {
        $area = new nc_search_area($area_string);

        // task: состояние сессии переиндексации
        $task = new nc_search_indexer_task(array(
                        'area' => $area,
                        'rule_id' => $area->get('rule_id'),
                        'runner_type' => $runner_type,
                ));
        $task->save();

        // runner: стратегия
        $runner_classes = array(
                nc_search::INDEXING_NC_CRON => 'nc_search_indexer_runner_console',
                nc_search::INDEXING_CONSOLE => 'nc_search_indexer_runner_console',
                nc_search::INDEXING_BROWSER => 'nc_search_indexer_runner_web',
                nc_search::INDEXING_CONSOLE_BATCH => 'nc_search_indexer_runner_batch',
        );

        if (!isset($runner_classes[$runner_type])) {
            throw new nc_search_exception("nc_search_indexer::index_area(): wrong runner type '$runner_type'");
        }
        $runner_class = $runner_classes[$runner_type];

        $indexer = new nc_search_indexer();
        return $indexer->start($task, new $runner_class());
    }

    /**
     *
     */
    public function __construct() {
        $this->crawler = new nc_search_indexer_crawler();
        $this->index = nc_search::get_provider();
    }

    /**
     * @param $query
     * @throws nc_search_exception
     * @return array
     */
    protected function query_db($query) {
        $db = nc_Core::get_object()->db;
        $res = $db->get_results($query, ARRAY_A);
        if ($db->is_error) {
            throw new nc_search_exception("Cannot execute database query '$query': '$db->last_error'");
        }
        return $res;
    }

    // ------- ↓ Методы, управляющие процессом индексации ↓ --------

    /**
     *
     * @param nc_search_indexer_task $task
     * @param nc_search_indexer_runner $runner
     * @return boolean TRUE if indexing is completed
     * @throws nc_record_exception
     */
    protected function start(nc_search_indexer_task $task, nc_search_indexer_runner $runner) {
        $this->task = $task;
        $this->runner = $runner;

        $rule = $task->get_rule();
        if ($rule) {
            $rule->set('last_start_time', $task->get('start_time'))->save();
        }

        // add start url to the queue
        $area = $this->get_area();
        $links = $area->get_urls();
        $task->add_links($links);

        if ($rule) {
            $base_scheme = ($area->get('https') ? 'https' : 'http');
        }
        else {
            $base_scheme = (substr($links[0], 0, 8) == 'https://' ? 'https' : 'http');
        }

        // get documents that previously were in the area;
        // add document paths as links to the task;
        // mark these documents with 'ToDelete=1'
        $docs_in_area = $this->query_db("SELECT `Document_ID`, `Path`, `Catalogue_ID`
                                           FROM `Search_Document`
                                          WHERE {$area->get_sql_condition()}");
        if ($docs_in_area) {
            $cat = nc_Core::get_object()->catalogue;
            $ids = array();
            foreach ($docs_in_area as $doc) {
                $host_name = $cat->get_by_id($doc['Catalogue_ID'], 'Domain');
                $task->add_link($doc['Path'], "$base_scheme://$host_name/");
                $ids[] = $doc['Document_ID'];
            }

            $this->query_db("UPDATE `Search_Document`
                                SET `ToDelete` = 1
                              WHERE `Document_ID` IN (".join(", ", $ids).")");
        }

        nc_search::log(nc_search::LOG_INDEXING_BEGIN_END, "Started indexing area '$area'\n");
        if (nc_search::will_log(nc_search::LOG_PARSER_DOCUMENT_LINKS)) {
            nc_search::log(nc_search::LOG_PARSER_DOCUMENT_LINKS,
                           "Initial URLs: ".join(", ", $task->get_links_as_string()));
        }

        return $runner->loop($this);
    }

    /**
     * Когда все ссылки обработаны
     * @param bool $is_cancelled
     * @return integer nc_search_indexer::TASK_FINISHED
     */
    public function finalize($is_cancelled = false) {
        $task = $this->task;

        if ($is_cancelled) {
            $this->query_db("UPDATE `Search_Document` SET `ToDelete` = 0 WHERE `ToDelete` = 1");
        } else {
            $absent_document_count = $this->remove_absent_documents();
            $task->set('total_deleted', $absent_document_count);
        }

        // we're done with the index
        $this->index->commit();
        $this->index->optimize();

        // save broken links
        $this->save_broken_links();

        // save indexing session statistics (rule)
        $rule = $task->get_rule();
        if ($rule) {
            $rule->set('last_start_time', $task->get('start_time'))
                 ->set('last_finish_time', time())
                 ->set('last_result', array(
                          'processed' => $task->get('total_processed'),
                          'checked' => $task->get('total_checked'),
                          'not_found' => $task->get('total_not_found'),
                          'deleted' => $task->get('total_deleted')))
                 ->save(); // schedule next run
        }

        // remove the task
        $task->delete();

        // также очистим историю заодно
        nc_search::purge_history();
        nc_search::purge_log();

        // отчитаемся о проделанной работе
        nc_search::log(nc_search::LOG_INDEXING_BEGIN_END, "Ended indexing area '{$this->get_area()}'");

        return self::TASK_FINISHED;
    }

    /**
     * 
     */
    protected function remove_absent_documents() {
        $absent_document_count = 0;
        /** @var nc_db $db */
        $db = nc_Core::get_object()->db;
        $query = "SELECT `Document_ID` FROM `Search_Document` WHERE `ToDelete` = 1 LIMIT 10000";
        while ($absent_documents = $db->get_results($query, ARRAY_A)) {
            foreach ($absent_documents as $row) {
                $doc = new nc_search_document();
                $doc->set_id($row['Document_ID']);
                $this->index->remove_document($doc);
            }
            $absent_document_count += count($absent_documents);
            // this operation might be slow, so save the task to prevent it from being marked as 'hung up'
            $this->task->save();
        }
        return $absent_document_count;
    }

    /**
     * Отмена переиндексации (пользователь вызвал переиндексацию "в вебе", но не
     * дождался окончания)
     */
    public function cancel() {
        nc_search::log(nc_search::LOG_INDEXING_BEGIN_END, "Indexing was stopped prematurely due to the user disconnection");
        return $this->finalize(true);
    }

    /**
     * Обработать следующую ссылку из очереди
     * @return integer 
     *   nc_search_indexer::TASK_STEP_SKIPPED (ничего не сделано),
     *   nc_search_indexer::TASK_STEP_FINISHED (сделан и обработан запрос),
     *   nc_search_indexer::TASK_FINISHED (задача завершена)
     */
    public function next() {
        $link = $this->task->get_next_link();
        if (!$link) {
            return $this->finalize();
        } // ССЫЛОК БОЛЬШЕ НЕТ

        $done_something = true; // флажок, означающий, что после выполнения задачи, возможно,
        // следует сделать паузу (в соответствии с настройками)
        $url = $link->get('url');

        $is_disallowed = $this->task->is_url_disallowed($url);

        if (!$is_disallowed && $this->get_area()->includes($url)) {
            $response = $this->crawler->get($url);
        } elseif (!$is_disallowed && nc_search::should("CrawlerCheckLinks") && ($this->is_internal_link($url) || nc_search::should("CrawlerCheckOutsideLinks"))) {
            // так нам её проверить, да?
            $response = $this->crawler->head($url);
        } else {
            $response = false;
            $done_something = false;
        }

        if ($response) {
            $code = $response->get_code(); // 0, если ничего не получено (напр., не резолвится домен)
            $max_doc_size = nc_search::get_setting("CrawlerMaxDocumentSize");
            if (!$code || $code == 400 || $code >= 402) { // их разыскивает пилиция  (401==Authorization required)
                $link->set('is_broken', true);
                $this->task->increment('total_not_found');
            } elseif ($response->has_body() && (!$max_doc_size || $response->get_body_length() <= $max_doc_size)) {
                // есть ответ и он не слишком длинный для нас
                $this->process_response($response, $link);
                $this->task->increment('total_processed');
            } else {
                $this->task->increment('total_checked');
            }
        }

        $link->set('is_processed', true);

        if ($link->get_id()) {
            // save the link status (broken, processed)
            $link->save();
            // set ToDelete for the broken links from this page
            try {
                $this->query_db("UPDATE `Search_BrokenLink`
                                    SET `ToDelete` = 1
                                  WHERE `Referrer_URL` = '".nc_search_util::db_escape($link->get('url'))."'");
            } catch (Exception $e) {
                trigger_error($e->getMessage(), E_USER_WARNING);
            }
        }

        return ($done_something ? self::TASK_STEP_FINISHED : self::TASK_STEP_SKIPPED);
    }

    /**
     * Перед перезапуском скрипта (индексация из веба)
     */
    public function interrupt($msg = '') {
        $this->index->commit();
        $this->task->set('is_idle', true);
        $this->save_task();

        nc_search::log(nc_search::LOG_INDEXING_BEGIN_END,
                        "Interrupting indexing area '{$this->get_area()}'".($msg ? " ($msg)" : ""));
    }

    /**
     * После перезапуска скрипта (индексация из веба)
     * @param nc_search_indexer_task $task
     * @param nc_search_indexer_runner $runner
     * @return boolean task is finished
     */
    public function resume(nc_search_indexer_task $task, nc_search_indexer_runner $runner) {
        $task->set('is_idle', false)->save();
        $this->task = $task;
        $this->runner = $runner;

        nc_search::log(nc_search::LOG_INDEXING_BEGIN_END, "Restarted indexing area '{$this->get_area()}'");

        return $runner->loop($this);
    }

    // ------- ↑ Конец методов, отвечающих за управление процессом индексации ↑ --------

    /**
     * Получить область переиндексации. Если не задана — возвращает allsites
     * @return nc_search_area
     */
    protected function get_area() {
        if (!$this->area) {
            $this->area = $this->task->get('area');
            if (!$this->area) {
                $this->area = new nc_search_area("allsites");
            }
        }
        return $this->area;
    }

    /**
     * Обработать ответ пользователя (crawler)
     * @param nc_search_indexer_crawler_response $response
     * @param nc_search_indexer_link $link
     * @return bool
     */
    protected function process_response(nc_search_indexer_crawler_response $response, nc_search_indexer_link $link) {
        $content_type = $response->get_content_type();

        try {
            $parser = $this->get_parser($content_type);
        }
        catch (nc_search_exception $e) {
            return false;
        }

        $parser->load($response);

        // пусть парсер скажет, будем ли мы обрабатывать этот документ?
        // (например, не будем, если есть meta robots=noindex)
        if (!$parser->should_index()) {
            return false;
        }

        // получить ссылки
        $page_hrefs = $this->filter_links($parser->extract_links());
        $page_url = $response->get_url();

        // добавить ссылки в очередь (где-то там разберутся, чтобы ссылки
        // не повторялись и были абсолютными):
        $page_link_ids = $this->task->add_links($page_hrefs, $page_url);

        // распарсить контент
        $document = $parser->get_document();

        $document->set_values(array(
                'url' => $page_url,
                'path' => nc_search_util::get_url_path($page_url),
                'content_type' => $content_type,
                'to_delete' => false,
                'last_updated' => strftime("%Y-%m-%d %H:%M:%S"),
                'last_modified' => $response->get_last_modified(),
        ));

        $this->apply_hierarchy_options($document);

        if (nc_search::will_log(nc_search::LOG_PARSER_DOCUMENT_BRIEF)) {
            nc_search::log(nc_search::LOG_PARSER_DOCUMENT_BRIEF,
                            "Parsed document from '$page_url'. Indexed content: ".
                            strlen($document->get('intact_content'))." bytes");
        }

        if (nc_search::will_log(nc_search::LOG_PARSER_DOCUMENT_VERBOSE)) {
            nc_search::log(nc_search::LOG_PARSER_DOCUMENT_VERBOSE, $document->dump());
        }

        // добавить в индекс
        $this->index->process_document($document);

        // сохранить информацию о том, кто куда ссылается
        // (referrer_link_id сохраняется из-за неясностей/неточностей в ТЗ, которое
        // подразумевает, что сбор ссылок может производиться на страницах,
        // которые не сохраняются в индексе)
        $referrer_link_id = (int)$link->get_id(); // might be null
        $doc_id = (int)$document->get_id();

        foreach ($page_link_ids as $page_link_id) {
            if (!$page_link_id) { continue; }
            $this->referrer_cache[] = "(" . $doc_id . "," .
                                            $referrer_link_id . "," .
                                            (int)$page_link_id .
                                      ")";
        }

        return true;
    }

    /**
     * Получить парсер для документов типа $content_type
     * @param string $content_type
     * @throws nc_search_exception
     * @return nc_search_document_parser
     */
    protected function get_parser($content_type) {
        if (!isset($this->parsers[$content_type])) {
            $context = new nc_search_context(array(
                            'content_type' => $content_type,
                            'search_provider' => nc_search::get_setting('SearchProvider'),
                    ));
            $parser = nc_search_extension_manager::get('nc_search_document_parser', $context)
                            ->first();
            if (!$parser) {
                throw new nc_search_exception("Cannot get parser for the '$content_type'");
            }
            $this->parsers[$content_type] = $parser;
        }
        return $this->parsers[$content_type];
    }

    /**
     * Вспомогательный метод для определения является ли ссылка внешней
     * @param string $url
     * @return boolean
     */
    protected function is_internal_link($url) {
        return $this->get_area()->get('sites')->includes($url);
    }

    /**
     * Фильтрует ссылки со схемами, отличными от http(s) (например, mailto:, ftp://)
     * @param array $raw_links
     * @return array
     */
    protected function filter_links(array $raw_links) {
        $links = array();
        foreach ($raw_links as $raw_link) {
            if (!strlen($raw_link)) {
                continue;
            } // empty link?!

            $has_root_scheme = strpos($raw_link, "://");
            if ($has_root_scheme && strpos($raw_link, "http") !== 0) { // http, https
                continue; // unsupported protocol like ftp, magnet, file etc.; skip it
            } elseif (!$has_root_scheme && strpos($raw_link, ":") && preg_match("/^\w+:/", $raw_link)) {
                continue; // probably "mailto:", "javascript:" etc; skip
            } elseif ($raw_link[0] == "#") { // hash anchor
                continue;
            }
            $links[] = $raw_link;
        } // end of "foreach link"
        return $links;
    }

    /**
     *
     * @param nc_search_document $doc
     * @return nc_search_document
     */
    protected function apply_hierarchy_options(nc_search_document $doc) {
        $nc_core = nc_Core::get_object();

        // попробовать в два захода: сначала сайт
        try {
            $site = $nc_core->catalogue->get_by_host_name(parse_url($doc->get('url'), PHP_URL_HOST));
            $site_id = $site["Catalogue_ID"];
            $doc->set('language', $site["Language"]);
        } catch (Exception $e) {
            nc_search::log(nc_search::LOG_INDEXING_NO_SUB,
                            "Cannot determine site of the document '{$doc->get('url')}': {$e->getMessage()}");
            $site_id = 1; // наугад
        }

        $doc->set('site_id', $site_id);

        // теперь раздел
        try {
            $resolved_path = nc_resolve_url($doc->get('url'), 'GET');
            if ($resolved_path && isset($resolved_path['folder_id'])) {
                $sub_id = $resolved_path['folder_id'];
                $sub = $nc_core->subdivision->get_by_id($sub_id);
            }
            else {
                throw new nc_search_exception();
            }

            $ancestors = array();
            $tree = $nc_core->subdivision->get_parent_tree($sub_id); // включает собственно раздел!
            foreach ($tree as $s) {
                if (isset($s["Subdivision_ID"])) {
                    $ancestors[] = "sub$s[Subdivision_ID]";
                }
            }

            $doc->set_values(array(
                    'sub_id' => $sub_id,
                    'language' => $nc_core->subdivision->get_lang($sub_id),
                    'ancestor_ids' => join(',', $ancestors),
                    // 'ancestor_titles' => '', // «хлебные крошки», решено «пока» не делать
                    // 'access_level' => '',
            ));

            $p = $nc_core->page;
            if ($sub[$p->get_field_name('sitemap_include')]) {
                $doc->set_values(array(
                        'sitemap_include' => true,
                        'sitemap_changefreq' => $sub[$p->get_field_name('sitemap_changefreq')],
                        'sitemap_priority' => $sub[$p->get_field_name('sitemap_priority')],
                ));
            }
        } catch (Exception $e) {
            nc_search::log(nc_search::LOG_INDEXING_NO_SUB,
                            "Cannot set subdivision data for the document '{$doc->get('url')}': {$e->getMessage()}");
        }

        return $doc;
    }

    /**
     * Сохранение задачи (на случай внезапного прекращения работы и для индикации
     * того, что процесс индексации «жив»)
     */
    public function save_task() {
        $this->task->save();
        $this->flush_referrer_cache();
    }

    /**
     * Работает ли в данный момент переиндексация?
     * @param bool $remove_hung_tasks
     * @return false|nc_search_indexer_task
     */
    public static function get_current_task($remove_hung_tasks = true) {
        $tasks = nc_search::load_all('nc_search_indexer_task', true);
        if (!sizeof($tasks)) {
            return false;
        }

        // не подвисли ли мы?
        /** @var nc_search_indexer_task $task */
        $task = $tasks->first();
        if ($remove_hung_tasks && time() > ($task->get('last_activity') + nc_search::get_setting("IndexerRemoveIdleTasksAfter"))) {
            $rule = $task->get_rule();
            if ($rule && $rule->get_id()) {
                $rule->set('last_finish_time', time())->save();
            }

            $task->delete();

            $db = nc_Core::get_object()->db;
            $db->query("TRUNCATE TABLE `Search_Link`");
            $db->query("TRUNCATE TABLE `Search_LinkReferrer`");

            nc_search::log(nc_search::LOG_ERROR,
                            "Indexer task was last active at ".strftime("%Y-%m-%d %H:%M:%S", (int) $task->get('last_activity')).
                            ". Task removed.");
            return false;
        }

        return $task;
    }

    /**
     *
     */
    protected function flush_referrer_cache() {
        if (!sizeof($this->referrer_cache)) { return; }

        $this->query_db("INSERT INTO `Search_LinkReferrer` (`Source_Document_ID`, `Source_Link_ID`, `Target_Link_ID`)
                         VALUES " . join(", ", $this->referrer_cache));
        $this->referrer_cache = array();
    }

    /**
     * Сохраняет список «битых» ссылок. 
     * Переносит данные в Search_BrokenLinks, после чего очищает таблицы 
     * Search_Link, Search_LinkReferrer.
     */
    protected function save_broken_links() {
        $this->flush_referrer_cache();
        $this->query_db("DELETE FROM `Search_BrokenLink` WHERE `ToDelete` = 1");
        $this->query_db("INSERT INTO `Search_BrokenLink` (`URL`, `Referrer_URL`, `Referrer_Document_ID`)
                         SELECT DISTINCT
                                `target`.`URL` AS `URL`,
                                `source`.`URL` AS `Referrer_URL`,
                                `ref`.`Source_Document_ID` as `Referrer_Document_ID`
                           FROM `Search_Link` AS `target`
                           JOIN `Search_LinkReferrer` AS `ref` ON (`target`.`Link_ID` = `ref`.`Target_Link_ID`)
                           JOIN `Search_Link` AS `source` ON (`ref`.`Source_Link_ID` = `source`.`Link_ID`)
                          WHERE `target`.`Broken` = 1");
        $this->query_db("TRUNCATE TABLE `Search_Link`");
        $this->query_db("TRUNCATE TABLE `Search_LinkReferrer`");
        return $this;
    }

}
