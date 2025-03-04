<?php

$NETCAT_FOLDER  = realpath(dirname(__FILE__) . '/../../../../') . DIRECTORY_SEPARATOR;

require_once $NETCAT_FOLDER . 'vars.inc.php';
require_once $ADMIN_FOLDER . 'function.inc.php';

$route = !empty($_GET['route']) ? $_GET['route'] : 'index';

switch ($route) {
	case 'index':
		$widget = !empty($_GET['widget']) ? $_GET['widget'] : false;
		if ($widget) {
			echo $nc_core->widget->show($widget);
		}
		break;

	case 'settings':
 		$form = $nc_core->ui->form('');

 		$result = $db->get_results("SELECT `Widget_Class_ID`, `Widget_ID`, `Name`, `Keyword` from `Widget` ORDER BY `Name`");
		$widgets = array();
		foreach ($result as $row) $widgets[$row->Keyword] = $row->Name;

		$form->add_row('widget')->select('widget', $widgets);

		echo $form->vertical();
		break;
}

// echo $nc_core->widget->generate('yandexmaps_new', array());