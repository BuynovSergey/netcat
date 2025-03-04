<?php

/**
 *
 */
class nc_search_provider_index_backup extends nc_search_provider_backup {

    /**
     * @param string $type
     * @param int $id
     */
    public function export($type, $id) {
        if ($type != 'site') { return; }
    }

    /**
     * @param string $type
     * @param int $id
     */
    public function import($type, $id) {
        if ($type != 'site') { return; }

        // класс службы поиска на исходном сайте указан в $this->dumper->get_dump_info('search_provider')

        // переиндексировать все сайты, если невозможно объединение индексов
        nc_search::get_provider()->schedule_indexing("site$id", 'now');
    }

}