<?php

require('function.inc.php');
require('settings.inc.php');

$Delimeter = " &gt ";
$main_section = "settings";
$item_id = 5;
$Title1 = "";
$Title2 = SYSTEMSETTINGS_TAB_LIST;
$Title3 = "<a href=" . $ADMIN_PATH . "settings.php>" . $Title2 . "</a>";
$Title4 = CONTROL_SETTINGSFILE_TITLE_ADD;
$Title5 = CONTROL_SETTINGSFILE_TITLE_EDIT;

$UI_CONFIG = new ui_config_tool(SYSTEMSETTINGS_TAB_LIST, SYSTEMSETTINGS_TAB_LIST, 'i_settings_big.gif', 'system.settings');


if (!isset($phase)) $phase = 0;

if (!$perm->isSupervisor()) {
    BeginHtml($Title2, $Title2, "http://" . $DOC_DOMAIN . "/settings/base/");
    nc_print_status(NETCAT_MODERATION_ERROR_NORIGHTS, 'error');
    EndHtml();
    exit;
}

if (in_array($phase, array(2))) {
    if (!$nc_core->token->verify()) {
        BeginHtml($Title2, $Title2, "http://" . $DOC_DOMAIN . "/settings/base/");
        nc_print_status(NETCAT_TOKEN_INVALID, 'error');
        EndHtml();
        exit;
    }
}

try {
    switch ($phase) {
        case 0:
            // показ системных настроек
            BeginHtml($Title2, $Title2, "http://" . $DOC_DOMAIN . "/settings/base/");
            ShowSettings();
            break;

        case 1:
            // форма изменения
            BeginHtml($Title5, $Title3 . $Delimeter . $Title5, "http://" . $DOC_DOMAIN . "/settings/base/change/");
            $UI_CONFIG = new ui_config_tool(SYSTEMSETTINGS_TAB_LIST, SYSTEMSETTINGS_TAB_ADD, 'i_settings_big.gif', 'system.edit');
            SettingsForm();
            break;

        case 2:
            // собственно изменение
            BeginHtml($Title2, $Title2, "http://" . $DOC_DOMAIN . "/settings/base/");
            if (SettingsCompleted()) {
                nc_print_status(SYSTEMSETTINGS_SAVE_OK, 'ok');
            } else {
                nc_print_status(SYSTEMSETTINGS_SAVE_ERROR, 'error');
            }
            SettingsForm();
            break;
    }
} catch (nc_Exception_DB_Error $e) {
    nc_print_status(sprintf(NETCAT_ERROR_SQL, $e->query(), $e->error()), 'error');
} catch (Exception $e) {
    nc_print_status($e->getMessage(), 'error');
}

EndHtml();
