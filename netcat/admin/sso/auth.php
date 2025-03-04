<?php

/**
 * OAuth-аутентификация с использованием учётной записи на sitemanager.ru, второй шаг
 * (получение auth code от sitemanager.ru и обмен его на auth token)
 *
 * Входящие параметры:
 *  − code
 *  − state
 */

require '../../connect_io.php';
$nc_core = nc_core::get_object();

try {
    $sitemanager_auth = nc_sitemanager_auth::get_instance();

    $code = $nc_core->input->fetch_get_post('code');
    $state = $nc_core->input->fetch_get_post('state');
    $session_hash = $nc_core->input->fetch_get_post('session_hash');

    // (1) Обменять код на токен
    $result = $sitemanager_auth->make_auth_token_request($code, $state, $session_hash);

    if ($result) {
        // (2) Войти и выполнить переадресацию
        $sitemanager_auth->log_in();
    }
} catch (nc_sitemanager_exception $e) {
    trigger_error($e->getMessage(), E_USER_WARNING);
}

header("Location: $nc_core->ADMIN_PATH");
exit;
