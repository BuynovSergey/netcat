<?php

$NETCAT_FOLDER  = realpath(dirname(__FILE__) . '/../../../') . DIRECTORY_SEPARATOR;

require_once $NETCAT_FOLDER . 'vars.inc.php';
require_once $ADMIN_FOLDER . 'function.inc.php';
require_once $ADMIN_FOLDER . 'admin.inc.php';

//--------------------------------------------------------------------------

$json = array(
	'success' => false,
	'error'   => false,
);

$action = isset($_GET['action']) ? $_GET['action'] : false;

//--------------------------------------------------------------------------

switch ($action) {

	// Сохранение виджетовов
	case 'save_user_widgets':
		$user_widgets = $nc_core->input->fetch_get_post('user_widgets');
		if ( ! empty($user_widgets) ) {
			// console::log($user_widgets);
			$nc_core->dashboard->save_user_widgets($user_widgets);
			$json['success'] = true;
		}
		else {
			$json['error'] = true;
			$json['error_message'] = 'user_widgets - not set';
		}
		break;

	// Сброс виджетов по умолчанию
	case 'reset_user_widgets':
		$nc_core->dashboard->save_user_widgets(false);
		$json['success'] = true;
		break;

	// Шаблон (обертка для iframe) полноэкранного отображения виджета
	case 'full':
		$view = $nc_core->ui->view($ADMIN_FOLDER . 'dashboard/views/full');
		echo $view->make();
		exit;
		break;

	default:
		$json['error'] = true;
		$json['error_message'] = 'unknown action';
		break;
}


echo json_safe_encode($json);

