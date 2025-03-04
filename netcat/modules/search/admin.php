<?php

$NETCAT_FOLDER = realpath("../../../");
require_once $NETCAT_FOLDER . '/vars.inc.php';
require_once $ADMIN_FOLDER . 'function.inc.php';
require_once nc_module_folder('search') . 'function.inc.php';

$input = (array) nc_Core::get_object()->input->fetch_get_post();
if (!$nc_core->NC_UNICODE) {
    $input = $nc_core->utf8->array_win2utf($input);
}
nc_search_admin_controller::process_request($input);