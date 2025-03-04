<?php
error_reporting(E_ALL);
require_once ("function.inc.php");
$system_env = $nc_core->get_settings();
$an = new nc_AdminNotice();
$adminNotice = $an->check();

/** @var Permission $perm */

?><!DOCTYPE html>
<!--[if lt IE 7]><html style="overflow-y:hidden" class="nc-ie6 nc-oldie"><![endif]-->
<!--[if IE 7]><html style="overflow-y:hidden" class="nc-ie7 nc-oldie"><![endif]-->
<!--[if IE 8]><html style="overflow-y:hidden" class="nc-ie8 nc-oldie"><![endif]-->
<!--[if gt IE 8]><!--><html style="overflow-y:hidden"><!--<![endif]-->
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=<?= $nc_core->NC_CHARSET ?>" />
    <link rel="shortcut icon" href="/favicon.ico" type="image/x-icon" />
    <title><?= (isset($title) ? $title : SECTION_INDEX_TITLE); ?></title>
    <script type='text/javascript'>
        var FIRST_TREE_MODE = '<?= $treeMode ?>';
    </script>
    <?= nc_js(); ?>
    <?php
    $js_files = array();
    $js_files[] = $ADMIN_PATH . 'js/main.js';
    $js_files[] = $ADMIN_PATH . 'js/container.js';
    $js_files[] = $ADMIN_PATH . 'js/dispatcher.js';
    $js_files[] = $ADMIN_PATH . 'js/url_routes.js';
    $js_files[] = $ADMIN_PATH . 'js/json2.js';
    /*<script type="text/javascript" src="<?= $ADMIN_PATH ?>js/main.js?<?= $LAST_LOCAL_PATCH ?>"></script>
    <script type='text/javascript' src='<?= $ADMIN_PATH ?>js/container.js?<?= $LAST_LOCAL_PATCH ?>'></script>
    <script type='text/javascript' src='<?= $ADMIN_PATH ?>js/dispatcher.js?<?= $LAST_LOCAL_PATCH ?>'></script>
    <script type='text/javascript' src='<?= $ADMIN_PATH ?>js/url_routes.js?<?= $LAST_LOCAL_PATCH ?>'></script>
    <script type='text/javascript' src='<?= $ADMIN_PATH ?>js/json2.js?<?= $LAST_LOCAL_PATCH ?>'></script>*/
    ?>
    <?
    // MODULE URL DISPATCHERS
    $modules = $nc_core->modules->get_data();
    //ADMIN_LANGUAGE

    if ( !empty($modules) ) {
        foreach ($modules as $module) {
            if (file_exists(nc_module_folder($module['Keyword']) . MAIN_LANG . '.lang.php')) {
                require_once nc_module_folder($module['Keyword']) . MAIN_LANG . '.lang.php';
            } else {
                require_once  nc_module_folder($module['Keyword']) . 'en.lang.php';
            }
            if (file_exists(nc_module_folder($module['Keyword']) . 'url_routes.js')) {
                $js_files[] = nc_module_path($module['Keyword']) . 'url_routes.js';
            }
        }
    }

    $js_files[] = $ADMIN_PATH . 'js/main_view.js';

    foreach(nc_minify_file($js_files, 'js') as $file) {
        echo "<script src='".$file."'></script>\n";
    }

    include($ADMIN_FOLDER."modules/module_list.inc.php");

    /*<script type='text/javascript' src='<?= $ADMIN_PATH ?>js/main_view.js?<?= $LAST_LOCAL_PATCH ?>'></script>
    <script type='text/javascript' src='<?= $ADMIN_PATH ?>js/drag.js?<?= $LAST_LOCAL_PATCH ?>'></script>*/
    ?>

    <script type='text/javascript'>
        var REMIND_SAVE = '<?= $REMIND_SAVE ?>';
        var TEXT_SAVE = '<?= NETCAT_REMIND_SAVE_TEXT ?>';
        var TEXT_REFRESH = '<?= NETCAT_TAB_REFRESH ?>';
    </script>
    <? $trial_message = function_exists('nc_demo_expired') ? nc_demo_expired() : ''; ?>
    <? if ($nc_core->is_trial && $trial_message): ?>
        <link rel="stylesheet" type="text/css" href="https://fonts.googleapis.com/css?family=Roboto:300,400&subset=cyrillic">
        <link rel="stylesheet" type="text/css" href="<?= $ADMIN_PATH; ?>skins/default/css/demo.css">
        <script type="text/javascript" src="<?= $ADMIN_PATH; ?>js/demo.js"></script>
    <? endif; ?>
</head>
<?php
//--------------------------------------------------------------------------
// Собираем главное меню (nc-navbar)
//--------------------------------------------------------------------------

$navbar = $nc_core->ui->navbar();

$navbar->menu->title(SECTION_INDEX_MENU_TITLE)->add_btn('#')->icon_large('logo-white')
    ->title(SECTION_INDEX_MENU_HOME . ': ' . CMS_SYSTEM_NAME, true)
    ->click('return true');

$all_site_admin = $perm->isAccess(NC_PERM_ITEM_SITE, 'viewall', 0, 0);

// получим id всех каталогов, к которому пользователь имеет доступ админа\модер
// или иммет доступ к его разделам, тоже админ\модер
// если ф-ция вернет не массив, то значит есть доступ ко всем
$array_id = $perm->GetAllowSite(MASK_ADMIN | MASK_MODERATE, true);
$sites = $db->get_results("SELECT `Catalogue_ID`, `Catalogue_Name`, `Domain`, `Mirrors`, `Checked`, `ncMobile`, `ncResponsive`
    FROM `Catalogue`".( is_array($array_id) && !empty($array_id) ? " WHERE `Catalogue_ID` IN (".join(',', $array_id).")" : "" )."
    ORDER BY `Priority`", ARRAY_A);
$sites_count = is_array($sites) ? count($sites) : 0;

// Cайт
//--------------------------------------------------------------------------
if ($perm->isAccessSiteMap() || $perm->isGuest()) {
    $navbar->menu->site = $navbar->menu->add_btn('#', SECTION_INDEX_MENU_SITE)->submenu();

    if ($sites) {
        foreach ($sites as $site) {
            // each site
            $site_icon = 'site' . ($site['ncMobile'] ? '-mobile' : ($site['ncResponsive'] ? '-adaptive' : ''));
            $navbar->menu->site->add_btn('#site.map(' . $site['Catalogue_ID'] . ')')
                ->text($site['Catalogue_Name'])
                ->icon($site_icon)
                ->disabled(!$site['Checked']);
        }

        $navbar->menu->site->add_divider();

        if ($all_site_admin) {
            if (defined('NC_SAAS_SITE') && defined('NC_SAAS_SITES_LIST_URL')) {
                $navbar->menu->site->add_btn(NC_SAAS_SITES_LIST_URL, SECTION_INDEX_SITE_LIST_SAAS)->icon('site-list');
            } else {
                $navbar->menu->site->add_btn('#site.list', SECTION_INDEX_SITE_LIST)->icon('site-list');
            }
        }
    }

    if ($all_site_admin && $nc_core->catalogue->can_create()) {
        $navbar->menu->site
            ->add_btn('#site.add()', CONTROL_CONTENT_CATALOUGE_FUNCS_SHOWCATALOGUELIST_ADDSITE)->icon('site-add')
            ->add_btn('#site.wizard(1,0)', SECTION_INDEX_WIZARD_SUBMENU_SITE)->icon('site-wizard');
    }
}

// Магазин
//-------------------------------------------------------------------------
if ($nc_core->modules->get_by_keyword('netshop') && $perm->hasAnyModulePermission('netshop')) {
    $site_id = $nc_core->catalogue->id();
    $netshop = nc_netshop::get_instance($site_id);
    $navbar->menu->shop = $navbar->menu->add_btn('#', NETCAT_MODULE_NETSHOP_SHOP)->submenu();

    // Заказы
    if ($perm->hasModulePermission('netshop', 'order_edit')) {
        $navbar->menu->shop->add_btn("#module.netshop.order({$site_id})", NETCAT_MODULE_NETSHOP_ORDERS)->icon('bill');
    }

    // Скидки
    if ($perm->hasModulePermission('netshop', 'discount_edit')) {
        $netshop_discount_link_type = $netshop->is_feature_enabled('promotion_discount_item') ? 'item' : 'cart';
        $navbar->menu->shop->add_btn("#module.netshop.promotion.discount.$netshop_discount_link_type({$site_id})", NETCAT_MODULE_NETSHOP_PROMOTION_DISCOUNTS)->icon('discount');
    }

    $show_netshop_exchange =
        ($netshop->is_feature_enabled('1c') && $perm->hasModulePermission('netshop', 'import_start')) ||
        $perm->hasModulePermission('netshop', 'market_export_edit');

    if ($show_netshop_exchange) {
        // Обмен данными
        $navbar->menu->shop->add_btn("#module.netshop.exchange", NETCAT_MODULE_NETSHOP_EXCHANGE)->icon('database');
    }

     // Маркетплейсы
    if ($perm->hasModulePermission('netshop', 'market_export_edit')) {
        $navbar->menu->shop->add_btn("#module.netshop.exchange.export.ozon.profile(index)", NETCAT_MODULE_NETSHOP_MARKETS)->icon('market-square');
    }

    // Статистика
    if ($perm->hasModulePermission('netshop', 'statistics_view')) {
        $navbar->menu->shop->add_btn("#module.netshop.statistics({$site_id})", NETCAT_MODULE_NETSHOP_STATISTICS)->icon('statistics');
    }


    // Настройки магазина
    if ($perm->hasModulePermission('netshop', 'settings_edit')) {
        $navbar->menu->shop->add_btn("#module.netshop.settings({$site_id})", NETCAT_MODULE_NETSHOP_DELIVERY_SERVICE_FIELD_MAPPING_SHOP)->icon('settings2');
    }

}

// Пользователи
//--------------------------------------------------------------------------
if ($perm->isUserMenuShow()) {
    $navbar->menu->users = $navbar->menu->add_btn('#', CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_USERS)->submenu();

    if ($perm->isAccess(NC_PERM_ITEM_USER, NC_PERM_ACTION_LIST, 0, 0)) {
        $navbar->menu->users->add_btn('#user.list', SECTION_CONTROL_USER_LIST)->icon('user');
    }

    if ($perm->isAccess(NC_PERM_ITEM_GROUP, NC_PERM_ACTION_LIST, 0, 0)) {
        $navbar->menu->users->add_btn('#usergroup.list', SECTION_CONTROL_USER_GROUP)->icon('user-group');
    }
    if ($perm->isAccess(NC_PERM_ITEM_USER, NC_PERM_ACTION_ADD, 0, 0)) {
        $navbar->menu->users->add_btn('#user.add', CONTROL_USER_REG)->icon('user-add');
    }

    if ($perm->isAccess(NC_PERM_ITEM_USER, NC_PERM_ACTION_LIST, 0, 0)) {
        $navbar->menu->users->add_btn('#user.mail', SECTION_INDEX_USER_USER_MAIL)->icon('mod-subscriber')->divider();
    }
}

// Инструменты
//--------------------------------------------------------------------------
$has_all_tools_access = $perm->isSupervisor() || $perm->isGuest();
$has_stats_access = $nc_core->modules->get_by_keyword('stats') && ($has_all_tools_access || $perm->hasAnyModulePermission('stats'));
$has_banner_access = $nc_core->modules->get_by_keyword('banner') && ($has_all_tools_access || $perm->hasAnyModulePermission('banner'));
$has_broken_links_access = $nc_core->modules->get_by_keyword('search') && ($has_all_tools_access || $perm->hasModulePermission('search', 'broken_links_view'));
$has_filemanager_access = $nc_core->modules->get_by_keyword('filemanager', 0, 0) && ($has_all_tools_access || $perm->hasAnyModulePermission('filemanager'));

if ($has_all_tools_access || $has_stats_access || $has_banner_access || $has_broken_links_access || $has_filemanager_access) {
    $navbar->menu->tools = $navbar->menu->add_btn('#', SECTION_INDEX_MENU_TOOLS)->submenu();

    if ($has_all_tools_access) {
        $navbar->menu->tools
            ->add_btn('#widgets', SECTION_SECTIONS_INSTRUMENTS_WIDGETS)->icon('mod-widgets')
            ->add_btn('#cron.settings', SECTION_SECTIONS_INSTRUMENTS_CRON)->icon('tasks')
            ->add_btn('#redirect.list(1)', TOOLS_REDIRECT)->icon('redirect')
            ->add_btn('#selftest', TOOLS_SELFTEST)->icon('selftest')
            ->add_divider();
    }


    if ($has_stats_access) {
        $navbar->menu->tools->add_btn('#module.stats', NETCAT_MODULE_STATS)->icon('mod-stats');
    }
    if ($has_banner_access) {
        $navbar->menu->tools->add_btn('#module.banner', NETCAT_MODULE_BANNER)->icon('mod-banner');
    }
    if ($has_broken_links_access) {
        $navbar->menu->tools->add_btn('#module.search.brokenlinks', NETCAT_MODULE_SEARCH_ADMIN_BROKEN_LINKS_MENU_ITEM)->icon('brokenlink');
    }
    if ($has_filemanager_access) {
        $navbar->menu->tools->add_btn('#module.filemanager', NETCAT_MODULE_FILEMANAGER)->icon('mod-filemanager');
    }

    if ($has_all_tools_access) {
        $navbar->menu->tools
            ->add_btn('#tools.copy()', TOOLS_COPYSUB)->icon('copy')
            ->add_btn('#trash.list', SECTION_SECTIONS_INSTRUMENTS_TRASH)->icon('trash')
            ->add_divider()
            ->add_btn('#tools.sql', SECTION_SECTIONS_INSTRUMENTS_SQL)->icon('sql-console')
            ->add_divider()
            ->add_btn('#tools.backup', SECTION_SECTIONS_MODDING_ARHIVES)->icon('mod-cache')
            ->add_btn('#tools.databackup.export', TOOLS_DATA_BACKUP)->icon('mod-cache')
            ->add_btn('#tools.csv.export', TOOLS_CSV)->icon('mod-cache')
            ->add_btn('#tools.patch', TOOLS_PATCH)->icon('update')
            ->add_btn('#tools.installmodule', TOOLS_MODULES_MOD_INSTALL)->icon('mod-default');
        // ->add_btn('#tools.store', TOOLS_STORE)->icon('mod-minishop');

        if ($nc_core->is_trial) {
            $navbar->menu->tools->add_btn('#tools.activation', TOOLS_ACTIVATION)->icon('')->off()->divider();
        }

        $navbar->menu->tools->add_btn('#tools.totalstat', SECTION_REPORTS_TOTAL)->icon('total-stats')->divider();

        if ($nc_core->modules->get_by_keyword('logging')) {
            $navbar->menu->tools->add_btn('#module.logging', NETCAT_MODULE_LOGGING)->icon('mod-logging');
        }

        $navbar->menu->tools->add_btn('#tools.systemmessages', SECTION_REPORTS_SYSMESSAGES)->icon('docs');
    }
}

// Разработка
//--------------------------------------------------------------------------
if ($perm->isAccessDevelopment() || $perm->isGuest()) {
    $navbar->menu->dev = $navbar->menu->add_btn('#', SECTION_INDEX_MENU_DEVELOPMENT)->submenu();

    //Access to class
    if ($perm->isSupervisor() || $perm->isGuest()) {
        $navbar->menu->dev->add_btn('#dataclass_fs.list', SECTION_CONTROL_CLASS)->icon('dev-components');
    }
    //Access to template
    if ($perm->isSupervisor() || $perm->isGuest()) {
        $navbar->menu->dev->add_btn('#template_fs.list', SECTION_CONTROL_TEMPLATE_SHOW)->icon('dev-templates');
    }
    //Access to system table
    if ($perm->isSupervisor() || $perm->isGuest()) {
        $navbar->menu->dev->add_btn('#systemclass_fs.list', SECTION_SECTIONS_OPTIONS_SYSTEM)->icon('dev-system-tables');
    }
    //Access to widget
    if ($perm->isSupervisor() || $perm->isGuest()) {
        $navbar->menu->dev->add_btn('#widgetclass_fs.list', SECTION_CONTROL_WIDGETCLASS)->icon('dev-com-widgets');
    }
    //Access to classificator
    if ($perm->isAnyClassificator() || $perm->isGuest()) {
        $navbar->menu->dev->add_btn('#classificator.list', SECTION_CONTROL_CONTENT_CLASSIFICATOR)->icon('dev-classificator');
    }
    //Access to classWizard
    if (false && $perm->isSupervisor() || $perm->isGuest()) {
        $navbar->menu->dev->add_btn('#dataclass_fs.wizard(1,0,0)', SECTION_INDEX_WIZARD_SUBMENU_CLASS)->icon('dev-com-wizard');
    }


    // $navbar->menu->dev->add_text("Netcat v4");
    $navbar->menu->dev->add_divider();

    // v4: Access to class
    if ($perm->isSupervisor() || $perm->isGuest()) {
        $navbar->menu->dev->add_btn('#dataclass.list', SECTION_CONTROL_CLASS." v4")->icon('dev-components-v4');
    }
    // v4: Access to template
    if ($perm->isSupervisor() || $perm->isGuest()) {
        $navbar->menu->dev->add_btn('#template.list', SECTION_CONTROL_TEMPLATE_SHOW." v4")->icon('dev-templates-v4');
    }
    // v4: Access to system table
    if ($perm->isSupervisor() || $perm->isGuest()) {
        $navbar->menu->dev->add_btn('#systemclass.list', SECTION_SECTIONS_OPTIONS_SYSTEM." v4")->icon('dev-system-tables-v4');
    }
    // v4: Access to widget
    if ($perm->isSupervisor() || $perm->isGuest()) {
        $navbar->menu->dev->add_btn('#widgetclass.list', SECTION_CONTROL_WIDGETCLASS." v4")->icon('dev-com-widgets-v4');
    }
}

// Настройки
//--------------------------------------------------------------------------
if ($perm->isSupervisor() || $perm->isGuest() || $perm->hasAnyModulePermission()) {
    $navbar->menu->settings = $navbar->menu->add_btn('#', SECTION_INDEX_MENU_SETTINGS)->submenu();

    if ($perm->isSupervisor() || $perm->isGuest()) {
        $navbar->menu->settings
            ->add_btn('#system.settings', SECTION_SECTIONS_OPTIONS)->icon('settings')
            ->add_btn('#module.list', SECTION_SECTIONS_OPTIONS_MODULE_LIST)->icon('settings')
            ->add_btn('#security.filter.settings(0)', SECTION_SECTIONS_OPTIONS_SECURITY)->icon('settings')
            ->add_btn('#wysiwyg.ckeditor.settings', SECTION_SECTIONS_OPTIONS_WYSIWYG)->icon('settings')
            ->add_btn(($sites_count === 1 ? "#site.edit(" . $sites[0]['Catalogue_ID'] . ")" : "#site.list"), SECTION_INDEX_SITES_SETTINGS)->icon('site');
    }

    if (!empty($modules)) {
        $navbar->menu->settings->add_divider();
        $custom_urls = array(
            'calendar' => '#module.calendar',
            'netshop'  => '#module.netshop',
            'search' => '#module.search.' . ($perm->hasModulePermission('search', 'settings_edit', 0) ? 'generalsettings' : 'info'),
        );
        foreach ($modules as $module) {
            if (!$perm->hasAnyModulePermission($module['Keyword'])) {
                continue;
            }
            if (isset($custom_urls[$module['Keyword']])) {
                $settings_url = $custom_urls[$module['Keyword']];
            } else {
                $settings_url = "#module.settings($module[Keyword])";
            }
            $modImg = file_exists($ADMIN_TEMPLATE_FOLDER . "img/i_module_$module[Keyword].gif") ? "i_module_$module[Keyword].gif" : 'i_modules.gif';
            //TODO: wtf, ради альтернативной ссылки модуля подключаются его классы
            if (file_exists(nc_module_folder($module['Keyword']) . "nc_$module[Keyword]_admin.class.php")) {
                require_once nc_module_folder($module['Keyword']) . "nc_$module[Keyword]_admin.class.php";
                if (class_exists($cn = "nc_$module[Keyword]_admin") && file_exists(nc_module_folder($module['Keyword']) . 'function.inc.php')) {
                    require_once nc_module_folder($module['Keyword']) . 'function.inc.php';
                    $admin_obj = new $cn();
                    if (is_callable(array($admin_obj, 'get_mainsettings_url'))) {
                        $settings_url = $admin_obj->get_mainsettings_url();
                    }
                }
            }

            $navbar->menu->settings->add_btn($settings_url, constant($module['Module_Name']))->icon('mod-' . $module['Keyword']);

            unset($modImg);
        }
    }
}

// Справка
//--------------------------------------------------------------------------
$support_url = defined('NC_SUPPORT_URL') ? NC_SUPPORT_URL : 'https://netcat.ru/forclients/support/tickets/';

if (defined('NC_SAAS_SITE')) {
    $navbar->menu->help = $navbar->menu->add_btn($support_url, SECTION_INDEX_MENU_SUPPORT);
} else {

    $navbar->menu->help = $navbar->menu->add_btn('#', SECTION_INDEX_MENU_HELP)->submenu();
    $navbar->menu->help
        ->add_btn($support_url, SECTION_INDEX_HELP_SUBMENU_HELPDESK)->icon('user-group')->attr('target', '_blank');

    if (defined('NC_SUPPORT_LOGIN')) {
        $navbar->menu->help
            ->add_btn('#user.support(1)', SECTION_INDEX_HELP_SUBMENU_SUPPORT)->icon('user-add');
    }
    $navbar->menu->help
        ->add_divider()
        ->add_btn('https://netcat.ru/developers/docs/', SECTION_INDEX_HELP_SUBMENU_DOC)->icon('docs')->attr('target', '_blank')
        ->add_btn('https://netcat.ru/support/forum/', SECTION_INDEX_HELP_SUBMENU_FORUM)->icon('mod-forum2')->attr('target', '_blank')
        ->add_btn('https://netcat.ru/support/knowledge/', SECTION_INDEX_HELP_SUBMENU_BASE)->icon('docs')->attr('target', '_blank')
        ->add_divider()
        ->add_btn('#help.about', SECTION_INDEX_HELP_SUBMENU_ABOUT)->icon('about');
}

//--------------------------------------------------------------------------
// Navbar tray
//--------------------------------------------------------------------------

// AJAX Loader
$navbar->tray->add_btn('#')->compact()->icon_large('navbar-loader')->id('nc-navbar-loader')->style('display:none');

if ($perm->isAccess(NC_PERM_REPORT)) {
    $ANY_SYSTEM_MESSAGE = $nc_core->db->get_var('SELECT COUNT(*) FROM `SystemMessage` WHERE `Checked` = 0');
    // Иконка с сообщениями
    $navbar->tray->add_btn('#tools.systemmessages')->compact()
        ->title($ANY_SYSTEM_MESSAGE ? BEGINHTML_ALARMON : BEGINHTML_ALARMOFF, true)
        ->icon_large('system-message')
        ->id('trayMessagesIcon')
        ->disabled(!$ANY_SYSTEM_MESSAGE);
}

// Меню пользователя
if (nc_module_check_by_keyword('auth')) {
    $logout_link = nc_module_path('auth')
        . '?logoff=1'
        . '&REQUESTED_FROM=' . urlencode($nc_core->REQUEST_URI);
} else {
    $logout_link = $ADMIN_PATH . 'unauth.php';
}

if (CMS_SYSTEM_NAME === 'Netcat') {
    if (defined('NC_SAAS_SITE')) {
        $navbar_left_button = $nc_core->ui->btn(
            defined('NC_SAAS_SITES_LIST_URL') ? NC_SAAS_SITES_LIST_URL : '#',
            CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_AUTH_PROFILE
        )->attr('target', '_blank')->light()->text_darken()->render();
    } else {

        $navbar_left_button = $nc_core->ui->btn('#', NETCAT_ADMIN_AUTH_CHANGE_PASS)->click('nc_password_change(); return false')->light()
                                          ->text_darken()->render();
    }

    $navbar_logout_button = $nc_core->ui->btn($logout_link, NETCAT_ADMIN_AUTH_LOGOUT)->red()->render();
} else {
    $navbar_left_button = '';
    $navbar_logout_button = $nc_core->ui->btn($logout_link, NETCAT_ADMIN_AUTH_LOGOUT)->red()->right()->render();
}

$navbar->tray->add_btn('#', $perm->getLogin())
    ->click('return false')
    ->title(BEGINHTML_USER . ': ' . $perm->getLogin())
    ->htext(BEGINHTML_USER)
    ->dropdown()
    ->div(
        // Права пользователя (список)
        NETCAT_ADMIN_AUTH_PERM . " <span class='nc-text-grey'>" . implode(', ', Permission::get_all_permission_names_by_id($AUTH_USER_ID)) . "</span><hr class='nc-hr'>"
        . "<div class='nc--nowrap'>"
        // Кнопка: Изменить пароль
        . $navbar_left_button
        // Кнопка: Выйти
        . $navbar_logout_button
        . '</div>'
    )->class_name('nc-padding-10');


?>
<body class="nc-admin" style="overflow-y:hidden">
    <?
        if ($nc_core->is_trial && $trial_message) {
           echo $trial_message;
        }
    ?>
    <?php echo $navbar; ?>
    
<?php
// Содержание модального окна быстрого изменения пароля
//TODO: Сделать загрузку содержимого окна через ajax
?>
<div id='nc_password_change' class='nc-shadow-large nc--hide'>
    <form class='nc-form' style='width:350px' method='post' action='<?=$ADMIN_PATH ?>user/index.php'>
        <div class='nc-padding-15'>
            <h2 class='nc-h2'><?=NETCAT_ADMIN_AUTH_CHANGE_PASS ?></h2>
            <hr class='nc-hr' style='margin:5px -15px 15px'>
            <div>
                <label><?=CONTROL_USER_NEWPASSWORD ?></label><br>
                <input class='nc--wide' type='password' name='Password1' maxlength='32' placeholder='<?=CONTROL_USER_NEWPASSWORD ?>' />
            </div>
            <div>
                <label><?=CONTROL_USER_NEWPASSWORDAGAIN ?></label><br>
                <input class='nc--wide' type='password' name='Password2' maxlength='32' placeholder='<?=CONTROL_USER_NEWPASSWORDAGAIN ?>' />
            </div>
            <input type='hidden' name='UserID' value='<?=$AUTH_USER_ID ?>' />
            <input type='hidden' name='phase' value='7' />
            <?=$nc_core->token->get_input() ?>
        </div>
    </form>
    <div class='nc-form-actions'>
        <button class='nc-btn nc--bordered nc--red nc--right' onclick='$nc.modal.close()' type='button'><?=CONTROL_BUTTON_CANCEL ?></button>
        <button class='nc_admin_metro_button nc-btn nc--blue nc--right' onclick='$nc("#nc_password_change form").submit()'><?=NETCAT_REMIND_SAVE_SAVE ?></button>
    </div>
</div>
<!-- /#nc_password_change -->


    <div class="middle" style="height: 10000px">
        <div class="middle_left">
            <div class='title' id='tree_mode_name'>
                <?= NETCAT_TREE_SITEMAP ?>
            </div>
            <script>
                var tree_modes = {
                    'sitemap' : '<?= NETCAT_TREE_SITEMAP; ?>',
                    'classificator' : '<?= SECTION_CONTROL_CONTENT_CLASSIFICATOR; ?>',
                    'dataclass' : '<?= SECTION_INDEX_DEV_CLASSES . ' v4'; ?>',
                    'dataclass_fs' : '<?= SECTION_INDEX_DEV_CLASSES; ?>',
                    'systemclass' : '<?= SECTION_SECTIONS_OPTIONS_SYSTEM . ' v4'; ?>',
                    'systemclass_fs' : '<?= SECTION_SECTIONS_OPTIONS_SYSTEM; ?>',
                    'template' : '<?= SECTION_INDEX_DEV_TEMPLATES . ' v4'; ?>',
                    'template_fs' : '<?= SECTION_INDEX_DEV_TEMPLATES; ?>',
                    'widgetclass' : '<?= SECTION_INDEX_DEV_WIDGET . ' v4'; ?>',
                    'widgetclass_fs' : '<?= SECTION_INDEX_DEV_WIDGET; ?>',
                    'modules' : '<?= NETCAT_TREE_MODULES; ?>',
                    'users' : '<?= NETCAT_TREE_USERS; ?>',
                    'redirect' : '<?= TOOLS_REDIRECT; ?>'
                }
            </script>
            <div class="menu_left_opacity"></div>
            <iframe name='treeIframe' id='treeIframe' width="100%" height="100%" frameborder="0" allowtransparency="true" title="<?= NETCAT_TREE_SITEMAP ?>"></iframe>
        </div>
        <div class="middle_right">
            <div class="wrap">
                <div class="wrap_block">
                    <div class="middle_border"></div>
                    <div class="wrap_block_2">
                        <div class="menu_right_opacity"></div>
                        <div class="header_block">
                            <span id='mainViewHeader'></span>

                            <div class="slider_block slider_block_1" id="tabs" style="display: none;">
                                <div class="left_gradient"><div class="gradient"></div></div>
                                <div class="right_gradient"><div class="gradient"></div></div>
                                <a href="#" onclick="return false;" class="arrow left_arrow"></a><a href="#" onclick="return false;" class="arrow right_arrow"></a>
                                <div class="overflow">
                                    <div class="slide">
                                        <ul id='mainViewTabs'></ul>
                                        <ul id='mainViewTabsTray'></ul>
                                    </div>
                                </div>
                            </div>

                            <div class="slider_block slider_block_2" id="sub_tabs" style="display: none;">
                                <div class="left_gradient"><div class="gradient"></div></div>
                                <div class="right_gradient"><div class="gradient"></div></div>
                                <a href="#" onclick="return false;" class="arrow left_arrow"></a><a href="#" onclick="return false;" class="arrow right_arrow"></a>
                                <div class="overflow">
                                    <div class="slide">
                                        <div class='toolbar'>
                                            <ul id='mainViewToolbar'></ul>
                                        </div>
                                    </div>
                                </div>
                            </div>


                        </div>
                        <div class="content_block">
                            <div id='mainViewContent'>
                                <iframe id='mainViewIframe' name='mainViewIframe' style='width:100%; height:100%;' frameborder='0'></iframe>
                            </div>
                        </div>


                        <div class="clear clear_footer"></div>
                    </div>
                </div>
            </div>
            <div class="nc_footer">
                <div class='main_view_buttons' id='mainViewButtons'></div>
            </div>
        </div>
    </div>
         <?php
            //файл экскурсии будет подключаться только при наличии таблицы
            if (CMS_SYSTEM_NAME === 'Netcat' && $db->get_var("SHOW TABLES LIKE 'Excursion'")) {
                $res = $db->get_row("SELECT `ShowNext` FROM `Excursion` WHERE `User_ID` = $AUTH_USER_ID", ARRAY_A);
                if (((is_null($res) || $res["ShowNext"] == 1)) && file_exists("excursion.php")) {
                    require_once("excursion.php");
                }
            }
         ?>
</body>
</html>
