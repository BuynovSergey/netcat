<?php

// get settings
$NETCAT_FOLDER = join(strstr(__FILE__, "/") ? "/" : "\\", array_slice(preg_split("/[\/\\\]+/", __FILE__), 0, -4)) . ( strstr(__FILE__, "/") ? "/" : "\\" );
require_once $NETCAT_FOLDER . 'vars.inc.php';
require_once $ADMIN_FOLDER . 'function.inc.php';
require_once nc_module_folder('comments') . 'nc_comments_admin.class.php';
require_once nc_module_folder('comments') . 'nc_comments.class.php';

// load modules env
if (!isset($MODULE_VARS)) {
    $MODULE_VARS = $nc_core->modules->get_module_vars(); //LoadModuleEnv();
}

?>

<table class="nc-widget-grid nc-widget-link" onclick="return nc.ui.dashboard.fullscreen(this, '<?= nc_module_path('comments'); ?>admin.php')">
	<col width="50%" />
	<col width="50%" />
	<tr>
		<td style="height:1%" colspan="2">
			<i class="nc-icon nc--mod-comments nc--white"></i> <?=NETCAT_MODULE_COMMENTS ?>
		</td>
	</tr>
	<tr>
		<td class="" rowspan="2">
			<dl class="nc-info nc--large nc--vertical">
				<dt class="nc-text-center"><?=$db->get_var('SELECT COUNT(*) FROM Comments_Text WHERE DATE(`Date`) = CURDATE()') ?></dt>
				<dd class="nc-text-center"><?=DASHBOARD_TODAY ?></dd>
			</dl>
		</td>
		<td class="nc-bg-dark">
			<dl class="nc-info nc--mini nc--vertical">
				<dt class="nc-text-center"><?=$db->get_var('SELECT COUNT(*) FROM Comments_Text WHERE DATE(`Date`) = ( CURDATE() - INTERVAL 1 DAY )') ?></dt>
				<dd class="nc-text-center"><?=DASHBOARD_YESTERDAY ?></dd>
			</dl>
		</td>
	</tr>

	<tr>
		<td class="nc-bg-darken">
			<dl class="nc-info nc--mini nc--vertical">
				<dt class="nc-text-center"><?=$db->get_var('SELECT COUNT(*) FROM Comments_Text WHERE DATE(`Date`) >= ( CURDATE() - INTERVAL 7 DAY )') ?></dt>
				<dd class="nc-text-center"><?=DASHBOARD_PER_WEEK ?></dd>
			</dl>
		</td>
	</tr>
</table>