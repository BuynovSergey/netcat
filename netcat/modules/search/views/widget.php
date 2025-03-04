<?php

if (!class_exists("nc_system")) { die; }

// if (!nc_search::should('EnableSearch')) {
//     nc_print_status(NETCAT_MODULE_SEARCH_ADMIN_SETTING_SEARCH_DISABLED, "error",
//             array($this->hash_href("#module.search.generalsettings"), "_top"));
//     return;
// }

$nc_core = nc_core();
$db = $this->get_db();
$provider = nc_search::get_provider();
$is_history_saved = nc_search::should('SaveQueryHistory');

// -----------------------------------------------------------------------------
// Невыполненные задачи
$rules = nc_search::load('nc_search_rule', "SELECT * FROM `%t%` ORDER BY `LastStartTime` DESC");
if (count($rules)) {
    $pending_time = time() - 12 * 60 * 60;
    $pending_tasks = $db->get_var("SELECT `StartTime`
                                     FROM `Search_Schedule`
                                    WHERE `StartTime` < $pending_time
                                    LIMIT 1");
    if ($pending_tasks) { $error_message = NETCAT_MODULE_SEARCH_WIDGET_CHECK_CRONTAB; }
}
else {
    $error_message = NETCAT_MODULE_SEARCH_WIDGET_NO_RULES;
}

// Ошибки конфигурации
ob_start();
// (1) Индексатор
$provider->check_environment(true);
// (2) Парсеры
$parser_context = new nc_search_context(array('search_provider' => $provider));
$all_parsers = nc_search_extension_manager::get('nc_search_document_parser', $parser_context)->get_all();
/** @var nc_search_document_parser $parser */
foreach ($all_parsers as $parser) { $parser->check_environment(true); }

$has_configuration_errors = strlen(ob_get_clean()) > 0;

if ($has_configuration_errors) { $error_message = NETCAT_MODULE_SEARCH_WIDGET_CONFIGURATION_ERRORS; }

// -----------------------------------------------------------------------------

$full_link             = nc_module_path('search') . 'admin.php?view=info';
$full_link_brokenlinks = nc_module_path('search') . 'admin.php?view=brokenlinks';
?>

<table class="nc-widget-grid">
    <col width="50%" />
    <col width="50%" />
    <tr>
        <td colspan="2" height="1" class="nc-bg-light">
            <a class="nc-blocked" onclick="return nc.ui.dashboard.fullscreen(this)" href="<?=$full_link ?>">
                <i class="nc-icon nc--mod-search nc--white"></i> <?=NETCAT_MODULE_SEARCH_TITLE ?>
            </a>
        </td>
    </tr>
    <tr class="nc-widget-link" onclick="return nc.ui.dashboard.fullscreen(null, '<?=$full_link ?>')">
        <td class="">
            <dl class="nc-info nc--medium nc--vertical">
                <dt><?=$provider->count_documents() ?></dt>
                <dd class="nc-text-left"><?=NETCAT_MODULE_SEARCH_ADMIN_STAT_NUM_DOCUMENTS ?></dd>
            </dl>
        </td>
        <td class="">
            <dl class="nc-info nc--medium nc--vertical">
                <dt><?=$db->get_var("SELECT COUNT(*) FROM `Search_Document` WHERE `IncludeInSitemap` = 1") ?></dt>
                <dd class="nc-text-left"><?=NETCAT_MODULE_SEARCH_ADMIN_STAT_NUM_SITEMAP_URLS ?></dd>
            </dl>
        </td>
    </tr>
    <? if($is_history_saved): ?>
        <tr class="nc-widget-link" onclick="return nc.ui.dashboard.fullscreen(null, '<?=$full_link ?>')">
            <td class="">
                <dl class="nc-info nc--medium nc--vertical">
                    <dt><?=$db->get_var("SELECT COUNT(*) FROM `Search_Query` WHERE `Timestamp`>=DATE(NOW())") ?></dt>
                    <dd class="nc-text-left"><?=NETCAT_MODULE_SEARCH_ADMIN_STAT_NUM_QUERIES_TODAY ?></dd>
                </dl>
            </td>
            <td class="">
                <dl class="nc-info nc--medium nc--vertical">
                    <dt><?=$db->get_var("SELECT COUNT(*) FROM `Search_Query` WHERE `Timestamp` BETWEEN DATE(NOW()-INTERVAL 1 DAY) AND DATE(NOW())") ?></dt>
                    <dd class="nc-text-left"><?=NETCAT_MODULE_SEARCH_ADMIN_STAT_NUM_QUERIES_YESTERDAY ?></dd>
                </dl>
            </td>
        </tr>
    <? endif ?>

    <tr>
        <td height="1" class="nc-bg-dark nc-text-center">
            <a onclick="return nc.ui.dashboard.fullscreen(this)" href="<?=$full_link_brokenlinks ?>">
                <dl class="nc-info nc--medium">
                    <dt><?=(int)$db->get_var("SELECT COUNT(*) FROM `Search_BrokenLink`") ?></dt>
                    <dd class="nc-text-left"><?=NETCAT_MODULE_SEARCH_WIDGET_BROKEN_LINKS ?></dd>
                </dl>
            </a>
        </td>
        <? if (!empty($error_message)): ?>
            <td class="nc-bg-darken">
                <a onclick="return nc.ui.dashboard.fullscreen(this)" href="<?=$full_link ?>">
                <dl class="nc-info nc--medium nc--horizontal">
                    <dt><i class="nc-icon-l nc--status-warning"></i></dt>
                    <dd><?=$error_message ?></dd>
                </dl>
                </a>
            </td>
        <? else: ?>
            <td class="nc-bg-darken"></td>
        <? endif ?>
    </tr>
</table>
