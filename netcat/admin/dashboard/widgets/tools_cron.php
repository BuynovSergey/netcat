<?php

$NETCAT_FOLDER = realpath(dirname(__FILE__) . '/../../../../') . DIRECTORY_SEPARATOR;

require_once $NETCAT_FOLDER . 'vars.inc.php';
require_once $ADMIN_FOLDER . 'function.inc.php';

$tasks = $db->get_results("SELECT * FROM `CronTasks` ORDER BY `Cron_Launch`");
$tasks = $tasks ?: array();

$last = null;
$next = null;
foreach ($tasks as $rs) {

    $time = 0;

    if ($rs->Cron_Minutes > 0) $time = ($rs->Cron_Minutes * 60);
    if ($rs->Cron_Hours   > 0) $time = ($rs->Cron_Hours * 3600);
    if ($rs->Cron_Days    > 0) $time = ($rs->Cron_Days * 86400);

    $rs->next = $rs->Cron_Launch + $time - time();

    if ($time) { // && $rs->time <= $now) {

        if ( ! $next || $next->next > $rs->next) {
            $next = $rs;
        }
        if ( ! $last || $last->Cron_Launch < $rs->Cron_Launch) {
            $last = $rs;
        }
    }
}

?>
    <table class="nc-table nc-widget-grid nc-widget-link nc--small" onclick="return nc.ui.dashboard.fullscreen(null, '<?=$ADMIN_PATH ?>crontasks.php')">
        <col width="1%">
        <col width="">
        <tr>
            <td colspan="2" height="1%" class="nc-bg-darken nc-text-small">
                <?=TOOLS_CRON ?>
            </td>
        </tr>
        <tr>
            <td>
                <? if ($last->next < -60): ?>
                    <i title="<?=date('H:i:s d.m.Y', $last->Cron_Launch) ?>" class="nc-icon-l nc--status-warning"></i>
                <? else: ?>
                    <div title="<?=date('H:i:s d.m.Y', $last->Cron_Launch) ?>" class="nc-label nc-bg-dark"><?=date('H:i', $last->Cron_Launch) ?></div>
                <? endif ?>
            </td>
            <td title="<?=TOOLS_CRON_SCRIPTURL  . ': ' . $last->Cron_Script_URL ?>" class="nc-text-small nc--compact">
                <?=TOOLS_CRON_LAUNCHED ?>
            </td>
        </tr>
        <tr>
            <td>
                <? if ($next->next < -60): ?>
                    <i title="<?=date('H:i:s d.m.Y', $next->Cron_Launch) ?>" class="nc-icon-l nc--status-warning"></i>
                <? else: ?>
                    <div title="<?=date('H:i:s d.m.Y', time() + $next->next) ?>" class="nc-label nc-bg-dark"><?=date('H:i', time() + $next->next) ?></div>
                <? endif ?>
            </td>
            <td title="<?=TOOLS_CRON_SCRIPTURL  . ': ' . $next->Cron_Script_URL ?>" class="nc-text-small nc--compact">
                <?=TOOLS_CRON_NEXT ?>
            </td>
        </tr>
    </table>