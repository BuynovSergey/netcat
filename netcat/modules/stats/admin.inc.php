<?php
/* $Id: admin.inc.php 8251 2012-10-22 11:36:02Z lemonade $ */

function stats_NavBar() {
    global $phase, $db;
    global $date_start_y, $date_start_m, $date_start_d;
    global $date_end_y, $date_end_m, $date_end_d;
    global $cat_id, $UI_CONFIG;

    $report[9] = NETCAT_MODULE_STATS_TYPE_MAIN;
    $report[1] = NETCAT_MODULE_STATS_TYPE_GUESTS;
    $report[2] = NETCAT_MODULE_STATS_TYPE_PAGES;
    $report[3] = NETCAT_MODULE_STATS_TYPE_EXTPAGES;
    $report[4] = NETCAT_MODULE_STATS_TYPE_USERAGENTS;
    $report[5] = NETCAT_MODULE_STATS_TYPE_OS;
    $report[6] = NETCAT_MODULE_STATS_TYPE_IP;
    $report[7] = NETCAT_MODULE_STATS_TYPE_GEO;
    $report[10] = NETCAT_MODULE_STATS_TYPE_PHRASES;


    list($today, $yesterday, $weekago, $monthago, $wholeperiod) = $db->get_row("SELECT CURRENT_DATE() AS Today,DATE_ADD(CURRENT_DATE(),INTERVAL -1 DAY) AS Yesterday,DATE_ADD(CURRENT_DATE(),INTERVAL -7 DAY) AS WeekAgo,DATE_ADD(CURRENT_DATE(),INTERVAL -30 DAY) AS MonthAgo,MIN(Date) AS WholePeriod FROM Stats_Attendance", ARRAY_N);

    $today_d = substr($today, 8, 2);
    $today_m = substr($today, 5, 2);
    $today_y = substr($today, 0, 4);

    $yesterday_d = substr($yesterday, 8, 2);
    $yesterday_m = substr($yesterday, 5, 2);
    $yesterday_y = substr($yesterday, 0, 4);

    $weekago_d = substr($weekago, 8, 2);
    $weekago_m = substr($weekago, 5, 2);
    $weekago_y = substr($weekago, 0, 4);

    $monthago_d = substr($monthago, 8, 2);
    $monthago_m = substr($monthago, 5, 2);
    $monthago_y = substr($monthago, 0, 4);

    $wholeperiod_d = substr($wholeperiod, 8, 2);
    $wholeperiod_m = substr($wholeperiod, 5, 2);
    $wholeperiod_y = substr($wholeperiod, 0, 4);

    echo "<table border=0 cellpadding=0 cellspacing=0 width=100%><form action=admin.php method=get id='stats_NavBar'>";
    echo "<input type='hidden' name='cat_id' value='" . $cat_id . "'>";
    echo "<tr><td width=100%><select onchange=\"this.form.submit()\" name=phase>";

    foreach ($report as $rep_k => $rep_v) {
        echo "<option value=$rep_k" . ($rep_k == $phase ? " selected" : "") . ">" . $rep_v . "</option>";
    }

    echo "</select></td>";

    if ($phase && $phase != 8 && $phase != 9) {
        echo "<td nowrap>" . NETCAT_MODULE_STATS_TXT_PERIOD . " ";
        echo "<input type=text size=2 name=date_start_d value='" . $date_start_d . "'>";
        echo "<input type=text size=2 name=date_start_m value='" . $date_start_m . "'>";
        echo "<input type=text size=4 name=date_start_y value='" . $date_start_y . "'>";
        echo " " . NETCAT_MODULE_STATS_TXT_OVER . " ";
        echo "<input type=text size=2 name=date_end_d value='" . $date_end_d . "'>";
        echo "<input type=text size=2 name=date_end_m value='" . $date_end_m . "'>";
        echo "<input type=text size=4 name=date_end_y value='" . $date_end_y . "'>";
        $UI_CONFIG->actionButtons[] = array("id" => "submit",
                "caption" => NETCAT_MODULE_STATS_BUT_SHOW,
                "action" => "mainView.submitIframeForm('stats_NavBar')");
        echo "<input type='submit' class='hidden'></td></tr>"; # <input type=submit title='".NETCAT_MODULE_STATS_BUT_SHOW."' value='".NETCAT_MODULE_STATS_BUT_SHOW."'>
        echo "<tr><td colspan=2><hr color=cccccc size=1></td></tr>";
        echo "<tr><td></td><td><font size=-2>";

        $link_pref = "?cat_id=" . $cat_id . "&phase=" . (!$phase ? 1 : $phase) . "&date_end_d=" . date("d") . "&date_end_m=" . date("m") . "&date_end_y=" . date("Y");
        echo "<a href=" . $link_pref . "&date_start_d=" . $today_d . "&date_start_m=" . $today_m . "&date_start_y=" . $today_y . ">" . NETCAT_MODULE_STATS_PERIOD_TODAY . "</a>";
        echo " | <a href=?cat_id=" . $cat_id . "&phase=" . (!$phase ? 1 : $phase) . "&date_end_d=" . $yesterday_d . "&date_end_m=" . $yesterday_m . "&date_end_y=" . $yesterday_y . "&date_start_d=" . $yesterday_d . "&date_start_m=" . $yesterday_m . "&date_start_y=" . $yesterday_y . ">" . NETCAT_MODULE_STATS_PERIOD_YESTERDAY . "</a>";
        echo " | <a href=" . $link_pref . "&date_start_d=" . $weekago_d . "&date_start_m=" . $weekago_m . "&date_start_y=" . $weekago_y . ">" . NETCAT_MODULE_STATS_PERIOD_WEEK . "</a>";
        echo " | <a href=" . $link_pref . "&date_start_d=" . $monthago_d . "&date_start_m=" . $monthago_m . "&date_start_y=" . $monthago_y . ">" . NETCAT_MODULE_STATS_PERIOD_MONTHS . "</a>";
        echo " | <a href=" . $link_pref . "&date_start_d=" . $wholeperiod_d . "&date_start_m=" . $wholeperiod_m . "&date_start_y=" . $wholeperiod_y . ">" . NETCAT_MODULE_STATS_PERIOD_ALL;
        echo "</td>";
    }
    echo "</tr></form></table><br>";
}

function stats_ShowReportTotal() {
    global $db, $cat_id, $SUB_FOLDER, $HTTP_ROOT_PATH, $UI_CONFIG;

    Stats_CreateReportAttendance("2000-01-01", "2037-01-01");

    $res = $db->get_row("SELECT SUM(NewHosts),SUM(Hits),SUM(NewVisitors) FROM Stats_Attendance WHERE Catalogue_ID='" . $cat_id . "' AND Date=CURRENT_DATE()", ARRAY_N);

    if ($db->num_rows) {
        list($hosts_today, $hits_today, $visitors_today) = $res;
    }

    $res = $db->get_row("SELECT SUM(NewHosts),SUM(Hits),SUM(NewVisitors) FROM Stats_Attendance WHERE Catalogue_ID='" . $cat_id . "' AND Date=DATE_ADD(CURRENT_DATE(),INTERVAL -1 DAY)", ARRAY_N);
    if ($db->num_rows) {
        list($hosts_yesterday, $hits_yesterday, $visitors_yesterday) = $res;
    }

    $res = $db->get_row("SELECT SUM(NewHosts),SUM(Hits),SUM(NewVisitors) FROM Stats_Attendance WHERE Catalogue_ID='" . $cat_id . "' AND Date>=DATE_ADD(CURRENT_DATE(),INTERVAL -7 DAY)", ARRAY_N);
    if ($db->num_rows) {
        list($hosts_week, $hits_week, $visitors_week) = $res;
    }

    $res = $db->get_row("SELECT SUM(NewHosts),SUM(Hits),SUM(NewVisitors) FROM Stats_Attendance WHERE Catalogue_ID='" . $cat_id . "' AND Date>=DATE_ADD(CURRENT_DATE(),INTERVAL -30 DAY)", ARRAY_N);
    if ($db->num_rows) {
        list($hosts_month, $hits_month, $visitors_month) = $res;
    }

    $res = $db->get_row("SELECT SUM(NewHosts),SUM(Hits),SUM(NewVisitors) FROM Stats_Attendance WHERE Catalogue_ID='" . $cat_id . "'", ARRAY_N);
    if ($db->num_rows) {
        list($hosts_total, $hits_total, $visitors_total) = $res;
    }

    echo "<table border=0 cellpadding=0 cellspacing=0 width=100%><tr><td><table class='admin_table' width=100%>";
    echo "<tr>";
    echo "<th width='16%'></th>";
    echo "<th width='16%'>" . ucfirst(NETCAT_MODULE_STATS_PERIOD_TODAY) . "</th>";
    echo "<th width='16%'>" . ucfirst(NETCAT_MODULE_STATS_PERIOD_YESTERDAY) . "</th>";
    echo "<th width='16%'>" . ucfirst(NETCAT_MODULE_STATS_PERIOD_WEEK) . "</th>";
    echo "<th width='16%'>" . ucfirst(NETCAT_MODULE_STATS_PERIOD_MONTHS) . "</th>";
    echo "<th width='16%'>" . ucfirst(NETCAT_MODULE_STATS_PERIOD_TOTAL) . "</th>";
    echo "</tr>";

    echo "<tr>";
    echo "<td ><font color=gray size=-1>" . NETCAT_MODULE_STATS_HOSTS . "</td>";
    echo "<td bgcolor=white><font size=-1>" . $hosts_today . "</td>";
    echo "<td bgcolor=white><font size=-1>" . $hosts_yesterday . "</td>";
    echo "<td bgcolor=white><font size=-1>" . $hosts_week . "</td>";
    echo "<td bgcolor=white><font size=-1>" . $hosts_month . "</td>";
    echo "<td bgcolor=white><font size=-1>" . $hosts_total . "</td>";
    echo "</tr>";

    $d_wholeperiod = $db->get_var("SELECT MIN(UNIX_TIMESTAMP(Date)) AS WholePeriod FROM Stats_Attendance WHERE Catalogue_ID='" . $cat_id . "'");

    $now_date = mktime(0, 0, 0, date("n"), date("j"), date("Y"));

    $d_yesterday = strtotime("-1 day", $now_date);
    $d_prev_week = strtotime("-1 week", $now_date);
    $d_prev_month = strtotime("-1 month", $now_date);
    $d_prev_year = strtotime("-1 year", $now_date);

    $a_today = "&cat_id=" . $cat_id . "&date_end_d=" . date("d") . "&date_end_m=" . date("m") . "&date_end_y=" . date("Y") . "&date_start_d=" . date("d") . "&date_start_m=" . date("m") . "&date_start_y=" . date("Y") . "'>";
    $a_yesterday = "&cat_id=" . $cat_id . "&date_end_d=" . date("d", $d_yesterday) . "&date_end_m=" . date("m", $d_yesterday) . "&date_end_y=" . date("Y", $d_yesterday) . "&date_start_d=" . date("d", $d_yesterday) . "&date_start_m=" . date("m", $d_yesterday) . "&date_start_y=" . date("Y", $d_yesterday) . "'>";
    $a_week = "&cat_id=" . $cat_id . "&date_end_d=" . date("d") . "&date_end_m=" . date("m") . "&date_end_y=" . date("Y") . "&date_start_d=" . date("d", $d_prev_week) . "&date_start_m=" . date("m", $d_prev_week) . "&date_start_y=" . date("Y", $d_prev_week) . "'>";
    $a_month = "&cat_id=" . $cat_id . "&date_end_d=" . date("d") . "&date_end_m=" . date("m") . "&date_end_y=" . date("Y") . "&date_start_d=" . date("d", $d_prev_month) . "&date_start_m=" . date("m", $d_prev_month) . "&date_start_y=" . date("Y", $d_prev_month) . "'>";
    $a_total = "&cat_id=" . $cat_id . "&date_end_d=" . date("d") . "&date_end_m=" . date("m") . "&date_end_y=" . date("Y") . "&date_start_d=" . date("d", $d_wholeperiod) . "&date_start_m=" . date("m", $d_wholeperiod) . "&date_start_y=" . date("Y", $d_wholeperiod) . "'>";

    echo "<tr>";
    echo "<td ><font color=gray size=-1>" . NETCAT_MODULE_STATS_HITS . "</font></td>";
    echo "<td bgcolor=white><font size=-1><a href='?phase=2" . $a_today . $hits_today . "</a></font></td>";
    echo "<td bgcolor=white><font size=-1><a href='?phase=2" . $a_yesterday . $hits_yesterday . "</a></font></td>";
    echo "<td bgcolor=white><font size=-1><a href='?phase=2" . $a_week . $hits_week . "</a></font></td>";
    echo "<td bgcolor=white><font size=-1><a href='?phase=2" . $a_month . $hits_month . "</a></font></td>";
    echo "<td bgcolor=white><font size=-1><a href='?phase=2" . $a_total . $hits_total . "</a></font></td>";
    echo "</tr>";

    echo "<tr>";
    echo "<td ><font color=gray size=-1>" . NETCAT_MODULE_STATS_USERS . "</font></td>";
    echo "<td bgcolor=white><font size=-1><a href='?phase=6" . $a_today . $visitors_today . "</font></td>";
    echo "<td bgcolor=white><font size=-1><a href='?phase=6" . $a_yesterday . $visitors_yesterday . "</font></td>";
    echo "<td bgcolor=white><font size=-1><a href='?phase=6" . $a_week . $visitors_week . "</font></td>";
    echo "<td bgcolor=white><font size=-1><a href='?phase=6" . $a_month . $visitors_month . "</font></td>";
    echo "<td bgcolor=white><font size=-1><a href='?phase=6" . $a_total . $visitors_total . "</font></td>";
    echo "</tr>";

    echo "</table></td></tr></table>";

    if (extension_loaded('gd')) {
        echo "<br><img width='590' height='420' src='" . nc_module_path('stats') . "diagram.php?cat_id=${cat_id}'><br>";
        echo "<br><br>";
        echo "<table cellspacing=0 cellpadding=5 style='border: 1px solid #cccccc;'>";
        echo "<tr><td style='border-bottom: 1px solid #cccccc;'><img src='" . nc_module_path('stats') . "images/red_line.gif'></td>";
        echo "<td style='border-bottom: 1px solid #cccccc;'>" . NETCAT_MODULE_STATS_HOSTS . "</td></tr>";
        echo "<tr><td><img src='" . nc_module_path('stats') . "images/green_line.gif'></td>";
        echo "<td>" . NETCAT_MODULE_STATS_HITS . "</td></tr>";
        echo "</table>";
    }

    echo "<br><hr size=1 color=cccccc>";
    echo "<form action='admin.php' method='get' name='clear' style='margin: 0px;' id='stats_ShowReportTotal'>" . NETCAT_MODULE_STATS_CLEAN . " ";
    echo "<input type='hidden' name='phase' value='8'>";
    echo "<input type='hidden' name='cat_id' value='" . $cat_id . "'>";
    echo "<input type='text' size='2' maxlength='2' name='d' value='" . date("d", $d_prev_year) . "'>";
    echo "<input type='text' size='2' maxlength='2' name='m' value='" . date("m", $d_prev_year) . "'>";
    echo "<input type='text' size='4' maxlength='4' name='y' value='" . date("Y", $d_prev_year) . "'>";
    $UI_CONFIG->actionButtons[] = array(
        "id" => "submit",
        "caption" => NETCAT_MODULE_STATS_CLEAN_BUTTON,
        "action" => "mainView.submitIframeForm('stats_ShowReportTotal')",
        "red_border" => true,
    );
    echo "<input type='submit' class='hidden'></form>";
}

function stats_ShowReportAttendance($date_start, $date_end) {
    global $db, $cat_id;

    $cat_id = intval($cat_id);
    $date_start = $db->escape($date_start);
    $date_end = $db->escape($date_end);

    Stats_CreateReportAttendance($date_start, $date_end);

    $res = $db->get_results("SELECT Hour,SUM(Hits),SUM(Hosts),SUM(Visitors),SUM(NewHosts),SUM(NewVisitors) FROM Stats_Attendance WHERE Catalogue_ID='" . $cat_id . "' AND Date>='" . $date_start . "' AND Date<='" . $date_end . "' GROUP BY Hour ORDER BY Hour", ARRAY_N);
    if (!$count = $db->num_rows)
        return;

    echo "<b>" . NETCAT_MODULE_STATS_TXT_HOSTSPERPERIOD . "</b><br><br>";

    echo "<table border=0 cellpadding=0 cellspacing=0 width=100%><tr><td><table class='admin_table' width=100%>";
    echo "<tr>";
    echo "<td width=25% ><font color=gray size=-1>" . NETCAT_MODULE_STATS_HOUR . "</td>";
    echo "<td width=25% ><font color=gray size=-1>" . NETCAT_MODULE_STATS_HOSTS . "</td>";
    echo "<td width=25% ><font color=gray size=-1>" . NETCAT_MODULE_STATS_HITS . "</td>";
    echo "<td width=25% ><font color=gray size=-1>" . NETCAT_MODULE_STATS_USERS . "</td>";
    echo "</tr>";

    $sum_hosts = 0;
    $sum_hits = 0;
    $sum_visitors = 0;

    for ($i = 0; $i < $count; $i++) {
        list($hour_tmp, $hits_tmp, $hosts_tmp, $visitors_tmp, $newhosts_tmp, $newvisitors_tmp) = $res[$i];

        $hits[$hour_tmp] = $hits_tmp;
        $hosts[$hour_tmp] = $hosts_tmp;
        $visitors[$hour_tmp] = $visitors_tmp;
        $newhosts[$hour_tmp] = $newhosts_tmp;
        $newvisitors[$hour_tmp] = $newvisitors_tmp;

        $sum_hosts += $newhosts_tmp;
        $sum_hits += $hits_tmp;
        $sum_visitors += $newvisitors_tmp;
    }

    for ($i = 0; $i < 24; $i++) {
        echo "<tr>";
        echo "<td bgcolor=white><font size=-1><b>" . $i . "</td>";
        echo "<td bgcolor=white><font size=-1>" . $hosts[$i] . ($newhosts[$i] ? " <font color=gray>(+" . $newhosts[$i] . ")" : "") . "<br></td>";
        echo "<td bgcolor=white><font size=-1>" . $hits[$i] . "<br></td>";
        echo "<td bgcolor=white><font size=-1>" . $visitors[$i] . ($newvisitors[$i] ? " <font color=gray>(+" . $newvisitors[$i] . ")" : "") . "<br></td>";
        echo "</tr>";
    }

    echo "<tr>";
    echo "<td ><font size=-1><b>" . ucfirst(NETCAT_MODULE_STATS_PERIOD_TOTAL) . "</td>";
    echo "<td ><font size=-1><b>" . $sum_hosts . "</td>";
    echo "<td ><font size=-1><b>" . $sum_hits . "</td>";
    echo "<td ><font size=-1><b>" . $sum_visitors . "</td>";
    echo "</tr>";

    echo "</table></td></tr></table><br>" . NETCAT_MODULE_STATS_TXT_TIPONE;


    echo "<br><br><b>" . NETCAT_MODULE_STATS_TXT_TIPTWO . "</b><br><br>";
    stats_ShowReportAttendanceAverage($date_start, $date_end);
}

function stats_ShowReportAttendanceAverage($date_start, $date_end) {
    global $db, $cat_id;

    $cat_id = intval($cat_id);
    $date_start = $db->escape($date_start);
    $date_end = $db->escape($date_end);

    $res = $db->get_results("SELECT Hour,ROUND(AVG(Hits)),ROUND(AVG(Hosts)),ROUND(AVG(Visitors)),ROUND(AVG(NewHosts)),ROUND(AVG(NewVisitors)) FROM Stats_Attendance WHERE Catalogue_ID='" . $cat_id . "' AND Date>='" . $date_start . "' AND Date<='" . $date_end . "' GROUP BY Hour ORDER BY Hour", ARRAY_N);
    if (!$count = $db->num_rows)
        return;

    echo "<table border=0 cellpadding=0 cellspacing=0 width=100%><tr><td ><table class='admin_table' width=100%>";
    echo "<tr>";
    echo "<td width=25% ><font color=gray size=-1>" . NETCAT_MODULE_STATS_HOUR . "</td>";
    echo "<td width=25% ><font color=gray size=-1>" . NETCAT_MODULE_STATS_HOSTS . "</td>";
    echo "<td width=25% ><font color=gray size=-1>" . NETCAT_MODULE_STATS_HITS . "</td>";
    echo "<td width=25% ><font color=gray size=-1>" . NETCAT_MODULE_STATS_USERS . "</td>";
    echo "</tr>";

    $sum_hosts = 0;
    $sum_hits = 0;
    $sum_visitors = 0;

    for ($i = 0; $i < $count; $i++) {
        list($hour_tmp, $hits_tmp, $hosts_tmp, $visitors_tmp, $newhosts_tmp, $newvisitors_tmp) = $res[$i];

        $hits[$hour_tmp] = $hits_tmp;
        $hosts[$hour_tmp] = $hosts_tmp;
        $visitors[$hour_tmp] = $visitors_tmp;
        $newhosts[$hour_tmp] = $newhosts_tmp;
        $newvisitors[$hour_tmp] = $newvisitors_tmp;

        $sum_hosts += $newhosts_tmp;
        $sum_hits += $hits_tmp;
        $sum_visitors += $newvisitors_tmp;
    }

    for ($i = 0; $i < 24; $i++) {
        echo "<tr>";
        echo "<td bgcolor=white><font size=-1><b>" . $i . "</td>";
        echo "<td bgcolor=white><font size=-1>" . $hosts[$i] . ($newhosts[$i] ? " <font color=gray>(+" . $newhosts[$i] . ")" : "") . "<br></td>";
        echo "<td bgcolor=white><font size=-1>" . $hits[$i] . "<br></td>";
        echo "<td bgcolor=white><font size=-1>" . $visitors[$i] . ($newvisitors[$i] ? " <font color=gray>(+" . $newvisitors[$i] . ")" : "") . "<br></td>";
        echo "</tr>";
    }

    echo "<tr>";
    echo "<td ><font size=-1><b>" . ucfirst(NETCAT_MODULE_STATS_PERIOD_TOTAL) . "</b></font></td>";
    echo "<td ><font size=-1><b>" . $sum_hosts . "</b></font></td>";
    echo "<td ><font size=-1><b>" . $sum_hits . "</b></font></td>";
    echo "<td ><font size=-1><b>" . $sum_visitors . "</b></font></td>";
    echo "</tr>";
    echo "</table></td></tr></table><br>" . NETCAT_MODULE_STATS_TXT_TIPONE;
}

function stats_ShowReportPopularity($date_start, $date_end) {
    global $db, $cat_id;

    $cat_id = intval($cat_id);
    $date_start = $db->escape($date_start);
    $date_end = $db->escape($date_end);

    Stats_CreateReportPopularity($date_start, $date_end);

    $total = $db->get_var("SELECT SUM(Hits) FROM Stats_Popularity WHERE Catalogue_ID='" . $cat_id . "' AND Date>='" . $date_start . "' AND Date<='" . $date_end . "'");

    $total += 0;

    $res = $db->get_results("SELECT IF(SUBSTRING(Link,1,4)='www.',SUBSTRING(Link,5),Link),SUM(Hits) AS Hits,((SUM(Hits)*100)/$total) FROM Stats_Popularity WHERE Catalogue_ID='" . $cat_id . "' AND Date>='" . $date_start . "' AND Date<='" . $date_end . "' GROUP BY IF(SUBSTRING(Link,1,4)='www.',SUBSTRING(Link,5),Link) ORDER BY Hits DESC LIMIT 50", ARRAY_N);
    if (!$count = $db->num_rows)
        return;

    echo "<table border=0 cellpadding=0 cellspacing=0 width=100%><tr><td ><table class='admin_table' width=100%>";
    echo "<tr>";
    echo "<td width=50% ><font color=gray size=-1>" . NETCAT_MODULE_STATS_PAGE . "</td>";
    echo "<td width=25% ><font color=gray size=-1>" . NETCAT_MODULE_STATS_HITS . "</td>";
    echo "<td width=25% ><font color=gray size=-1>" . NETCAT_MODULE_STATS_PERCENTINGROUP . "</td>";
    echo "</tr>";

    for ($i = 0; $i < $count; $i++) {
        list($link, $hits, $percent) = $res[$i];

        echo "<tr>";
        echo "<td bgcolor=white><font size=-1><a href=http://" . $link . " target=_blank>" . (strlen($link) > 50 ? substr($link, 0, 50) . " ..." : $link) . "</a></td>";
        echo "<td bgcolor=white><font size=-1>" . $hits . "</td>";
        echo "<td bgcolor=white><font size=-1>" . $percent . "%</td>";
        echo "</tr>";
    }

    echo "</table></td></tr></table>";
}

function stats_ShowReportReferer($date_start, $date_end) {
    global $db, $ref;

    global $phase;
    global $date_start_d, $date_start_m, $date_start_y;
    global $date_end_d, $date_end_m, $date_end_y;
    global $cat_id;

    Stats_CreateReportReferer($date_start, $date_end);

    $total = $db->get_var("SELECT SUM(Hits) FROM Stats_Referer WHERE Catalogue_ID='" . $cat_id . "' AND Date>='" . $date_start . "' AND Date<='" . $date_end . "'");

    $total += 0;
    $SQL = "SELECT " . ($ref ? "Referer" : "SUBSTRING(Referer,8,LOCATE('/',SUBSTRING(Referer,8))-1) AS Referer") . ",
                                    SUM(Hits) AS Hits,
                                    SUM(Hosts),
                                    SUM(Visitors),
                                    ((SUM(Hits)*100)/$total)
                                 FROM Stats_Referer
                                     WHERE Catalogue_ID='" . $cat_id . "'
                                       AND Date>='" . $date_start . "'
                                       AND Date<='" . $date_end . "'" . ($ref ? "
                                       AND Referer LIKE 'http://" . $ref . "%'" : "") . "
                                          GROUP BY Referer
                                              ORDER BY Hits DESC
                                                  LIMIT 50";
    $res = $db->get_results($SQL, ARRAY_N);
    if (!$count = $db->num_rows)
        return;

    echo "<table border=0 cellpadding=0 cellspacing=0 width=100%><tr><td><tableclass='admin_table' width=100%>";
    echo "<tr>";
    echo "<td width=50% ><font color=gray size=-1>" . ($ref ? NETCAT_MODULE_STATS_PAGE : CONTROL_CONTENT_CATALOUGE_ONESITE) . "</td>";
    echo "<td width=25% ><font color=gray size=-1>" . NETCAT_MODULE_STATS_HITS . "</td>";
    echo "<td width=25% ><font color=gray size=-1>" . NETCAT_MODULE_STATS_PERCENTINGROUP . "</td>";
    echo "</tr>";

    for ($i = 0; $i < $count; $i++) {
        list($link, $hits, $hosts, $visitors, $percent) = $res[$i];

        echo "<tr><td bgcolor=white><font size=-1>";

        $l = parse_url($link);
        $l = ($l['scheme'] == 'http') ? $link : "http://" . $link;

        if (strlen($link) > 0) {
            echo "<a href=";
            echo ($ref ? $l . " target=_blank" : "?cat_id=" . $cat_id . "&phase=$phase&date_start_y=" . $date_start_y . "&date_start_m=" . $date_start_m . "&date_start_d=" . $date_start_d . "&date_end_y=" . $date_end_y . "&date_end_m=" . $date_end_m . "&date_end_d=" . $date_end_d . "&ref=" . $link) . ">" . (strlen($link) > 50 ? substr($link, 0, 50) . " ..." : $link);
            echo "</a>";
        } else {
            echo NETCAT_MODULE_STATS_NOT_DEFINED;
        }

        echo "</td><td bgcolor=white><font size=-1>" . $hits . "</td>";
        echo "<td bgcolor=white><font size=-1>" . $percent . "%</td>";
        echo "</tr>";
    }

    echo "</table></td></tr></table>";
}

function stats_ShowReportBrowser($date_start, $date_end) {
    global $db;
    global $cat_id;
    global $date_start_d, $date_start_m, $date_start_y;
    global $date_end_d, $date_end_m, $date_end_y, $SUB_FOLDER, $HTTP_ROOT_PATH;

    Stats_CreateReportBrowser($date_start, $date_end);

    $total = $db->get_var("SELECT SUM(Visitors) FROM Stats_Browser WHERE Catalogue_ID='" . $cat_id . "' AND Date>='" . $date_start . "' AND Date<='" . $date_end . "'");

    $total += 0;

    $res = $db->get_results("SELECT Browser,SUM(Visitors) AS Visitors,((SUM(Visitors)*100)/$total) FROM Stats_Browser WHERE Catalogue_ID='" . $cat_id . "' AND Date>='" . $date_start . "' AND Date<='" . $date_end . "' GROUP BY Browser ORDER BY Visitors DESC LIMIT 50", ARRAY_N);
    if (!$count = $db->num_rows)
        return;

    echo "<table border=0 cellpadding=0 cellspacing=0 width=100%><tr><td><table class='admin_table' width=100%>";
    echo "<tr>";
    echo "<td width=50% ><font color=gray size=-1>" . NETCAT_MODULE_STATS_BROWSER . "</td>";
    echo "<td width=25% ><font color=gray size=-1>" . NETCAT_MODULE_STATS_USERS . "</td>";
    echo "<td width=25% ><font color=gray size=-1>" . NETCAT_MODULE_STATS_PERCENTINGROUP . "</td>";
    echo "</tr>";

    for ($i = 0; $i < $count; $i++) {
        list($browser, $visitors, $percent) = $res[$i];
        echo "<tr>";
        echo "<td bgcolor=white><font size=-1>" . ($browser ? $browser : NETCAT_MODULE_STATS_UNKNOWN) . "</td>";
        echo "<td bgcolor=white><font size=-1>" . $visitors . "</td>";
        echo "<td bgcolor=white><font size=-1>" . $percent . "%</td>";
        echo "</tr>";
    }

    echo "</table></td></tr></table>";

    if (extension_loaded('gd')) {
        echo "<br>";
        echo "<table border='0' cellpadding='0' cellspacing='0'>";
        echo "<tr><td valign='top'>";
        echo "<img width='196' height='196' alt='' src='" . nc_module_path('stats') . "pie.php?phase=3&cat_id=" . $cat_id . "&date_start_y=" . $date_start_y . "&date_start_m=" . $date_start_m . "&date_start_d=" . $date_start_d . "&date_end_y=" . $date_end_y . "&date_end_m=" . $date_end_m . "&date_end_d=" . $date_end_d . "'><br>";
        echo "</td><td width='10'>&nbsp;</td><td valign='top'>";
        echo "<table cellspacing='0' cellpadding='0' border='0'>";

        for ($i = 0; $i < $count; $i++) {
            if ($i > 9)
                break;
            $img_i = $i + 1;
            list($browser, $visitors, $percent) = $res[$i];
            echo "<tr>";
            echo "<td align='right' style='font-size: 12px;' valign='center'>" . $img_i . ".</td>";
            echo "<td>&nbsp;</td>";
            echo "<td valign='center'><img src='" . nc_module_path('stats') . "images/marker_" . $img_i . ".gif'></td>";
            echo "<td width='6'>&nbsp;</td>";
            echo "<td valign='center' style='font-size: 12px;'>" . ($browser ? $browser : NETCAT_MODULE_STATS_UNKNOWN) . "</td>";
            echo "</tr>";
            echo "<tr><td colspan='5' height='4'></td></tr>";
        }

        echo "<tr><td>&nbsp;</td><td>&nbsp;</td>";
        echo "<td valign='center'><img src='" . nc_module_path('stats') . "images/marker_other.gif'></td>";
        echo "<td width='6'>&nbsp;</td>";
        echo "<td valign='center' style='font-size: 12px;'>" . NETCAT_MODULE_STATS_PIE_OTHER . "</td>";
        echo "</tr>";

        echo "</table></td></tr></table>";
    }
}

function stats_ShowReportOS($date_start, $date_end) {
    global $db;
    global $cat_id;
    global $date_start_d, $date_start_m, $date_start_y;
    global $date_end_d, $date_end_m, $date_end_y, $SUB_FOLDER, $HTTP_ROOT_PATH;

    Stats_CreateReportOS($date_start, $date_end);

    $total = $db->get_var("SELECT SUM(Visitors) FROM Stats_OS WHERE Catalogue_ID='" . $cat_id . "' AND Date>='" . $date_start . "' AND Date<='" . $date_end . "'");

    $total += 0;

    $res = $db->get_results("SELECT OS,SUM(Visitors) AS Visitors,((SUM(Visitors)*100)/$total) FROM Stats_OS WHERE Catalogue_ID='" . $cat_id . "' AND Date>='" . $date_start . "' AND Date<='" . $date_end . "' GROUP BY OS ORDER BY Visitors DESC LIMIT 50", ARRAY_N);
    if (!$count = $db->num_rows)
        return;

    echo "<table border=0 cellpadding=0 cellspacing=0 width=100%><tr><td><table class='admin_table' width=100%>";
    echo "<tr>";
    echo "<td width=50% ><font color=gray size=-1>" . NETCAT_MODULE_STATS_OS . "</td>";
    echo "<td width=25% ><font color=gray size=-1>" . NETCAT_MODULE_STATS_USERS . "</td>";
    echo "<td width=25% ><font color=gray size=-1>" . NETCAT_MODULE_STATS_PERCENTINGROUP . "</td>";
    echo "</tr>";

    for ($i = 0; $i < $count; $i++) {
        list($os, $visitors, $percent) = $res[$i];

        echo "<tr>";
        echo "<td bgcolor=white><font size=-1>" . ($os ? $os : NETCAT_MODULE_STATS_UNKNOWN) . "</td>";
        echo "<td bgcolor=white><font size=-1>" . $visitors . "</td>";
        echo "<td bgcolor=white><font size=-1>" . $percent . "%</td>";
        echo "</tr>";
    }

    echo "</table></td></tr></table>";

    if (extension_loaded('gd')) {
        echo "<br>";
        echo "<table border='0' cellpadding='0' cellspacing='0'>";
        echo "<tr><td valign='top'>";
        echo "<img width='196' height='196' alt='' src='" . nc_module_path('stats') . "pie.php?phase=2&cat_id=" . $cat_id . "&date_start_y=" . $date_start_y . "&date_start_m=" . $date_start_m . "&date_start_d=" . $date_start_d . "&date_end_y=" . $date_end_y . "&date_end_m=" . $date_end_m . "&date_end_d=" . $date_end_d . "'><br>";
        echo "</td><td width='10'>&nbsp;</td><td valign='top'>";
        echo "<table cellspacing='0' cellpadding='0' border='0'>";


        for ($i = 0; $i < $count; $i++) {
            if ($i > 9)
                break;
            $img_i = $i + 1;
            list($os, $visitors, $percent) = $res[$i];
            echo "<tr>";
            echo "<td align='right' style='font-size: 12px;' valign='center'>" . $img_i . ".</td>";
            echo "<td>&nbsp;</td>";
            echo "<td valign='center'><img src='" . nc_module_path('stats') . "images/marker_" . $img_i . ".gif'></td>";
            echo "<td width='6'>&nbsp;</td>";
            echo "<td valign='center' style='font-size: 12px;'>" . ($os ? $os : NETCAT_MODULE_STATS_UNKNOWN) . "</td>";
            echo "</tr>";
            echo "<tr><td colspan='5' height='4'></td></tr>";
        }

        echo "<tr><td>&nbsp;</td><td>&nbsp;</td>";
        echo "<td valign='center'><img src='" . nc_module_path('stats') . "images/marker_other.gif'></td>";
        echo "<td width='6'>&nbsp;</td>";
        echo "<td valign='center' style='font-size: 12px;'>" . NETCAT_MODULE_STATS_PIE_OTHER . "</td>";
        echo "</tr>";

        echo "</table></td></tr></table>";
    }
}

function stats_ShowReportIP($date_start, $date_end) {
    global $db;

    global $phase, $ip;
    global $date_start_d, $date_start_m, $date_start_y;
    global $date_end_d, $date_end_m, $date_end_y;
    global $cat_id, $SUB_FOLDER, $HTTP_ROOT_PATH;

    $cat_id = intval($cat_id);
    $date_start = $db->escape($date_start);
    $date_end = $db->escape($date_end);

    Stats_CreateReportIP($date_start, $date_end);

    if ($ip) {
        echo "<font size=+1><b>$ip" . (($host = gethostbyaddr($ip)) != $ip ? " ($host)" : "") . "</b></font>";
        $nextServer = "whois.ripe.net";
        $buffer = "";

        if (!$sock = fsockopen($nextServer, 43, $num, $error, 10)) {
            unset($sock);
        } else {
            fputs($sock, "$ip$extra\n");
            while (!feof($sock))
                $buffer .= fgets($sock, 10240);
            fclose($sock);
        }

        $buffer = str_replace(" ", "&nbsp;", $buffer);
        $msg = $buffer;

        echo "<pre><font size=+0>$msg</pre></font>";
        return;
    }

    $total = $db->get_var("SELECT SUM(Hits) FROM Stats_IP WHERE Catalogue_ID='" . $cat_id . "' AND Date>='" . $date_start . "' AND Date<='" . $date_end . "'");

    $total += 0;

    $res = $db->get_results("SELECT IP,SUM(Hits) AS Hits,((SUM(Hits)*100)/$total) FROM Stats_IP WHERE Catalogue_ID='" . $cat_id . "' AND Date>='" . $date_start . "' AND Date<='" . $date_end . "' GROUP BY IP ORDER BY Hits DESC LIMIT 50", ARRAY_N);
    if (!$count = $db->num_rows)
        return;

    echo "<table border=0 cellpadding=0 cellspacing=0 width=100%><tr><td><table class='admin_table' width=100%>";
    echo "<tr>";
    echo "<td width=50%><font color=gray size=-1>" . NETCAT_MODULE_STATS_IP . "</td>";
    echo "<td width=25% ><font color=gray size=-1>" . NETCAT_MODULE_STATS_HITS . "</td>";
    echo "<td width=25% ><font color=gray size=-1>" . NETCAT_MODULE_STATS_PERCENTINGROUP . "</td>";
    echo "</tr>";

    for ($i = 0; $i < $count; $i++) {
        list($ip, $hits, $percent) = $res[$i];

        echo "<tr>";
        echo "<td bgcolor=white><font size=-1><a href=?cat_id=" . $cat_id . "&phase=$phase&date_start_y=" . $date_start_y . "&date_start_m=" . $date_start_m . "&date_start_d=" . $date_start_d . "&date_end_y=" . $date_end_y . "&date_end_m=" . $date_end_m . "&date_end_d=" . $date_end_d . "&ip=" . $ip . ">" . $ip . "</a></td>";
        echo "<td bgcolor=white><font size=-1>" . $hits . "</td>";
        echo "<td bgcolor=white><font size=-1>" . $percent . "%</td>";
        echo "</tr>";
    }

    echo "</table></td></tr></table>";

    if (extension_loaded('gd')) {
        echo "<br>";
        echo "<table border='0' cellpadding='0' cellspacing='0'>";
        echo "<tr><td valign='top'>";
        echo "<img width='196' height='196' alt='' src='" . nc_module_path('stats') . "pie.php?phase=1&cat_id=" . $cat_id . "&date_start_y=" . $date_start_y . "&date_start_m=" . $date_start_m . "&date_start_d=" . $date_start_d . "&date_end_y=" . $date_end_y . "&date_end_m=" . $date_end_m . "&date_end_d=" . $date_end_d . "'><br>";
        echo "</td><td width='10'>&nbsp;</td><td valign='top'>";
        echo "<table cellspacing='0' cellpadding='0' border='0'>";


        for ($i = 0; $i < $count; $i++) {
            if ($i > 9)
                break;
            $img_i = $i + 1;
            list($ip, $hits, $percent) = $res[$i];
            echo "<tr>";
            echo "<td align='right' style='font-size: 12px;' valign='center'>" . $img_i . ".</td>";
            echo "<td>&nbsp;</td>";
            echo "<td valign='center'><img src='" . nc_module_path('stats') . "images/marker_" . $img_i . ".gif'></td>";
            echo "<td width='6'>&nbsp;</td>";
            echo "<td valign='center' style='font-size: 12px;'>" . $ip . "</td>";
            echo "</tr>";
            echo "<tr><td colspan='5' height='4'></td></tr>";
        }

        echo "<tr><td>&nbsp;</td><td>&nbsp;</td>";
        echo "<td valign='center'><img src='" . nc_module_path('stats') . "images/marker_other.gif'></td>";
        echo "<td width='6'>&nbsp;</td>";
        echo "<td valign='center' style='font-size: 12px;'>" . NETCAT_MODULE_STATS_PIE_OTHER . "</td>";
        echo "</tr>";

        echo "</table></td></tr></table>";
    }
}

function stats_ShowReportGeo($date_start, $date_end) {
    Stats_CreateReportGeo($date_start, $date_end);

    global $db, $cat_id, $SUB_FOLDER, $HTTP_ROOT_PATH;

    $s = "SELECT SUM(Hosts), SUM(Hits), SUM(Visitors)
            FROM Stats_Geo
           WHERE (Date BETWEEN '${date_start}' AND '${date_end}')
             AND Catalogue_ID='${cat_id}'";

    list($hosts_sum, $hits_sum, $visitors_sum) = $db->get_row($s, ARRAY_N);
    if ($db->num_rows == 0)
        return;

    if (!isset($hits_sum) || !$hits_sum)
        return;
    ?>
    <table border='0' cellpadding='0' cellspacing='0' width='100%'>
        <tr>
            <td>
                <table class='admin_table' width='100%'>
                    <tr>
                        <td >&nbsp;</td>
                        <td width='55%' >
                            <font color='gray' size='-1'><?= NETCAT_MODULE_STATS_COUNTRY ?></font>
                        </td>
                        <td width='15%' >
                            <font color='gray' size='-1'><?= NETCAT_MODULE_STATS_HOSTS ?></font>
                        </td>
                        <td width='15%' >
                            <font color='gray' size='-1'><?= NETCAT_MODULE_STATS_HITS ?></font>
                        </td>
                        <td width='15%' >
                            <font color='gray' size='-1'><?= NETCAT_MODULE_STATS_USERS ?></font>
                        </td>
                    </tr>
                    <?php
                    $s = "SELECT Country,
             SUM(Hosts) AS Hosts,
             SUM(Hits) AS Hits,
             SUM(Visitors) AS Visitors
        FROM Stats_Geo
       WHERE (Date BETWEEN '${date_start}' AND '${date_end}') AND Catalogue_ID='${cat_id}'
       GROUP BY Country ORDER BY Hosts DESC, Hits DESC";

                    $q = $db->get_results($s, ARRAY_A);

                    if ($db->num_rows != 0) {
                        foreach ($q as $row) {
                            $c_code = strtolower($row["Country"]);

                            $const_country = "NETCAT_MODULE_STATS_" . $row['Country'];
                            if (defined($const_country))
                                $country = constant($const_country);
                            else
                                $country = NETCAT_MODULE_STATS_UNKNOWN;
                            ?>
                            <tr>
                                <td bgcolor='white'>
                                    <img src='<?= nc_module_path('stats'); ?>flags/<?= $c_code ?>.png' width='18' height='12' alt=''>
                                </td>
                                <td bgcolor='white'><font size='-1'><?= $country ?></font></td>
                                <td bgcolor='white'><font size='-1'><? printf("%d (%.2f%%)", $row["Hosts"], $row["Hosts"] / $hosts_sum * 100); ?></font></td>
                                <td bgcolor='white'><font size='-1'><? printf("%d (%.2f%%)", $row["Hits"], $row["Hits"] / $hits_sum * 100); ?></font></td>
                                <td bgcolor='white'><font size='-1'><? printf("%d (%.2f%%)", $row["Visitors"], $row["Visitors"] / $visitors_sum * 100); ?></font></td>
                            </tr>
            <?php
        }
    }
    ?>
                </table>
            </td>
        </tr>
    </table>
    <?php
}

function stats_ClearStats() {
    global $db;
    global $d, $m, $y;
    global $cat_id;

    $stat_tbls = array(
            'Stats_Browser',
            'Stats_IP',
            'Stats_OS',
            'Stats_Popularity',
            'Stats_Referer',
            'Stats_Phrases',
            'Stats_Geo',
            'Stats_Attendance');

    if (strlen($d) > 0 && strlen($m) > 0 && strlen($y) > 0) {
        foreach ($stat_tbls as $table_to_clean) {
            $s = "DELETE FROM $table_to_clean WHERE Date <= '$y-$m-$d' AND Catalogue_ID = " .  $cat_id;
            $db->query($s);
        }

        $s = "DELETE FROM Stats_Log WHERE Created <= '$y-$m-$d' AND Catalogue_ID = " . $cat_id;
        $db->query($s);


        echo "<br>" . NETCAT_MODULE_STATS_CLEAN_SUCCESS . "<br>";
    }
}

function stats_ShowCatalogues() {
    global $db;

    $s = "SELECT Catalogue_ID, Catalogue_Name FROM Catalogue ORDER BY Catalogue_ID";
    $res = $db->get_results($s, ARRAY_N);

    echo "<form action='admin.php' method='get'>";
    echo "<input type='hidden' name='phase' value='9'>";
    echo "<select onchange=\"this.form.submit()\" name='cat_id'>";

    foreach ($res as $row) {
        list($cat_id, $cat_name) = $row;
        echo "<option value='$cat_id'>" . $cat_id . ": " . $cat_name . "</option>";
    }

    echo "</select><br><hr size='1' color='cccccc'>";
    echo "<div align='left'><input class='s' type='submit' title='" . NETCAT_MODULE_STATS_SHOW_BUTTON . "' value='" . NETCAT_MODULE_STATS_SHOW_BUTTON . "'></div>";
    echo "</form>";
}

function stats_ShowReportPhrases($date_start, $date_end) {
    global $db;

    global $phase, $ip;
    global $date_start_d, $date_start_m, $date_start_y;
    global $date_end_d, $date_end_m, $date_end_y;
    global $cat_id;
    $cat_id = intval($cat_id);
    $date_start = $db->escape($date_start);
    $date_end = $db->escape($date_end);
    $total = $db->get_var("SELECT SUM(Hits) FROM Stats_Phrases WHERE Catalogue_ID='" . $cat_id . "' AND Date>='" . $date_start . "' AND Date<='" . $date_end . "'");

    $total += 0;

    $res = $db->get_results("SELECT Phrase,SUM(Hits) AS Hits,((SUM(Hits)*100)/$total) FROM Stats_Phrases WHERE Catalogue_ID='" . $cat_id . "' AND Date>='" . $date_start . "' AND Date<='" . $date_end . "' GROUP BY Phrase ORDER BY Hits DESC LIMIT 50", ARRAY_N);
    if (!$count = $db->num_rows)
        return;

    echo "<table border=0 cellpadding=0 cellspacing=0 width=100%><tr><td><table class='admin_table' width=100%>";
    echo "<tr>";
    echo "<td width=50% ><font color=gray size=-1>" . NETCAT_MODULE_STATS_PHRASE . "</td>";
    echo "<td width=25% ><font color=gray size=-1>" . NETCAT_MODULE_STATS_HITS . "</td>";
    echo "<td width=25% ><font color=gray size=-1>" . NETCAT_MODULE_STATS_PERCENTINGROUP . "</td>";
    echo "</tr>";

    for ($i = 0; $i < $count; $i++) {
        list($phrase, $hits, $percent) = $res[$i];

        $phrase = htmlspecialchars(stripslashes($phrase));

        echo "<tr>";
        echo "<td bgcolor=white><font size=-1>" . $phrase . "</a></td>";
        echo "<td bgcolor=white><font size=-1>" . $hits . "</td>";
        echo "<td bgcolor=white><font size=-1>" . $percent . "%</td>";
        echo "</tr>";
    }

    echo "</table></td></tr></table>";
}

function stats_IsOneCatalogue() {
    global $db;

    $q = $db->get_var("SELECT Catalogue_ID FROM Catalogue");
    if ($db->num_rows > 1) {
        return FALSE;
    } elseif ($db->num_rows != 0) {
        return $q;
    }

    return FALSE;
}

// --------------------------------------------------------------------------
// --------------------------------------------------------------------------

/**
 * Создает все отчеты и очищает Stats_Log от старых записей
 */
function Stats_CreateReports() {
    global $db;

    list($date_from, $date_to) = $db->get_row(
            "SELECT DATE_FORMAT(MIN(Created),'%Y-%m-%d'),
                             DATE_FORMAT(MAX(Created),'%Y-%m-%d')
                        FROM Stats_Log", ARRAY_N);

    Stats_CreateReportAttendance($date_from, $date_to);
    Stats_CreateReportPopularity($date_from, $date_to);
    Stats_CreateReportReferer($date_from, $date_to);
    Stats_CreateReportGeo($date_from, $date_to);
    Stats_CreateReportIP($date_from, $date_to);
    Stats_CreateReportBrowser($date_from, $date_to);
    Stats_CreateReportOS($date_from, $date_to);

    $db->query("DELETE FROM Stats_Log WHERE Created < CURRENT_DATE()");
}

/**
 * Проверяет, есть ли данные на указанный диапазон дат в Stats_Log.
 *
 * Если данные отсутствуют, возвращает FALSE.
 *
 * Если данные есть, удаляет из таблицы $stat_table данные для указанного
 * диапазона дат и возвращает TRUE.
 *
 * @param string имя таблицы
 * @param string начальная дата %Y-%m-%d
 * @param string конечная дата %Y-%m-%d
 * @param boolean есть или нет данные для данного диапазона дат
 */
function Stats_PrepareTable($stat_table, $date_from, $date_to) {
    global $db;
    $date_from = $db->escape($date_from);
    $date_to = $db->escape($date_to);
    $stat_table = $db->escape($stat_table);
    // проверяем, есть ли данные в указанном диапазоне дат
    // и получаем минимальную и максимальную дату
    list($has_data_from, $has_data_to) = $db->get_row(
            "SELECT DATE_FORMAT(MIN(Created),'%Y-%m-%d'),
                             DATE_FORMAT(MAX(Created),'%Y-%m-%d')
                        FROM Stats_Log
                       WHERE Created BETWEEN '" . $date_from . " 00:00:00'
                                         AND '" . $date_to . " 23:59:59'", ARRAY_N);
    $has_data_from = $db->escape($has_data_from);
    $has_data_to = $db->escape($has_data_to);
    if (!$has_data_from)
        return false; // данных нет!

    $db->query("DELETE FROM `Stats_" . $stat_table . "`
               WHERE Date BETWEEN '" . $has_data_from . " 00:00:00' AND '" . $has_data_to . " 23:59:59'");

    return true;
}

/**
 * Создает отчет Attendance на основе данных из stats_log
 */
function Stats_CreateReportAttendance($date_from, $date_to) {
    if (!Stats_PrepareTable("Attendance", $date_from, $date_to))
        return;

    global $db;

    $qry_created = "Created BETWEEN '$date_from 00:00:00' AND '$date_to 23:59:59'";

    // Создать отчет
    // 1. Хиты, хосты и посетители
    $db->query("INSERT INTO Stats_Attendance ( Date,
                                              Hour,
                                              Hosts,
                                              Hits,
                                              Visitors,
                                              Catalogue_ID )

               SELECT DATE_FORMAT(Created,'%Y-%m-%d') AS Date,
                      DATE_FORMAT(Created,'%H') AS Hour,
                      COUNT(DISTINCT REMOTE_ADDR) AS Hosts,
                      COUNT(*) AS Hits,
                      COUNT(DISTINCT REMOTE_ADDR,HTTP_USER_AGENT,Cookie_ID) AS Visitors,
                      Catalogue_ID

                 FROM Stats_Log
                WHERE $qry_created
                GROUP BY Date,Hour,Catalogue_ID");

    // в $new будем складывать данные по новым посетителям и хостам
    $new = array();
    // "Новые хосты"
    $res = $db->get_results("SELECT DATE_FORMAT(Created,'%Y-%m-%d') AS Date,
                                   MIN(DATE_FORMAT(Created,'%H')) AS Hour,
                                   COUNT(DISTINCT REMOTE_ADDR) AS New_Hosts,
                                   Catalogue_ID
                              FROM Stats_Log
                             WHERE $qry_created
                             GROUP BY Date, REMOTE_ADDR, Catalogue_ID
                            ", ARRAY_A);
    foreach ((array) $res as $row) {
        $new[$row['Catalogue_ID']][$row['Date']][$row['Hour']]['Hosts'] += $row['New_Hosts'];
    }

    // "Новые посетители"
    $res = $db->get_results("SELECT DATE_FORMAT(Created,'%Y-%m-%d') AS Date,
                                   MIN(DATE_FORMAT(Created,'%H')) AS Hour,
                                   COUNT(DISTINCT REMOTE_ADDR,HTTP_USER_AGENT,Cookie_ID) as New_Visitors,
                                   Catalogue_ID
                              FROM Stats_Log
                             WHERE $qry_created
                             GROUP BY Date,REMOTE_ADDR,HTTP_USER_AGENT,Cookie_ID,Catalogue_ID
                              	", ARRAY_A);
    foreach ((array) $res as $row) {
        $new[$row['Catalogue_ID']][$row['Date']][$row['Hour']]['Visitors'] += $row['New_Visitors'];
    }

    foreach ((array) $new as $catalogue_id => $cat_data) {
        foreach ($cat_data as $date => $date_data) {
            foreach ($date_data as $hour => $hour_data) {
                $db->query("UPDATE Stats_Attendance
                           SET NewVisitors='$hour_data[Visitors]',
                               NewHosts='$hour_data[Hosts]'
                         WHERE Catalogue_ID='$catalogue_id'
                           AND Date='$date'
                           AND Hour='$hour'");
            }
        }
    }
}

/**
 * Создание отчета Popularity
 */
function Stats_CreateReportPopularity($date_from, $date_to) {
    if (!Stats_PrepareTable("Popularity", $date_from, $date_to))
        return;

    global $db;

    $qry_created = "Created BETWEEN '$date_from 00:00:00' AND '$date_to 23:59:59'";

    $db->query("INSERT INTO Stats_Popularity (
						Date,
						Link,
						Hosts,
						Hits,
						Visitors,
						Catalogue_ID
					)
				SELECT
					DATE_FORMAT(Created,'%Y-%m-%d') AS Date,
					CONCAT(HTTP_HOST,IF(RIGHT(CONCAT(IF(LOCATE('?',REQUEST_URI),'',REQUEST_URI),SUBSTRING(REQUEST_URI,1,LOCATE('?',REQUEST_URI)-1)),1)='/',LEFT(CONCAT(IF(LOCATE('?',REQUEST_URI),'',REQUEST_URI),SUBSTRING(REQUEST_URI,1,LOCATE('?',REQUEST_URI)-1)),LENGTH(CONCAT(IF(LOCATE('?',REQUEST_URI),'',REQUEST_URI),SUBSTRING(REQUEST_URI,1,LOCATE('?',REQUEST_URI)-1)))-1),CONCAT(IF(LOCATE('?',REQUEST_URI),'',REQUEST_URI),SUBSTRING(REQUEST_URI,1,LOCATE('?',REQUEST_URI)-1)))) AS Link,
					COUNT(DISTINCT REMOTE_ADDR),
					COUNT(*),
					COUNT(DISTINCT REMOTE_ADDR,HTTP_USER_AGENT,Cookie_ID),
					Catalogue_ID
				FROM
					Stats_Log
				WHERE $qry_created
				GROUP BY
					Date,Link,Catalogue_ID
				");
}

function Stats_CreateReportReferer($date_from, $date_to) {
    if (!Stats_PrepareTable("Referer", $date_from, $date_to))
        return;

    global $db;

    $date_from = $db->escape($date_from);
    $date_to = $db->escape($date_to);

    $qry_created = "Created BETWEEN '$date_from 00:00:00' AND '$date_to 23:59:59'";

    $db->query("INSERT INTO
					Stats_Referer (
						Date,
						Referer,
						Hosts,
						Hits,
						Visitors,
						Catalogue_ID
					)
				SELECT
					DATE_FORMAT(Created,'%Y-%m-%d') AS Date,
					IF(LEFT(HTTP_REFERER,7)='http://',HTTP_REFERER,'') AS Referer,
					COUNT(DISTINCT REMOTE_ADDR),
					COUNT(*),
					COUNT(DISTINCT REMOTE_ADDR,HTTP_USER_AGENT,Cookie_ID),
					Catalogue_ID
				FROM
					Stats_Log
				WHERE $qry_created
				GROUP BY
					Date,Referer,Catalogue_ID
				");
}

function Stats_CreateReportIP($date_from, $date_to) {

    if (!Stats_PrepareTable("IP", $date_from, $date_to))
        return;

    global $db;

    $qry_created = "Created BETWEEN '$date_from 00:00:00' AND '$date_to 23:59:59'";

    $db->query("INSERT INTO
					Stats_IP (
						Date,
						IP,
						Hits,
						Catalogue_ID
					)
				SELECT
					DATE_FORMAT(Created,'%Y-%m-%d') AS Date,
					REMOTE_ADDR AS IP,
					COUNT(*),
					Catalogue_ID
				FROM
					Stats_Log
				WHERE $qry_created
				GROUP BY
					Date,IP,Catalogue_ID
				");
}

function Stats_CreateReportBrowser($date_from, $date_to) {
    if (!Stats_PrepareTable("Browser", $date_from, $date_to))
        return;

    global $db;

    $date_from = $db->escape($date_from);
    $date_to = $db->escape($date_to);

    $qry_created = "Created BETWEEN '$date_from 00:00:00' AND '$date_to 23:59:59'";

    $db->query("INSERT INTO
						Stats_Browser (
							Date,
							Browser,
							Visitors,
							Catalogue_ID
						)
					SELECT
						DATE_FORMAT(Created,'%Y-%m-%d') AS Date,
						Browser,
						COUNT(DISTINCT REMOTE_ADDR,HTTP_USER_AGENT,Cookie_ID),
						Catalogue_ID
					FROM
						Stats_Log
					WHERE $qry_created
					GROUP BY
						Date,Browser,Catalogue_ID
					");
}

function Stats_CreateReportGeo($date_from, $date_to) {
    if (!Stats_PrepareTable("Geo", $date_from, $date_to)) {
        return;
    }

    global $db;
    $db->query("INSERT INTO Stats_Geo (Date, Country, Hosts, Hits, Visitors, Catalogue_ID)
               SELECT DATE_FORMAT(Created, '%Y-%m-%d') as Date,
                      Country,
                      COUNT(DISTINCT REMOTE_ADDR) AS Hosts,
                      COUNT(*) AS Hits,
                      COUNT(DISTINCT REMOTE_ADDR,HTTP_USER_AGENT,Cookie_ID) AS Visitors,
                      Catalogue_ID
                 FROM Stats_Log
                WHERE Country != ''
                  AND Created BETWEEN '$date_from 00:00:00' AND '$date_to 23:59:59'
                GROUP BY Date, Country, Catalogue_ID");
}

function Stats_CreateReportOS($date_from, $date_to) {
    if (!Stats_PrepareTable("OS", $date_from, $date_to))
        return;

    global $db;

    $date_from = $db->escape($date_from);
    $date_to = $db->escape($date_to);

    $qry_created = "Created BETWEEN '$date_from 00:00:00' AND '$date_to 23:59:59'";

    $s = "INSERT INTO
					Stats_OS (
						Date,
						OS,
						Visitors,
						Catalogue_ID
					)
				SELECT
					DATE_FORMAT(Created,'%Y-%m-%d') AS Date,
					OS,
					COUNT(DISTINCT REMOTE_ADDR,HTTP_USER_AGENT,Cookie_ID),
					Catalogue_ID
				FROM
					Stats_Log
				WHERE $qry_created
				GROUP BY
					Date,OS,Catalogue_ID
				";
    $db->query($s);
}
?>
