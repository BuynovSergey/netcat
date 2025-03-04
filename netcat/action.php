<?php

/**
 *
 * $_GET['ctrl'] - путь к классу контроллера относительно /netcat/
 * К примеру: ctrl = admin.widget.widget_block
 * Будет искать класс nc_widget_block_controller.class.php в папке /netcat/admin/widget/
 *
 * $_GET['action'] - действие (метод) контроллера
 */

//-------------------------------------------------------------------------

$NETCAT_FOLDER  = realpath(dirname(__FILE__) . '/..') . DIRECTORY_SEPARATOR;

require_once $NETCAT_FOLDER . 'vars.inc.php';
require_once $ADMIN_FOLDER . 'function.inc.php';

$ctrl   = nc_core()->input->fetch_post_get('ctrl');
$action = nc_core()->input->fetch_post_get('action');

if (!$action) {
    $action = 'index';
}

//-------------------------------------------------------------------------

$validators = array(
    'ctrl' => '@^[a-z][._a-z0-9]+[a-z0-9]$@',
    'action'  => '@^[a-z][_a-z0-9]+$@',
);

foreach ($validators as $var => $regexp) {
    if (!preg_match($regexp, $$var)) {
        die('Security error');
    }
}

//-------------------------------------------------------------------------

$controller_path  = explode('.', $ctrl);
$controller_class = 'nc_' . array_pop($controller_path) . '_controller';
$controller_path  = $ROOT_FOLDER . implode('/', $controller_path);
$controller       = $nc_core->ui->controller($controller_class, $controller_path);

echo $controller->execute($action);

//-------------------------------------------------------------------------