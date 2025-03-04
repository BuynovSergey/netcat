<?php

$NETCAT_FOLDER = join(strstr(__FILE__, "/") ? "/" : "\\", array_slice(preg_split("/[\/\\\]+/", __FILE__), 0, -4)).( strstr(__FILE__, "/") ? "/" : "\\" );
require_once $NETCAT_FOLDER. 'vars.inc.php';
require_once $ADMIN_FOLDER. 'function.inc.php';
require_once nc_module_folder('cache') . 'function.inc.php';
require_once nc_module_folder('cache') . 'nc_cache_admin.class.php';

// load modules env
if (!isset($MODULE_VARS)) {
    $MODULE_VARS = $nc_core->modules->get_module_vars(); //LoadModuleEnv();
}
// UI config
require_once $ADMIN_FOLDER . 'modules/ui.php';
// default
if (!$page) {
    $page = 'settings';
}
require_once nc_module_folder('cache') . 'ui_config.php';

$Title1 = NETCAT_MODULES;
$Title2 = NETCAT_MODULE_CACHE;


// default phase
if (!isset($phase)) $phase = 1;

if (in_array($phase, array(2))) {
    if (!$nc_core->token->verify()) {
        BeginHtml($Title2, $Title2, "");
        nc_print_status(NETCAT_TOKEN_INVALID, 'error');
        EndHtml();
        exit;
    }
}

// UI functional
$UI_CONFIG = new ui_config_module_cache('admin', $page);

// admin object
try {
    $nc_cache_admin = new nc_cache_admin();
} catch (Exception $e) {
    BeginHtml($Title2, $Title2, "http://".$DOC_DOMAIN."/settings/modules/cache/");
    // got error
    nc_print_status($e->getMessage(), "error");
    EndHtml();
    exit;
}

switch ($phase) {
    // step 1: show settings form
    case 1:
        BeginHtml($Title2, $Title1, "http://".$DOC_DOMAIN."/settings/modules/cache/");
        // check permission
        $perm->ExitIfNotAccess(array(NC_PERM_MODULE, 'cache'), 0, 0, 0, 0);
        // show settings form
        $nc_cache_admin->settings();
        break;

    // step 2: save settings
    case 2:
        BeginHtml($Title2, $Title1, "http://".$DOC_DOMAIN."/settings/modules/cache/");
        // check permission
        $perm->ExitIfNotAccess(array(NC_PERM_MODULE, 'cache'), 0, 0, 0, 1);
        // save settings
        $nc_cache_admin->settingsSave();
        // successfully saved
        nc_print_status(NETCAT_MODULE_CACHE_ADMIN_SAVE_OK, "ok");
        // show settings form
        $nc_cache_admin->settings();
        break;

    // step 3: show information
    case 3:
        BeginHtml($Title2, $Title1, "http://".$DOC_DOMAIN."/settings/modules/cache/");
        // check permission
        $perm->ExitIfNotAccess(array(NC_PERM_MODULE, 'cache'), 0, 0, 0, 0);
        // show information
        $nc_cache_admin->info();
        break;

    // step 4: update audit info
    case 4:
        // design header
        BeginHtml($Title2, $Title1, "http://".$DOC_DOMAIN."/settings/modules/cache/");
        // check permission
        $perm->ExitIfNotAccess(array(NC_PERM_MODULE, 'cache'), 0, 0, 0, 1);
        $nc_cache_admin->clearInfoDrop();
        // ok flag
        nc_print_status(NETCAT_MODULE_CACHE_ADMIN_MAININFO_DROP_CLEAR_OK, "ok");
        $nc_cache_admin->info();
        break;

    // step 5: show audit info
    case 5:
        BeginHtml($Title2, $Title1, "http://".$DOC_DOMAIN."/settings/modules/cache/");
        // check permission
        $perm->ExitIfNotAccess(array(NC_PERM_MODULE, 'cache'), 0, 0, 0, 0);
        // show audit info
        $nc_cache_admin->auditInfo();
        break;

    // step 6: audit information to clear table
    case 6:
        BeginHtml($Title2, $Title1, "http://".$DOC_DOMAIN."/settings/modules/cache/");
        // check permission
        $perm->ExitIfNotAccess(array(NC_PERM_MODULE, 'cache'), 0, 0, 0, 1);
        // audit information to clear table
        $nc_cache_admin->auditInfoToClear();
        // successfully saved
        nc_print_status(NETCAT_MODULE_CACHE_ADMIN_AUDIT_SAVE_CLEAR_OK, "ok");
        // show audit info
        $nc_cache_admin->auditInfo();
        break;

    // step 7: clear audit information
    case 7:
        BeginHtml($Title2, $Title1, "http://".$DOC_DOMAIN."/settings/modules/cache/");
        // check permission
        $perm->ExitIfNotAccess(array(NC_PERM_MODULE, 'cache'), 0, 0, 0, 1);
        // clear audit information
        $nc_cache_admin->auditInfoClear();
        // successfully saved
        nc_print_status(NETCAT_MODULE_CACHE_ADMIN_AUDIT_DROP_OK, "ok");
        // show audit info
        $nc_cache_admin->auditInfo();
        break;
}

EndHtml();
?>