<?php

/**
 * Функционал добавдения пользователей в друзья или враги 
 */
/*
  CREATE TABLE `Auth_UserRelation` (
  `ID` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
  `User_ID` INT NOT NULL ,
  `Related_ID` INT NOT NULL ,
  `Type` INT NOT NULL ,
  `Created` DATETIME NOT NULL
  ) ENGINE = MYISAM
 */

# const
define('NC_AUTH_FRIEND', 1);
define('NC_AUTH_BANNED', 2);

#user function
# Функции добавления отношений

/**
 * Добавить отношения между пользователями
 *
 * @param int тип (NC_AUTH_FRIEN или NC_AUTH_BANNED)
 * @param int Related_ID - пользователь, который становится кем-то
 * @param int User_ID - "владелец" отношения. (по умолчанию - текущий пользователь)
 * @return int 0 -ошибка
 */
function nc_auth_add_relation($type, $Related_ID, $User_ID = 0) {
    global $db, $current_user;

    // приведение к целому типу
    $type = intval($type);
    $Related_ID = intval($Related_ID);
    $User_ID = intval($User_ID);

    //пользователь по умочланию
    if (!$User_ID) $User_ID = $current_user['User_ID'];

    //проверка на существование пользователей
    if (2 != $db->get_var("SELECT COUNT(`User_ID`) FROM `User` WHERE `User_ID` IN ('".$User_ID."', '".$Related_ID."')")) {
        return 0;
    }

    // проверка на существование отношений
    if (nc_auth_is_friend($Related_ID, $User_ID, 1)) return 0;
    if (nc_auth_is_banned($Related_ID, $User_ID, 1)) return 0;

    //сообственно добавление
    $db->insert_id = 0;
    $db->query("INSERT INTO `Auth_UserRelation`(`User_ID`, `Related_ID`, `Type`, `Created` )
              VALUES ('".$User_ID."', '".$Related_ID."', '".$type."', NOW())");

    return $db->insert_id;
}

/**
 * Добавить друга
 *
 * @param int $Related_ID
 * @param int $User_ID
 * @return int
 */
function nc_auth_add_friend($Related_ID, $User_ID = 0) {
    $type = NC_AUTH_FRIEND;
    return nc_auth_add_relation($type, $Related_ID, $User_ID);
}

/**
 * Добавить врага
 *
 * @param int $Related_ID
 * @param int $User_ID
 * @return int
 */
function nc_auth_add_bann($Related_ID, $User_ID = 0) {
    $type = NC_AUTH_BANNED;
    return nc_auth_add_relation($type, $Related_ID, $User_ID);
}

# Функции для проверки отношений

/**
 * Вернуть все отношения пользователя
 *
 * @param  int User_ID - индетификатор пользователя. По умолчанию - текущий 
 * @return array  [related_id] => type
 */
function nc_auth_get_all_relation($User_ID = 0, $reset = 0, $output_all = 0) {
    global $db, $current_user;

    // статические массивы
    static $init;     // флаги инициализации
    static $relation; // результат выполнения функции
    // приведение к целому типу
    $User_ID = intval($User_ID);

    // пользователь по умолчанию
    if (!$User_ID) $User_ID = $current_user['User_ID'];

    //проверка на существование пользователей
    //if ( $db->get_var("SELECT `User_ID` FROM `User` WHERE `User_ID` = '".$User_ID."'") ){
    //  return 0;
    //}
    // результат в статичнском массиве нет
    if (!nc_array_value($init, $User_ID) || $reset) {
        $result = $db->get_results("SELECT a.`Related_ID`, a.`Type` as `ncRelType`, u.*
                                FROM `Auth_UserRelation` as `a`, `User` AS `u`
                                WHERE u.User_ID = a.Related_ID
                                AND a.`User_ID` = '".$User_ID."'", ARRAY_A);

        $init[$User_ID] = true;
        $relation[$User_ID] = array();

        // проход по результату
        if (!empty($result))
                foreach ($result as $row) {
                $relation[$User_ID][$row['Related_ID']] = $row;
            }
    }

    if ($output_all) {
        return $relation[$User_ID];
    } else {
        $res = array();
        foreach ($relation[$User_ID] as $v)
            $res[$v['Related_ID']] = $v['ncRelType'];
        return $res;
    }
}

/**
 * Вернуть друзей пользователя
 *
 * @param User_ID
 * @return array []=> User_ID
 */
function nc_auth_get_all_friend($User_ID = 0, $reset = 0, $output_all = 0) {
    // возвращаемое значение
    $ret = array();
    // все отношения пользователя
    $relation = nc_auth_get_all_relation($User_ID, $reset, $output_all);

    // если ли отношения
    if (empty($relation)) return $ret;

    // проход по все отношения в поисках друзей
    foreach ($relation as $related_id => $type) {
        if (!$output_all && $type == NC_AUTH_FRIEND) {
            $ret[] = $related_id;
        } else if ($output_all && ($type['ncRelType'] == NC_AUTH_FRIEND)) {
            $ret[$type['Related_ID']] = $type;
        }
    }

    return $ret;
}

/**
 * Вернуть врагов пользователя
 *
 * @param User_ID
 * @return array []=> User_ID
 */
function nc_auth_get_all_banned($User_ID = 0, $reset = 0, $output_all = 0) {
    // возвращаемое значение
    $ret = array();
    // все отношения пользователя
    $relation = nc_auth_get_all_relation($User_ID, $reset, $output_all);

    // если ли отношения
    if (empty($relation)) return $ret;

    // проход по все отношения в поисках врагов
    foreach ($relation as $related_id => $type) {
        if ($type == NC_AUTH_BANNED) {
            $ret[] = $related_id;
        }
    }

    return $ret;
}

/**
 * Проверить, является ли пользователь Related другом User
 *
 * @param int $Related_ID
 * @param int $User_ID
 * @return bool
 */
function nc_auth_is_friend($Related_ID, $User_ID = 0, $reset = 0) {
    global $current_user;
    // пользователь по умолчанию
    if (!$User_ID) $User_ID = $current_user['User_ID'];
    // все друзья пользователя
    $friends = nc_auth_get_all_friend($User_ID, $reset);

    return (!empty($friends) && in_array($Related_ID, $friends));
}

/**
 * Проверить, является ли пользователь Related врагом User
 *
 * @param int $Related_ID
 * @param int $User_ID
 * @return bool
 */
function nc_auth_is_banned($Related_ID, $User_ID = 0, $reset = 0) {
    global $current_user;
    // пользователь по умолчанию
    if (!$User_ID) $User_ID = $current_user['User_ID'];
    // все друзья пользователя
    $banned = nc_auth_get_all_banned($User_ID, $reset);

    return (!empty($banned) && in_array($Related_ID, $banned));
}

/**
 * Узнать отношения между пользователями
 *
 * @param unknown_type $Related_ID
 * @param unknown_type $User_ID
 * @return unknown
 */
function nc_auth_get_relation($Related_ID, $User_ID = 0, $reset = 0) {
    // is friend ?
    if (nc_auth_is_friend($Related_ID, $User_ID, $reset)) return NC_AUTH_FRIEND;
    // is banned ?
    if (nc_auth_is_banned($Related_ID, $User_ID, $reset)) return NC_AUTH_BANNED;

    return 0;
}

/**
 * Пользовтели взаимные друзья\ враги?
 *
 * @param unknown_type $Related_ID
 * @param unknown_type $User_ID
 * @return int. NC_AUTH_FRIEND - взаимные друзья. NC_AUTH_BANNED - взаимные враги
 */
function nc_auth_is_mutual($Related_ID, $User_ID = 0, $reset = 0) {
    global $current_user;
    // пользователь по умолчанию
    if (!$User_ID) $User_ID = $current_user['User_ID'];

    // взаимные друзьм
    if (nc_auth_is_friend($Related_ID, $User_ID, $reset) && nc_auth_is_friend($User_ID, $Related_ID, $reset))
            return NC_AUTH_FRIEND;

    // взаимные враги
    if (nc_auth_is_banned($Related_ID, $User_ID, $reset) && nc_auth_is_banned($User_ID, $Related_ID, $reset))
            return NC_AUTH_BANNED;

    return 0;
}

# Функции для удаления отношения

/**
 * Удалить отношение между пользователями
 *
 * @param unknown_type $Related_ID
 * @param unknown_type $User_ID
 * @return unknown
 */
function nc_auth_delete_relation($Related_ID, $User_ID = 0) {
    global $current_user, $db;
    // пользователь по умолчанию
    if (!$User_ID) $User_ID = $current_user['User_ID'];

    $User_ID = intval($User_ID);
    $Related_ID = intval($Related_ID);

    $db->query("DELETE FROM `Auth_UserRelation` WHERE `User_ID` = '".$User_ID."' AND `Related_ID` = '".$Related_ID."'");

    return 0;
}

/**
 * Удалитть все отношения, где присутствует пользователь
 *
 * @param mixed $User_ID array with ids or id
 */
function nc_auth_delete_all_relation($User_ID) {
    global $db;

    if (!is_array($User_ID)) $User_ID = array($User_ID);

    if (empty($User_ID)) return false;

    $db->query("DELETE FROM `Auth_UserRelation`
              WHERE `User_ID` IN (".join(',', $User_ID).") 
              OR `Related_ID` IN (".join(',', $User_ID).") ");

    return true;
}

/**
 * Получить url для добавления в друзья пользователя
 * @param int номер пользоваетеля
 * @return string url
 */
function nc_auth_add_to_friend_url($User_ID) {
    $rel_url = nc_module_path('auth');
    $rel_url .= "add_relation.php?redirect_url=".htmlspecialchars($_SERVER['REQUEST_URI'])."&amp;";
    $rel_url .= "user_".intval($User_ID)."=".NC_AUTH_FRIEND;

    return $rel_url;
}

/**
 * Получить url для добавления во враги пользователя
 * @param int номер пользоваетеля
 * @return string url
 */
function nc_auth_add_to_banned_url($User_ID) {
    $rel_url = nc_module_path('auth');
    $rel_url .= "add_relation.php?redirect_url=".htmlspecialchars($_SERVER['REQUEST_URI'])."&amp;";
    $rel_url .= "user_".intval($User_ID)."=".NC_AUTH_BANNED;

    return $rel_url;
}

/**
 * Получить url для удаления пользоваетля из врагов/друзей
 * @param int номер пользоваетеля
 * @return string url
 */
function nc_auth_drop_rel_url($User_ID) {
    $rel_url = nc_module_path('auth');
    $rel_url .= "add_relation.php?redirect_url=".htmlspecialchars($_SERVER['REQUEST_URI'])."&amp;";
    $rel_url .= "user_".intval($User_ID)."=-1";

    return $rel_url;
}
?>