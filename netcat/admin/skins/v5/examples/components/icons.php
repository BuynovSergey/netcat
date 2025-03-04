<?php if ( ! defined('NC')) exit ?>
<?/*------------------------------------------------------------------------*/?>

<?php $icons = get_icons('icons-20') ?>
<? $k = ceil( count($icons) / 4 ) ?>

<? example('nc-icon') ?>
<table class="nc-box nc--light" width="100%">
<col width="25%" /><col width="25%" /><col width="25%" /><col width="25%" />
<tr valign="top">
<td>
<? foreach ($icons as $i => $ico): ?>
<? if($i && $i%$k==0): ?>
</td><td>
<?endif ?>
	<p>
		<?=$nc_core->ui->icon($ico) ?>

		<?=$nc_core->ui->icon($ico)->white() ?>

		<?=$nc_core->ui->icon($ico)->dark() ?>

		<?=$ico ?>

	</p>
<? endforeach ?>
</td>
</tr>
</table>
<? example() ?>

<?/*------------------------------------------------------------------------*/?>

<? example('nc-icon nc--required & nc--badge-*') ?>


	<? foreach ($accent_colors as $color): ?>
		<i class='nc-icon nc--folder-dark nc--badge-<?=$color ?>'></i>
	<? endforeach ?>

	<i class='nc-icon nc--field-int nc--required'></i>

<? example() ?>

<?/*------------------------------------------------------------------------*/?>

<? example('nc-icon nc--hovered') ?>
<div class="nc-box">
<? foreach ($icons as $i => $ico): ?>
    <?=$nc_core->ui->icon($ico)->title($ico)->hovered() ?>
<? endforeach ?>
</div>
<? example() ?>

<?/*------------------------------------------------------------------------*/?>

<? example('nc-icon nc--disabled') ?>
<div class="nc-box">
<? foreach ($icons as $i => $ico): ?>
    <?=$nc_core->ui->icon($ico)->title($ico)->disabled() ?>
<? endforeach ?>
</div>
<? example() ?>

<?/*------------------------------------------------------------------------*/?>

<?php $icons = get_icons('icons-34') ?>
<? example('nc-icon-l') ?>
<div class="nc-box nc--grey">
<? foreach ($icons as $i => $ico): ?>
    <?=$nc_core->ui->icon($ico)->title($ico)->large() ?>
<? endforeach ?>
</div>
<? example() ?>

<?/*------------------------------------------------------------------------*/?>

<? example('nc-icon-l nc--disabled') ?>
<div class="nc-box nc--grey">
<? foreach ($icons as $i => $ico): ?>
    <?=$nc_core->ui->icon($ico)->title($ico)->large()->disabled() ?>
<? endforeach ?>
</div>
<? example() ?>

<?/*------------------------------------------------------------------------*/?>

<?php $icons = get_icons('icons-50-dark') ?>
<? example('nc-icon-x') ?>
<div class="nc-box nc--grey">
<? foreach ($icons as $i => $ico): ?>
    <?=$nc_core->ui->icon($ico)->title($ico)->xlarge() ?>
<? endforeach ?>
</div>
<? example() ?>

<?/*------------------------------------------------------------------------*/?>

<?php $icons = get_icons('icons-50-white') ?>
<? example('nc-icon-x nc--white') ?>
<div class="nc-box nc--grey">
<? foreach ($icons as $i => $ico): ?>
    <?=$nc_core->ui->icon($ico)->title($ico)->xlarge()->white() ?>
<? endforeach ?>
</div>
<? example() ?>

<?/*------------------------------------------------------------------------*/?>



<?php
//--------------------------------------------------------------------------
function get_icons($folder) {
	$files = scandir("../img/{$folder}/");
	$icons = array();
	foreach ($files as $f)
	{
		if (preg_match('@(.*)\.png$@', $f, $m)) $icons[] = $m[1];
	}
	return $icons;
}
//--------------------------------------------------------------------------
