<?php

$NETCAT_FOLDER = realpath(__DIR__ . '/../../../') . '/';
require $NETCAT_FOLDER . 'vars.inc.php';
require $ROOT_FOLDER . 'connect_io.php';
require_once nc_module_folder('captcha') . 'function.inc.php';

if (preg_match('/^[a-f0-9]{32}$/i', $_GET['code'])) {
    $code = $_GET['code'];
} else {
    $code = '';
}

while (ob_get_level() && @ob_end_clean()) {
    continue;
}

$captcha = new nc_captcha_provider_image();
if (function_exists('imagegif')) {
    header("Content-Encoding: identity");
    header("Content-Type: image/gif");
    $captcha->output_image($code);
    ob_flush();
} else {
    header('Content-Type: text/plain');
    echo 'Can\'t generate CAPTCHA image: GD Library with GIF support is not installed.';
}

// Удаление протухших каптч
$captcha->delete_expired_challenges();
