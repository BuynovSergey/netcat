<?php

$module_keyword = "auth";
$main_section = "settings";
$item_id = 3;

$NETCAT_FOLDER = join(strstr(__FILE__, "/") ? "/" : "\\", array_slice(preg_split("/[\/\\\]+/", __FILE__), 0, -4)).( strstr(__FILE__, "/") ? "/" : "\\" );
include_once ($NETCAT_FOLDER."vars.inc.php");
require_once ($ADMIN_FOLDER."function.inc.php");
require_once ($ADMIN_FOLDER."modules/ui.php");

$Title1 = NETCAT_MODULES_TUNING;
$Title2 = NETCAT_MODULES;

$UI_CONFIG = new ui_config_tool(TOOLS_MODULES_LIST, TOOLS_MODULES_LIST, 'i_modules_big.gif', 'module.list');

if (!($perm->isSupervisor() || $perm->isGuest())) {
    BeginHtml($Title2, $Title1, "http://".$DOC_DOMAIN."/settings/modules/");
    nc_print_status(NETCAT_MODERATION_ERROR_NORIGHTS, 'error');
    EndHtml ();
    exit;
}

// проверка, установлен этот модуль или нет
$res = $db->get_row("SELECT * FROM `Module` WHERE `Keyword` = '".$db->escape($module_keyword)."' AND `Installed` = 0", ARRAY_A);
// вывод сообщения об успешном окончании установки
if (!$db->num_rows) {
    BeginHtml($Title2, $Title1, "http://".$DOC_DOMAIN."/settings/modules/");
    nc_print_status(NETCAT_MODULE_INSTALLCOMPLIED, 'ok');
    EndHtml();
    exit;
} else {
    $module_data = $res;
}

$lang = $nc_core->lang->detect_lang(1);
$MODULE_VARS = $nc_core->modules->load_env($lang);

$ClassID = $db->get_var("SELECT `Class_ID` FROM `Class` WHERE `System_Table_ID` = 3 AND `ClassTemplate` = 0");

if (!isset($phase)) $phase = 2;

switch ($phase) {
    case 1:
        BeginHtml($Title2, $Title1, "http://".$DOC_DOMAIN."/settings/modules/");
        break;

    case 2:
        BeginHtml($Title2, $Title1, "http://".$DOC_DOMAIN."/settings/modules/");
        SelectParentSub();
        break;

    case 3:
        BeginHtml($Title2, $Title1, "http://".$DOC_DOMAIN."/settings/modules/");

        $ProfileID = InsertSub(NETCAT_MODULE_AUTH_SETUP_PROFILE, "profile", "", 2, 0, 0, 0, 0, $ClassID, $SubdivisionID, $CatalogueID, "index", 1);
        InsertSub(NETCAT_MODULE_AUTH_SETUP_REGISTRATION, "registration", "", 3, 1, 0, 0, 0, $ClassID, $ProfileID, $CatalogueID, "add", 0);
        InsertSub(NETCAT_MODULE_AUTH_SETUP_PASSWORD_RECOVERY, "password_recovery", nc_module_path('auth') . "password_recovery.php", 0, 0, 0, 0, 0, $ClassID, $ProfileID, $CatalogueID, "index", 0);
        $ModifySub = InsertSub(NETCAT_MODULE_AUTH_SETUP_MODIFY, "modify", "", 2, 0, 2, 0, 0, $ClassID, $ProfileID, $CatalogueID, "index", 1);
        InsertSub(NETCAT_MODULE_AUTH_SETUP_PASSWORD, "password", nc_module_path('auth') . "password_change.php", 2, 0, 0, 0, 0, $ClassID, $ModifySub, $CatalogueID, "index", 1);
        InsertSub(NETCAT_MODULE_AUTH_SETUP_PM, "pm", "", 2, 0, 0, 0, 0, $nc_core->get_settings('pm_class_id', 'auth'), $ProfileID, $CatalogueID, "index", 1);


        UpdateHiddenURL("/", 0, $CatalogueID);

        //$module_data["Parameters"] = UpdateParameters($module_data["Parameters"], "USER_MODIFY_SUB", $ModifySub);
        //$db->query("UPDATE `Module` SET `Parameters` = '".$module_data["Parameters"]."' WHERE `Module_ID` = '".intval($module_data["Module_ID"])."'");
        $nc_core->set_settings('modify_sub', $ModifySub, 'auth');
        $ProfileCC = $db->get_var("SELECT Sub_Class_ID FROM Sub_Class WHERE Subdivision_ID = '".intval($ProfileID)."'");
        $nc_core->set_settings('user_list_cc', $ProfileCC, 'auth');
        // пометим как установленный
        $db->query("UPDATE `Module` SET `Installed` = 1 WHERE `Module_ID` = '".intval($module_data["Module_ID"])."'");
        echo "<br/><br/>";
        nc_print_status(NETCAT_MODULE_INSTALLCOMPLIED, 'ok');

        break;
}

EndHtml ();
