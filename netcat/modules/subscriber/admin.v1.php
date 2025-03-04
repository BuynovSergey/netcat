<?php

$item_id = 3;
$main_section = 'settings';

$NETCAT_FOLDER = join(strstr(__FILE__, "/") ? "/" : "\\", array_slice(preg_split("/[\/\\\]+/", __FILE__), 0, -4)).( strstr(__FILE__, "/") ? "/" : "\\" );
require_once $NETCAT_FOLDER . 'vars.inc.php';
require_once nc_module_folder('subscriber') . 'admin.inc.php';
require_once $ADMIN_FOLDER . 'function.inc.php';

require_once $ADMIN_FOLDER . 'modules/ui.php';
$UI_CONFIG = new ui_config_module('subscriber');

$Delimeter = ' &gt ';
$Title1 = "<a href=\"" . $nc_core->catalogue->get_url_by_id($nc_core->catalogue->id()) . $ADMIN_PATH . "modules/\">" . NETCAT_MODULES . "</a>{$Delimeter}" . NETCAT_MODULE_SUBSCRIBES;
$Title2 = NETCAT_MODULE_SUBSCRIBES;
$Title5 = GetLoginByID($UserID);
$Title6 = NETCAT_MODULE_SUBSCRIBE_ADM_PASSCHANGE;
$Title7 = GetLoginByID($UserID)." (".NETCAT_MODULE_SUBSCRIBE_ADM_ACCESSRIGHTS.")";
$Title8 = "<a href=\"" . $nc_core->catalogue->get_url_by_id($nc_core->catalogue->id()) . $ADMIN_PATH . "user/?phase=8&UserID={$UserID}\"> (" . NETCAT_MODULE_SUBSCRIBE_ADM_ACCESSRIGHTS . ")</a>";
$Title9 = NETCAT_MODULE_SUBSCRIBE_ADM_SECSITE;
$Title10 = NETCAT_MODULE_SUBSCRIBE_ADM_SECSEL;
$Title13 = NETCAT_MODULE_SUBSCRIBE_ADM_ADDING;



if (!isset($phase)) $phase = 1;

switch ($phase) {

    case 1:
        # покажем форму поиска подписчиков
        BeginHtml($Title2, $Title1, "http://{$DOC_DOMAIN}/settings/modules/subscriber/");
        $perm->ExitIfNotAccess(array(NC_PERM_MODULE, 'subscriber'), 0, 0, 0, 0);
        SearchSubscriberForm ();
        SearchSubscriberResult ();

        break;

    case 2:
        # покажем результаты поиска подписчиков
        BeginHtml($Title2, $Title1, "http://{$DOC_DOMAIN}/settings/modules/subscriber/");
        $perm->ExitIfNotAccess(array(NC_PERM_MODULE, 'subscriber'), 0, 0, 0, 0);
        SearchSubscriberResult ();

        break;

    case 3:
        # выключим/включим подписку
        BeginHtml($Title2, $Title1, "http://{$DOC_DOMAIN}/settings/modules/subscriber/");
        $perm->ExitIfNotAccess(array(NC_PERM_MODULE, 'subscriber'), 0, 0, 0, 1);
        ToggleSubscriber($SubscriberID);
        SearchSubscriberResult ();

        break;

    case 4:
        # удалим подписчиков
        BeginHtml($Title2, $Title2, "http://{$DOC_DOMAIN}/settings/modules/subscriber/");
        $perm->ExitIfNotAccess(array(NC_PERM_MODULE, 'subscriber'), 0, 0, 0, 1);
        DeleteSubscribers ();
        SearchSubscriberResult ();

        break;
}

EndHtml();