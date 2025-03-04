<?php

$NETCAT_FOLDER = join(strstr(__FILE__, "/") ? "/" : "\\", array_slice(preg_split("/[\/\\\]+/", __FILE__), 0, -4)).( strstr(__FILE__, "/") ? "/" : "\\" );
include_once ($NETCAT_FOLDER."vars.inc.php");
require ($ADMIN_FOLDER."function.inc.php");
require ($ADMIN_FOLDER."user/function.inc.php");
require_once ($INCLUDE_FOLDER."s_common.inc.php");

/** @var nc_core $nc_core */
/** @var Permission $perm */

if (is_numeric($UserID)) {
    $user_login = GetLoginByID($UserID);
} else {
    $user_login = "";
}

$main_section = "control";
$item_id = 5;
$Delimeter = " &gt ";
$Title2 = CONTROL_USER_USERSANDRIGHTS;
$Title3 = "<a href=\"".$ADMIN_PATH."user/\">".SECTION_CONTROL_USER_PERMISSIONS."</a>";
$Title5 = $user_login;
$Title6 = CONTROL_USER_PASSCHANGE;
$Title7 = $user_login." (".CONTROL_USER_ACESSRIGHTS.")";
$Title8 = "<a href=\"".$ADMIN_PATH."user/?phase=8&UserID=".$UserID."\">".$user_login." (".CONTROL_USER_ACESSRIGHTS.")</a>";
$Title9 = CONTROL_USER_CATALOGUESWITCH;
$Title10 = CONTROL_USER_SECTIONSWITCH;
$Title11 = CONTROL_USER_TITLE_USERINFOEDIT;
$Title12 = CONTROL_USER_TITLE_PASSWORDCHANGE." ".$user_login;
$Title13 = CONTROL_SETTINGSFILE_TITLE_ADD;

// описание интерфейса
$UI_CONFIG = new ui_config_user();
$UI_CONFIG->user_list_page();

if (!isset($phase)) {
    $phase = 1;
} else {
    $phase = (int)$phase;
}

if ($phase === 30 && !defined('NC_SUPPORT_LOGIN')) {
    $phase = 1;
}

if (in_array($phase, array(3, 5, 7, 10, 11, 12, 13, 14, 16, 31))) {
    if (!$nc_core->token->verify()) {
        BeginHtml($Title2, $Title2, "");
        nc_print_status(NETCAT_TOKEN_INVALID, 'error');
        EndHtml();
        exit;
    }
}

try {
    switch ($phase) {
        case 1:
            # покажем форму поиска пользователей
            BeginHtml($Title2, $Title2, "http://".$DOC_DOMAIN."/management/users/");
            $perm->ExitIfNotAccess(NC_PERM_ITEM_USER, NC_PERM_ACTION_LIST, 0, 0, 0);
            $UI_CONFIG = new ui_config_user();
            $UI_CONFIG->user_list_page();
            SearchUserResult ();
            break;

        case 2:
            # покажем результаты поиска пользователей
            BeginHtml($Title2, $Title2, "http://".$DOC_DOMAIN."/management/users/");
            $perm->ExitIfNotAccess(NC_PERM_ITEM_USER, NC_PERM_ACTION_LIST, 0, 0, 0);
            $UI_CONFIG = new ui_config_user();
            $UI_CONFIG->user_list_page();
            SearchUserResult ();
            break;

        case 3:
            # удалим пользователей
            BeginHtml($Title2, $Title2, "http://".$DOC_DOMAIN."/management/users/");
            $perm->ExitIfNotAccess(NC_PERM_ITEM_USER, NC_PERM_ACTION_DEL, -1, 0, 1);
            $UI_CONFIG = new ui_config_user();
            $UI_CONFIG->user_list_page();

            foreach ($_POST as $key => $val) {
                if (strpos($key, 'Delete') === 0) {
                    $user_to_delete[] = $val;
                }
            }

            DeleteUsers($user_to_delete);
            SearchUserResult ();
            break;

        case 4:
            # покажем форму редактирования пользователя
            BeginHtml($Title11, $Title3.$Delimeter.$Title5, "http://".$DOC_DOMAIN."/management/users/info/");
            $perm->ExitIfNotAccess(NC_PERM_ITEM_USER, NC_PERM_ACTION_EDIT, $UserID, 0, 0);
            $UI_CONFIG = new ui_config_user();
            $UI_CONFIG->user_list_page();
            $UI_CONFIG->user_page($UserID, $user_login, "edit");
            UserForm($UserID, "index.php", 5, 2);
            break;

        case 5:
            # проапдейтим информацию о пользователе
            BeginHtml($Title2, $Title2, "http://".$DOC_DOMAIN."/management/users/");
            $perm->ExitIfNotAccess(NC_PERM_ITEM_USER, NC_PERM_ACTION_EDIT, $UserID, 0, 1);
            $UI_CONFIG->user_page($UserID, $user_login, "edit");

            if (($warnText = ActionUserCompleted("index.php", 2))) {
                nc_print_status($warnText, 'error');
            }
            else {
                nc_print_status(CONTROL_USER_EDITSUCCESS, 'ok');
            }
            UserForm($UserID, "index.php", 5, 2);

            break;

        case 6:
            # покажем форму смены пароля
            $UI_CONFIG->user_page($UserID, $user_login, "edit", "user.password($UserID)");
            BeginHtml($Title12, $Title3.$Delimeter.$Title6, "http://".$DOC_DOMAIN."/management/users/password/");
            $perm->ExitIfNotAccess(NC_PERM_ITEM_USER, NC_PERM_ACTION_EDIT, $UserID, 0, 0);
            ChangePasswordFormAdmin($UserID);
            break;

        case 7:
            # собственно сменим пароль
            $perm->ExitIfNotAccess(NC_PERM_ITEM_USER, NC_PERM_ACTION_EDIT, $UserID, 0, 1);
            if (( strlen($Password1) == 0) && (strlen($Password2) == 0)) {
                BeginHtml($Title2, $Title3.$Delimeter.$Title6, "http://".$DOC_DOMAIN."/management/users/password/");
                $UI_CONFIG->user_page($UserID, $user_login, "edit", "user.password($UserID)");
                nc_print_status(CONTROL_USER_ERROR_EMPTYPASS."<BR>\n".CONTROL_USER_ERROR_RETRY."<BR>\n", 'error');
                ChangePasswordFormAdmin($UserID);
            } elseif ($Password1 == $Password2) {
                BeginHtml($Title2, $Title2, "http://".$DOC_DOMAIN."/management/users/");
                ChangePassword($UserID, $Password1, $db);
                unset($UserID);
                SearchUserResult ();
            } else {
                $UI_CONFIG->user_page($UserID, $user_login, "edit", "user.password($UserID)");
                BeginHtml($Title2, $Title3.$Delimeter.$Title6, "http://".$DOC_DOMAIN."/management/users/password/");
                nc_print_status(CONTROL_USER_ERROR_PASSDIFF."<BR>\n".CONTROL_USER_ERROR_RETRY."<BR>\n", 'error');
                ChangePasswordFormAdmin($UserID);
            }
            break;

        case 8:
            # показать права доступа пользователя
            $UI_CONFIG->user_page($UserID, $user_login, "rights");
            BeginHtml($Title2, $Title3.$Delimeter.$Title7, "http://".$DOC_DOMAIN."/management/users/rights/");
            $perm->ExitIfNotAccess(NC_PERM_ITEM_USER, NC_PERM_ACTION_RIGHT, $UserID, 0, 0);
            ShowUserPermissions($UserID, 11);
            break;

        case 9:
            # показать форму добавления новых прав доступа
            $UI_CONFIG->user_page($UserID, $user_login, "rights");
            BeginHtml($Title2, $Title3.$Delimeter.$Title8.$Delimeter.$Title13, "http://".$DOC_DOMAIN."/management/users/rights/add/");
            $perm->ExitIfNotAccess(NC_PERM_ITEM_USER, NC_PERM_ACTION_RIGHT, $UserID, 0, 0);
            AddPermissionForm($UserID);
            break;

        case 10:
            # добавить права доступа
            BeginHtml($Title2, $Title3.$Delimeter.$Title7, "http://".$DOC_DOMAIN."/management/users/rights/");
            $perm->ExitIfNotAccess(NC_PERM_ITEM_USER, NC_PERM_ACTION_RIGHT, $UserID, 0, 1);
            $nc_core->token->exit_if_invalid();
            $UI_CONFIG->user_page($UserID, $user_login, "rights");
            $ret_val = AddPermissionCompleted();
            $array_error = GetArrayWithError_User();
            if ($ret_val > 0) {
                nc_print_status($array_error[$ret_val], 'error');
                AddPermissionForm($UserID);
            } else {
                if ($ret_val === -1) {
                    nc_print_status(CONTROL_USER_RIGHTS_ERROR_SOME_AVAILABLE, 'error');
                } else if ($ret_val === -2) {
                    nc_print_status(CONTROL_USER_RIGHTS_ERROR_EXISTED, 'info');
                } else {
                    nc_print_status(CONTROL_USER_RIGHTS_ADDED, 'ok');
                }
                ShowUserPermissions($UserID, 11);
            }


            break;

        case 11:
            # проапдейтить права доступа пользователя
            BeginHtml($Title2, $Title3.$Delimeter.$Title7, "http://".$DOC_DOMAIN."/management/users/rights/");
            $perm->ExitIfNotAccess(NC_PERM_ITEM_USER, NC_PERM_ACTION_RIGHT, $UserID, 0, 1);
            $UI_CONFIG->user_page($UserID, $user_login, "rights");
            UpdatePermission ();
            ShowUserPermissions($UserID, 11);
            break;


        case 12:
            # включить/выключить пользователя
            BeginHtml($Title2, $Title2, "http://".$DOC_DOMAIN."/management/users/");
            $perm->ExitIfNotAccess(NC_PERM_ITEM_USER, NC_PERM_ACTION_EDIT, $UserID, 0, 1);

            ChangeCheckedForUser($UserID);

            unset($UserID);
            SearchUserResult ();
            break;

        case 13:
            # удалим одного пользователя
            BeginHtml($Title2, $Title2, "http://".$DOC_DOMAIN."/management/users/");
            $perm->ExitIfNotAccess(NC_PERM_ITEM_USER, NC_PERM_ACTION_DEL, $KillUserID, 0, 1);
            DeleteUsers($KillUserID);
            SearchUserResult ();
            break;
        case 14:
            #Подверждение удаления
            BeginHtml($Title2, $Title2, "http://".$DOC_DOMAIN."/management/users/");
            $perm->ExitIfNotAccess(NC_PERM_ITEM_USER, NC_PERM_ACTION_DEL, $UserID, 0, 0);
            if (ConfirmDeleteUsers ()) {
                $UI_CONFIG->actionButtons[] = array(
                    "id" => "confirm",
                    "caption" => CONTROL_USER_FUNC_CONFIRM_DEL_OK,
                    "action" => "mainView.submitIframeForm()",
                    "red_border" => true,
                );
            }
            break;
        // подписки пользователя
        case 15:
            BeginHtml($Title2, $Title2, "http://".$DOC_DOMAIN."/management/users/");
            $UI_CONFIG = new ui_config_user();
            $UI_CONFIG->user_page($UserID, $user_login, 'subscribers');
            $perm->ExitIfNotAccess(NC_PERM_ITEM_USER, NC_PERM_ACTION_EDIT, $UserID, 0, 0);
            require_once nc_module_folder('subscriber') . 'nc_subscriber_admin.class.php';
            $nc_subscriber_admin = new nc_subscriber_admin();
            $nc_subscriber_admin->user_show($UserID);
            break;

        case 16: // подписать пользователя
            BeginHtml($Title2, $Title2, "http://".$DOC_DOMAIN."/management/users/");
            $UI_CONFIG = new ui_config_user();
            $UI_CONFIG->user_page($UserID, $user_login, 'subscribers');
            $perm->ExitIfNotAccess(NC_PERM_ITEM_USER, NC_PERM_ACTION_EDIT, $UserID, 0, 0);
            require_once nc_module_folder('subscriber') . 'nc_subscriber_admin.class.php';
            $nc_subscriber_admin = new nc_subscriber_admin();
            if ($action_type != 'add_ask') {
                $nc_subscriber_admin->sbs_update();
                $nc_subscriber_admin->user_show($UserID);
            } else {
                $nc_subscriber_admin->user_add_form($UserID);
            }
            break;

        case 20: // результаты ajax
            $has_rights =
                $perm->isAccess(NC_PERM_ITEM_USER, NC_PERM_ACTION_RIGHT, 0, 0) ||
                $perm->isAccess(NC_PERM_ITEM_GROUP, NC_PERM_ACTION_RIGHT, 0, 0);

            if (!$has_rights) {
                nc_print_status(NETCAT_MODERATION_ERROR_NORIGHTS, 'error');
                EndHtml();
                exit();
            }

            $getsublist = (int)nc_array_value($_POST, 'getsublist');           // Получить разделы каталога getsublist
            $getsublist_cc = (int)nc_array_value($_POST, 'getsublist_cc');        // Получить разделы каталога getsublist для выбора сс
            $getclassificator = (int)nc_array_value($_POST, 'getclassificator');     // Получить класификаторы
            $getsubclasslist = (int)nc_array_value($_POST, 'getsubclasslist');      // Получить компонеты в разделе getsubclasslist
            $getmailers = (int)nc_array_value($_POST, 'getmailers');           // Получить все рассылки

            $res = '';

            if ($getsublist) { // Получить разделы данного сайта
                $Result = $db->get_results("SELECT `Subdivision_ID` AS value,
                                    CONCAT(Subdivision_ID, '. ', Subdivision_Name) AS description,
                                    `Parent_Sub_ID` AS parent
                                    FROM `Subdivision`
                                    WHERE `Catalogue_ID` = '".$getsublist."'
                                    ORDER BY `Priority`, `Subdivision_ID`", ARRAY_A);
                if (!empty($Result)) $res = nc_select_options($Result);
            }
            else if ($getsublist_cc) { // Получить разделы данного сайта для выбора сс
                $Result = $db->get_results("SELECT s.`Subdivision_ID` AS value,
                                    CONCAT(s.Subdivision_ID, '. ', Subdivision_Name) AS description,
                                    `Parent_Sub_ID` AS parent,
                                    NOT(COUNT(`Sub_Class_ID`)) AS without_cc
                                    FROM `Subdivision` as s
                                    LEFT JOIN `Sub_Class`
                                    ON `Sub_Class`.`Subdivision_ID` = s.`Subdivision_ID`
                                    WHERE s.`Catalogue_ID` = '".$getsublist_cc."'
                                    GROUP BY s.`Subdivision_ID`
                                    ORDER BY s.`Subdivision_ID` ", ARRAY_A);
                if (!empty($Result)) $res = nc_select_options($Result);
            }
            else if ($getsubclasslist) { // Получить cc
                $subclasses = $db->get_results("SELECT CONCAT(`Sub_Class_ID`, '. ', `Sub_Class_Name`) AS descr, `Sub_Class_ID` AS id
                                        FROM `Sub_Class`
                                        WHERE `Subdivision_ID` = '".$getsubclasslist."'
                                        ORDER BY `Sub_Class_ID`", ARRAY_A);
                foreach ((array) $subclasses as $sc) {
                    $res .= "<option value='".$sc['id']."'>".$sc['descr']."</option>\n";
                }
            } else if ($getclassificator) { // Получить классификаторы
                $classificators = $db->get_results("SELECT `Classificator_ID` AS id, `Classificator_Name` AS name, `System` AS sys
                                           FROM `Classificator`
                                           ORDER BY `Classificator_ID`", ARRAY_A);
                $res .= "<option value='0'>".CONTENT_CLASSIFICATORS_NAMEALL."</option>\n";
                foreach ((array) $classificators as $cl) {
                    $font_color = $cl['sys'] ? '#FF0000' : '000000';
                    $res .= "<option value='".$cl['id']."' style='color: ".$font_color."'>".$cl['id'].". ".$cl['name']."</option>\n";
                }
            } else if ($getmailers) {
                $mailers = $db->get_results("SELECT `Mailer_ID` AS id, `Name` AS name
                                    FROM `Subscriber_Mailer`
                                    WHERE `Type` IN (1,2)
                                    ORDER BY `Mailer_ID`", ARRAY_A);
                if (!empty($mailers)) {
                    foreach ($mailers as $mailer) {
                        $res .= "<option value='".$mailer['id']."'>".$mailer['id'].". ".$mailer['name']."</option>\n";
                    }
                }
            }

            print $res;
            exit();

        // присвоение прав пользователю поддержки
        case 30: // GET
        case 31: // POST
            $exit_with_message = function($message) use ($nc_core) {
                nc_print_status($message . ' ' . CONTROL_USER_SUPPORT_CREATE_MANUALLY, 'error', array($nc_core->ADMIN_PATH));
                EndHtml();
                exit();
            };

            BeginHtml(SECTION_INDEX_HELP_SUBMENU_SUPPORT);
            $perm->ExitIfNotAccess(NC_PERM_ITEM_USER, NC_PERM_ACTION_ADD);
            if (!$nc_core->get_settings('SsoEnabled')) {
                $exit_with_message(CONTROL_USER_SUPPORT_NO_SSO);
            }

            $duration = !empty($duration) ? (int)$duration : 1;
            $user_info = nc_get_support_user_info();

            if ($phase === 31 && $_POST) {
                $user_info = nc_enable_support_user($duration, $user_info);
                if (empty($user_info['User_ID'])) {
                    $exit_with_message(CONTROL_USER_SUPPORT_ERROR_CREATING);
                }
            }

            $UI_CONFIG->support_user_page($duration, nc_array_value($user_info, 'SupervisorPermissionsExpireAt') !== false);

            echo $nc_core->ui
                ->view($nc_core->ADMIN_FOLDER . 'views/user/support')
                ->with('duration', $duration)
                ->with('user', $user_info);

            break;
    }
} catch (Exception $e) {
    BeginHtml($Title2, $Title2, "http://".$DOC_DOMAIN."/management/users/");
    nc_print_status($e->getMessage(), "error");
    EndHtml();
    exit;
}

EndHtml();
