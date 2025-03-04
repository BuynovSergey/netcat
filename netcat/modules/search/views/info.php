<?php
if (!class_exists("nc_system")) {
    die;
}

if (!nc_search::should('EnableSearch')) {
    nc_print_status(NETCAT_MODULE_SEARCH_ADMIN_SETTING_SEARCH_DISABLED, "error",
            array($this->hash_href("#module.search.generalsettings"), "_top"));
    return;
}

$db = $this->get_db();
$provider = nc_search::get_provider();
$is_history_saved = nc_search::should('SaveQueryHistory');
$has_history = $is_history_saved && $db->get_var("SELECT COUNT(*) FROM `Search_Query` LIMIT 1") && nc_search_permission::check('queries');

$num_queries = 5; // сколько последних/популярных запросов выводить в статистике
$queries_period = "`Timestamp` > (NOW() - INTERVAL 3 MONTH)"; // учитывать только эти запросы в списке популярных
// -----------------------------------------------------------------------------
// ОБЩИЕ ПРЕДУПРЕЖДЕНИЯ
// Индексатор
nc_search::check_environment();
nc_search::get_provider()->check_environment();

// Парсеры
$parser_context = new nc_search_context(array('search_provider' => nc_search::get_setting('SearchProvider')));
$all_parsers = nc_search_extension_manager::get('nc_search_document_parser', $parser_context)->get_all();
foreach ($all_parsers as $parser) { $parser->check_environment(); }

// Невыполненные задачи
$rules = nc_search::load('nc_search_rule', "SELECT * FROM `%t%` ORDER BY `LastStartTime` DESC")
         ->set_output_encoding(nc_core('NC_CHARSET'));

if (!count($rules)) {
    nc_print_status(NETCAT_MODULE_SEARCH_ADMIN_NO_RULES, 'info', array($this->hash_href("#module.search.rules_edit")));
} else {
    $pending_time = time() - 12 * 60 * 60;
    $pending_tasks = $db->get_var("SELECT `StartTime` FROM `Search_Schedule` WHERE `StartTime` < $pending_time LIMIT 1");
    if ($pending_tasks) {
        nc_print_status(NETCAT_MODULE_SEARCH_ADMIN_STAT_CHECK_CRONTAB, 'error');
    }
}

// -----------------------------------------------------------------------------
echo "<div class='stat'>";
// -----------------------------------------------------------------------------

echo "<fieldset><legend>", NETCAT_MODULE_SEARCH_ADMIN_STAT_HEADER, "</legend>",
     "<div class='param'><span class='name'>", NETCAT_MODULE_SEARCH_ADMIN_STAT_NUM_DOCUMENTS, "</span>: ",
     "<span class='value'>", $provider->count_documents(), "</span></div>\n",
//      подсчёт количества терминов в индексе может быть слишком медленным
//        "<div class='param'><span class='name'>", NETCAT_MODULE_SEARCH_ADMIN_STAT_NUM_TERMS, "</span>: ",
//        "<span class='value'>", $provider->count_terms(), "</span></div>\n",

     "<div class='param'><span class='name'>", NETCAT_MODULE_SEARCH_ADMIN_STAT_NUM_SITEMAP_URLS, "</span>: ",
     "<span class='value'>",
     $db->get_var("SELECT COUNT(*) FROM `Search_Document` WHERE `IncludeInSitemap` = 1"),
     "</span></div>\n";

if ($is_history_saved) {
    echo "<div class='param'><span class='name'>", NETCAT_MODULE_SEARCH_ADMIN_STAT_NUM_QUERIES_TODAY, "</span>: ",
         "<span class='value'>",
         $db->get_var("SELECT COUNT(*)
                         FROM `Search_Query`
                        WHERE `Timestamp` >= DATE(NOW())"),
         "</span></div>\n",
         "<div class='param'><span class='name'>", NETCAT_MODULE_SEARCH_ADMIN_STAT_NUM_QUERIES_YESTERDAY, "</span>: ",
         "<span class='value'>",
         $db->get_var("SELECT COUNT(*)
                         FROM `Search_Query`
                        WHERE `Timestamp` BETWEEN DATE(NOW() - INTERVAL 1 DAY) AND DATE(NOW())"),
         "</span></div>\n";
} else {
    echo "<div class='info'>",
         sprintf(NETCAT_MODULE_SEARCH_ADMIN_QUERY_LOG_DISABLED, $this->hash_href("#module.search.generalsettings")),
         "</div>";
}

echo "</fieldset>\n"; // END OF USELESS STATISTICS FIELDSET
// -----------------------------------------------------------------------------

$result_link_title = "title='".htmlspecialchars(NETCAT_MODULE_SEARCH_ADMIN_QUERY_OPEN_RESULTS_LINK_HINT)."'";
$query_link_title = "title='".htmlspecialchars(NETCAT_MODULE_SEARCH_ADMIN_QUERY_OPEN_LOG_LINK_HINT)."'";

if ($has_history) {
    // ИСТОРИЯ ЗАПРОСОВ: ПОСЛЕДНИЕ

    echo "<div class='legend'>", NETCAT_MODULE_SEARCH_ADMIN_STAT_LAST_QUERIES,
             " &nbsp;<span class='query_log_link'>(",
             $this->hash_link('#module.search.queries', NETCAT_MODULE_SEARCH_ADMIN_SHOW_QUERY_LOG),
             ")</span>&nbsp;",
         "</div>",
         "<table class='nc-table nc--large nc--hovered nc--striped list'><tr>",
             "<th width='15%' class='nc-text-center'>", NETCAT_MODULE_SEARCH_ADMIN_QUERY_LAST_QUERY_TIME, "</th>",
             "<th>", NETCAT_MODULE_SEARCH_ADMIN_QUERY_STRING, "</th>",
             "<th width='100' class='nc-text-center'>", NETCAT_MODULE_SEARCH_ADMIN_QUERY_LAST_QUERY_RESULT_COUNT, "</th>",
             "<th width='200' class='nc-text-center'>", NETCAT_MODULE_SEARCH_ADMIN_QUERY_LAST_QUERY_USER, "</th>",
         "</tr>";

    $last_queries = $db->get_results("SELECT `q`.*, `u`.`Email`
                                      FROM `Search_Query` AS `q`
                                           LEFT JOIN `User` AS `u` ON (`q`.`User_ID` = `u`.`User_ID`)
                                     ORDER BY `Timestamp` DESC 
                                     LIMIT $num_queries", ARRAY_A);

    foreach ($last_queries as $row) {
        $search_link = nc_search::get_object()->get_search_url($row['Catalogue_ID'], true).
                "?nologging=1&amp;search_query=".rawurlencode($row['QueryString']).
                "&amp;area=".rawurlencode($row['Area']);

        echo "<tr class='nc-text-center'>",
                 "<td class='nc--nowrap'>", nc_search_util::format_time($row['Timestamp']), "</td>",
                 "<td align='left'><a href='?view=queries_details&amp;query=",
                     rawurlencode($row['QueryString']), "' $query_link_title>",
                     htmlspecialchars($row['QueryString']), "</a></td>",
                 "<td><a href='$search_link' target='_blank' $result_link_title>$row[ResultsCount]</a></td>",
                 "<td>", long2ip($row['IP']),
                     ($row['User_ID'] ? " (".$this->hash_link("#user.edit($row[User_ID])", $row["Email"]).")" : ""),
                 "</td>",
             "</tr>\n";
    }

    echo "</table>\n";

    // ---------------------------------------------------------------------------
    // ИСТОРИЯ ЗАПРОСОВ: ПОПУЛЯРНЫЕ

    $pop_queries = $db->get_results("SELECT COUNT(*) AS `Count`, `q`.*
                                      FROM `Search_Query` AS `q`
                                     WHERE $queries_period
                                     GROUP BY `QueryString`
                                     ORDER BY `Count` DESC 
                                     LIMIT $num_queries", ARRAY_A);

    echo "<div class='legend'>", NETCAT_MODULE_SEARCH_ADMIN_STAT_MOST_POPULAR, "</div>",
         "<table class='nc-table nc--large nc--hovered nc--striped list'><tr>",
             "<th>", NETCAT_MODULE_SEARCH_ADMIN_QUERY_STRING, "</th>",
             "<th width='150' class='nc-text-center'>", NETCAT_MODULE_SEARCH_ADMIN_QUERY_COUNT, "</th>",
             "<th width='150' class='nc-text-center'>", NETCAT_MODULE_SEARCH_ADMIN_QUERY_LAST_QUERY_RESULT_COUNT, "</th>",
         "</tr>\n";

    if (is_array($pop_queries)) {
        foreach ($pop_queries as $row) {
            $search_link = nc_search::get_object()->get_search_url($row['Catalogue_ID'], true).
                "?nologging=1&amp;search_query=".rawurlencode($row['QueryString']).
                "&amp;area=".rawurlencode($row['Area']);

            echo "<tr class='nc-text-center'>",
                     "<td align='left'><a href='?view=queries_details&amp;query=",
                         rawurlencode($row['QueryString']), "' $query_link_title>",
                         htmlspecialchars($row['QueryString']), "</a></td>",
                     "<td>", $row["Count"], "</td>",
                     "<td><a href='$search_link' target='_blank' $result_link_title>$row[ResultsCount]</a></td>",
                 "</tr>\n";
        }
    }

    echo "</table>\n";

    // ---------------------------------------------------------------------------
    // ИСТОРИЯ ЗАПРОСОВ: БЕЗРЕЗУЛЬТАТНО-ПОПУЛЯРНЫЕ
    $nores_queries = $db->get_results("SELECT COUNT(*) AS `Count`, `q`.*
                                       FROM `Search_Query` AS `q`
                                      WHERE `ResultsCount` = 0 AND $queries_period
                                      GROUP BY `QueryString`
                                      ORDER BY `Count` DESC 
                                      LIMIT $num_queries", ARRAY_A);

    if ($nores_queries) {
        echo "<div class='legend'>", NETCAT_MODULE_SEARCH_ADMIN_STAT_MOST_POPULAR_NO_RESULTS, "</div>",
             "<table class='nc-table nc--large nc--hovered nc--striped list'>",
             "<tr>",
                 "<th>", NETCAT_MODULE_SEARCH_ADMIN_QUERY_STRING, "</th>",
                 "<th width='313'>", NETCAT_MODULE_SEARCH_ADMIN_QUERY_COUNT, "</th>",
             "</tr>\n";

        foreach ($nores_queries as $row) {
            $search_link = nc_search::get_object()->get_search_url($row['Catalogue_ID'], true).
                    "?nologging=1&amp;search_query=".rawurlencode($row['QueryString']).
                    "&amp;area=".rawurlencode($row['Area']);

            echo "<tr class='nc-text-center'>",
                     "<td align='left'><a href='?view=queries_details&amp;query=",
                        rawurlencode($row['QueryString']), "' $query_link_title>",
                         htmlspecialchars($row['QueryString']), "</a></td>",
                     "<td>", $row["Count"], "</td>",
                 "</tr>\n";
        }

        echo "</table>\n";
    }
}

// -----------------------------------------------------------------------------
// ВРЕМЯ ПОСЛЕДНЕЙ ПЕРЕИНДЕКСАЦИИ, ЗАПУСК ПЕРЕИНДЕКСИРОВАНИЯ

if (nc_search_permission::check('rules')) {
    $indexing_in_progress = $db->get_var("SELECT COUNT(*) FROM `Search_Task`");

    if (count($rules)) {
        echo "<div class='legend'>", NETCAT_MODULE_SEARCH_ADMIN_STAT_INDEXING, "</div>",
        "<table class='nc-table nc--large nc--hovered nc--striped list'>",
        "<tr>",
        "<th width='30%'>", NETCAT_MODULE_SEARCH_ADMIN_RULE, "</th>",
        "<th>", NETCAT_MODULE_SEARCH_ADMIN_RULE_SITE, "</th>",
        "<th class='nc-text-center'>", NETCAT_MODULE_SEARCH_ADMIN_RULE_SCHEDULE, "</th>",
        "<th class='nc-text-center'>", NETCAT_MODULE_SEARCH_ADMIN_RULE_LAST_RUN, "</th>",
        "<th class='nc-text-center'>", NETCAT_MODULE_SEARCH_ADMIN_STAT_INDEX, "</th>",
        "</tr>\n";

        foreach ($rules as $r) {
            $rule_id = $r->get_id();
            $last_start_time = $r->get('last_start_time');
            $last_finish_time = $r->get('last_finish_time');

            $last_run = $last_start_time ? nc_search_util::format_time($last_start_time) : "&mdash;";
            $rule_name = $this->if_null($r->get('name'), NETCAT_MODULE_SEARCH_ADMIN_UNNAMED_RULE);

            $action_cell = "";
            if ($last_finish_time < $last_start_time) {
                $action_cell = NETCAT_MODULE_SEARCH_ADMIN_STAT_INDEXING_NOW;
            } else {
                $action_cell =
                    "<a class='ajax' href='javascript:search_schedule($rule_id)'>" .
                    NETCAT_MODULE_SEARCH_ADMIN_STAT_INDEX_IN_BACKGROUND .
                    "</a>";
                if (!$indexing_in_progress) {
                    $action_cell .= " | <a href='javascript:search_index_now($rule_id)'>" .
                        NETCAT_MODULE_SEARCH_ADMIN_STAT_INDEX_IN_BROWSER .
                        "</a>";
                }
            }

            $rule_link = nc_search_permission::check('rules_edit')
                ? $this->hash_link("#module.search.rules_edit($rule_id)", $rule_name)
                : $rule_name;

            echo "<tr>",
                     "<td>", $rule_link, "</td>",
                     "<td>", $r->get_site_name(), "</td>",
                     "<td class='nc-text-center'>", $r->get_schedule_string(), "</td>",
                     "<td class='nc-text-center'>", $last_run, "</td>",
                     "<td class='nc-text-center nc--nowrap'>", $action_cell, "</td>",
                 "</tr>\n";
    }

        echo "</table>\n";
    }

    // -----------------------------------------------------------------------------
    // ИНДЕКСИРОВАНИЕ CUSTOM ОБЛАСТИ
    echo "<form id='index_custom_area' onsubmit='return false;'><table><tr>",
    "<td class='caption'>", NETCAT_MODULE_SEARCH_ADMIN_STAT_INDEX_AREA, "</td> ",
    "<td class='input'><input type='text' name='area' id='area' /></td>",
    "<td class='buttons'>",
    "<input type='button' id='btn_schedule' title='", NETCAT_MODULE_SEARCH_ADMIN_STAT_INDEX_AREA_IN_BACKGROUND, "' value='", NETCAT_MODULE_SEARCH_ADMIN_STAT_INDEX_AREA_IN_BACKGROUND, "' /> ",
    "<input type='button' id='btn_now' title='", NETCAT_MODULE_SEARCH_ADMIN_STAT_INDEX_AREA_IN_BROWSER, "' value='", NETCAT_MODULE_SEARCH_ADMIN_STAT_INDEX_AREA_IN_BROWSER, "' ",
    ($indexing_in_progress ? "disabled='disabled' " : ""), "/>",
    "</td>",
    "</tr></table></form>";

}

// -----------------------------------------------------------------------------

echo "</div>";
?>
<script type="text/javascript">
    $nc('#btn_schedule').click(function() {
        var area = $nc('#area').val();
        if (area) { search_schedule(area); }
    });

    $nc('#btn_now').click(function() {
        var area = $nc('#area').val();
        if (area) { search_index_now(area); }
    });

    search_msg = {
        rule_queue_loading: '<?=htmlspecialchars(NETCAT_MODULE_SEARCH_ADMIN_RULE_QUEUE_LOADING) ?>',
        rule_queued: '<?=htmlspecialchars(NETCAT_MODULE_SEARCH_ADMIN_RULE_QUEUED) ?>',
        rule_queue_error: '<?=htmlspecialchars(NETCAT_MODULE_SEARCH_ADMIN_RULE_QUEUE_ERROR) ?>'
    }
</script>
