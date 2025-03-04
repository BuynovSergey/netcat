<?php

$NETCAT_FOLDER  = realpath(dirname(__FILE__) . '/../../../../') . DIRECTORY_SEPARATOR;

require_once $NETCAT_FOLDER . 'vars.inc.php';
require_once $ADMIN_FOLDER . 'function.inc.php';

?>

<table class="nc-widget-grid nc-widget-link" onclick="document.location.href = '<?=$ADMIN_PATH ?>user/'">
	<col width="50%" />
	<col width="1" />
	<col width="50%" />
	<tr>
		<td class="nc-bg-lighten" style="height:1%" colspan="3">
			<i class="nc-icon nc--user-group nc--white"></i> <?=DASHBOARD_WIDGET_MOD_AUTH ?>
		</td>
	</tr>
	<tr>
		<td class="nc-bg-light" style="height:25%">
			<dl class="nc-info nc--medium nc--vertical">
				<dt class="nc-text-center"><?=$db->get_var('SELECT COUNT(*) FROM User WHERE DATE(`Created`) = CURDATE()') ?></dt>
				<dd class="nc-text-center"><?=DASHBOARD_TODAY ?></dd>
			</dl>
		</td>
		<td class="nc-bg-light" style="padding:10px 0">
			<dl class="nc-info nc--medium nc--vertical">
				<dt>:</dt>
				<dd>&nbsp;</dd>
			</dl>
		</td>
		<td class="nc-bg-light">
			<dl class="nc-info nc--medium nc--vertical">
				<dt class="nc-text-center"><?=$db->get_var('SELECT COUNT(*) FROM User WHERE DATE(`Created`) = ( CURDATE() - INTERVAL 1 DAY )') ?></dt>
				<dd class="nc-text-center"><?=DASHBOARD_YESTERDAY ?></dd>
			</dl>
		</td>
	</tr>
	<tr>
		<td class="" colspan="3" style="height:25%">
			<dl class="nc-info nc--medium nc--vertical">
				<dt class="nc-text-center"><?=$db->get_var('SELECT COUNT(*) FROM User WHERE DATE(`Created`) >= ( CURDATE() - INTERVAL 7 DAY )') ?></dt>
				<dd class="nc-text-center"><?=DASHBOARD_PER_WEEK ?></dd>
			</dl>
		</td>
	</tr>

	<tr>
		<td class="nc-bg-dark" colspan="3" style="height:25%">
			<dl class="nc-info nc--medium nc--vertical">
				<dt class="nc-text-center"><?=$db->get_var('SELECT COUNT(*) FROM User WHERE `Checked`=0') ?></dt>
				<dd class="nc-text-center"><?=DASHBOARD_DONT_ACTIVE ?></dd>
			</dl>
		</td>
	</tr>
	<tr>
		<td class="nc-bg-darken" colspan="3" style="height:25%">
			<dl class="nc-info nc--medium nc--vertical">
				<dt class="nc-text-center"><?=$db->get_var('SELECT COUNT(*) FROM User') ?></dt>
				<dd class="nc-text-center"><?=CONTROL_USER_GROUP_TOTAL ?></dd>
			</dl>
		</td>
	</tr>
</table>