<?php

$NETCAT_FOLDER = join(strstr(__FILE__, "/") ? "/" : "\\", array_slice(preg_split("/[\/\\\]+/", __FILE__), 0, -4)) . (strstr(__FILE__, "/") ? "/" : "\\");
include_once($NETCAT_FOLDER . "vars.inc.php");
require($ADMIN_FOLDER . "function.inc.php");
require($ADMIN_FOLDER . "user/function.inc.php");

$group_name = "";
if (!isset($PermissionGroupID)) {
    $PermissionGroupID = null;
}
if ($PermissionGroupID) {
    $group_name = GetPermissionGroupName($PermissionGroupID);
}


$Delimeter = " &gt ";
$main_section = "control";
$item_id = 6;
$Title2 = CONTROL_USER_GROUPS;
$Title3 = "<a href=\"" . $ADMIN_PATH . "user/group.php\">" . CONTROL_USER_GROUPS . "</a>";
$Title5 = $group_name;
$Title7 = $group_name . " (" . CONTROL_USER_ACESSRIGHTS . ")";
$Title8 = "<a href=\"" . $ADMIN_PATH . "user/group.php?phase=8&PermissionGroupID=" . $PermissionGroupID . "\"> (" . CONTROL_USER_ACESSRIGHTS . ")</a>";
$Title9 = CONTROL_USER_GROUPS_ADD;
$Title10 = CONTROL_USER_GROUPS_EDIT;

if (!isset($phase)) {
    $phase = 1;
} else {
    $phase = (int)$phase;
}

$UI_CONFIG = new ui_config_usergroup();
$UI_CONFIG->usergroup_list_page();

if (!isset($phase)) {
    $phase = 1;
} else {
    $phase = (int) $phase;
}

switch ($phase) {

    case 1:
        # покажем список групп
        BeginHtml($Title2, $Title2, "http://" . $DOC_DOMAIN . "/management/usergroups/");
        $perm->ExitIfNotAccess(NC_PERM_ITEM_GROUP, NC_PERM_ACTION_LIST, 0, 0, 0);
        GroupList();
        break;

    case 2:
        # удалим группы
        BeginHtml($Title2, $Title2, "http://" . $DOC_DOMAIN . "/management/usergroups/");
        $nc_core->token->exit_if_invalid();
        $perm->ExitIfNotAccess(NC_PERM_ITEM_GROUP, NC_PERM_ACTION_DEL, 0, 0, 1);
        DeleteGroups();
        GroupList();
        break;

    case 3:
        # покажем форму редактирования группы
        BeginHtml($Title10, $Title3 . $Delimeter . $Title5, "http://" . $DOC_DOMAIN . "/management/usergroups/");
        $perm->ExitIfNotAccess(NC_PERM_ITEM_GROUP, NC_PERM_ACTION_LIST, $PermissionGroupID, 0, 0);
        $UI_CONFIG->group_page($PermissionGroupID, $group_name, "edit");
        GroupForm($PermissionGroupID, "group.php", 4, 2);
        break;

    case 4:
        # проапдейтим информацию о группе
        BeginHtml($Title2, $Title2, "http://" . $DOC_DOMAIN . "/management/usergroups/");
        $nc_core->token->exit_if_invalid();
        $perm->ExitIfNotAccess(NC_PERM_ITEM_GROUP, NC_PERM_ACTION_EDIT, $PermissionGroupID, 0, 1);
        ActionGroupCompleted(2);
        GroupList();
        break;

    case 5:
        # покажем форму добавления группы
        BeginHtml($Title9, $Title3 . $Delimeter . $Title9, "http://" . $DOC_DOMAIN . "/management/usergroups/");
        $perm->ExitIfNotAccess(NC_PERM_ITEM_GROUP, NC_PERM_ACTION_ADD, 0, 0, 0);
        $UI_CONFIG->new_group_page();
        GroupForm(0, "group.php", 6, 1);
        break;

    case 6:
        # добавим группу
        BeginHtml($Title2, $Title2, "http://" . $DOC_DOMAIN . "/management/usergroups/");
        $nc_core->token->exit_if_invalid();
        $perm->ExitIfNotAccess(NC_PERM_ITEM_GROUP, NC_PERM_ACTION_ADD, 0, 0, 1);
        ActionGroupCompleted(1);
        GroupList();
        break;

    case 8:
        # показать права доступа пользователя
        BeginHtml($Title2, $Title3 . $Delimeter . $Title7, "http://" . $DOC_DOMAIN . "/management/usergroups/");
        $perm->ExitIfNotAccess(NC_PERM_ITEM_GROUP, NC_PERM_ACTION_RIGHT, $PermissionGroupID, 0, 0);
        $UI_CONFIG->group_page($PermissionGroupID, $group_name, "rights");
        ShowUserPermissions(0, 22, "group.php", $PermissionGroupID);
        break;

    case 9:
        # показать форму добавления новых прав доступа
        BeginHtml($Title2, $Title3 . $Delimeter . $Title8 . $Delimeter . $Title7, "http://" . $DOC_DOMAIN . "/management/usergroups/");
        $perm->ExitIfNotAccess(NC_PERM_ITEM_GROUP, NC_PERM_ACTION_RIGHT, $PermissionGroupID, 0, 0);
        $UI_CONFIG->group_page($PermissionGroupID, $group_name, "rights");
        AddPermissionForm(0, 10, "group.php", $PermissionGroupID);
        break;

    case 10:
        # добавить права доступа
        BeginHtml($Title2, $Title3 . $Delimeter . $Title7, "http://" . $DOC_DOMAIN . "/management/usergroups/");
        $nc_core->token->exit_if_invalid();
        $perm->ExitIfNotAccess(NC_PERM_ITEM_GROUP, NC_PERM_ACTION_RIGHT, $PermissionGroupID, 0, 1);
        $ret_val = AddPermissionCompleted($UserID);
        $array_error = GetArrayWithError_User();
        if ($ret_val) {
            if ($ret_val != '1') {
                nc_print_status($array_error[$ret_val], 'error');
            }
        } else {
            nc_print_status(CONTROL_USER_RIGHTS_ADDED, 'ok');
        }
        ShowUserPermissions(0, 22, "group.php", $PermissionGroupID);
        break;

    case 22:
        # проапдейтить права доступа пользователя
        BeginHtml($Title2, $Title3 . $Delimeter . $Title7, "http://" . $DOC_DOMAIN . "/management/usergroups/");
        $nc_core->token->exit_if_invalid();
        $perm->ExitIfNotAccess(NC_PERM_ITEM_GROUP, NC_PERM_ACTION_RIGHT, $PermissionGroupID, 0, 1);
        $UI_CONFIG->group_page($PermissionGroupID, $group_name, "rights");
        $tmpPerm = new Permission(0, $PermissionGroupID);
        if (($tmpPerm->isDirector()) && (!$perm->isDirector())) {
            ShowUserPermissions(0, 22, "group.php", $PermissionGroupID);
            EndHtml();
            exit;
        }
        UpdatePermission();
        ShowUserPermissions(0, 22, "group.php", $PermissionGroupID);
        break;
}

EndHtml();
