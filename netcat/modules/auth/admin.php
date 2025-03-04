<?php

$NETCAT_FOLDER = join(strstr(__FILE__, "/") ? "/" : "\\", array_slice(preg_split("/[\/\\\]+/", __FILE__), 0, -4)).( strstr(__FILE__, "/") ? "/" : "\\" );
include_once $NETCAT_FOLDER . 'vars.inc.php';
require $ADMIN_FOLDER . 'function.inc.php';

// UI config
require_once $ADMIN_FOLDER . 'modules/ui.php';
require_once nc_module_folder('auth') . 'ui_config.php';

require_once nc_module_folder('auth') . 'nc_auth_admin.class.php';
$nc_auth_admin = new nc_auth_admin();

if (!$view) {
    $view = 'info';
}
$Title1 = NETCAT_MODULES;
$Title2 = NETCAT_MODULE_AUTH;

$AJAX_SAVER = !( $perm->isGuest() || $view == 'info' );

BeginHtml($Title2, $Title1, "http://".$DOC_DOMAIN."/settings/modules/auth/");
$perm->ExitIfNotAccess(array(NC_PERM_MODULE, 'auth'));
$UI_CONFIG = new ui_config_module_auth($view, '');

// имена методов для сохранения и показа
$method_show = $view."_show";
$method_save = $view."_save";

if (!is_callable(array($nc_auth_admin, $method_show)) || !is_callable(array($nc_auth_admin, $method_save))) {
    nc_print_status("Incorrect view: ".htmlspecialchars($view), 'error');
    exit;
}

// сохранение информации
if ($nc_core->input->fetch_get_post('act') === 'save') {
    try {
        $nc_auth_admin->$method_save();
        nc_print_status(NETCAT_MODULE_AUTH_ADMIN_SAVE_OK, 'ok');
    } catch (Exception $e) {
        nc_print_status($e->getMessage(), 'error');
    }
}

// показ какой-либо формы
$nc_auth_admin->$method_show();

EndHtml();
