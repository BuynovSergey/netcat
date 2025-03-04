<?php

if (!class_exists("nc_system")) { die; }

$this->get_ui()->add_lists_toolbar();

if (!nc_search::should('SaveQueryHistory')) {
    nc_print_status(NETCAT_MODULE_SEARCH_ADMIN_QUERY_LOG_DISABLED, 'info',
                    array($this->hash_href("#module.search.generalsettings")));
}

// генерация полей для ввода диапазонов дат
$time_fields = array(
        "d" => 2,
        "m" => 2,
        "Y" => 4,
        "H" => 2,
        "M" => 2,
);

foreach (array('from', 'to') as $i) {
    $input = NETCAT_MODULE_SEARCH_DATETIME_FORMAT;
    foreach ($time_fields as $key => $length) {
        $name = "datetime_{$i}_{$key}";
        $input = str_replace("%$key",
                        "<input type='text' name='$name' value='".
                        $this->format_input($name, "%0{$length}d").
                        "' class='i$length' maxlength='$length' size='$length' />",
                        $input);
    }
    ${"datetime_$i"} = $input;
}

$results = $this->get_input('results');
$per_page = (int) $this->get_input('per_page', 20);
$sort_by = $this->get_input('sort_by');

?>

<!-- фильтр -->
<form method="GET" action="">
    <input type="hidden" name="view" value="queries" />
    <table class="query_filter">
        <tr>
            <td class="fragment_cell">
                <div class="caption"><?=NETCAT_MODULE_SEARCH_ADMIN_QUERY_FRAGMENT ?></div>
                <input type="text" name="fragment" value="<?=$this->escape_input('fragment') ?>" class="fragment" />
            </td>
            <td id="timespan_inputs">
                <div class="caption"><?=NETCAT_MODULE_SEARCH_ADMIN_QUERY_TIME_PERIOD ?>
                    <span id="clear_timespan">[<a class="internal"><?=NETCAT_MODULE_SEARCH_ADMIN_QUERY_TIME_PERIOD_CLEAR ?></a>]</span>
                </div>
                <span class="timespan">
                    <?=NETCAT_MODULE_SEARCH_ADMIN_QUERY_TIME_PERIOD_FROM ?>
                    <?=$datetime_from ?>
                </span>
                <span class="timespan">
                    <?=NETCAT_MODULE_SEARCH_ADMIN_QUERY_TIME_PERIOD_TO ?>
                    <?=$datetime_to ?>
                </span>
            </td>
            <td>
                <div class="caption"><?=NETCAT_MODULE_SEARCH_ADMIN_QUERY_RESULTS ?></div>
                <select name="results">
                    <option value=""><?=NETCAT_MODULE_SEARCH_ADMIN_QUERY_RESULTS_ALL ?></option>
                    <option value="none"<?=($results == 'none' ? ' selected' : '') ?>><?=NETCAT_MODULE_SEARCH_ADMIN_QUERY_RESULTS_NONE ?></option>
                    <option value="matched"<?=($results == 'matched' ? ' selected' : '') ?>><?=NETCAT_MODULE_SEARCH_ADMIN_QUERY_RESULTS_MATCHED ?></option>
                </select>
            </td>
            <td class="submit">
                <input type="submit" title="<?=NETCAT_MODULE_SEARCH_ADMIN_QUERY_SUBMIT_FILTER?>" value="<?=NETCAT_MODULE_SEARCH_ADMIN_QUERY_SUBMIT_FILTER?>" />
        <?=sprintf(NETCAT_MODULE_SEARCH_ADMIN_QUERY_PER_PAGE,
        "<input type='text' name='per_page' size='2' class='i3' value='$per_page' />") ?>
            </td>
        </tr>
    </table>
</form>

<script type="text/javascript">
    (function($) {
        var inp = $('#timespan_inputs input'),
        clear_link = $('#clear_timespan'),
        update = function() {
            var has_value = false;
            inp.each(function() { if ($(this).val() != '') { has_value = true; return false; }});
            clear_link.css('visibility', has_value ? 'visible' : 'hidden');
        };
        inp.change(update);
        clear_link.click(function() { inp.val(''); clear_link.css('visibility', 'hidden'); });
        update();
    })($nc);
</script>

<?php
// составляем и выполняем запрос к БД

$order_by = "`QueryCount` DESC";
if ($sort_by == 'time') {
    $order_by = "`Timestamp` DESC";
} elseif ($sort_by == 'query') {
    $order_by = "`QueryString` ASC";
}

$where = array(1);
if (strlen($this->get_input('fragment'))) {
    $where[] = "`QueryString` LIKE '%".nc_search_util::db_escape($this->get_input('fragment'))."%'";
}

// если установлен день или час, считать, что речь идёт о текущем дне/месяце/годе
$from_today = $this->get_input('datetime_from_d') || $this->get_input('datetime_from_H');

$timestamp_from = call_user_func_array('mktime', array(
                (int) $this->get_input('datetime_from_H', 0),
                (int) $this->get_input('datetime_from_M', 0),
                0,
                (int) $this->get_input('datetime_from_m', ($from_today ? date("m") : 1)),
                (int) $this->get_input('datetime_from_d', ($from_today ? date("d") : 1)),
                (int) $this->get_input('datetime_from_Y', ($from_today ? date("Y") : 2000)),
        ));

// если установлен день или час, считать, что речь идёт о текущем дне/месяце/годе
$to_today = $this->get_input('datetime_to_d') || $this->get_input('datetime_to_H');

$timestamp_to = call_user_func_array('mktime', array(
                (int) $this->get_input('datetime_to_H', 23),
                (int) $this->get_input('datetime_to_M', 59),
                59,
                (int) $this->get_input('datetime_to_m', ($to_today ? date("m") : 12)),
                (int) $this->get_input('datetime_to_d', ($to_today ? date("d") : 31)),
                (int) $this->get_input('datetime_to_Y', ($to_today ? date("Y") : 2037)),
        ));

$sql_datetime_from = nc_search_util::sql_datetime($timestamp_from);
$sql_datetime_to = nc_search_util::sql_datetime($timestamp_to);

if ($sql_datetime_from != '2000-01-01 00:00:00' || $sql_datetime_to != '2037-12-31 23:59:59') {
    $where[] = "`Timestamp` BETWEEN '$sql_datetime_from' AND '$sql_datetime_to'";
}

if ($results == 'none') {
    $where[] = '`ResultsCount` = 0';
} elseif ($results == 'matched') {
    $where[] = '`ResultsCount` > 0';
}

$where = join(" AND ", $where);

$offset = (int) $this->get_input('offset');

$query = "SELECT `latest`.*,
                 `q`.`QueryCount`,
                 `u`.`Email`
            FROM (SELECT MAX(`Query_ID`) AS `Query_ID`,
                         COUNT(`Query_ID`) AS `QueryCount`
                    FROM `Search_Query`
                   WHERE $where
                   GROUP BY `QueryString`
                   ORDER BY $order_by
                   LIMIT $per_page OFFSET $offset) AS `q`
            JOIN `Search_Query` AS `latest` ON (`q`.`Query_ID` = `latest`.`Query_ID`)
            LEFT JOIN `User` AS `u` ON (`latest`.`User_ID` = `u`.`User_ID`)";

$res = $this->get_db()->get_results($query, ARRAY_A);

if ($res) {
    $found_rows = $this->get_db()->get_var("SELECT COUNT(DISTINCT(`QueryString`))
                                              FROM `Search_Query`
                                             WHERE $where");

    // строка с вариантами сортировки
    $sort_link = $this->make_page_query(array('sort_by'));

    echo '<div class="query_sort">',
         $this->link_if($sort_by != '', $sort_link, NETCAT_MODULE_SEARCH_ADMIN_QUERY_SORT_BY_RESULT_COUNT),
         " | ",
         $this->link_if($sort_by != 'time', "$sort_link&amp;sort_by=time", NETCAT_MODULE_SEARCH_ADMIN_QUERY_SORT_BY_TIME),
         " | ",
         $this->link_if($sort_by != 'query', "$sort_link&amp;sort_by=query", NETCAT_MODULE_SEARCH_ADMIN_QUERY_SORT_BY_QUERY),
         "</div>";

    // таблица с результатами
    echo "<table class='nc-table nc--large nc--hovered nc--striped list'>\n",
         "<tr>",
             "<th rowspan='2' width='40%'>", NETCAT_MODULE_SEARCH_ADMIN_QUERY_STRING, "</th>",
             "<th rowspan='2' width='10%' class='nc-text-center'>", NETCAT_MODULE_SEARCH_ADMIN_QUERY_COUNT, "</th>",
             "<th colspan='3' class='nc-text-center'>", NETCAT_MODULE_SEARCH_ADMIN_QUERY_LAST_QUERY, "</th>",
         "</tr>\n",
         "<tr>",
             "<th class='nc-text-center'>", NETCAT_MODULE_SEARCH_ADMIN_QUERY_LAST_QUERY_TIME, "</th>",
             "<th class='nc-text-center'>", NETCAT_MODULE_SEARCH_ADMIN_QUERY_LAST_QUERY_RESULT_COUNT, "</th>",
             "<th class='nc-text-center'>", NETCAT_MODULE_SEARCH_ADMIN_QUERY_LAST_QUERY_USER, "</th>",
         "</tr>\n";

    $result_link_title = "title='".htmlspecialchars(NETCAT_MODULE_SEARCH_ADMIN_QUERY_OPEN_RESULTS_LINK_HINT)."'";
    foreach ($res as $row) {
        // ссылка на страницу с результатами поиска
        $search_link = nc_search::get_object()->get_search_url($row['Catalogue_ID'], true).
                "?nologging=1&amp;search_query=".rawurlencode($row['QueryString']).
                "&amp;area=".rawurlencode($row['Area']);

        echo "<tr class='nc-text-center'>",
                 "<td align='left'><a href='?view=queries_details&amp;query=", // плохой английский - из-за универсальных обработчиков...
                     rawurlencode($row['QueryString']), "'>", htmlspecialchars($row['QueryString']), "</a></td>",
                 "<td>", $row['QueryCount'], "</td>",
                 "<td>", nc_search_util::format_time($row['Timestamp']), "</td>",
                 "<td><a href='$search_link' target='_blank' $result_link_title>$row[ResultsCount]</a></td>",
                 "<td>", long2ip($row['IP']),
                 ($row['User_ID'] ? " (".$this->hash_link("#user.edit($row[User_ID])", $row["Email"]).")" : ""),
                 "</td>",
             "</tr>\n";
    }

    echo "</table>";

    echo $this->result_count($offset + 1, $per_page, $found_rows);

    // листалка по страницам
    $ui = $this->get_ui();
    $page_link = $this->make_page_query(array('offset'), true);
    if ($offset > 0) {
        $prev_page = $page_link."&amp;offset=".($offset - $per_page);
        $ui->actionButtons[] = array("id" => "prev_page",
                "caption" => NETCAT_MODULE_SEARCH_ADMIN_QUERY_PREV_PAGE,
                "action" => "mainView.loadIframe('$prev_page')",
                "align" => "left");
    }
    if ($found_rows > $offset + $per_page) {
        $next_page = $page_link."&amp;offset=".($offset + $per_page);
        $ui->actionButtons[] = array("id" => "next_page",
                "caption" => NETCAT_MODULE_SEARCH_ADMIN_QUERY_NEXT_PAGE,
                "action" => "mainView.loadIframe('$next_page')");
    }
} else {
    nc_print_status(NETCAT_MODULE_SEARCH_ADMIN_QUERY_NO_ENTRIES, 'info');
}

