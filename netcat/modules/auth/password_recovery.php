<?php

$NETCAT_FOLDER = join(strstr(__FILE__, "/") ? "/" : "\\", array_slice(preg_split("/[\/\\\]+/", __FILE__), 0, -4)) . ( strstr(__FILE__, "/") ? "/" : "\\" );
include_once ($NETCAT_FOLDER . "vars.inc.php");
require_once ($ROOT_FOLDER . "connect_io.php");

$url = $parsed_url['path'] . (!empty($parsed_url['query']) ? '?' . $parsed_url['query'] : '');
$url = str_replace($nc_core->SUB_FOLDER, '', $url);

$current_catalogue = $nc_core->catalogue->get_by_host_name($_SERVER['HTTP_HOST']);
if ($current_catalogue) {
    $catalogue = $current_catalogue['Catalogue_ID'];
    $sub = $db->get_var(
        "SELECT `Subdivision_ID`
           FROM `Subdivision`
          WHERE `Catalogue_ID` = $current_catalogue[Catalogue_ID]
            AND `ExternalURL` LIKE '%" . $db->escape($url) . "%'"
    );
    if (!$sub && !isset($template)) {
        $template = $current_catalogue['Template_ID'];
        $nc_use_site_template_settings = 1;
    }
}

if ((!isset($sub) || !(int)$sub) && isset($_GET['sub'])) {
    $sub = (int)$_GET['sub'];
}

require_once($INCLUDE_FOLDER . "index.php");
require_once($ADMIN_FOLDER . "admin.inc.php");

if ($File_Mode) {
    $auth_view = new nc_tpl_module_view();
    $auth_view->load('auth', $nc_core->get_interface());
}

ob_start();

do {
    // check if user is a guest (deny password recovery then)
    if ($perm instanceof Permission && $perm->isGuest()) {
        $warnText = NETCAT_MODERATION_ERROR_NORIGHT;
        if ($File_Mode) {
            echo output_auth_field('recovery_password_warn', $auth_view);
        } else {
            eval(nc_check_eval("echo \"" . $nc_core->get_settings('recovery_password_warn', 'auth') . "\";"));
        }
        break;
    }

    // самостоятельное восстановление пароля запрещено
    if ($nc_core->get_settings('deny_recovery', 'auth')) {
        if ($File_Mode) {
            echo output_auth_field('recovery_password_deny', $auth_view);
        } else {
            eval(nc_check_eval("echo \"" . $nc_core->get_settings('recovery_password_deny', 'auth') . "\";"));
        }        
        break;
    }

    // показ формы изменения пароля
    if (isset($_GET['uid']) && isset($_GET['ucc'])) {
        $uid = (int) $_GET['uid'];
        $confirm_code = $db->get_var("SELECT `RegistrationCode` FROM User WHERE User_ID = '" . $uid . "'");

        if ($confirm_code == md5($_GET['ucc'] . ';-$')) {
            echo $nc_auth->change_password_form();
        } else {
            $warnText = NETCAT_MODULE_AUTH_NEWPASS_ERROR;
            if ($File_Mode) {
                echo output_auth_field('recovery_password_warn', $auth_view);
            } else {
                eval(nc_check_eval("echo \"" . $nc_core->get_settings('recovery_password_warn', 'auth') . "\";"));
            }
        }
        break;
    }

    $fromname = $system_env['SpamFromName'];
    $fromemail = $system_env['SpamFromEmail'];
    $EmailField = $system_env['UserEmailField'];

    // нет поля для Email
    if (!$EmailField) {
        $warnText = NETCAT_MODULE_AUTH_ERR_NOFIELDSET;
        if ($File_Mode) {
            echo output_auth_field('recovery_password_warn', $auth_view);
        } else {
            eval(nc_check_eval("echo \"" . $nc_core->get_settings('recovery_password_warn', 'auth') . "\";"));
        }
        break;
    }

    $warnText = '';
    /**
     * Коды ошибок:
     * 1 - поля не заполнены
     * 2 - пользователь не найден
     */
    $nc_error_num = 0;
    $nc_err_text[1] = NETCAT_MODULE_AUTH_MSG_FILLFIELD;
    $nc_err_text[2] = NETCAT_MODULE_AUTH_ERR_NOUSERFOUND;
    $nc_err_text[3] = NETCAT_MODULE_AUTH_MSG_BADEMAIL;
    // проверки
    if (!empty($post)) {
        $Login = $db->escape($Login);
        $Email = $db->escape($Email);
        if (!isset($_POST['Login']) && !$Email) {
            $nc_error_num = 3;
        } else if (!$Login && !$Email) {
            $nc_error_num = 1;
        } else if ($Email && !preg_match('/^([a-z0-9_\.-]+)@([a-z0-9_\.-]+)\.([a-z\.]{2,6})$/i', $Email)) {
            $nc_error_num = 3;
        } else {
            // поиск пользователя
            $res = $db->get_row("SELECT `User_ID`, `" . $EmailField . "`, `" . $AUTHORIZE_BY . "`
                         FROM `User`
                         WHERE `Checked` = '1' AND ( 0
                         " . ( $Email ? "OR `" . $EmailField . "`='" . $Email . "'" : "") . "
                         " . ( $Login ? "OR `" . $AUTHORIZE_BY . "`='" . $Login . "'" : "") . "
                         )", ARRAY_N);
            if (!$res) {
                $nc_error_num = 2;
            } else {
                list($UserID, $UserEmail, $UserLogin) = $res;
            }
        }

        if ($nc_error_num)
            $post = 0;
    }

    // показ формы заполнения
    if (empty($post)) {
        if ($nc_error_num) {
            $warnText = $nc_err_text[$nc_error_num];
            
            if ($File_Mode) {
                echo output_auth_field('recovery_password_warn', $auth_view);
            } else {
                eval(nc_check_eval("echo \"" . $nc_core->get_settings('recovery_password_warn', 'auth') . "\";"));
            }
        }
        echo $nc_auth->recovery_password_form();
    } else {
        // old: sha1(uniqid(time() - rand()));
        $confirm_code = md5( sha1( $nc_core->token->seed() ) . $nc_core->get_settings('SecretKey') );
        
        $mail_info = $nc_auth->get_recovery_mail($UserID, $confirm_code);

        $db->query("UPDATE `User` SET `RegistrationCode` = '" . md5($confirm_code . ';-$') . "' WHERE `User_ID` = '" . $UserID . "'");

        $mail_body = $nc_core->mail->attachment_attach($mail_info['body'], 'auth_recovery_' . $catalogue);
        $nc_core->mail->mailbody(strip_tags($mail_body), $mail_info['html'] ? $mail_body : '');
        $nc_core->mail->send($UserEmail, $fromemail, $fromemail, $mail_info['subject'], $fromname);

        if ($File_Mode) {
            echo output_auth_field('recovery_password_after', $auth_view);
        } else {
            eval(nc_check_eval("echo \"" . $nc_core->get_settings('recovery_password_after', 'auth') . "\";"));
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
        include($field_path);
        return ob_get_clean(); 
    }
    
    return '';
}
