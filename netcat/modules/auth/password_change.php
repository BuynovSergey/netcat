<?php
ob_start();

do {
    $NETCAT_FOLDER = join(strstr(__FILE__, "/") ? "/" : "\\", array_slice(preg_split("/[\/\\\]+/", __FILE__), 0, -4)) . (strstr(__FILE__, "/") ? "/" : "\\");
    include_once $NETCAT_FOLDER . 'vars.inc.php';
    require_once $ROOT_FOLDER . 'connect_io.php';
    require_once $ADMIN_FOLDER . 'admin.inc.php';

    /**
     * @var nc_Db $db
     * @var nc_auth $nc_auth
     */
    if (!isset($parsed_url['query'])) {
        $parsed_url['query'] = '';
    }
    $url = $parsed_url['path'] . ($parsed_url['query'] ? '?' . $parsed_url['query'] : '');
    $url = str_replace($nc_core->SUB_FOLDER, '', $url);

    $current_catalogue = $nc_core->catalogue->get_by_host_name($_SERVER['HTTP_HOST']);

    if ($current_catalogue && !isset($sub)) {
        $catalogue = $current_catalogue['Catalogue_ID'];
        if (empty($uid) && empty($ucc)) {
            $sub = $db->get_var(
                "SELECT `Subdivision_ID`
                   FROM `Subdivision`
                  WHERE `Catalogue_ID` = $current_catalogue[Catalogue_ID]
                    AND `ExternalURL` LIKE '%" . $db->escape($url) . "%'"
            );
        }
    }

    if ((!isset($sub) || !(int)$sub) && !isset($template)) {
        $template = $current_catalogue['Template_ID'];
        $nc_use_site_template_settings = 1;
    }

    require_once $INCLUDE_FOLDER . 'index.php';

    if ($File_Mode) {
        $auth_view = new nc_tpl_module_view();
        $auth_view->load('auth', $nc_core->get_interface());
    }

    $is_guest = false;
    /**
     * Коды ошибок:
     * 0 - нет ошибок
     * 1 - пароли не совпадают
     * 2 - пароль пустой
     * 3 - пароль слишком короткий
     * 4 - ссылка неправильная
     * 5 - нет прав
     */
    $nc_error_num = 0;
    $nc_err_text[1] = NETCAT_MODULE_AUTH_CHANGEPASS_NOTEQUAL;
    $nc_err_text[2] = CONTROL_USER_ERROR_EMPTYPASS;
    $nc_err_text[3] = NETCAT_MODERATION_MSG_PASSMIN;
    $nc_err_text[4] = NETCAT_MODULE_AUTH_NEWPASS_ERROR;
    $nc_err_text[5] = NETCAT_MODERATION_ERROR_NORIGHT;

    // определение пользователя
    if (!$AUTH_USER_ID && $uid && $ucc) {
        $confirm_code = $db->get_var("SELECT `RegistrationCode` FROM `User` WHERE `User_ID` = '" . (int)$uid . "'");
        if ($confirm_code === md5($ucc . ';-$')) {
            $AUTH_USER_ID = (int)$uid;
        }
    }
    if (!$AUTH_USER_ID) {
        $nc_error_num = 4;
    }

    $p = new Permission($AUTH_USER_ID);
    if ($p->isGuest()) {
        $nc_error_num = 5;
    }

    // если есть ошибки, то дальше продолжать нельзя
    if ($nc_error_num) {
        $warnText = $nc_err_text[$nc_error_num];
        if ($File_Mode) {
            echo output_auth_field('change_password_warn', $auth_view);
        } else {
            eval(nc_check_eval("echo \"" . $nc_core->get_settings('change_password_warn', 'auth') . "\";"));
        }
        break;
    }

    // обработка ошибок
    if (!empty($post)) {
        $pass_min = $nc_core->get_settings('pass_min', 'auth');
        if ($Password1 !== $Password2) {
            $nc_error_num = 1;
            $warnText = $nc_err_text[$nc_error_num];
        } else if (!$Password1) {
            $nc_error_num = 2;
            $warnText = $nc_err_text[$nc_error_num];
        } else if ($pass_min && nc_strlen($Password1) < $pass_min) {
            $nc_error_num = 3;
            $warnText = sprintf($nc_err_text[$nc_error_num], $pass_min);
        }
        if ($nc_error_num) {
            $post = 0;
        }
    }

    if (!empty($post) && !$nc_core->token->verify()) {
        $post = 0;
        $nc_error_num = 4;
        $warnText = NETCAT_TOKEN_INVALID;
    }

    if (empty($post)) {
        if ($nc_error_num) {
            if ($File_Mode) {
                echo output_auth_field('change_password_warn', $auth_view);
            } else {
                eval(nc_check_eval("echo \"" . $nc_core->get_settings('change_password_warn', 'auth') . "\";"));
            }
        }
        echo $nc_auth->change_password_form();
    } else {
        $nc_core->user->change_password($AUTH_USER_ID, $Password1, 1);
        if ($File_Mode) {
            echo output_auth_field('change_password_after', $auth_view);
        } else {
            eval(nc_check_eval("echo \"" . $nc_core->get_settings('change_password_after', 'auth') . "\";"));
        }
    }
} while (false);

$nc_result_msg = ob_get_clean();

if ($File_Mode) {
    require_once $INCLUDE_FOLDER . 'index_fs.inc.php';
} else {
    nc_evaluate_template($template_header, $template_footer, false);
}

$nc_core->output_page($template_header, $nc_result_msg, $template_footer);

function output_auth_field($field, $auth_view) {
    $field_path = $auth_view->get_field_path($field);
    global $warnText;

    if (file_get_contents($field_path)) {
        ob_start();
        include $field_path;
        return ob_get_clean();
    }

    return '';
}
