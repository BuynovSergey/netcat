<?php

$NETCAT_FOLDER = join(strstr(__FILE__, "/") ? "/" : "\\", array_slice(preg_split("/[\/\\\]+/", __FILE__), 0, -3)).( strstr(__FILE__, "/") ? "/" : "\\" );
include_once $NETCAT_FOLDER . 'vars.inc.php';
require_once $ROOT_FOLDER . 'connect_io.php';

/** @var $nc_core nc_Core */
$nc_core->admin_mode = $admin_mode = true;

Unauthorize();
LoginFormHeader();

switch ($nc_core->AUTHORIZATION_TYPE) {
    case "cookie":
        $login_text = BEGINHTML_LOGOUT_RELOGIN;
        echo "<p>" . BEGINHTML_LOGOUT_OK . "</p><p>[<a href='$nc_core->ADMIN_PATH' class='relogin'>$login_text</a>]</p>";
        break;
    case "http":
        echo "<p>" . BEGINHTML_LOGOUT_IE . "</p>";
        break;
    case "session":
        $login_text = BEGINHTML_LOGOUT_RELOGIN;
        echo "<p>" . BEGINHTML_LOGOUT_OK . "</p><p>[<a href='$nc_core->ADMIN_PATH' class='relogin'>$login_text</a>]</p>";
        unset($_SESSION['User']);
        break;
}

LoginFormFooter();