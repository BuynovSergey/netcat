<?php

/**
 * Установка патча из консоли
 *
 * console.php [--json] [/local/file/path.tgz]
 *
 * Аргументы командной строки:
 * --json: если указан, выведет результат установки патча в виде JSON
 * полный путь к файлу: если указан, будет установлен патч из указанного файла на локальном диске
 *
 * Если файл патча не указан, то скачивает и устанавливает обновления с сервера обновлений.
 */

ob_start();

require __DIR__ . '/../../require/console_api.inc.php';

$nc_core = nc_core::get_object();

$ADMIN_FOLDER = $nc_core->ADMIN_FOLDER;
require $ADMIN_FOLDER . 'lang/Russian_utf8.php';
require $ADMIN_FOLDER . 'patch/function.inc.php';
require $ADMIN_FOLDER . 'install.inc.php';
require $ADMIN_FOLDER . 'tar.inc.php';

$nc_core->init_admin_mode();
$nc_core->modules->load_env();

$file = false;
$args = array_slice($_SERVER['argv'] ?: array(), 1);
foreach ($args as $arg) {
    $file = realpath($arg);
    if ($file) {
        break;
    }
}

try {
    $installer = new nc_patch_installer();
    $installer->set_mode($installer::MODE_AUTOMATED);

    if ($file) {
        $result = $installer->install_from_file($file);
    } else {
        $result = $installer->install_from_server();
    }
} catch (Exception $e) {
    $result = array(
        'success' => false,
        'error' => $e->getMessage(),
    );
}

ob_end_clean();

if (in_array('--json', $args, true)) {
    exit(nc_array_json($result));
}

$status = $result['success'] ? 'OK' : 'ERROR';
$messages = nc_array_value($result, 'error') . "\n\n" . nc_array_value($result, 'messages');
echo "$status\n\n" . trim(strip_tags($messages)) . "\n";
exit($result['success'] ? 0 : 1);
