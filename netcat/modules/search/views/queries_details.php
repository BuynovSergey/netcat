<?php

if (!class_exists("nc_system")) { die; }

$this->get_ui()->add_lists_toolbar();

$query_string = $this->get_input('query');
if (!strlen($query_string)) {
    $this->redirect("?view=queries");
}

$per_page = 100;
$offset = (int) $this->get_input('offset');

$query = "SELECT SQL_CALC_FOUND_ROWS q.*, u.Email
            FROM Search_Query AS q 
                 LEFT JOIN User AS u ON (q.User_ID = u.User_ID)
           WHERE q.QueryString='".nc_search_util::db_escape($query_string)."'
           ORDER BY Timestamp DESC
           LIMIT $per_page OFFSET $offset";

$db = $this->get_db();
$db->query("SET NAMES utf8");
$res = $db->get_results($query, ARRAY_A);
$found_rows = $db->get_var("SELECT FOUND_ROWS()");
$db->query("SET NAMES " . nc_core('MYSQL_CHARSET'));

// this is actually incorrect:
echo "<div class='query_details_header'>",
     "<b>", sprintf(NETCAT_MODULE_SEARCH_ADMIN_QUERY_ALL_QUERIES, htmlspecialchars(nc_search_util::convert($query_string))), "</b> (",
    NETCAT_MODULE_SEARCH_ADMIN_QUERY_OPEN_RESULTS_HINT, "):",
    "</div>";

// таблица с результатами
echo "<table class='nc-table nc--large nc--hovered nc--striped list'>\n",
     "<tr>",
         "<th class='nc-text-center'>", NETCAT_MODULE_SEARCH_ADMIN_QUERY_TIME, "</th>",
        "<th class='nc-text-center' width='40%'>", NETCAT_MODULE_SEARCH_ADMIN_QUERY_AREA, "</th>",
        "<th class='nc-text-center'>", NETCAT_MODULE_SEARCH_ADMIN_QUERY_RESULTS_COUNT, "</th>",
        "<th class='nc-text-center'>", NETCAT_MODULE_SEARCH_ADMIN_QUERY_USER, "</th>",
        "<th class='nc-text-center'>", NETCAT_MODULE_SEARCH_ADMIN_QUERY_IP, "</th>",
    "</tr>\n";

foreach ($res as $row) {
    $has_area = strlen($row['Area']) > 0;
    $site_area = new nc_search_area("site$row[Catalogue_ID]");
    list($site_description) = $site_area->get_description(false);

    if (!$has_area) {
        $area_cell = "<td>" . NETCAT_MODULE_SEARCH_ADMIN_RULE_AREA_DESCRIPTION_ALLSITES . "</td>";
    } else {
        $area = new nc_search_area($row['Area'], $row['Catalogue_ID']);
        $description = array(
            "included" => $area->get_description(false),
            "excluded" => $area->get_description(true)
        );
        $hint = "<div class='header'><strong>$site_description</strong></div>";
        if ($description["included"]) {
            $hint .= "<div class='header'><strong>".NETCAT_MODULE_SEARCH_ADMIN_QUERY_AREA_INCLUDED."</strong>:</div><div class='list'>";
            foreach ($description["included"] as $item) {
                $hint .= "<div class='item'>".NETCAT_MODULE_SEARCH_ADMIN_BULLET." $item</div>\n";
            }
            $hint .= "</div>";
        }
        if ($description["excluded"]) {
            $hint .= "<div class='header'><strong>".NETCAT_MODULE_SEARCH_ADMIN_QUERY_AREA_EXCLUDED."</strong>:</div><div class='list'>";
            foreach ($description["excluded"] as $item) {
                $hint .= "<div class='item'>".NETCAT_MODULE_SEARCH_ADMIN_BULLET." $item</div>\n";
            }
            $hint .= "</div>";
        }

        $area_cell = "<td class='area_hint'><div>".$area->to_string()."</div>".
                     "<div class='inline_help area_description'>$hint</div>".
                     "</td>";
    }

    $search_link = nc_search::get_object()->get_search_url($row['Catalogue_ID'], true).
            "?nologging=1&amp;search_query=".rawurlencode($row['QueryString']).
            "&amp;area=".rawurlencode($row['Area']);

    echo "<tr class='nc-text-center'>",
             "<td>", nc_search_util::format_time($row['Timestamp']), "</td>",
             $area_cell,
             "<td><a href='$search_link' target='_blank' title='", htmlspecialchars(NETCAT_MODULE_SEARCH_ADMIN_QUERY_OPEN_RESULTS_LINK_HINT),
             "'>", $row['ResultsCount'], "</a></td>",
             "<td>", ($row['User_ID'] ? $this->hash_link("#user.edit($row[User_ID])", $row["Email"]) : "&nbsp;"), "</td>",
             "<td>", long2ip($row['IP']), "</td>",
         "</tr>\n";
}

$ui = $this->get_ui();

$ui->actionButtons[] = array("id" => "prev_page",
        "caption" => NETCAT_MODULE_SEARCH_ADMIN_QUERY_BACK_TO_LIST,
        "location" => "#module.search.queries",
        "align" => "left");

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
?>
<script type='text/javascript'>
$nc('td.area_hint').hover(
    function() {
        var cell = $nc(this);
        cell.find('.area_description').width(cell.innerWidth()-48).show();
    },
    function() { $nc(this).find('.area_description').hide(); }
);
</script>