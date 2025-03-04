<?php

$NETCAT_FOLDER = join(strstr(__FILE__, "/") ? "/" : "\\", array_slice(preg_split("/[\/\\\]+/", __FILE__), 0, -4)) . ( strstr(__FILE__, "/") ? "/" : "\\" );
require_once $NETCAT_FOLDER . 'vars.inc.php';
require_once $ADMIN_FOLDER . 'function.inc.php';
require_once nc_module_folder('comments') . 'nc_comments_admin.class.php';
require_once nc_module_folder('comments') . 'nc_comments.class.php';

/** @var Permission $perm */

// load modules env
if (!isset($MODULE_VARS)) {
    $MODULE_VARS = $nc_core->modules->get_module_vars(); //LoadModuleEnv();
}

if (!isset($phase)) {
    $phase = 0; 
}

$phase = (int)$phase;

if (!$phase) {
    if ($perm->isAccess(array(NC_PERM_MODULE, 'comments'), 'comment_manage')) {
        $phase = 1;
    } else if ($perm->isAccess(array(NC_PERM_MODULE, 'comments'), 'subscription_manage')) {
        $phase = 10;
    } else {
        $perm->ExitIfNotSupervisor();
    }
}

$Title1 = NETCAT_MODULES;
$Title2 = NETCAT_MODULE_COMMENTS;

// UI config
require_once $ADMIN_FOLDER . 'modules/ui.php';
require_once nc_module_folder('comments') . 'ui_config.php';


$AJAX_SAVER = ( !$perm->isGuest() && ( $phase == 2 || $phase == 21 ));

// admin object
try {
    $nc_comments_admin = new nc_comments_admin();
    //$nc_comments = new nc_comments();
} catch (Exception $e) {
    BeginHtml($Title2, $Title2, "http://" . $DOC_DOMAIN . "/settings/modules/comments/");
    // got error
    nc_print_status($e->getMessage(), "error");
    EndHtml();
    exit;
}

ob_start();
do {
    $loop = false;

    switch ($phase) {
        // step 1: comments list
        case 1:
            BeginHtml($Title2, $Title1, "http://" . $DOC_DOMAIN . "/settings/modules/comments/");
            $UI_CONFIG = new ui_config_module_comments('admin', 'list');
            $perm->ExitIfNotAccess(array(NC_PERM_MODULE, 'comments'), 'comment_manage', 0, 0, 0);
            $nc_comments_admin->comments_list();
            break;


        case 11:
            # включить/выключить комментарий
            BeginHtml($Title2, $Title1, "http://" . $DOC_DOMAIN . "/settings/modules/comments/");
            $UI_CONFIG = new ui_config_module_comments('admin', 'list');
            $perm->ExitIfNotAccess(array(NC_PERM_MODULE, 'comments'), 'comment_manage', 0, 0, 0);
            //получаем номер инфоблока и вызываем конструктор для комментариев
            $sub_comment = $nc_core->db->get_var("SELECT `Sub_Class_ID` FROM `Comments_Text` WHERE `id` = ".(int) $comment);
            $nc_comments = new nc_comments($sub_comment);
            //$nc_comments = new nc_comments($comment); неправильный вызов конструктора!!!
            $nc_comments->changeChecked($comment, $action);
            unset($comment);
            //show comments list
            if ($action == 'Check')
                nc_print_status(NETCAT_MODULE_COMMENTS_ADMIN_CHECK_OK, "ok");
            else
                nc_print_status(NETCAT_MODULE_COMMENTS_ADMIN_UNCHECK_OK, "ok");
            $nc_comments_admin->comments_list();
            break;


        case 12:
            # включение комментариев
            BeginHtml($Title2, $Title1, "http://" . $DOC_DOMAIN . "/settings/modules/comments/");
            $UI_CONFIG = new ui_config_module_comments('admin', 'list');
            $perm->ExitIfNotAccess(array(NC_PERM_MODULE, 'comments'), 'comment_manage', 0, 0, 0);
            $comments_num = 0;
            foreach ($_POST as $key => $val) {
                if (strpos($key, 'comment') === 0) {
                    $id = substr($key, 7);
                    $nc_comments = new nc_comments($val);
                    $nc_comments->changeChecked($id, 'Check');
                    $comments_num++;
                }
            }
            if ($comments_num) {
                nc_print_status(NETCAT_MODULE_COMMENTS_ADMIN_CHECK_COMMENTS_OK, "ok");
            } else {
                nc_print_status(NETCAT_MODULE_COMMENTS_ADMIN_NO_SELECTED_COMMENTS, "error");
            }
            //show comments list
            $nc_comments_admin->comments_list();
            break;


        case 121:
            # выключение комментариев
            BeginHtml($Title2, $Title1, "http://" . $DOC_DOMAIN . "/settings/modules/comments/");
            $UI_CONFIG = new ui_config_module_comments('admin', 'list');
            $perm->ExitIfNotAccess(array(NC_PERM_MODULE, 'comments'), 'comment_manage', 0, 0, 0);
            $comments_num = 0;
            foreach ($_POST as $key => $val) {
                if (strpos($key, 'comment') === 0) {
                    $id = substr($key, 7);
                    $nc_comments = new nc_comments($val);
                    $nc_comments->changeChecked($id, 'Uncheck');
                    $comments_num++;
                }
            }
            if ($comments_num) {
                nc_print_status(NETCAT_MODULE_COMMENTS_ADMIN_UNCHECK_COMMENTS_OK, "ok");
            } else {
                nc_print_status(NETCAT_MODULE_COMMENTS_ADMIN_NO_SELECTED_COMMENTS, "error");
            }
            //show comments list
            $nc_comments_admin->comments_list();
            break;


        case 13:
            # подтверждение удаления всех комментариев
            BeginHtml($Title2, $Title1, "http://" . $DOC_DOMAIN . "/settings/modules/comments/");
            $UI_CONFIG = new ui_config_module_comments('admin', 'list');
            $perm->ExitIfNotAccess(array(NC_PERM_MODULE, 'comments'), 'comment_manage', 0, 0, 0);
            if (confirm_deleteComment('All')) {
                $UI_CONFIG->actionButtons[] = array(
                    "id" => "confirm",
                    "caption" => NETCAT_MODULE_COMMENTS_ADMIN_CONFIRM_DEL_OK,
                    "action" => "mainView.submitIframeForm()",
                    "red_border" => true,
                );
                $UI_CONFIG->actionButtons[] = array("id" => "back",
                        "align" => "left",
                        "caption" => NETCAT_MODULE_COMMENTS_ADMIN_DEL_BACK,
                        "location" => "module.comments");
            }
            break;


        case 131:
            # подтверждение удаления выбранных комментариев
            BeginHtml($Title2, $Title1, "http://" . $DOC_DOMAIN . "/settings/modules/comments/");
            $UI_CONFIG = new ui_config_module_comments('admin', 'list');
            $perm->ExitIfNotAccess(array(NC_PERM_MODULE, 'comments'), 'comment_manage', 0, 0, 0);
            if (confirm_deleteComment('Selected')) {
                $UI_CONFIG->actionButtons[] = array(
                    "id" => "confirm",
                    "caption" => NETCAT_MODULE_COMMENTS_ADMIN_CONFIRM_DEL_OK,
                    "action" => "mainView.submitIframeForm()",
                    "red_border" => true,
                );
                $UI_CONFIG->actionButtons[] = array("id" => "back",
                        "align" => "left",
                        "caption" => NETCAT_MODULE_COMMENTS_ADMIN_DEL_BACK,
                        "location" => "module.comments");
            } else {
                ob_clean();
                $loop = true;
                $phase = 1;
            }
            break;


        case 14:
            # удаление выбранных комментариев
            BeginHtml($Title2, $Title1, "http://" . $DOC_DOMAIN . "/settings/modules/comments/");
            $UI_CONFIG = new ui_config_module_comments('admin', 'list');
            $perm->ExitIfNotAccess(array(NC_PERM_MODULE, 'comments'), 'comment_manage', 0, 0, 0);
            $comments_num = 0;
            foreach ($_POST as $key => $val) {
                if (strpos($key, 'comment') === 0) {
                    $id = (int)substr($key, 7);
                    $nc_comments = new nc_comments($val);
                    $nc_comments->deleteComment($id);
                    $comments_num++;
                }
            }
            if ($comments_num) {
                nc_print_status(NETCAT_MODULE_COMMENTS_ADMIN_DEL_OK, "ok");
            } else {
                nc_print_status(NETCAT_MODULE_COMMENTS_ADMIN_NO_SELECTED_COMMENTS, "error");
            }
            //show comments list
            $nc_comments_admin->comments_list();
            break;


        case 141:
            # удаление всех комментариев
            BeginHtml($Title2, $Title1, "http://" . $DOC_DOMAIN . "/settings/modules/comments/");
            $UI_CONFIG = new ui_config_module_comments('admin', 'list');
            $perm->ExitIfNotAccess(array(NC_PERM_MODULE, 'comments'),'comment_manage', 0, 0, 0);
            if (delete_allComments())
                nc_print_status(NETCAT_MODULE_COMMENTS_ADMIN_DEL_ALL_OK, "ok");
            else
                nc_print_status(NETCAT_MODULE_COMMENTS_ADMIN_NO_SELECTED_COMMENTS, "error");
            //show comments list
            $nc_comments_admin->comments_list();
            break;

        case 15:
            # форма изменения комментария
            BeginHtml($Title2, $Title1, "http://" . $DOC_DOMAIN . "/settings/modules/comments/");
            $UI_CONFIG = new ui_config_module_comments('admin', 'list', 'edit(' . intval($comment) . ')');
            $perm->ExitIfNotAccess(array(NC_PERM_MODULE, 'comments'), 'comment_manage', 0, 0, 0);
            $nc_comments = new nc_comments($message_cc);
            $nc_comments->editCommentForm($comment);
            $UI_CONFIG->actionButtons[] = array(
                    "id" => "save",
                    "caption" => NETCAT_MODULE_COMMENTS_ADMIN_MAINSETTINGS_SAVE_BUTTON,
                    "action" => "mainView.submitIframeForm('adminForm')"
            );
            $UI_CONFIG->actionButtons[] = array(
                "id" => "save",
                "caption" => NETCAT_MODULE_COMMENTS_ADD_FORM_DELETE_BUTTON,
                "action" => "mainView.submitIframeForm('deleteForm')",
                "align" => "left",
                "red_border" => true,
            );

            break;

        case 151:
            # сохранение изменения комментария
            BeginHtml($Title2, $Title1, "http://" . $DOC_DOMAIN . "/settings/modules/comments/");
            $UI_CONFIG = new ui_config_module_comments('admin', 'list', 'edit(' . intval($comment) . ')');
            $perm->ExitIfNotAccess(array(NC_PERM_MODULE, 'comments'), 'comment_manage', 0, 0, 0);
            $nc_comments = new nc_comments($message_cc);
            $nc_comments->editComment($nc_core->input->fetch_get_post());
            $nc_comments->editCommentForm($comment);
            $UI_CONFIG->actionButtons[] = array(
                    "id" => "save",
                    "caption" => NETCAT_MODULE_COMMENTS_ADMIN_MAINSETTINGS_SAVE_BUTTON,
                    "action" => "mainView.submitIframeForm()");
            break;


        // step 2: comment's template form
        case 2:
            BeginHtml($Title2, $Title1, "http://" . $DOC_DOMAIN . "/settings/modules/comments/");
            $UI_CONFIG = new ui_config_module_comments('settings', 'template');
            $perm->ExitIfNotSupervisor();
            // show settings form
            $nc_comments_admin->template();
            break;


        // step 2: save template
        case 21:
            BeginHtml($Title2, $Title1, "http://" . $DOC_DOMAIN . "/settings/modules/comments/");
            $UI_CONFIG = new ui_config_module_comments('settings', 'template');
            // check permission
            $perm->ExitIfNotSupervisor();
            // save settings
            $nc_comments_admin->template_save();
            // successfully saved
            nc_print_status(NETCAT_MODULE_COMMENTS_ADMIN_TEMPLATE_SETTINGS_SAVE_OK, "ok");
            // show settings form
            $nc_comments_admin->template();
            break;


        //step 3: subscribe settings form
        case 3:
            BeginHtml($Title2, $Title1, "http://" . $DOC_DOMAIN . "/settings/modules/comments/");
            $UI_CONFIG = new ui_config_module_comments('settings', 'subscribe');
            $perm->ExitIfNotSupervisor();
            // show settings form
            $nc_comments_admin->subscribe_settings();
            break;


        //step 3: save subscribe settings
        case 31:
            BeginHtml($Title2, $Title1, "http://" . $DOC_DOMAIN . "/settings/modules/comments/");
            $UI_CONFIG = new ui_config_module_comments('settings', 'subscribe');
            $perm->ExitIfNotSupervisor();
            // save settings
            $nc_comments_admin->subscribe_settings_save();
            // successfully saved
            nc_print_status(NETCAT_MODULE_COMMENTS_ADMIN_SUBSCRIBE_SETTINGS_SAVE_OK, "ok");
            $nc_comments_admin->subscribe_settings();
            break;


        // step 4, 5, 6: converter
        case 4:
        case 5:
        case 6:
            BeginHtml($Title2, $Title1, "http://" . $DOC_DOMAIN . "/settings/modules/comments/");
            $UI_CONFIG = new ui_config_module_comments('admin', 'converter');
            $perm->ExitIfNotSupervisor();
            // catalogue if setted
            $conv_catalogue = $nc_core->input->fetch_post('ConverterCatalogue') ?: 0;
            // subdivision if setted
            $conv_subdivision = $nc_core->input->fetch_post('ConverterSubdivision') ?: 0;
            // show convert form
            $nc_comments_admin->converter($phase, $conv_catalogue, $conv_subdivision);
            break;


        // step 7: converter done
        case 7:
            BeginHtml($Title2, $Title1, "http://" . $DOC_DOMAIN . "/settings/modules/comments/");
            $UI_CONFIG = new ui_config_module_comments('admin', 'converter');
            // check permission
            $perm->ExitIfNotSupervisor();
            // save convert
            if ($nc_comments_admin->converterSave()) {
                // successfully converted
                nc_print_status(NETCAT_MODULE_COMMENTS_ADMIN_CONVERT_OK, "ok");
            }
            // show convert form
            $nc_comments_admin->converter();
            break;


        // step 8: optimizer
        case 8:
            BeginHtml($Title2, $Title1, "http://" . $DOC_DOMAIN . "/settings/modules/comments/");
            $UI_CONFIG = new ui_config_module_comments('admin', 'optimize');
            // check permission
            $perm->ExitIfNotSupervisor();
            // show optimize form
            $nc_comments_admin->optimize();
            break;


        // step 81: save optimize data
        case 81:
            BeginHtml($Title2, $Title1, "http://" . $DOC_DOMAIN . "/settings/modules/comments/");
            $UI_CONFIG = new ui_config_module_comments('admin', 'optimize');
            // check permission
            $perm->ExitIfNotSupervisor();
            // save optimize data
            if ($_POST['OptimizeComments']) {
                if (($_commentsOptimized = $nc_comments_admin->optimizeSave())) {
                    // successfully optimized
                    nc_print_status(str_replace("%COUNT", $_commentsOptimized, NETCAT_MODULE_COMMENTS_ADMIN_OPTIMIZE_OK), "ok");
                } else {
                    // no data
                    nc_print_status(NETCAT_MODULE_COMMENTS_ADMIN_OPTIMIZE_NO_DATA, "info");
                }
            }
            // show optimize form
            $nc_comments_admin->optimize();
            break;


        // step 9: settings form
        case 9:
            BeginHtml($Title2, $Title1, "http://" . $DOC_DOMAIN . "/settings/modules/comments/");
            $UI_CONFIG = new ui_config_module_comments('settings', '');
            $perm->ExitIfNotSupervisor();
            // show settings form
            $nc_comments_admin->settings();
            break;


        // step 91: save settings
        case 91:
            BeginHtml($Title2, $Title1, "http://" . $DOC_DOMAIN . "/settings/modules/comments/");
            $UI_CONFIG = new ui_config_module_comments('settings', '');
            // check permission
            $perm->ExitIfNotSupervisor();
            // save settings
            $nc_comments_admin->settings_save();
            // successfully saved
            nc_print_status(NETCAT_MODULE_COMMENTS_ADMIN_SETTINGS_SAVE_OK, "ok");
            // show settings form
            $nc_comments_admin->settings();
            break;
        // step 10: show subscribes list
        case 10:
            BeginHtml($Title2, $Title1, "http://" . $DOC_DOMAIN . "/settings/modules/comments/");
            $UI_CONFIG = new ui_config_module_comments('admin', 'subscribes');
            // check permission
            $perm->ExitIfNotAccess(array(NC_PERM_MODULE, 'comments'), 'subscription_manage', 0, 0, 0);
            // show settings form
            $nc_comments_admin->subscribes_list();
            break;
        // step 101: show subscribe add form
        case 101:
            BeginHtml($Title2, $Title1, "http://" . $DOC_DOMAIN . "/settings/modules/comments/");
            $UI_CONFIG = new ui_config_module_comments('admin', 'subscribes');
            // check permission
            $perm->ExitIfNotAccess(array(NC_PERM_MODULE, 'comments'), 'subscription_manage', 0, 0, 0);

            $nc_comments_admin->subscribe_form();
            break;
        // step 102: add subscribe
        case 102:
            BeginHtml($Title2, $Title1, "http://" . $DOC_DOMAIN . "/settings/modules/comments/");
            $UI_CONFIG = new ui_config_module_comments('admin', 'subscribes');
            // check permission
            $perm->ExitIfNotAccess(array(NC_PERM_MODULE, 'comments'), 'subscription_manage', 0, 0, 0);

            if($nc_comments_admin->subscribe_form_save()) {
                $nc_comments_admin->subscribes_list();
            }
            else { //если сохранение подписки не удалась покажем заново форму
                $nc_comments_admin->subscribe_form();
            }
            break;
        // step 103: show subscribe delete confirmation
        case 103:
            BeginHtml($Title2, $Title1, "http://" . $DOC_DOMAIN . "/settings/modules/comments/");
            $UI_CONFIG = new ui_config_module_comments('admin', 'subscribes');
            // check permission
            $perm->ExitIfNotAccess(array(NC_PERM_MODULE, 'comments'), 'subscription_manage', 0, 0, 0);

            $nc_comments_admin->subscribes_delete_confirm();
            break;
        // step 104: delete subscribes
        case 104:
            BeginHtml($Title2, $Title1, "http://" . $DOC_DOMAIN . "/settings/modules/comments/");
            $UI_CONFIG = new ui_config_module_comments('admin', 'subscribes');
            // check permission
            $perm->ExitIfNotAccess(array(NC_PERM_MODULE, 'comments'), 'subscription_manage', 0, 0, 0);

            $nc_comments_admin->subscribes_delete();
            $nc_comments_admin->subscribes_list();

            break;
    }
} while ($loop);

echo ob_get_clean();
EndHtml();
?>
