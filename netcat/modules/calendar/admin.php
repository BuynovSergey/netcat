<?php

$main_section = "settings";
$NETCAT_FOLDER = join(strstr(__FILE__, "/") ? "/" : "\\", array_slice(preg_split("/[\/\\\]+/", __FILE__), 0, -4)).( strstr(__FILE__, "/") ? "/" : "\\" );
require_once $NETCAT_FOLDER . 'vars.inc.php';
require_once $ADMIN_FOLDER . 'function.inc.php';
require nc_module_folder('calendar') . 'admin.inc.php';

# UI
require_once $ADMIN_FOLDER . 'modules/ui.php';
$UI_CONFIG = new ui_config_module('calendar');

if ((int)$nc_core->input->fetch_post('ID')) {
    $calendarSettingBackLink = nc_module_path('calendar') . 'admin.php?CalendarTheme=' . (int)$nc_core->input->fetch_post('ID');
}

# начальное оформление
$Title1 = NETCAT_MODULES;
$Title2 = NETCAT_MODULE_CALENDAR;


if (!isset($phase)) $phase = 1;

if (in_array($phase, array(2, 4))) {
    if (!$nc_core->token->verify()) {
        BeginHtml($Title2, $Title2, "http://".$DOC_DOMAIN."/settings/sql/");
        nc_print_status(NETCAT_TOKEN_INVALID, 'error');
        EndHtml();
        exit;
    }
}

$perm->ExitIfNotAccess(array(NC_PERM_MODULE, 'calendar'), NC_PERM_MODULE_ALL, 0, 0, $phase == 1 ? 0 : 1);

switch ($phase) {

    case 1:
        // выводим настройки календаря
        BeginHtml($Title2, $Title1, "http://".$DOC_DOMAIN."/settings/modules/calendar/");
        CalendarShowSettings();

        break;

    case 2:
        // действия после нажатия кнопок сохранить или default
        BeginHtml($Title2, $Title1, "http://".$DOC_DOMAIN."/settings/modules/calendar/");

        // названия дней недели
        for ($i = 1; $i <= 7; $i++) {
            $DayName[] = $_POST["DayName$i"];
        }
        $DaysName = join(",", $DayName);

        $ThemeNameCorrect = false;
        if (isset($_POST['ThemeName']) && nc_preg_match("/^[0-9A-Za-z".NETCAT_RUALPHABET."\s_-]+$/is", $_POST['ThemeName']))
                $ThemeNameCorrect = true;
        if (isset($_POST['ThemeName']) && $ThemeNameCorrect) {
            SaveFunctional($nc_core->input->fetch_get_post());
            echo ($_POST['ID'] == "new" ? NETCAT_MODULE_CALENDAR_THEME_CREATED : NETCAT_MODULE_CALENDAR_THEME_UPDATED)."<br><br><a href='".$calendarSettingBackLink."'>".NETCAT_MODULE_CALENDAR_ADMIN_GOBACK."</a>";
        } elseif (!$ThemeNameCorrect) {
            echo NETCAT_MODULE_CALENDAR_THEME_NAME_NOTICE."<br><br><a href='".$calendarSettingBackLink."'>".NETCAT_MODULE_CALENDAR_ADMIN_GOBACK."</a>";
        } else {
            echo NETCAT_MODULE_CALENDAR_THEME_NAME_EMPTY."<br><br><a href='".$calendarSettingBackLink."'>".NETCAT_MODULE_CALENDAR_ADMIN_GOBACK."</a>";
        }

        break;

    case 3:
        // действия после нажатия кнопок сохранить или default
        BeginHtml($Title2, $Title1, "http://".$DOC_DOMAIN."/settings/modules/calendar/");

        echo "<form method='post' action='admin.php' style='padding:0; margin:0;'>
				<fieldset>
				<legend>
					<b><font color='gray'>".NETCAT_MODULE_CALENDAR_THEME_NAME_DELETE." \"".htmlspecialchars($_POST['ThemeName'])."\"?</font></b>
				</legend>
					<div style='margin:20px 0; text-align:center'>
					<input type='submit' name='YesDelete' title='".NETCAT_MODULE_CALENDAR_YES."' value='".NETCAT_MODULE_CALENDAR_YES."' style='width:70px; margin-right:20px'>
					<input type='button' name='NoDelete' title='".NETCAT_MODULE_CALENDAR_NO."' value='".NETCAT_MODULE_CALENDAR_NO."' style='width:70px' onClick='location.href=\"".$calendarSettingBackLink."\"'>
					</div>
				</fieldset>
				<input type='hidden' name='phase' value='4'>
				<input type='hidden' name='ID' value='" . htmlspecialchars($_POST['ID']) . "'>
        ".$nc_core->token->get_input()."
				</form>";

        break;

    case 4:
        // восстановление исходных настроек
        BeginHtml($Title2, $Title1, "http://".$DOC_DOMAIN."/settings/modules/calendar/");
        if ($_POST['YesDelete']) {
            DeleteFunctional($_POST['ID']);
            echo NETCAT_MODULE_CALENDAR_THEME_NAME_DELETED."<br><br><a href='".$calendarSettingBackLink."'>".NETCAT_MODULE_CALENDAR_ADMIN_GOBACK."</a>";
        }

        break;
}

EndHtml();