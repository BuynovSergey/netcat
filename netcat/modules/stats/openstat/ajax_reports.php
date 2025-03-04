<?php

/* $Id: ajax_reports.php 4290 2011-02-23 15:32:35Z denis $ */

$NETCAT_FOLDER = join(strstr(__FILE__, "/") ? "/" : "\\", array_slice(preg_split("/[\/\\\]+/", __FILE__), 0, -5)).( strstr(__FILE__, "/") ? "/" : "\\" );
include_once $NETCAT_FOLDER . 'vars.inc.php';
require $ADMIN_FOLDER . 'function.inc.php';
require_once nc_module_folder('stats') . 'openstat/function.inc.php';
require_once nc_module_folder('stats') . 'openstat/openstat_core_class.php';

function show_err($err_code) {

    if ($err_code == 401) {
        return NETCAT_MODULE_STATS_OPENSTAT_ERROR_COUNTER_AUTH_ERROR;  // 401 ошибка - неверный логин-пароль
    } elseif ($err_code == 404) {
        return NETCAT_MODULE_STATS_OPENSTAT_ERROR_INVALID_OPENSTAT_COUNTER_ID;  // 404 ошибка - нет такого счетчика
    } else {
        return NETCAT_MODULE_STATS_OPENSTAT_ERROR_COUNTER_REPORT." ".$err_code;  // ошибка, выводим ее код
    }
}

function echo_2_reports($caption, $table1, $table2) {
    $fieldset = new nc_admin_fieldset($caption, 'off');

    $fieldset->add("<div style='width:48%;float:left'>
  ".$table1['caption']." <i>(".NETCAT_MODULE_STATS_OPENSTAT_TOP_5.")</i><br />
  <table border=0 cellpadding=0 cellspacing=0 width=100%>
  <tr><td bgcolor=cccccc>\n
  <table border=0 cellpadding=4 cellspacing=1 width=100%>
    <tr>
      <td bgcolor=eeeeee width=3%><font size=-2><b>".$table1['col_captions'][0]."</font></td>
      <td bgcolor=eeeeee width=3%><font size=-2><b>".$table1['col_captions'][1]."</font></td>
    </tr>");
    if (isset($table1['val'])) {
        foreach ($table1['val'] as $val) {
            $fieldset->add("    <tr>
      <td bgcolor='white'><font size='-2'>".$val[0]."</font></td>
      <td bgcolor='white'><font size='-2'>".$val[1]."</font></td>
    </tr>");
        }
    } else {
        $fieldset->add("    <tr>
      <td bgcolor='white' align='center' colspan='2'><font size='-2'><i>(".NETCAT_MODULE_STATS_OPENSTAT_NO_DATA.")</i></font></td>
    </tr>");
    }
    $fieldset->add("  </table></td></tr></table>");
    if (isset($table1['msg'])) {
        $fieldset->add("<i>".$table1['msg']."</i>");
    }
    $fieldset->add("</div>");


    $fieldset->add("<div style='width:48%;float:right'>
  ".$table2['caption']." <i>(".NETCAT_MODULE_STATS_OPENSTAT_TOP_5.")</i><br />
  <table border=0 cellpadding=0 cellspacing=0 width=100%>
  <tr><td bgcolor=cccccc>\n
  <table border=0 cellpadding=4 cellspacing=1 width=100%>
    <tr>
      <td bgcolor=eeeeee width=3%><font size=-2><b>".$table2['col_captions'][0]."</font></td>
      <td bgcolor=eeeeee width=3%><font size=-2><b>".$table2['col_captions'][1]."</font></td>
    </tr>");
    if (isset($table2['val'])) {
        foreach ($table2['val'] as $val) {
            $fieldset->add("    <tr>
      <td bgcolor='white'><font size='-2'>".$val[0]."</font></td>
      <td bgcolor='white'><font size='-2'>".$val[1]."</font></td>
    </tr>");
        }
    } else {
        $fieldset->add("    <tr>
      <td bgcolor='white' align='center' colspan='2'><font size='-2'><i>(".NETCAT_MODULE_STATS_OPENSTAT_NO_DATA.")</i></font></td>
    </tr>");
    }
    $fieldset->add("  </table></td></tr></table>");
    if (isset($table2['msg'])) {
        $fieldset->add("<i>".$table2['msg']."</i>");
    }
    $fieldset->add("</div>");

    echo $fieldset->result();
    unset($fieldset);
}

if (!$perm->isSupervisor()) {
    exit;
}

$lang = $nc_core->lang->detect_lang(1);

if (!isset($counter_id)) {
    echo "<big>Error: no counter ID specified</big>";
    exit;
}

if (!isset($from) || !isset($to)) {
    echo "<big>Error: no date limits specified</big>";
    exit;
}

$time_offset = intval($time_offset);
$from = intval($from);
$to = intval($to);

if (($from < 946674000) || ($to > 4102434000)) { // год между 2000 и 2100
    echo "<big>Error: invalid date limits specified</big>";
    exit;
}

$counter_id = intval($counter_id);

$counter = $db->get_row("SELECT * FROM `Stats_Openstat_Counters` WHERE `Counter_Id` = '".$counter_id."'");
if (!$counter) {
    echo "<big>Error: invalid counter ID</big>";
    exit;
}

if ($counter->User_Counter_Code) {  // пользовательский счетчик
    echo "it's user-counter";
    exit;
}

// График "посетители"
echo "<img style='display:block; margin:0 auto;' alt='' src='attendance_diagram.php?counter_id=".$counter->Openstat_Counter_Id."&start_date=".$from."&end_date=".$to."&time_offset=".$time_offset."&level_of_detailing=".$period."&width=".$width."'>";
echo "<br />";
echo "<div style='clear: both; width: 100%;'>\n";

$from = $from + $time_offset;
$to = $to + $time_offset;

$openstat = new nc_Openstat_core_class($nc_core->get_settings('Openstat_Login', 'stats'), $nc_core->get_settings('Openstat_Password', 'stats'));


//        ----------------- сводная статистика ---------------------

$fieldset = new nc_admin_fieldset(NETCAT_MODULE_STATS_OPENSTAT_SUMMARY_STATS, 'on');
$fieldset->add("<table border='0' width='100%'>");

// Просмотры - визиты - посетители
$fieldset->add("<tr><td>");
$columns = array("0%0Dvisitors_sum", "0%0Dsessions_sum", "0%0Dpageviews_sum");
$attendance_report = $openstat->get_counter_report($counter->Openstat_Counter_Id, "Attendance", $from, $to, $period, $columns, 1, $lang);
if (!is_array($attendance_report)) {
    $fieldset->add(show_err($attendance_report));
} else {
    $sessions_num = $attendance_report['sum'][1];
    $fieldset->add(NETCAT_MODULE_STATS_OPENSTAT_SUM_PAGEVIEWS.": <big>".$attendance_report['sum'][2]."</big></td>");
    $fieldset->add("<td>".NETCAT_MODULE_STATS_OPENSTAT_SUM_SESSIONS.": <big>".$attendance_report['sum'][1]."</big></td>");
    $fieldset->add("<td>".NETCAT_MODULE_STATS_OPENSTAT_SUM_VISITORS.": <big>".$attendance_report['sum'][0]."</big>");
}
$fieldset->add("</td></tr>");
unset($attendance_report);

// показатель отказов и в среднем просмотров на визит
$fieldset->add("<tr><td>");
$columns = array("0%0Dsessions_sum", "0%0Dsessions_sum_verticalpercent");
$pageviewsonvisit_report = $openstat->get_counter_report($counter->Openstat_Counter_Id, "PageviewsOnVisit", $from, $to, "day", $columns, 0, $lang);
if (!is_array($pageviewsonvisit_report)) {
    $fieldset->add(show_err($pageviewsonvisit_report));
} else {
    $rep_val = $pageviewsonvisit_report['item'][0]['c'][1] + 0;
    $fieldset->add(NETCAT_MODULE_STATS_OPENSTAT_FAILURES_NUM.": <big>".($rep_val ? $rep_val."</big>%" : "-</big>")."</td>\n");

    if ($sessions_num) {
        $average = $pageviewsonvisit_report['item'][0]['c'][0] + // i'm loving it
                $pageviewsonvisit_report['item'][1]['c'][0] * 2 +
                $pageviewsonvisit_report['item'][2]['c'][0] * 3 +
                $pageviewsonvisit_report['item'][3]['c'][0] * 4 +
                $pageviewsonvisit_report['item'][4]['c'][0] * 5 +
                $pageviewsonvisit_report['item'][5]['c'][0] * 6 +
                $pageviewsonvisit_report['item'][6]['c'][0] * 7 +
                $pageviewsonvisit_report['item'][7]['c'][0] * 8 +
                $pageviewsonvisit_report['item'][8]['c'][0] * 9 +
                $pageviewsonvisit_report['item'][9]['c'][0] * 12 +
                $pageviewsonvisit_report['item'][10]['c'][0] * 17 +
                $pageviewsonvisit_report['item'][11]['c'][0] * 24.5 +
                $pageviewsonvisit_report['item'][12]['c'][0] * 39.5 +
                $pageviewsonvisit_report['item'][13]['c'][0] * 59.5 +
                $pageviewsonvisit_report['item'][14]['c'][0] * 84.5 +
                $pageviewsonvisit_report['item'][15]['c'][0] * 149.5 +
                $pageviewsonvisit_report['item'][16]['c'][0] * 249.5 +
                $pageviewsonvisit_report['item'][17]['c'][0] * 399.5 +
                $pageviewsonvisit_report['item'][18]['c'][0] * 750 +
                $pageviewsonvisit_report['item'][19]['c'][0] * 1500;
        $res = $average / $sessions_num;
    }
    $fieldset->add("<td>".NETCAT_MODULE_STATS_OPENSTAT_AVERAGE_PAGEVIEWS_PER_SESSION.": <big>".($res ? ($res > 1 ? round($res) : round($res, 2)) : "-")."</big></td>\n");
}
unset($pageviewsonvisit_report);

// средняя длительность визитов
$columns = array("0%0Dsessions_sum", "0%0Dsessions_sum_verticalpercent");
$timeonsite_report = $openstat->get_counter_report($counter->Openstat_Counter_Id, "TimeOnSite", $from, $to, "day", $columns, 0, $lang);
if (!is_array($timeonsite_report)) {
    $fieldset->add(show_err($timeonsite_report));
} else {
    if ($sessions_num) {
        $average = $timeonsite_report['item'][0]['c'][0] * 5 + // i'm loving it
                $timeonsite_report['item'][1]['c'][0] * 20.5 +
                $timeonsite_report['item'][2]['c'][0] * 45.5 +
                $timeonsite_report['item'][3]['c'][0] * 2 * 60 +
                $timeonsite_report['item'][4]['c'][0] * 6.5 * 60 +
                $timeonsite_report['item'][5]['c'][0] * 20 * 60 +
                $timeonsite_report['item'][6]['c'][0] * 60 * 60;
        $res = $average / $sessions_num;
    }
    $fieldset->add("<td>".NETCAT_MODULE_STATS_OPENSTAT_AVERAGE_TIME_SITE.": <big>".($res ? ($res > 60 ? round($res / 60)."</big> ".NETCAT_MODULE_STATS_OPENSTAT_MIN : round($res)."</big> ".NETCAT_MODULE_STATS_OPENSTAT_SEC) : "-</big>")."</td>\n");
}

$fieldset->add("</td></tr>");
$fieldset->add("</table>");
echo $fieldset->result();
unset($fieldset);



//  --------------------------- Другие отчеты ------------------------------
// "Страницы" - попул. страницы и точки входа
$columns = array("0%0Dpageviews_sum");
$report = $openstat->get_counter_report($counter->Openstat_Counter_Id, "TopPages", $from, $to, "day", $columns, 5, $lang, "level=2");
$table1['caption'] = (!$nc_core->NC_UNICODE ? $nc_core->utf8->utf2win($report["title"]) : $report["title"] );
if (isset($report['meta']['notes'][0]['msg'])) {
    $table1['msg'] = $report['meta']['notes'][0]['msg'];
}
$table1['col_captions'][0] = NETCAT_MODULE_STATS_OPENSTAT_PAGE;
$table1['col_captions'][1] = NETCAT_MODULE_STATS_OPENSTAT_PAGEVIEWS;
if ($report && count($report['item'])) {
    foreach ($report['item'] as $item) {
        $table1['val'][] = array("<a target='_blank' href='".$item["href"]."'>".(!$nc_core->NC_UNICODE ? $nc_core->utf8->utf2win($item["t"]) : $item["t"])."</a>", $item["c"][0]);
    }
}

$columns = array("0%0Dtransitions_sum");
$report = $openstat->get_counter_report($counter->Openstat_Counter_Id, "LandingPages", $from, $to, "day", $columns, 5, $lang);
$table2['caption'] = (!$nc_core->NC_UNICODE ? $nc_core->utf8->utf2win($report["title"]) : $report["title"] );
if (isset($report['meta']['notes'][0]['msg'])) {
    $table2['msg'] = $report['meta']['notes'][0]['msg'];
}
$table2['col_captions'][0] = NETCAT_MODULE_STATS_OPENSTAT_PAGE;
$table2['col_captions'][1] = NETCAT_MODULE_STATS_OPENSTAT_TRANSITIONS;
if ($report && count($report['item'])) {
    foreach ($report['item'] as $item) {
        $table2['val'][] = array("<a target='_blank' href='".$item["href"]."'>".(!$nc_core->NC_UNICODE ? $nc_core->utf8->utf2win($item["t"]) : $item["t"])."</a>", $item["c"][0]);
    }
}

echo_2_reports(NETCAT_MODULE_STATS_OPENSTAT_PAGES, $table1, $table2);
unset($table1);
unset($table2);


// "Источники трафика" - источники трафика и поисковые запросы
$columns = array("0%0Dtransitions_sum");
$report = $openstat->get_counter_report($counter->Openstat_Counter_Id, "Sources", $from, $to, "day", $columns, 5, $lang);
$table1['caption'] = (!$nc_core->NC_UNICODE ? $nc_core->utf8->utf2win($report["title"]) : $report["title"] );
if (isset($report['meta']['notes'][0]['msg'])) {
    $table1['msg'] = $report['meta']['notes'][0]['msg'];
}
$table1['col_captions'][0] = NETCAT_MODULE_STATS_OPENSTAT_TYPE;
$table1['col_captions'][1] = NETCAT_MODULE_STATS_OPENSTAT_TRANSITIONS;
if ($report && count($report['item'])) {
    foreach ($report['item'] as $item) {
        $table1['val'][] = array((!$nc_core->NC_UNICODE ? $nc_core->utf8->utf2win($item["r"][0]) : $item["r"][0]), $item["c"][0]);
    }
}

$columns = array("0%0Dtransitions_sum");
$report = $openstat->get_counter_report($counter->Openstat_Counter_Id, "SearchTerms", $from, $to, "day", $columns, 5, $lang, "level=2");
$table2['caption'] = (!$nc_core->NC_UNICODE ? $nc_core->utf8->utf2win($report["title"]) : $report["title"] );
if (isset($report['meta']['notes'][0]['msg'])) {
    $table2['msg'] = $report['meta']['notes'][0]['msg'];
}
$table2['col_captions'][0] = NETCAT_MODULE_STATS_OPENSTAT_PHRASE;
$table2['col_captions'][1] = NETCAT_MODULE_STATS_OPENSTAT_TRANSITIONS;
if ($report && count($report['item'])) {
    foreach ($report['item'] as $item) {
        $table2['val'][] = array((!$nc_core->NC_UNICODE ? $nc_core->utf8->utf2win($item["r"][0]) : $item["r"][0]), $item["c"][0]);
    }
}

echo_2_reports(NETCAT_MODULE_STATS_OPENSTAT_TRAFIC_SOURCES, $table1, $table2);
unset($table1);
unset($table2);

// "География" - страны и регионы
$columns = array("0%0Dvisitors_sum");
$report = $openstat->get_counter_report($counter->Openstat_Counter_Id, "Geo", $from, $to, "day", $columns, 5, $lang);
$table1['caption'] = NETCAT_MODULE_STATS_OPENSTAT_TRAFIC_GEOGRAPY_BY_COUNTRIES;
if (isset($report['meta']['notes'][0]['msg'])) {
    $table1['msg'] = $report['meta']['notes'][0]['msg'];
}
$table1['col_captions'][0] = NETCAT_MODULE_STATS_OPENSTAT_COUNTRY;
$table1['col_captions'][1] = NETCAT_MODULE_STATS_OPENSTAT_VISITORS;
if ($report && count($report['item'])) {
    foreach ($report['item'] as $item) {
        $table1['val'][] = array((!$nc_core->NC_UNICODE ? $nc_core->utf8->utf2win($item["r"][0]) : $item["r"][0]), $item["c"][0]);
    }
}

$columns = array("0%0Dvisitors_sum");
$report = $openstat->get_counter_report($counter->Openstat_Counter_Id, "Geo", $from, $to, "day", $columns, 5, $lang, "level=2");
$table2['caption'] = NETCAT_MODULE_STATS_OPENSTAT_TRAFIC_GEOGRAPY_BY_REGIONS;
if (isset($report['meta']['notes'][0]['msg'])) {
    $table2['msg'] = $report['meta']['notes'][0]['msg'];
}
$table2['col_captions'][0] = NETCAT_MODULE_STATS_OPENSTAT_REGION;
$table2['col_captions'][1] = NETCAT_MODULE_STATS_OPENSTAT_VISITORS;
if ($report && count($report['item'])) {
    foreach ($report['item'] as $item) {
        $table2['val'][] = array((!$nc_core->NC_UNICODE ? $nc_core->utf8->utf2win($item["r"][0]) : $item["r"][0]), $item["c"][0]);
    }
}

echo_2_reports(NETCAT_MODULE_STATS_OPENSTAT_TRAFIC_GEOGRAPY, $table1, $table2);
unset($table1);
unset($table2);


echo "</div>
  <br />";

echo "<div style='clear: both; width: 100%;'>\n";
echo "<center><big>".NETCAT_MODULE_STATS_OPENSTAT_SEE_FULL_STATS_ON_OPENSTAT." <a href='redirector.php?url=".urlencode("/counter/".$counter->Openstat_Counter_Id."/report/summary/")."' target='_blank'>Openstat</a></big></center>";
echo "</div>";