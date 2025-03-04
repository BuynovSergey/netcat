<?php

/**
 * Переиндексация области по запросу «в браузере»
 */
if (!class_exists("nc_system")) { die; }

if (!nc_search::should('EnableSearch')) {
    nc_print_status(NETCAT_MODULE_SEARCH_ADMIN_SETTING_SEARCH_DISABLED, "error",
            array($this->hash_href("#module.search.generalsettings"), "_blank"));
    return;
}


$rule_id = $this->get_input('rule_id');
$continue = $this->get_input('continue');

if (!$rule_id && !$continue) {
    $this->halt_param('rule_id');
}

$provider = nc_search::get_provider();
$current_task = $provider->is_reindexing();

// куки должны быть включены
if (($rule_id && $current_task) || ($current_task && $this->get_input('token') != $current_task->get('token'))) {
    $this->halt(NETCAT_MODULE_SEARCH_ADMIN_INDEXING_IN_PROGRESS_ERROR, $current_task->get('area'));
}

nc_search::register_logger(new nc_search_logger_html);

nc_print_status(NETCAT_MODULE_SEARCH_ADMIN_INDEXING_IN_PROGRESS, 'info');
print "<div class='search_indexing'>";

while (@ob_end_flush());

if ($rule_id) { // первый запуск
    $done = $provider->index_area($rule_id, nc_search::INDEXING_BROWSER);
    // нам понадобится $curent_task для того, чтобы вытащить из неё token
    $current_task = $provider->is_reindexing();
} else if ($continue && $current_task) { // продолжение переиндексации (после перезагрузки страницы)
    $indexer = new nc_search_indexer();
    $done = $indexer->resume($current_task, new nc_search_indexer_runner_web);
}

// check again if we're done
if (!$current_task || $done) {
    $stats = "";
    if ($current_task) {
        $stats = sprintf("<br />".NETCAT_MODULE_SEARCH_ADMIN_INDEXING_DONE_STATS,
                        nc_search_util::format_seconds(time() - $current_task->get('start_time')),
                        $current_task->get('total_processed'),
                        $current_task->get('total_checked'),
                        $current_task->get('total_not_found'),
                        $current_task->get('total_deleted'));
    }
    nc_print_status(NETCAT_MODULE_SEARCH_ADMIN_INDEXING_DONE.$stats, 'ok');
    echo "<script type='text/javascript'>\$nc(window).scrollTop(100000);</script>";
} else {
    echo "<script type='text/javascript'>",
         "window.location = '?continue=1&token=", $current_task->get('token'), "';",
         "</script>\n";
}


print "</div>";