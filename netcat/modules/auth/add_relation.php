<?php

$NETCAT_FOLDER = join(strstr(__FILE__, "/") ? "/" : "\\", array_slice(preg_split("/[\/\\\]+/", __FILE__), 0, -4)) . (strstr(__FILE__, "/") ? "/" : "\\");
include_once($NETCAT_FOLDER . "vars.inc.php");
require_once($INCLUDE_FOLDER . "index.php");

// авторизация
if (!Authorize()) {
    return 0;
}

$input = array_merge((array)$_GET, (array)$_POST);

foreach ($input as $k => $v) {
    if (substr($k, 0, 4) == 'user') {
        $user_id = intval(substr($k, 5));
        if (!$user_id) {
            continue;
        }

        switch ($v) {
            case NC_AUTH_FRIEND: # добавить друга
                if ($nc_core->get_settings('friend_allow', 'auth')) {
                    nc_auth_delete_relation($user_id);
                    nc_auth_add_friend($user_id);
                }
                break;
            case NC_AUTH_BANNED: # добавить врага
                if ($nc_core->get_settings('banned_allow', 'auth')) {
                    nc_auth_delete_relation($user_id);
                    nc_auth_add_bann($user_id);
                }
                break;
            case -1:  #удалить
                nc_auth_delete_relation($user_id);
                break;
        }
    }
}
if ($input['redirect_url']) {
    ob_clean();
    nc_check_redirect($input['redirect_url']);
}
exit();