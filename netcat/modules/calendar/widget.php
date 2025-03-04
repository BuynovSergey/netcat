<?php

$NETCAT_FOLDER  = realpath(dirname(__FILE__) . '/../../../') . DIRECTORY_SEPARATOR;

require_once $NETCAT_FOLDER . 'vars.inc.php';
require_once $ADMIN_FOLDER . 'function.inc.php';
// require_once $ADMIN_FOLDER . 'admin.inc.php';

require_once 'function.inc.php';

$route = !empty($_GET['route']) ? $_GET['route'] : 'index';

switch ($route) {

	//--------------------------------------------------------------------------

	case 'index':
		$theme = !empty($_GET['theme']) ? (int)$_GET['theme'] : 0;
		$cc    = !empty($_GET['cc']) ? (int)$_GET['cc'] : 0;

		echo nc_show_calendar($theme, $cc) . "<script>\$nc('#nc_calendar_block>table').css({width:'100%',height:'100%'})</script>";
		break;

	//--------------------------------------------------------------------------

	case 'settings':
		$form = $nc_core->ui->form('');
		
		$result = $db->get_results("SELECT ID, ThemeName FROM Calendar_Settings ORDER BY ID", ARRAY_A);
		$themes = array();
		foreach ($result as $row) $themes[$row['ID']] = $row['ThemeName'];

		$form->add_row('Theme')->select('theme', $themes, 3);
		$form->add_row('Cc')->string('cc', 0);

		echo $form->vertical();
		break;

	//--------------------------------------------------------------------------
	
}