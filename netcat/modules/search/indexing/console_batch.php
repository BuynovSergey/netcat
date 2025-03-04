#!/usr/local/bin/php
<?php
/**
 * Скрипт для переиндексации из crontab фрагментами (с перерывами).
 * Работает только с поисковыми библиотеками, использующими стандартный
 * индексатор.
 * Поведение скрипта управляется настройками модуля:
 *   IndexerConsoleMemoryThreshold
 *   IndexerConsoleTimeThreshold
 *   IndexerConsoleDocumentsPerSession
 *   IndexerConsoleRestartHungTasks
 */


// Запуск только из crontab/консоли
if (isset($_SERVER['REMOTE_ADDR'])) { die("Access denied."); }

$NETCAT_FOLDER = realpath(dirname(__FILE__)."/../../../../");
putenv("DOCUMENT_ROOT=$NETCAT_FOLDER");
putenv("HTTP_HOST=localhost");
putenv("REQUEST_URI=/");

require_once("$NETCAT_FOLDER/vars.inc.php");
require_once($ROOT_FOLDER."connect_io.php");

$nc_core = nc_Core::get_object();
$nc_core->modules->load_env('ru');

$lang = $nc_core->lang->detect_lang();
require_once($ADMIN_FOLDER."lang/".$lang.".php");
error_reporting(E_PARSE | E_ERROR | E_WARNING | E_USER_ERROR | E_USER_WARNING);

// замедление работы при необходимости
$delay = trim(nc_search::get_setting('IndexerConsoleSlowdownDelay')); // секунды
if ($delay) {
    define('NC_SEARCH_INDEXER_DELAY_VALUE', (int) ($delay * 1000000)); // микросекунды
    function nc_search_indexer_delay() { usleep(NC_SEARCH_INDEXER_DELAY_VALUE); }
    register_tick_function('nc_search_indexer_delay');
    declare(ticks=10000);
}

while (@ob_end_flush());

nc_search::register_logger(new nc_search_logger_plaintext(nc_search::LOG_CONSOLE));

$remove_hung_tasks = !nc_search::should('IndexerConsoleRestartHungTasks');
$current_task = nc_search_indexer::get_current_task($remove_hung_tasks);

$continue = $current_task instanceof nc_search_indexer_task &&
            $current_task->get('runner_type') == nc_search::INDEXING_CONSOLE_BATCH &&
            ($current_task->get('is_idle') || (
                nc_search::should('IndexerConsoleRestartHungTasks') &&
                time() > ($current_task->get('last_activity') + nc_search::get_setting("IndexerRemoveIdleTasksAfter"))
            ));

if ($continue) {
    $indexer = new nc_search_indexer();
    $indexer->resume($current_task, new nc_search_indexer_runner_batch);
}
else {
    nc_search_scheduler::run(nc_search::INDEXING_CONSOLE_BATCH);
}

