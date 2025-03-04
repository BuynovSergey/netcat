<?php

$NETCAT_FOLDER  = realpath(dirname(__FILE__) . '/../../') . DIRECTORY_SEPARATOR;
require_once $NETCAT_FOLDER . 'vars.inc.php';
require_once $ADMIN_FOLDER . 'function.inc.php';
require_once $ADMIN_FOLDER . 'admin.inc.php';

// require_once ($ADMIN_FOLDER."catalogue/function.inc.php");
// require_once $ADMIN_FOLDER."catalogue/function.inc.php";

//--------------------------------------------------------------------------

// Инициализация системных виджетов
require_once $ADMIN_FOLDER . 'dashboard/init_widgets.php';

//--------------------------------------------------------------------------

$view = $nc_core->ui->view($ADMIN_FOLDER . 'dashboard/views/index');

//--------------------------------------------------------------------------


$user_widgets         = $nc_core->dashboard->user_widgets(false);
$user_widgets_json    = json_safe_encode($user_widgets);

$allowed_widgets      = $nc_core->dashboard->allowed_widgets(false);
$allowed_widgets_json = json_safe_encode($allowed_widgets);

foreach ($user_widgets as &$widget) {
    $widget_type = $allowed_widgets[$widget['type']];
    if ( is_array($widget_type) ) {
		$widget = array_merge($widget_type, $widget);
		$widget_controller = $DOCUMENT_ROOT . $widget_type['controller'];
	}

    if (file_exists($widget_controller) && is_file($widget_controller)) {
        if (isset($widget['query'])) {
            parse_str($widget['query'], $_GET);
            if (empty($_GET['route'])) {
                $_GET['route'] = 'index';
            }
        }
        chdir(dirname($widget_controller));
        ob_start();
        require_once $widget_controller;
        $widget['content'] = ob_get_clean();
    }
}

$_GET = array();
chdir( dirname(__FILE__) );

$view->with('nc_core',              $nc_core);
$view->with('allowed_widgets',      $allowed_widgets);
$view->with('allowed_widgets_json', $allowed_widgets_json);
$view->with('user_widgets',         $user_widgets);
$view->with('user_widgets_json',    $user_widgets_json);
$view->with('default_color',        'lighten');

$sql = "SELECT `Catalogue_ID`, `Catalogue_Name` FROM `Catalogue` WHERE `ncMode` = 'demo' ORDER BY `Priority`";
$demo_catalogues = (array)nc_core('db')->get_results($sql, ARRAY_A);
$view->with('demo_catalogues', $demo_catalogues);

//--------------------------------------------------------------------------
// Параметры UI Config (Дерево, ...)
//--------------------------------------------------------------------------

$treeMode = 'users';

if ($perm->isAccessDevelopment()) {
    $treeMode = 'developer';
}
if ($perm->isAccessSiteMap() || $perm->isGuest()) {
    $treeMode = 'sitemap';
}

$UI_CONFIG = new ui_config(array(
	// 'headerText'   => '<span class="nc--hide-text">' . DASHBOARD_WIDGET . '</span>',
	'tabs'         => array(array('id' => 'welcame', 'caption' => DASHBOARD_WIDGET)),
	'activeTab'    => 'welcame',
	'treeMode'     => $treeMode,
	'locationHash' => 'index',
    'actionButtons' => array(
        array(
            // 'id'   => 'nc_dashboard_add_widget',
            'caption' => DASHBOARD_ADD_WIDGET,
            'action'  => 'return nc.ui.dashboard.widget_dialog()',
            'align'   => 'left',
            'style'   => 'nc_dashboard_add_widget', // className
        ),
        array(
            // 'id'   => 'nc_dashboard_settings',
            'caption' => STRUCTURE_TAB_SETTINGS,
            'action'  => 'return nc.ui.dashboard.edit_mode(this)',
            'align'   => 'left',
            'style'   => 'nc_dashboard_settings', // className
        ),
        array(
            // 'id'      => 'nc_dashboard_reset_widgets',
            'caption'    => DASHBOARD_DEFAULT_WIDGET,
            'action'     => 'return nc.ui.dashboard.reset_user_widgets(this)',
            'align'      => 'right',
            'red_border' => true,
            'style'      => 'nc_dashboard_reset_widgets', // className
        ),
    ),
));


if (is_object($UI_CONFIG) && method_exists($UI_CONFIG, 'to_json')) {
    $view->with('ui_config', $UI_CONFIG->to_json());
}

//--------------------------------------------------------------------------

echo $view->make();
