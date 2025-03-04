<?php
$NETCAT_FOLDER = realpath(__DIR__ . '/../../../') . DIRECTORY_SEPARATOR;
require_once $NETCAT_FOLDER . 'vars.inc.php';
require $ROOT_FOLDER . 'connect_io.php';
require_once nc_module_folder('captcha') . 'function.inc.php';

/** @var nc_Core $nc_core */

if ($nc_core->input->fetch_get('nc_get_new_captcha')) {
    $provider = nc_captcha::get_instance()->get_provider();
    echo $provider->get_new_challenge_data();
    exit;
}
