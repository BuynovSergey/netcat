<?php

require_once realpath("../../../") . "/vars.inc.php";
require_once $ADMIN_FOLDER . "function.inc.php";

require_once("./function.inc.php");

$lang = $nc_core->lang->acronym_from_full($nc_core->lang->detect_lang());
require_once("$lang.lang.php");

$input['view']         = 'widget';
$input['ui_config']    = false;
$input['print_header'] = false;
$input['print_footer'] = false;

nc_search_admin_controller::process_request($input);