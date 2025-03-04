<?php

$NETCAT_FOLDER = join(strstr(__FILE__, "/") ? "/" : "\\", array_slice(preg_split("/[\/\\\]+/", __FILE__), 0, -4)).( strstr(__FILE__, "/") ? "/" : "\\" );
require_once $NETCAT_FOLDER . 'vars.inc.php';

$module_keyword = "logging";
require_once $ADMIN_FOLDER . 'function.inc.php';
require_once nc_module_folder('logging') . 'nc_logging_admin.class.php';

// load modules env
if (!isset($MODULE_VARS)) $MODULE_VARS = $nc_core->modules->get_module_vars();

// UI config
require_once ($ADMIN_FOLDER."modules/ui.php");
// UI functional
$UI_CONFIG = new ui_config_module($module_keyword);

$Title1 = NETCAT_MODULES;
$Title2 = NETCAT_MODULE_LOGGING;

// default phase
if (!isset($phase)) $phase = 1;

// admin object
try {
    $nc_logging_admin = new nc_logging_admin();
} catch (Exception $e) {
    // header
    BeginHtml($Title2, $Title2, "http://".$DOC_DOMAIN."/settings/modules/logging/");
    // got error
    nc_print_status($e->getMessage(), "error");
    // footer
    EndHtml();
    // stop
    exit;
}

switch ($phase) {
    // step 1: show logging data
    case 1:
        BeginHtml($Title2, $Title1, "http://".$DOC_DOMAIN."/settings/modules/".$module_keyword."/");
        // check permission
        $perm->ExitIfNotAccess(array(NC_PERM_MODULE, 'logging'), 0, 0, 0, 0);
        // show logging data
        $nc_logging_admin->show_logging_data(isset($curPos) ? $curPos : 0);
        break;

    // step 2: show clear dialog
    case 2:
        BeginHtml($Title2, $Title1, "http://".$DOC_DOMAIN."/settings/modules/".$module_keyword."/");
        // check permission
        $perm->ExitIfNotAccess(array(NC_PERM_MODULE, 'logging'), 0, 0, 0, 0);
        // show dialog
        $nc_logging_admin->clear_logging_dialog();
        break;

    // step 3: clear logging data
    case 3:
        BeginHtml($Title2, $Title1, "http://".$DOC_DOMAIN."/settings/modules/".$module_keyword."/");
        // check permission
        $perm->ExitIfNotAccess(array(NC_PERM_MODULE, 'logging'), 0, 0, 0, 0);
        // clear logging data
        $nc_logging_admin->clear_logging_data();
        // show logging data
        $nc_logging_admin->show_logging_data();
        break;
}

EndHtml();
