<?php

$NETCAT_FOLDER = join(strstr(__FILE__, "/") ? "/" : "\\", array_slice(preg_split("/[\/\\\]+/", __FILE__), 0, -4)).( strstr(__FILE__, "/") ? "/" : "\\" );
require_once $NETCAT_FOLDER . 'vars.inc.php';
require_once $ADMIN_FOLDER . 'function.inc.php';

// load modules env
if (!isset($MODULE_VARS)) {
    $MODULE_VARS = $nc_core->modules->get_module_vars();
}

if ($MODULE_VARS['subscriber']['VERSION'] && $MODULE_VARS['subscriber']['VERSION'] == 1) {
    require_once nc_module_folder('subscriber') . 'admin.v1.php';
    exit();
}

require_once nc_module_folder('subscriber') . 'nc_subscriber_admin.class.php';


// UI config
require_once $ADMIN_FOLDER . 'modules/ui.php';
require_once nc_module_folder('subscriber') . 'ui_config.php';

$Title1 = NETCAT_MODULES;
$Title2 = NETCAT_MODULE_SUBSCRIBES;


// default
if (!isset($phase) || !$phase) $phase = 1;
$mailer_id = intval($nc_core->input->fetch_get_post('mailer_id'));


try {
    $nc_subscriber_admin = new nc_subscriber_admin();


    switch ($phase) {
        // step 1: show settings form
        case 1:
            BeginHtml($Title2, $Title1, "http://".$DOC_DOMAIN."/settings/modules/subscriber/");
            $UI_CONFIG = new ui_config_module_subscriber('admin', 'mailer');
            // check permission
            $perm->ExitIfNotAccess(array(NC_PERM_MODULE, 'subscriber'), 0, 0, 0, 0);
            // show settings form
            $nc_subscriber_admin->mailer_list();
            break;

        case 11:
            BeginHtml($Title2, $Title1, "http://".$DOC_DOMAIN."/settings/modules/subscriber/");
            $UI_CONFIG = new ui_config_module_subscriber('admin', 'mailer');
            // check permission
            $perm->ExitIfNotAccess(array(NC_PERM_MODULE, 'subscriber'), 0, 0, 0, 1);
            $nc_subscriber_admin->mailer_list_update();

            if ($action_type == 'delete') {
                $nc_subscriber_admin->mailer_confirm_deletion();
            } else {
                $nc_subscriber_admin->mailer_list();
            }
            break;

        // форма добавления/редактирования рассылки
        case 2:
            BeginHtml($Title2, $Title1, "http://".$DOC_DOMAIN."/settings/modules/subscriber/");
            $UI_CONFIG = new ui_config_module_subscriber('admin', 'mailer', (!$mailer_id ? 'add' : 'edit('.$mailer_id.')'));
            // check permission
            $perm->ExitIfNotAccess(array(NC_PERM_MODULE, 'subscriber'), 0, 0, 0, 0);
            // show settings form
            $nc_subscriber_admin->mailer_form($mailer_id);
            break;

        case 21:
            BeginHtml($Title2, $Title1, "http://".$DOC_DOMAIN."/settings/modules/subscriber/");
            $UI_CONFIG = new ui_config_module_subscriber('admin', 'mailer', (!$mailer_id ? 'add' : 'edit('.$mailer_id.')'));
            // check permission
            $perm->ExitIfNotAccess(array(NC_PERM_MODULE, 'subscriber'), 0, 0, 0, 1);
            // show settings form
            try {
                $nc_subscriber_admin->mailer_save($mailer_id);
            } catch (Exception $e) {
                nc_print_status($e->getMessage(), "error");
                $nc_subscriber_admin->mailer_form($mailer_id);
                break;
            }
            nc_print_status($mailer_id ? NETCAT_MODULE_SUBSCRIBER_MAILER_SUCCESS_EDIT : NETCAT_MODULE_SUBSCRIBER_MAILER_SUCCESS_ADD, 'ok');
            $nc_subscriber_admin->mailer_list();

            break;


        case 3:
            BeginHtml($Title2, $Title1, "http://".$DOC_DOMAIN."/settings/modules/subscriber/");
            $UI_CONFIG = new ui_config_module_subscriber('admin', 'stats');
            $perm->ExitIfNotAccess(array(NC_PERM_MODULE, 'subscriber'), 0, 0, 0, 0);
            $nc_subscriber_admin->stats();
            break;

        case 4:
            BeginHtml($Title2, $Title1, "http://".$DOC_DOMAIN."/settings/modules/subscriber/");
            $UI_CONFIG = new ui_config_module_subscriber('admin', 'stats', 'mailer('.$mailer_id.')');
            $perm->ExitIfNotAccess(array(NC_PERM_MODULE, 'subscriber'), 0, 0, 0, 0);
            $nc_subscriber_admin->stats_mailer($mailer_id);
            break;

        case 41:
            $db->query("DELETE FROM `Subscriber_Log` WHERE `Mailer_ID` = '".intval($mailer_id)."' ");
            BeginHtml($Title2, $Title1, "http://".$DOC_DOMAIN."/settings/modules/subscriber/");
            $UI_CONFIG = new ui_config_module_subscriber('admin', 'stats');
            nc_print_status(NETCAT_MODULE_SUBSCRIBER_STATS_DROP, 'ok');
            $perm->ExitIfNotAccess(array(NC_PERM_MODULE, 'subscriber'), 0, 0, 0, 0);
            $nc_subscriber_admin->stats();
            break;

        case 5:
            BeginHtml($Title2, $Title1, "http://".$DOC_DOMAIN."/settings/modules/subscriber/");
            $UI_CONFIG = new ui_config_module_subscriber('admin', 'settings');
            $perm->ExitIfNotSupervisor();
            $nc_subscriber_admin->settings();
            break;

        case 51:
            BeginHtml($Title2, $Title1, "http://".$DOC_DOMAIN."/settings/modules/subscriber/");
            $UI_CONFIG = new ui_config_module_subscriber('admin', 'settings');
            $perm->ExitIfNotSupervisor();
            $nc_subscriber_admin->settings_save();
            nc_print_status(NETCAT_MODULE_SUBSCRIBER_SETTINGS_OK, 'ok');
            $nc_subscriber_admin->settings();
            break;

        case 6:
            BeginHtml($Title2, $Title1, "http://".$DOC_DOMAIN."/settings/modules/subscriber/");
            $UI_CONFIG = new ui_config_module_subscriber('admin', 'mailer', 'users('.$mailer_id.')');
            $perm->ExitIfNotAccess(array(NC_PERM_MODULE, 'subscriber'), 0, 0, 0, 0);
            $nc_subscriber_admin->sbs_show($mailer_id);
            break;

        case 61:
            BeginHtml($Title2, $Title1, "http://".$DOC_DOMAIN."/settings/modules/subscriber/");
            $UI_CONFIG = new ui_config_module_subscriber('admin', 'mailer', 'users('.$mailer_id.')');
            $perm->ExitIfNotAccess(array(NC_PERM_MODULE, 'subscriber'), 0, 0, 0, 1);
            $nc_subscriber_admin->sbs_update();
            $nc_subscriber_admin->sbs_show($mailer_id);
            break;

        // единоразовая рассылка по базе
        case 7:
            BeginHtml($Title2, $Title1, "http://".$DOC_DOMAIN."/settings/modules/subscriber/");
            $perm->ExitIfNotAccess(array(NC_PERM_MODULE, 'subscriber'), 0, 0, 0, 0);
            $UI_CONFIG = new ui_config_module_subscriber('admin', 'once');
            $nc_subscriber_admin->once_show();
            break;
        // тестовая рассылка
        case 71:
            BeginHtml($Title2, $Title1, "http://".$DOC_DOMAIN."/settings/modules/subscriber/");
            $perm->ExitIfNotAccess(array(NC_PERM_MODULE, 'subscriber'), 0, 0, 0, 1);
            $UI_CONFIG = new ui_config_module_subscriber('admin', 'once');
            $nc_subscriber_admin->once_test_send();
            $nc_subscriber_admin->once_show();
            break;
        // рассылка по базе
        case 72:
            BeginHtml($Title2, $Title1, "http://".$DOC_DOMAIN."/settings/modules/subscriber/");
            $perm->ExitIfNotAccess(array(NC_PERM_MODULE, 'subscriber'), 0, 0, 0, 1);
            $UI_CONFIG = new ui_config_module_subscriber('admin', 'once');
            $count = $nc_subscriber_admin->once_send();
            if (!$count) {
                nc_print_status(NETCAT_MODULE_SUBSCRIBER_USER_NOT_FOUND, 'info');
            } else {
                nc_print_status(sprintf(NETCAT_MODULE_SUBSCRIBER_MAIL_SEND_TO_USER, $count), 'ok');
            }
            $nc_subscriber_admin->once_show();
            break;
    }

    EndHtml();
} catch (Exception $e) {
    BeginHtml($Title2, $Title2, "http://".$DOC_DOMAIN."/settings/modules/subscriber/");
    // got error
    nc_print_status($e->getMessage(), "error");
    EndHtml();
    exit;
}
?>