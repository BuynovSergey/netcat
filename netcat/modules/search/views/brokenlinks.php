<?php

if (!class_exists("nc_system")) {
    die;
}

$ui = $this->get_ui();
$ui->add_lists_toolbar();

$per_page = 100;
$offset = (int)$this->get_input('offset');
$group_by = $this->get_input('group_by', 'url'); // referrer|url

$max_referrer_links = 1000;

if ($group_by == 'referrer') {
    $query = "SELECT SQL_CALC_FOUND_ROWS
                     l.`Referrer_URL`,
                     doc.`Title`,
                     doc.`Catalogue_ID`,
                     doc.`Subdivision_ID`
                FROM `Search_BrokenLink` AS l
                LEFT JOIN `Search_Document` AS doc
                       ON (l.`Referrer_Document_ID` = doc.`Document_ID`)
               GROUP BY l.`Referrer_URL`
               ORDER BY l.`Referrer_URL`
               LIMIT $per_page OFFSET $offset";
}
else { // 'url' or whatever it was
    $group_by = 'url';
    $query = "SELECT SQL_CALC_FOUND_ROWS
                     `URL`
                FROM `Search_BrokenLink`
               GROUP BY `URL`
               ORDER BY `URL`
               LIMIT $per_page OFFSET $offset";
}

/** @var nc_db $db */
$db = $this->get_db();
$res = $db->get_results($query, ARRAY_A);

if ($res) {
    $found_rows = $this->get_db()->get_var("SELECT FOUND_ROWS()");
    // строка с вариантами сортировки
    $group_link = $this->make_page_query(array('group_by'));

    echo '<br /><div class="link_grouping">',
         NETCAT_MODULE_SEARCH_ADMIN_BROKEN_LINK_GROUP_BY, " ",
         $this->link_if($group_by != 'url', "$group_link&amp;group_by=url", NETCAT_MODULE_SEARCH_ADMIN_BROKEN_LINK_GROUP_BY_URL),
         " | ",
         $this->link_if($group_by != 'referrer', "$group_link&amp;group_by=referrer", NETCAT_MODULE_SEARCH_ADMIN_BROKEN_LINK_GROUP_BY_REFERRER),
         "</div>\n";

    echo "<div class='broken_link_list'><ul>\n";

    // --------------------------------------------------------------- //
    function _edit_doc_link($row) { // global function inside a method... huh?
        if (!$row["Subdivision_ID"]) { return ""; } // oops

        $resolved_url_data = nc_resolve_url($row["Referrer_URL"]);
        $url = parse_url($row["Referrer_URL"]);

        $edit_link_params = array(
            "sub" => nc_array_value($resolved_url_data, "folder_id"),
            "cc" => nc_array_value($resolved_url_data, "infoblock_id"),
            "message" => nc_array_value($resolved_url_data, "object_id"),
        );

        $edit_link = $url['scheme'] . "://" . $url['host'] .
                     nc_core("SUB_FOLDER") . nc_core("HTTP_ROOT_PATH") .
                     ($resolved_url_data["resource_type"] == "object" ? "message.php" : "") .
                     "?" . (isset($url["query"]) ? $url["query"] . "&" : "") .
                     http_build_query($edit_link_params, null, '&amp;');

        return "<a href='" . htmlspecialchars($edit_link) . "' target='_blank'>" . NETCAT_MODULE_SEARCH_ADMIN_BROKEN_LINK_EDIT . "</a>";
    }

    // --------------------------------------------------------------- //

    foreach ($res as $row) {
        echo "<li>";
        if ($group_by == 'referrer') {
            echo "<b><a href='", htmlspecialchars($row['Referrer_URL']), "' target='_blank'>",
                 $row['Title'] ? $row['Title'] : $row['Referrer_URL'],
                 "</a></b> &nbsp; ", _edit_doc_link($row), "\n",
                 "<ul>\n";

            $where = ($row["Referrer_Document_ID"] ? "`Referrer_Document_ID` = '$row[Referrer_Document_ID]'" : "`Referrer_URL` = '" . $db->escape($row["Referrer_URL"]) . "'");

            $broken_links = $db->get_col("SELECT `URL`
                                            FROM `Search_BrokenLink`
                                           WHERE $where");
            foreach ($broken_links as $link) {
                echo "<li>" . nc_search_util::decode_url($link) . "</li>\n";
            }
            echo "</ul>\n";
        }
        else { // group by broken link URL
            echo "<b>" . nc_search_util::decode_url($row['URL']) . "</b>\n<ul>";

            $referrers = $db->get_results(
                "SELECT l.`Referrer_URL`,
                        doc.`Title`,
                        doc.`Catalogue_ID`,
                        doc.`Subdivision_ID`
                   FROM `Search_BrokenLink` AS l
                   LEFT JOIN `Search_Document` AS doc
                        ON (l.`Referrer_Document_ID` = doc.`Document_ID`)
                  WHERE l.`URL` = '" . $db->escape($row['URL']) . "'
                  LIMIT $max_referrer_links",
                ARRAY_A);

            foreach ($referrers as $n => $ref) {
                echo "<li><b><a href='", htmlspecialchars($ref['Referrer_URL']), "' target='_blank'>",
                     $ref['Title'] ? $ref['Title'] : $ref['Referrer_URL'],
                     "</a></b> &nbsp; ", _edit_doc_link($ref), "</li>\n";
            }

            echo "</ul>\n";
            if (count($referrers) == $max_referrer_links) {
                echo "<div>",
                     sprintf(NETCAT_MODULE_SEARCH_ADMIN_BROKEN_LINKS_REFERRER_LIMIT, $max_referrer_links),
                     "</div>";
            }
        }

        echo "</li>\n";
    }

    echo "</ul></div>";

    // листалка по страницам
    $page_link = $this->make_page_query(array('offset'), true);
    if ($offset > 0) {
        $prev_page = $page_link . "&amp;offset=" . ($offset - $per_page);
        $ui->actionButtons[] = array("id" => "prev_page",
            "caption" => NETCAT_MODULE_SEARCH_ADMIN_BROKEN_LINK_PREV_PAGE,
            "action" => "mainView.loadIframe('$prev_page')",
            "align" => "left");
    }
    if ($found_rows > $offset + $per_page) {
        $next_page = $page_link . "&amp;offset=" . ($offset + $per_page);
        $ui->actionButtons[] = array("id" => "next_page",
            "caption" => NETCAT_MODULE_SEARCH_ADMIN_BROKEN_LINK_NEXT_PAGE,
            "action" => "mainView.loadIframe('$next_page')");
    }
}
else {
    nc_print_status(NETCAT_MODULE_SEARCH_ADMIN_NO_BROKEN_LINKS, 'info');
}
