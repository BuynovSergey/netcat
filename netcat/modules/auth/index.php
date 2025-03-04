<?php

ob_start();

$template_header = '';
$template_footer = '';

/**
 * @var nc_auth_provider_vk $nc_auth_vk
 * @var nc_auth_provider_fb $nc_auth_fb
 * @var nc_auth_provider_twitter $nc_auth_twitter
 * @var nc_auth_provider_openid $nc_auth_openid
 * @var nc_auth_provider_oauth $nc_auth_oauth
 * @var nc_Db $db
 */

do {

    // Данная константа проверяется в index.php для предотвращения вывода
    // сообщения "Нет прав для осуществления операции" из require/index.php
    // в случае, если у запрошенного шаблона установлены права "только для
    // зарегистрированных" и т.п.
    define('NC_AUTH_IN_PROGRESS', 1);
    $NETCAT_FOLDER = join(strstr(__FILE__, "/") ? "/" : "\\", array_slice(preg_split("/[\/\\\]+/", __FILE__), 0, -4)) . (strstr(__FILE__, "/") ? "/" : "\\");

    include_once $NETCAT_FOLDER . 'vars.inc.php';
    require_once $ROOT_FOLDER . 'connect_io.php';

    $url = $parsed_url['path'] . ($parsed_url['query'] ? '?' . $parsed_url['query'] : '');
    $redirect_to = nc_get_scheme() . '://' . $HTTP_HOST . $REQUESTED_FROM;

    $url = trim(str_replace($nc_core->SUB_FOLDER, '', $_SERVER['PHP_SELF']), '/');
    $current_catalogue = $nc_core->catalogue->get_by_host_name($_SERVER['HTTP_HOST']);
    if ($current_catalogue) {
        $catalogue = $current_catalogue['Catalogue_ID'];
        $sub = $db->get_var(
            "SELECT `Subdivision_ID`
               FROM `Subdivision`
              WHERE `Catalogue_ID` = {$catalogue}
                AND `ExternalURL` LIKE '%" . $db->escape($url) . "%'"
        );
        if (!$sub && !isset($template)) {
            $template = $current_catalogue['Template_ID'];
            $nc_use_site_template_settings = 1;
        }
    }

    if ($logoff) {
        Unauthorize();
        $redirect = '/';
        if (!empty($REQUESTED_FROM)) {
            if (!empty($REQUESTED_BY) && $REQUESTED_BY === 'POST') {
                $redirect = nc_get_scheme() . '://' . $HTTP_HOST . $nc_core->SUB_FOLDER . '/';
            } else {
                $redirect = nc_get_scheme() . '://' . $HTTP_HOST . $REQUESTED_FROM;
            }
        }
        if ($redirect) {
            nc_check_redirect($redirect);
            exit;
        } else {
            printf(NETCAT_MODULE_AUTH_MSG_SESSION_CLOSED, $REQUESTED_FROM);
        }
        break;
    }

    try {
        require $INCLUDE_FOLDER . 'index.php';
    } catch (Exception $e) {
        // сайт не найден
    }

    // Авторизация вконтакте
    if ($nc_vk && $nc_auth_vk->enabled()) {
        $nc_auth_vk->set_redirect_to($redirect_to);
        $nc_auth_vk->process();
    }

    // Авторизация через facebook
    if ($nc_fb && $nc_auth_fb->enabled()) {
        $nc_auth_fb->set_redirect_to($redirect_to);
        $nc_auth_fb->process();
    }

    if ($nc_twitter && $nc_auth_twitter->enabled()) {
        $nc_auth_twitter->set_redirect_to($redirect_to);
        $nc_auth_twitter->process();
    }

    // запрос на OpenID авторизацию
    if ($openid_url && $nc_auth_openid->enabled()) {
        $nc_auth_openid->set_redirect_to($redirect_to);
        $nc_auth_openid->process(array('openid_identifier' => $openid_url));
    }

    // OAuth авторизация
    if ($nc_oauth && $nc_auth_oauth->enabled()) {
        $nc_auth_oauth->set_default_provider($nc_oauth);
        $nc_auth_oauth->set_redirect_to($redirect_to);
        $nc_auth_oauth->process();
    }

    // попытка авторизации
    if ($AuthPhase && !$_GET['openid_mode']) {
        $IsAuthorized = $nc_core->user->authorize_by_pass($AUTH_USER, $AUTH_PW, $nc_core->input->fetch_get_post('nc_captcha_code'));
    }

    if ((!$AuthPhase || !$IsAuthorized) && !$nc_core->get_settings('user_login_form_disable', 'auth')) {
        $nc_auth->login_form();
    } else {
        $redirect = nc_get_scheme() . '://' . $HTTP_HOST . $REQUESTED_FROM;
        $Password = $db->get_var('SELECT ' . $nc_core->MYSQL_ENCRYPT . "('{$AUTH_PW}')");
        if ($redirect) {
            nc_check_redirect($redirect);
            exit;
        } else {
            printf(NETCAT_MODULE_AUTH_MSG_AUTH_SUCCESS, $REQUESTED_FROM);
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
