<?php

ob_start();
require '../../require/cron_api.inc.php';

$ADMIN_FOLDER = nc_core::get_object()->ADMIN_FOLDER;

$nc_core = nc_core::get_object();

$ADMIN_FOLDER = $nc_core->ADMIN_FOLDER;
require $ADMIN_FOLDER . 'lang/Russian_utf8.php';
require $ADMIN_FOLDER . 'patch/function.inc.php';
require $ADMIN_FOLDER . 'install.inc.php';
require $ADMIN_FOLDER . 'tar.inc.php';

$nc_core->init_admin_mode();
$nc_core->modules->load_env();

try {
    $installer = new nc_patch_installer();
    $installer->set_mode($installer::MODE_AUTOMATED);

    $result = $installer->install_from_server();
} catch (Exception $e) {
    $result = array(
        'success' => false,
        'error' => $e->getMessage(),
    );
}

ob_end_clean();

header('Content-Type: application/json');
echo nc_array_json($result);
