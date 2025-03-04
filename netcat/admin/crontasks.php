<?php

/* $Id: crontasks.php 7302 2012-06-25 21:12:35Z alive $ */

require_once("function.inc.php");
require_once("crontasks.inc.php");

$Delimeter = " &gt ";
$main_section = "settings";
$item_id = 2;
$Title2 = TOOLS_CRON;
$Title3 = "<a href=\"".$ADMIN_PATH."crontasks.php\">".TOOLS_CRON."</a>";
$Title4 = CONTROL_SETTINGSFILE_TITLE_ADD;
$Title5 = CONTROL_CONTENT_CATALOUGE_FUNCS_SHOWMENU_EDIT;

$UI_CONFIG = new ui_config_tool(CRONTAB_TAB_LIST, CRONTAB_TAB_LIST, 'i_settings_big.gif', 'cron.settings');

if (isset($phase)) {
    if (in_array($phase, array(2, 3, 5)) && !$nc_core->token->verify()) {
        BeginHtml($Title2, $Title2, "http://".$DOC_DOMAIN."/settings/sql/");
        nc_print_status(NETCAT_TOKEN_INVALID, 'error');
        EndHtml();
        exit;
    }

    switch ($phase) {
        case 1:
            # форма для добавления
            BeginHtml($Title4, $Title3.$Delimeter.$Title4, "http://".$DOC_DOMAIN."/settings/crontab/");
            $perm->ExitIfNotAccess(NC_PERM_CRON, 0, 0, 0, 0);
            $UI_CONFIG = new ui_config_tool(CRONTAB_TAB_LIST, CRONTAB_TAB_ADD, 'i_settings_big.gif', 'cron.add');
            CronForm(0);
            break;

        case 2:
            # собственно добавление
            BeginHtml($Title2, $Title2, "http://".$DOC_DOMAIN."/settings/crontasks/");
            $perm->ExitIfNotAccess(NC_PERM_CRON, 0, 0, 0, 1);
            if (CronCompleted(0, $Cron_Minutes, $Cron_Hours, $Cron_Days, $Cron_Script_URL))
                    CrontasksList ();


            break;

        case 3:
            # удаление
            BeginHtml($Title2, $Title2, "http://".$DOC_DOMAIN."/settings/crontab/");
            $perm->ExitIfNotAccess(NC_PERM_CRON, 0, 0, 0, 1);
            foreach ($_POST as $key => $val) {
                if (strcmp(substr($key, 0, 6), 'Delete') == 0) {
                    DeleteCron($val);
                }
            }

            CrontasksList ();

            break;

        case 4:
            # форма изменения
            BeginHtml($Title5, $Title3.$Delimeter.$Title5, "http://".$DOC_DOMAIN."/settings/crontab/");
            $perm->ExitIfNotAccess(NC_PERM_CRON, 0, 0, 0, 0);
            $UI_CONFIG = new ui_config_tool(CRONTAB_TAB_LIST, CRONTAB_TAB_EDIT, 'i_settings_big.gif', 'cron.edit');
            CronForm($CronID);

            break;

        case 5:
            # собственно изменение
            BeginHtml($Title2, $Title2, "http://".$DOC_DOMAIN."/settings/crontab/");
            $perm->ExitIfNotAccess(NC_PERM_CRON, 0, 0, 0, 1);
            CronCompleted($CronID, $Cron_Minutes, $Cron_Hours, $Cron_Days, $Cron_Script_URL);
            CrontasksList();


            break;
    }
} else {
    BeginHtml($Title2, $Title2, "http://".$DOC_DOMAIN."/settings/crontab/");
    $perm->ExitIfNotAccess(NC_PERM_CRON, 0, 0, 0, 0);
    CrontasksList ();
}


EndHtml ();