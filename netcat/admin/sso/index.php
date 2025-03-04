<?php

/**
 * Переадресация на sitemanager.ru для получения auth code (первый этап)
 *
 * Параметры:
 *  - return_path − адрес для переадресации после успешного входа
 */

require '../../connect_io.php';
$nc_core = nc_core::get_object();

if (empty($AUTH_USER_ID)) {
    try {
        $return_path = $nc_core->input->fetch_post_get('return_path');
        nc_sitemanager_auth::get_instance()->make_auth_code_request($return_path);
    } catch (nc_sitemanager_exception $e) {
        trigger_error($e->getMessage(), E_USER_WARNING);
    }
}

header("Location: $nc_core->ADMIN_PATH");
exit;

