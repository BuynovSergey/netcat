<?php

/* $Id: console.php 8366 2012-11-07 16:30:14Z aix $ */

/**
 * 
 */
class nc_search_indexer_runner_console implements nc_search_indexer_runner {

    /**
     *
     */
    public function __construct() {
        set_time_limit(0);
        ignore_user_abort(true);
        nc_Core::get_object()->db->query("SET wait_timeout=900"); // might loose connection when running in slow mode

        nc_search::enable_error_logging();
    }

    /**
     *
     * @param nc_search_indexer $indexer
     * @throws nc_search_exception
     * @return boolean true when task is finished
     */
    public function loop(nc_search_indexer $indexer) {
        $cycle_number = 0;
        $delay = (int) nc_search::get_setting('CrawlerDelay');

        while (true) {
            // сохранять задачу каждые X циклов
            if ($cycle_number % nc_search::get_setting('IndexerSaveTaskEveryNthCycle') == 0) {
                $indexer->save_task();
            }

            switch ($indexer->next()) {
                case nc_search_indexer::TASK_FINISHED:
                    return true; // we're done
                case nc_search_indexer::TASK_STEP_FINISHED:
                    $delay && sleep($delay);
                    break;
                case nc_search_indexer::TASK_STEP_SKIPPED:
                    break;
                default:
                    throw new nc_search_exception("Incorrect return value from nc_search_indexer::next()");
            }

            $cycle_number++;
        };
    }



}
