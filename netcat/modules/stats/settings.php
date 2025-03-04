<?php

/* $Id: settings.php 4290 2011-02-23 15:32:35Z denis $ */

$NETCAT_FOLDER = join(strstr(__FILE__, "/") ? "/" : "\\", array_slice(preg_split("/[\/\\\]+/", __FILE__), 0, -4)) . (strstr(__FILE__, "/") ? "/" : "\\");
include_once $NETCAT_FOLDER . 'vars.inc.php';
require_once $ADMIN_FOLDER . 'function.inc.php';

require_once $ADMIN_FOLDER . 'modules/ui.php';
require_once nc_module_folder('stats') . "ui_config.php";

$UI_CONFIG = new ui_config_module_stats('settings', '', '');
$input = nc_core('input');

$Delimeter = " &gt ";
$Title1 = NETCAT_MODULE_STATS;
$Title2 = "<a href=" . $ADMIN_PATH . "modules/>" . NETCAT_MODULES . "</a>";

// check permission
$perm->ExitIfNotAccess(array(NC_PERM_MODULE, 'stats'), 0, 0, 0, 1);

//LoadModuleEnv();
$MODULE_VARS = $nc_core->modules->get_module_vars();

BeginHtml($Title1, $Title2 . $Delimeter . $Title1, "http://" . $DOC_DOMAIN . "/settings/modules/stats/tools");


// check permission
$perm->ExitIfNotAccess(array(NC_PERM_MODULE, 'stats'), 0, 0, 0, 1);

$openstat_enable = $nc_core->get_settings('Openstat_Enabled', 'stats');
$nc_stat_enable = $nc_core->get_settings('NC_Stat_Enabled', 'stats');

if (isset($DoAction)) {
    $openstat_enable = $input->fetch_post('openstat_enable');
    $nc_stat_enable = $input->fetch_post('nc_stat_enable');

    $nc_core->set_settings('Openstat_Enabled', (int)$openstat_enable, 'stats');
    $nc_core->set_settings('NC_Stat_Enabled', (int)$nc_stat_enable, 'stats');

    nc_print_status(NETCAT_MODULE_STATS_CHANGES_SAVED, "ok");
}

echo "<form name='ToolsForm' id='ToolsForm' method='post' action='settings.php'>\n" .
    "<input type='hidden' name='DoAction' value='1'>\n" .
    nc_admin_checkbox(NETCAT_MODULE_STATS_OPENSTAT_ENABLE, 'openstat_enable', $openstat_enable) .
    nc_admin_checkbox(NETCAT_MODULE_STATS_ENABLE, 'nc_stat_enable', $nc_stat_enable) .
    "</form>";

$UI_CONFIG->actionButtons[] = array("id" => "submit",
    "caption" => NETCAT_MODULE_STATS_SAVE_CHANGES,
    "action" => "mainView.submitIframeForm('ToolsForm')");
EndHtml();