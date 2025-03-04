<?php

ob_start();

$NETCAT_FOLDER = join(strstr(__FILE__, "/") ? "/" : "\\", array_slice(preg_split("/[\/\\\]+/", __FILE__), 0, -4)).( strstr(__FILE__, "/") ? "/" : "\\" );
include_once ($NETCAT_FOLDER."vars.inc.php");

require_once ($ROOT_FOLDER."connect_io.php");
require_once ($ADMIN_FOLDER."admin.inc.php");

$url = $parsed_url['path'];
$url = str_replace($nc_core->SUB_FOLDER, '', $url);

$current_catalogue = $nc_core->catalogue->get_by_host_name($_SERVER['HTTP_HOST']);

if ($current_catalogue && (!isset($sub) || !(int)$sub)) {
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

require_once ($INCLUDE_FOLDER."index.php");

$id = intval($id);
$code = $db->escape($code);
$nc_user_confirm = 0;

if ($id && $code) {
    $IsChecked = ( $nc_core->get_settings('premoderation', 'auth') ) ? 0 : 1;

    // подтверждение пользователя
    $res = $db->query("UPDATE `User`
		SET `Confirmed` = '1', `RegistrationCode` = ''".($IsChecked ? ", `Checked` = '".$IsChecked."'" : "")."
		WHERE `RegistrationCode` = '".$code."' AND `User_ID` = '".$id."'");
    $nc_user_confirm = $db->rows_affected;
    unset($res);

    // оповещение администратора
    if ($nc_user_confirm && $nc_core->get_settings('notify_admin', 'auth')) {
        $mailer = new CMIMEMail();
        $mailinfo = $nc_auth->get_notify_admin_mail($id);
        $mailer->mailbody(strip_tags($mailinfo['body']), $mailinfo['html'] ? $mailinfo['body'] : "");
        $mailer->send($nc_core->get_settings('admin_notification_email', 'auth') ?: $system_env['NotificationEmail'], $system_env['SpamFromEmail'], $system_env['SpamFromEmail'], $mailinfo['subject'], $system_env['SpamFromName']);
    }

    // если пользователь включен и стоит опция "авторизация после подтверждения"
    if ($nc_user_confirm && $IsChecked && $nc_core->get_settings('autoauthorize', 'auth')) {
        Authorize($id, 'authorize');
    }
}

if ($nc_user_confirm) { // успешное подтверждение
    $CheckActionTemplate = $db->get_var("SELECT `CheckActionTemplate` FROM `Class`
                                       WHERE `System_Table_ID`=3 AND `ClassTemplate` = 0 ");

    if ($CheckActionTemplate) eval(nc_check_eval("echo \"".$CheckActionTemplate."\";"));

    if ($nc_core->get_settings('confirm_after_mail', 'auth')) {
        $fromname = $nc_core->get_settings('SpamFromName');
        $fromemail = $nc_core->get_settings('SpamFromEmail');
        $EmailField = $nc_core->get_settings('UserEmailField');

        $mail_info = $nc_auth->get_confirm_after_mail($id);

        $mail_body = $nc_core->mail->attachment_attach($mail_info['body'], 'auth_confirm_after_' . $catalogue);
        $nc_core->mail->mailbody(strip_tags($mail_body), $mail_info['html'] ? $mail_body : '');
        $nc_core->mail->send($mail_info['user_email'], $fromemail, $fromemail, $mail_info['subject'], $fromname);
    }

    $after_msg = output_auth_field('confirm_after', $File_Mode);
    echo $after_msg ? $after_msg : "<div id='confirm_after'>" . NETCAT_MODULE_AUTH_REG_OK . "</div>";
    if (!$IsChecked) {
        echo "<div>" . NETCAT_MODULE_AUTH_REG_ADMIN . "</div>";
    }
    
} elseif (!$id || !$code) { // неправильная ссылка
    $warnText = NETCAT_MODULE_AUTH_REG_INVALIDLINK;
} elseif ($db->get_var("SELECT COUNT(*) FROM `User` WHERE `User_ID` = '" . $id . "' AND `RegistrationCode` = ''")) {
    // пользователь уже активирован
    echo "<div>".NETCAT_MODULE_AUTH_REG_AGAIN."</div>"; 
    if (!$IsChecked && $db->get_var("SELECT `Checked` FROM `User` WHERE `User_ID` = '" . $id . "'")) {
        $IsChecked = 1;
    }
    if (!$IsChecked) {
        echo "<div>".NETCAT_MODULE_AUTH_REG_ADMIN."</div>";
    }
} else { // пользователь не найден
    $warnText = NETCAT_MODULE_AUTH_REG_ERROR;
}

if ($warnText) {
    $warn_msg = output_auth_field('confirm_after_warn', $File_Mode);
    echo $warn_msg ? $warn_msg : "<div id='confirm_warn'>$warnText</div>";
}

$nc_result_msg = ob_get_clean();

if ($File_Mode) {
    require_once $INCLUDE_FOLDER.'index_fs.inc.php';
} else {
    nc_evaluate_template($template_header, $template_footer, false);
}

$nc_core->output_page($template_header, $nc_result_msg, $template_footer);

function output_auth_field($field, $file_mode) {
    global $warnText;
    ob_start();

    if ($file_mode) {
        $auth_view = new nc_tpl_module_view();
        $auth_view->load('auth', nc_core()->get_interface());
        $field_path = $auth_view->get_field_path($field);
        if (file_get_contents($field_path)) {
            include($field_path);
        }
    }
    else {
        eval(nc_check_eval("echo \"" . nc_core()->get_settings($field, 'auth') . "\";"));
    }

    return ob_get_clean();
}