<?php

/* $Id: user.inc.php 8121 2012-09-11 08:50:28Z ewind $ */

function GeneratePassword($length) {
    srand((double) microtime() * 1000000);
    while (1) {
        $val = rand(65, 122);
        if (!(($val > 90) && ($val < 97))) {
            $len++;
            $Password .= chr($val);
            if ($len >= $length) break;
        }
    }
    return $Password;
}

function ChangePassword($UserID, $Password, $db) {
    global $nc_core;

    $UserID = intval($UserID);
    $Password = $db->escape($Password);

    // новый пароль совпадает с текущим?
    if ($db->get_var("SELECT `User_ID` FROM `User` WHERE `User_ID` = '".$UserID."' AND `Password` = ".$nc_core->MYSQL_ENCRYPT."('".$Password."')")) {
        nc_print_status(CONTROL_USER_ERROR_NEWPASS_IS_CURRENT, 'error');
        return;
    }

    // Есть ли поле RegistrationCode в таблице пользователей?
    $RegistrationCodeExists = false;
    $res = $db->get_results("EXPLAIN `User`", ARRAY_A);
    foreach ($res as $row) {
        if ($row['Field'] == 'RegistrationCode') {
            $RegistrationCodeExists = true;
            break;
        }
    }
    // если нет, то нужно создать
    if (!$RegistrationCodeExists) {
        $db->query("ALTER TABLE `User` ADD `RegistrationCode` VARCHAR( 255 ) NOT NULL ");
    }

    $db->last_error = '';

    // execute core action
    $nc_core->event->execute(nc_Event::BEFORE_USER_UPDATED, $UserID);

    // сообственно, запро
    $db->query("UPDATE `User` SET `Password`=".$nc_core->MYSQL_ENCRYPT."('".$Password."'), `RegistrationCode` = '' WHERE `User_ID` = '".$UserID."'");

    if (!$db->last_error) {
        nc_print_status(CONTROL_USER_OK_CHANGEDPASS, 'ok');
    } else {
        nc_print_status(CONTROL_USER_ERROR_NOTCANGEPASS, 'error');
    }

    // execute core action
    $nc_core->event->execute(nc_Event::AFTER_USER_UPDATED, $UserID);
}

function GetLoginByID($UserID) {
    global $db;
    global $AUTHORIZE_BY;

    $AUTHORIZE_BY = $db->escape($AUTHORIZE_BY);
    $UserID = intval($UserID);

    return $db->get_var("select `".$AUTHORIZE_BY."` from User where User_ID='".$UserID."'");
}

function GetPermissionGroupName($PermissionGroupID) {
    global $db;
    return $db->get_var("select PermissionGroup_Name from PermissionGroup where PermissionGroup_ID='".intval($PermissionGroupID)."'");
}

/**
 * Добавляет новую группу пользователей
 *
 * @param str имя группы
 * @return int номер добавленной группы. 0 в случае ошибки
 */
function nc_usergroup_create($name) {
    global $db;

    $db->insert_id = 0;
    if (!trim($name)) return 0;

    $db->query("INSERT INTO `PermissionGroup` (`PermissionGroup_Name`) VALUES ('".$db->escape($name)."')");

    return $db->insert_id;
}

/**
 * Изменить имя группы пользователей
 *
 * @param int Permission Group ID
 * @param str name
 * @return int 0 - неудачно, 1 - удачно
 */
function nc_usergroup_rename($PermissionGroupID, $name) {
    global $db;

    $PermissionGroupID = intval($PermissionGroupID);
    $db->rows_affected = 0;

    if (!trim($name) || !$PermissionGroupID) return 0;

    $db->query("UPDATE `PermissionGroup` SET `PermissionGroup_Name` = '".($db->escape($name))."' WHERE `PermissionGroup_ID` = '".$PermissionGroupID."'");

    return $db->rows_affected;
}

/**
 * Удалить группу (группы)
 *
 * @param mixed (int or array) PermissionGroup ID
 * @return array массив с удаленныи группами
 */
function nc_usergroup_delete($PermissionGroupID) {
    global $db;

    $ret = array(); // возвращаемое значение

    if (!$PermissionGroupID) return 0;

    // если массиив - то каждую группу удаляем по отдельности
    if (is_array($PermissionGroupID) && !empty($PermissionGroupID)) {
        foreach ($PermissionGroupID as $grpID) {
            $tmp = nc_usergroup_delete($grpID);
            // если удаление произошло - запишем номер удаленной группы в результат
            if (!empty($tmp)) $ret[] = $tmp[0];
        }
    }
    else { // сообственно, удалить группу
        // получим всех пользователей группы
        $users = nc_usergroup_get_users_from_group($PermissionGroupID);

        // нельзя удалить группу, если есть пользователь, который состоит только в этой группе
        if (!empty($users)) {
            $query = "SELECT COUNT(`ID`) FROM `User_Group` WHERE `User_ID` IN (".join(',', $users).") GROUP BY `User_ID`";
            // есть пользователь, который состоит только в этой группе - удалить нельзя
            if (in_array(1, $db->get_col($query))) return 0;
        }
        // удаление группы
        foreach ((array) $users as $user_id) {
            nc_usergroup_remove_from_group($user_id, $PermissionGroupID);
        }
        // удаление из таблицы-связки
        $db->query("DELETE FROM `User_Group` WHERE `PermissionGroup_ID` = '".intval($PermissionGroupID)."'");
        // удаление из таблицы с группами
        $db->rows_affected = 0;
        $db->query("DELETE FROM `PermissionGroup` WHERE `PermissionGroup_ID` = '".intval($PermissionGroupID)."'");
        if ($db->rows_affected) $ret[0] = $PermissionGroupID;
    }

    return $ret;
}

/**
 * Возвращает массив с пользователями, входящих в группу
 *
 * @param mixed (int or array) PermissionGroup ID
 * @param int output_type - структура возвращаемого массива
 *                       0: просто номера пользователей [0] => user1 id, [1] => user2 id, etc
 *                       1: [group_id][user_id] => Login
 * @return unknown
 */
function nc_usergroup_get_users_from_group($PermissionGroupID, $output_type = 0) {
    global $db;
    $nc_core = nc_Core::get_object();
    $ret = array();

    if (!$PermissionGroupID) return 0;
    if (!is_array($PermissionGroupID))
            $PermissionGroupID = array($PermissionGroupID);
    $PermissionGroupID = array_map('intval', $PermissionGroupID);

    $query = "SELECT u.`User_ID`, u.`".$nc_core->AUTHORIZE_BY."`, ug.`PermissionGroup_ID`
            FROM `User` AS u, `User_Group` AS ug 
            WHERE u.`User_ID` = ug.`User_ID` 
            AND ug.`PermissionGroup_ID` IN  (".join(',', $PermissionGroupID).")";

    // arrays with user id, login and group id
    $user_id = $db->get_col($query);
    $login = $db->get_col(null, 1);
    $group = $db->get_col(null, 2);

    if (empty($user_id)) return $ret;

    switch ($output_type) {
        case 0: // [0] => user_id, [1] => user_id
            $ret = $user_id;
            break;
        case 1: // [group][user] = login
            foreach ($group as $k => $v) {
                $ret[$v][$user_id[$k]] = $login[$k];
            }
            break;
        default:
            return $ret;
    }

    return $ret;
}

/**
 * Добавить пользователя в группу
 *
 * @param int User ID
 * @param int PermissionGroup ID
 * @param int exec_event транслировать событие "Изменение пользователя"
 * @return 0 - ошибка, -1 - пользователь уже состоит в группе, положительное число - пользователь добавлен
 */
function nc_usergroup_add_to_group($UserID, $PermissionGroupID, $exec_event = 1) {
    global $nc_core, $db;

    $UserID = intval($UserID);
    $PermissionGroupID = intval($PermissionGroupID);
    $db->insert_id = 0;

    if (!$UserID || !$PermissionGroupID) return 0;

    //пользователь уже состоит в группе?
    if ($db->get_var("SELECT `ID` FROM `User_Group` WHERE `User_ID` = '".$UserID."' AND `PermissionGroup_ID`='".$PermissionGroupID."'"))
            return -1;
    // группа существует ?
    if (!$db->get_var("SELECT `PermissionGroup_ID` FROM `PermissionGroup` WHERE `PermissionGroup_ID`='".$PermissionGroupID."'"))
            return 0;

    // execute core action
    if ($exec_event) $nc_core->event->execute(nc_Event::BEFORE_USER_UPDATED, $UserID);

    $db->query("INSERT INTO `User_Group`(`User_ID`, `PermissionGroup_ID`) VALUES('".$UserID."','".$PermissionGroupID."')");

    // execute core action
    if ($exec_event) $nc_core->event->execute(nc_Event::AFTER_USER_UPDATED, $UserID);

    return $db->insert_id;
}

/**
 * Функция исключает пользователя из группы
 *
 * @param int $UserID
 * @param int $PermissionGroupID
 * @return bool
 */
function nc_usergroup_remove_from_group($UserID, $PermissionGroupID) {
    global $nc_core, $db;

    $UserID = intval($UserID);
    $PermissionGroupID = intval($PermissionGroupID);

    if (!$UserID || !$PermissionGroupID) return false;
    $groups = nc_usergroup_get_group_by_user($UserID);
    // пользователь состоит в группе? + пользователь должен состоять как минимум в одной группе
    if (empty($groups) || !in_array($PermissionGroupID, $groups) || count($groups) <= 1)
            return false;

    // execute core action
    $nc_core->event->execute(nc_Event::BEFORE_USER_UPDATED, $UserID);

    $db->query("DELETE FROM `User_Group` WHERE `User_ID` = '".$UserID."' AND `PermissionGroup_ID` = '".$PermissionGroupID."'");

    // нужно обновить значение в таблице user
    foreach ($groups as $k => $v) {
        if ($v == $PermissionGroupID) {
            unset($groups[$k]);
        }
    }
    $mainGroup = intval(min((array) $groups));
    $db->query("UPDATE `User` SET `PermissionGroup_ID` = '".$mainGroup."' WHERE `User_ID` = '".$UserID."'");

    // execute core action
    $nc_core->event->execute(nc_Event::AFTER_USER_UPDATED, $UserID);

    return true;
}

/**
 * Все группы, где состоит пользователь
 *
 * @param int $UserID
 * @return array
 */
function nc_usergroup_get_group_by_user($UserID, $output_type = 0) {
    global $db;

    $UserID = intval($UserID);
    if (!$UserID) return array();

    $groups_id = (array) $db->get_col("SELECT ug.`PermissionGroup_ID`, g.`PermissionGroup_Name`
                       FROM `User_Group` as ug, `PermissionGroup` as g 
                       WHERE ug.`PermissionGroup_ID` = g.`PermissionGroup_ID`
                       AND ug.`User_ID` = '".$UserID."'");
    $groups_name = (array) $db->get_col(null, 1);

    if ($output_type == 0) {
        $ret = $groups_id;
    } else {
        foreach ($groups_id as $k => $v) {
            $ret[$v] = $groups_name[$k];
        }
    }

    return $ret;
}
?>