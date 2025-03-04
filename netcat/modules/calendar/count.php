<?php

$NETCAT_FOLDER = join(strstr(__FILE__, "/") ? "/" : "\\", array_slice(preg_split("/[\/\\\]+/", __FILE__), 0, -4)).( strstr(__FILE__, "/") ? "/" : "\\" );
require_once($NETCAT_FOLDER."vars.inc.php");

if (!isset($NC_CHARSET) || !$NC_CHARSET) $NC_CHARSET = "windows-1251";

# формируем заголовок в правильной кодировке
header("Content-type: text/plain; charset=$NC_CHARSET");
// header("Accept-Language", "ru, en");
// header("Accept-Charset", "$CHARSET; q=1");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);

if ((int) $_GET['calendar']) {

    # этот параметр не даст выдавать страницу с запросом авторизации
    define("NC_AUTH_IN_PROGRESS", 1);

    # подключаем ядро
    $NETCAT_FOLDER = join(strstr(__FILE__, "/") ? "/" : "\\", array_slice(preg_split("/[\/\\\]+/", __FILE__), 0, -4)).( strstr(__FILE__, "/") ? "/" : "\\" );
    include_once ($NETCAT_FOLDER."vars.inc.php");
    $passed_thru_404 = true;
    require ($INCLUDE_FOLDER."index.php");

    # нужные переменные
    # идентификатор компонента в разделе должен передаваться с именем отличным от $cc,
    # например $needcc, иначе вместе с календарём система выдаст экран авторизации,
    # т.к. тут подключается вся система!
    $needcc = $_GET['needcc'];
    $needcc = explode(',', $needcc);
    if (!is_array($needcc)) $needcc = array($needcc);
    $needcc = array_map('intval', $needcc);
    $theme = (int) $_GET['theme'];
    $datefield = $db->escape($_GET['datefield']);
    $filled = (int) $_GET['filled'];
    $day = (int) $_GET['day'];
    $month = (int) $_GET['month'];
    $year = (int) $_GET['year'];
    $popup = (int) $_GET['popup'];
    $queryDate = preg_match("/\d{4}(-\d{2})?(-\d{2})?/s", $_GET['querydate']) ? $_GET['querydate'] : "";
    $cc_ignore = (int) $_GET['cc_ignore'];

    $field_day = htmlspecialchars($nc_core->input->fetch_get_post('field_day'), ENT_QUOTES);
    $field_month = htmlspecialchars($nc_core->input->fetch_get_post('field_month'), ENT_QUOTES);
    $field_year = htmlspecialchars($nc_core->input->fetch_get_post('field_year'), ENT_QUOTES);
    $admin_mode = (int)$nc_core->input->fetch_get_post('admin_mode');

    # генерируем календарь
    $calendar = nc_set_calendar($theme).nc_show_calendar($theme, $needcc, $year."-".$month."-".$day, $datefield, (int) $filled, $queryDate, $popup, array($field_day, $field_month, $field_year), $cc_ignore, $admin_mode);
    echo ($calendar ? $calendar : "");
}