<?php
if (!class_exists('nc_core')) {
    die;
}
$nc_core = nc_core::get_object();
?>
<script type='text/javascript'>
function showhide(btn, id){
	$nc('#' + id).slideToggle();
	$nc(btn).parent().toggleClass('nc--alt');
	return false;
}
function filter() {
	var widget_filter = $nc('#widget_filter').val();
	var filter_id     = 'widget_' + widget_filter;

	if (widget_filter == 0) return $nc('div.widget').slideDown();

	$nc('div.widget').each(function(){
		$widget = $nc(this);
		$widget.hasClass(filter_id) ? $widget.slideDown() : $widget.slideUp();
	});
}
</script>
<script type='text/javascript' src='<?= nc_add_revision_to_url($ADMIN_PATH . 'js/widget_prev.js') ?>'></script>
<script type='text/javascript'>nc_widget_prev_obj = new nc_widget_prev();</script>



<br />
<div class='nc-form'>
	<?=WIDGET_LIST_CATEGORY ?>:&nbsp;&nbsp;
	<div class='nc-select'>
		<select id='widget_filter' onchange='filter();$nc(".nc_widget_prev").hide();return false'>
			<option value='0' selected='selected'><?=WIDGET_LIST_ALL ?></option>
			<? foreach ($categories as $cat): ?>
				<option value='<?=md5($cat) ?>'><?=$cat ?></option>
			<? endforeach ?>
		</select>
		<i class='nc-caret'></i>
	</div>
</div>
<hr>

<div style="padding-right:15px">
	<? foreach ((array)$widgets as $row): ?>
	<? $toolbar = $nc_core->ui->toolbar()->left() ?>
	<? $href = "admin.php?widget_id=" . $row->Widget_ID . "&fs=" . get_fs() . "&phase=" ?>
	<? $toolbar->add_btn($href . 20)->click('parent.nc_form(this.href);return false')->icon('copy')->title(NETCAT_MODERATION_COPY_OBJECT) ?>
	<? $toolbar->add_btn($href . 30)->icon('edit')->title(NETCAT_MODERATION_CHANGE) /* ->click('parent.nc_form(this.href);return false') */ ?>
	<? $toolbar->add_btn($href . 61)->click('parent.nc_action_message(this.href);return false')->icon('remove')->title(NETCAT_MODERATION_DELETE) ?>
	<? $toolbar->add_divider() ?>
	<? $toolbar->add_btn('#', WIDGET_LIST_INSERT_CODE)->click('return showhide(this, "'.$row->Keyword.'")') ?>
	<? $toolbar->add_btn('#', WIDGET_LIST_PREVIEW)->click('nc_widget_prev_obj.change("'.$row->Keyword.'");return false') ?>

	<div class='widget widget_<?=md5($widget_categories[$row->Widget_Class_ID]) ?>'>
		<h4><?=$row->Name ?></h4>
		<div><?=$toolbar ?></div>

		<div class='ncf_row nc_clear'></div>

		<div class='nc_widget_prev' id='<?=$row->Keyword ?>' style='display:none'>
			<table class="nc-table nc--bordered">
				<tr>
					<td class='nc-bg-lighten nc-text-right'><?=WIDGET_LIST_INSERT_CODE_CLASS ?>:</td>
					<td><code class='nc-code'>&lt;?=$nc_core-&gt;widget-&gt;show('<?=$row->Keyword ?>') ?&gt;</code></td>
				</tr>
				<tr>
					<td class='nc-bg-lighten nc-text-right'><?=WIDGET_LIST_INSERT_CODE_TEXT ?>:</td>
					<td><code class='nc-code'>%NC_WIDGET_SHOW('<?=$row->Keyword ?>')%</code></td>
				</tr>
			</table>
		</div>

		<div class='nc_widget_prev nc-box' id='prev_load_<?=$row->Keyword ?>' style='display:none;'><?=WIDGET_LIST_LOAD ?></div>
		<div class='nc_widget_prev nc-box' id='prev_<?=$row->Keyword ?>' style='display:none;'></div>
		<br>
	</div>

<? endforeach ?>
</div>