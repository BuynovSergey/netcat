<?php

$NETCAT_FOLDER = join(strstr(__FILE__, "/") ? "/" : "\\", array_slice(preg_split("/[\/\\\]+/", __FILE__), 0, -4)) . (strstr(__FILE__, "/") ? "/" : "\\");
require_once $NETCAT_FOLDER . 'vars.inc.php';

global $nc_core, $nc_auth, $nc_auth_fb, $nc_auth_vk, $nc_auth_openid, $nc_auth_oauth, $nc_auth_twitter;

// функционал друзей-врагов
require_once __DIR__ . '/nc_auth.relation.php';
// require needed classes
require_once __DIR__ . '/nc_auth.php';
$nc_core->register_class_autoload_path('nc_auth_', __DIR__ . '/classes');

nc_auth_listener::init();

$nc_auth = nc_auth::get_object();
$nc_auth_fb = new nc_auth_provider_fb();
$nc_auth_vk = new nc_auth_provider_vk();
$nc_auth_twitter = new nc_auth_provider_twitter();
$nc_auth_openid = new nc_auth_provider_openid();
$nc_auth_oauth = new nc_auth_provider_oauth();

// для совместимости
$MODULE_VARS['auth']['USER_PREMODERATION'] = $nc_core->get_settings('premoderation', 'auth');
$MODULE_VARS['auth']['USER_CONFIRMATION'] = $nc_core->get_settings('confirm', 'auth');
$MODULE_VARS['auth']['USER_GROUP'] = $nc_core->get_settings('group', 'auth');
$MODULE_VARS['auth']['USER_REG_NOTIFY'] = $nc_core->get_settings('notify_admin', 'auth');
$MODULE_VARS['auth']['USER_MESSAGES_CLASS'] = $nc_core->get_settings('pm_class_id', 'auth');
$MODULE_VARS['auth']['USER_MODIFY_SUB'] = $nc_core->get_settings('modify_sub', 'auth');
$MODULE_VARS['auth']['USER_LIST_CC'] = $nc_core->get_settings('user_list_cc', 'auth');
$MODULE_VARS['auth']['USER_BIND_TO_CATALOGUE'] = $nc_core->get_settings('bind_to_catalogue', 'auth');
$MODULE_VARS['auth']['USER_ONLINE_TIME_LEFT'] = $nc_core->get_settings('online_timeleft', 'auth');
$MODULE_VARS['auth']['IP_CHECK_LEVEL'] = $nc_core->get_settings('ip_check_level', 'auth');
$MODULE_VARS['auth']['COOKIES_WITH_SUBDOMAIN'] = $nc_core->get_settings('with_subdomain', 'auth');
$MODULE_VARS['auth']['PERSONAL_MESSAGES_ENABLED'] = $nc_core->get_settings('pm_allow', 'auth');

function AttemptToAuthorize() {
    return nc_core::get_object()->user->attempt_to_authorize();
}

/* ======= USER FUNCTIONS ======= */

/**
 * Получение пути к странице "Регистрация пользователя"
 *
 * @param integer $catalogue_id ID сайта (optional)
 * @param boolean $with_cc_url Вернуть путь к инфоблоку, а не к разделу
 * @return string|boolean $with_cc_url путь к разделу от корня
 */
function nc_auth_regform_url($catalogue_id = 0, $with_cc_url = true) {
    global $db, $SUB_FOLDER;

    $catalogue_id = (int)($catalogue_id ?: $GLOBALS['catalogue']);
    $additional_column = ($with_cc_url ? 'CONCAT(sub.Hidden_URL, cc.EnglishName, ".html")' : 'sub.Hidden_URL');

    $result = (array)$db->get_row(
        "SELECT sub.Subdivision_ID,
                cc.Sub_Class_ID,
                {$additional_column}
		 FROM Subdivision as sub, Sub_Class as cc, Class as class
		 WHERE class.System_Table_ID = 3
         AND class.Class_ID = cc.Class_ID
         AND cc.Catalogue_ID = '{$catalogue_id}'
         AND cc.DefaultAction = 'add'
         AND cc.Checked = 1
         AND cc.Subdivision_ID = sub.Subdivision_ID
         LIMIT 1", ARRAY_N);

    if ($result) {
        list($folder_id, $infoblock_id, $url) = $result;
        if (nc_module_check_by_keyword('routing')) {
            return $with_cc_url
                ? nc_routing::get_infoblock_path($infoblock_id)
                : nc_routing::get_folder_path($folder_id);
        } else {
            return $SUB_FOLDER . $url;
        }
    }

    return false;
}

/**
 * url адрес на профайл пользователя
 *
 * @param int|int[] $User_ID Идентификатор(ы) пользователя (optional)
 * @param boolean $allow_keyword Учитывать keyword пользователя или нет (optional)
 * @param boolean $sub_url Вернуть путь к разделу с профайлами
 *
 * @return string|false путь к профайлу от корня
 */
function nc_auth_profile_url($User_ID = 0, $allow_keyword = false, $sub_url = false) {
    $nc_core = nc_Core::get_object();

    $db = $nc_core->db;
    $catalogue = $nc_core->catalogue->get_current('Catalogue_ID');
    $SUB_FOLDER = $nc_core->SUB_FOLDER;

    static $keywords = array(); // массив с keyword'ами пользователей
    static $infoblock_id; // ID инфоблока с профилями на сайте

    // нужно узнать ключевые слова пользователей
    if ($allow_keyword && empty($keywords)) {
        $res = $db->get_results("SELECT `User_ID`, `Keyword` FROM `User` WHERE `Keyword` <> ''", ARRAY_A, 'User_ID');
        $keywords = $res ?: $keywords;
    }

    // номера компонентов в разделе с пользователями
    $UserListCc = $nc_core->get_settings('user_list_cc', 'auth');

    if (!$UserListCc) {
        return false;
    }

    if (!$infoblock_id) {
        $infoblock_id = $db->get_var(
            'SELECT sc.Sub_Class_ID
             FROM Sub_Class AS sc
             WHERE sc.Catalogue_ID = ' . (int)$catalogue . '
             AND sc.Sub_Class_ID IN (' . $UserListCc . ')'
        );
    }

    if (!$infoblock_id) {
        return false;
    }

    $infoblock_data = $nc_core->sub_class->get_by_id($infoblock_id);

    if ($sub_url) {
        return nc_folder_path($infoblock_data['Subdivision_ID']);
    }

    if (nc_module_check_by_keyword('routing')) {
        $routing_object_parameters = array(
            'site_id'           => $catalogue,
            'folder'            => $infoblock_data['Hidden_URL'],
            'folder_id'         => $infoblock_data['Subdivision_ID'],
            'infoblock_id'      => $infoblock_id,
            'infoblock_keyword' => $infoblock_data['EnglishName'],
            'object_id'         => null,
            'object_keyword'    => null,
        );

        if ($User_ID) {
            $result = array();

            foreach ((array)$User_ID as $id) {
                $routing_object_parameters['object_id'] = $id;
                if ($allow_keyword && isset($keywords[$id]) && $keywords[$id]['Keyword']) {
                    $routing_object_parameters['object_keyword'] = $keywords[$id]['Keyword'];
                }
                $result[] = nc_routing::get_object_path('User', $routing_object_parameters);
            }

            return (is_array($User_ID) ? $result : $result[0]);
        } else {
            return preg_replace('/\.html$/', '_', nc_infoblock_path($infoblock_id));
        }
    } else { // модуль роутинга не используется
        $folder_path = $SUB_FOLDER . nc_array_value($infoblock_data, 'Hidden_URL');
        if ($User_ID) {
            $result = array();

            foreach ((array)$User_ID as $id) {
                if ($allow_keyword && isset($keywords[$id]) && $keywords[$id]['Keyword']) {
                    $result[] = $folder_path . $keywords[$id]['Keyword'] . '.html';
                } else {
                    $result[] = $folder_path . $infoblock_data['EnglishName'] . '_' . $id . '.html';
                }
            }

            return (is_array($User_ID) ? $result : $result[0]);
        } else {
            return $folder_path . '_';
        }
    }
}

/**
 * пользователи online
 *
 * @param mixed $template Шаблон вывода списка пользователей или режим вывода (optional)
 * @param string $select_fields Альтернативное поле имени пользователя (optional)
 * @return mixed
 */
function nc_auth_users_online($template = null, $select_fields = null) {
    global $nc_core, $catalogue, $ADMIN_AUTHTIME;

    $result = null;
    $TimeLeft = ($ADMIN_AUTHTIME ? time() + $ADMIN_AUTHTIME : strtotime('+1 day')) - $nc_core->get_settings('online_timeleft', 'auth');
    $tags = array('%NAME', '%URL');
    $tags2 = array('%GUESTS', '%REGISTERED', '%ONLINE');
    $select_fields = ($select_fields ? $select_fields . ', ' : " IF(u.`Name` <> '', u.`Name`, u.`Email`) AS Name, ");
    $query_where_cat = $nc_core->get_settings('bind_to_catalogue', 'auth') ? 'Catalogue_ID IN (0, ' . (int)$catalogue . ')' : "";
    $auth_profile_url = nc_auth_profile_url();

    switch ($template) {
        case ARRAY_N:
            return (array)$nc_core->db->get_col(
                'SELECT User_ID
				 FROM Session
				 WHERE User_ID != 0
				 AND SessionTime > ' . $TimeLeft . ($query_where_cat ? ' AND ' . $query_where_cat : "") . '
				 GROUP BY User_ID
				 ORDER BY User_ID'
            );
            break;

        case ARRAY_A:
            return (array)$nc_core->db->get_results(
                "SELECT u.User_ID, {$select_fields} CONCAT('{$auth_profile_url}', u.User_ID, '.html') AS `Url`
				 FROM User AS u
				 INNER JOIN Session AS s
				 ON u.User_ID = s.User_ID
				 WHERE s.SessionTime > " . $TimeLeft . ($query_where_cat ? ' AND s.' . $query_where_cat : "") . '
				 GROUP BY u.User_ID
				 ORDER BY Name',
                ARRAY_A
            );
            break;
    }

    if (!$template) {
        $template = array(
            'prefix'  => '',
            'suffix'  => '',
            'divider' => ', ',
            'link'    => "<a href='%URL'>%NAME</a>"
        );
    }

    $Guests = $nc_core->db->get_var(
        'SELECT COUNT(Session_ID)
		 FROM Session
		 WHERE User_ID = 0 AND SessionTime > ' . $TimeLeft . ($query_where_cat ? ' AND ' . $query_where_cat : "")
    );

    if (is_array($template)) {
        $OnlineUsers = (array)$nc_core->db->get_results(
            "SELECT IF(u.`Name` <> '', u.`Name`, u.`Email`) AS Name, CONCAT('{$auth_profile_url}', u.User_ID, '.html') AS `Url`
			 FROM User AS u
			 INNER JOIN Session AS s
			 ON u.User_ID = s.User_ID
			 WHERE s.User_ID != 0
			 AND s.SessionTime > " . $TimeLeft . ($query_where_cat ? ' AND s.' . $query_where_cat : "") . '
			 GROUP BY u.User_ID
			 ORDER BY Name',
            ARRAY_A
        );

        if ($Registered = count($OnlineUsers)) {
            if ($template['link']) {
                foreach ($OnlineUsers as $user) {
                    $result[] = str_replace($tags, $user, $template['link']);
                }
                $result = implode(($template['divider'] ?: ''), $result);
            }
            $result = ($template['prefix'] ?: '') . $result . ($template['suffix'] ?: '');
            $result = str_replace($tags2, array($Guests, $Registered, $Guests + $Registered), $result);
        }
    } else {
        $Registered = count($nc_core->db->get_results(
            'SELECT User_ID
			 FROM Session
			 WHERE User_ID != 0
			 AND SessionTime > ' . $TimeLeft . ($query_where_cat ? ' AND ' . $query_where_cat : "") . '
			 GROUP BY User_ID',
            ARRAY_N
        ));
        $result = str_replace($tags2, array($Guests, $Registered, $Guests + $Registered), $template);
    }


    return $result;
}

/**
 * проверка статуса пользователя
 *
 * @param int $User_ID Идентификатор пользователя
 * @return bool
 */
function nc_auth_is_online($User_ID) {
    static $online;

    if (!is_array($online)) {
        $online = nc_auth_users_online(ARRAY_N);
    }

    return is_array($online) && in_array($User_ID, $online);
}

/**
 * Количество новых сообщений для текущего или заданного пользователя
 *
 * @param int $User_ID Идентификатор пользователя (optional)
 * @param int $Sub_Class_ID Идентификатор инфоблока (optional)
 * @return int
 */
function nc_auth_messages_new($User_ID = 0, $Sub_Class_ID = 0) {
    global $db, $current_user, $catalogue;
    global $MODULE_VARS;

    if (!$current_user) {
        return false;
    }

    if (!$User_ID) {
        $User_ID = $current_user['User_ID'];
    }

    $User_ID = (int)$User_ID;
    $catalogue = (int)$catalogue;
    if (!isset($MODULE_VARS['auth']['UserMessagesNew'])) {
        $MODULE_VARS['auth']['UserMessagesNew'] = $db->get_var(
            'SELECT COUNT(m.Message_ID)
			 FROM `Message' . (int)nc_Core::get_object()->get_settings('pm_class_id', 'auth') . '` AS m
			 RIGHT JOIN Sub_Class AS s
			 ON m.Sub_Class_ID = s.Sub_Class_ID
			 WHERE m.Status = 0
			 AND m.ToUser = "' . $User_ID . '"
			 AND s.Catalogue_ID = ' . $catalogue . ($Sub_Class_ID ? ' AND m.Sub_Class_ID = ' . (int)$Sub_Class_ID : "")
        );
    }

    return $MODULE_VARS['auth']['UserMessagesNew'];
}

/**
 * URL Адрес страницы с сообщениями / адрес отправки сообщения
 *
 * @param int $User_ID Идентификатор пользователя (получателя) для отправки сообщения (optional)
 * @param int $Sub_Class_ID Идентификатор компонента личных сообщений в разделе (optional)
 * @return string
 */
function nc_auth_messages_url($User_ID = 0, $Sub_Class_ID = 0) {
    global $catalogue;
    static $default_infoblock_id = false;
    $nc_core = nc_core::get_object();

    if (!$Sub_Class_ID && $default_infoblock_id === false) {
        $default_infoblock_id = $nc_core->db->get_var(
            'SELECT sc.Sub_Class_ID
             FROM Sub_Class AS sc
             WHERE sc.Class_ID = ' . $nc_core->get_settings('pm_class_id', 'auth') . '
             AND sc.Catalogue_ID = ' . (int)$catalogue . '
             LIMIT 1'
        );
    }

    $infoblock_id = ($Sub_Class_ID ?: $default_infoblock_id);

    if (!$infoblock_id) {
        return '';
    }

    if ($User_ID) {
        return nc_infoblock_path($infoblock_id, 'add', 'html', null, array('uid' => $User_ID));
    }

    return nc_folder_path($nc_core->sub_class->get_by_id($infoblock_id, 'Subdivision_ID'));
}

function nc_auth_time_left() {
    global $ADMIN_AUTHTIME;
    return ($ADMIN_AUTHTIME ? time() + $ADMIN_AUTHTIME : strtotime('+1 day')) - nc_Core::get_object()->get_settings('online_timeleft', 'auth');
}

/** DEPRECATED FUNCTIONS */
function nc_auth_openid_field_exist() {
    return false;
}

function nc_auth_openid_possibility() {
    return false;
}

function nc_auth_get_settings($item = '') {
    return nc_Core::get_object()->get_settings($item, 'auth');
}

