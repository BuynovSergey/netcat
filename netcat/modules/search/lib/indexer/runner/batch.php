<?php
/**
 * Индексирование из консоли с перезапуском
 */
class nc_search_indexer_runner_batch extends nc_search_indexer_runner_interrupted {

    protected function get_time_threshold() {
        return nc_search::get_setting('IndexerConsoleTimeThreshold');
    }

    protected function get_memory_threshold() {
        return nc_search::get_setting('IndexerConsoleMemoryThreshold');
    }

    protected function get_max_cycles_number() {
        return (int)nc_search::get_setting('IndexerConsoleDocumentsPerSession');
    }

}
