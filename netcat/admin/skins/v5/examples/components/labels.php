<?php if ( ! defined('NC')) exit ?>
<?/*------------------------------------------------------------------------*/?>

<? example('nc-label') ?>

<? foreach ($bw_colors as $color): ?>
	<?=$nc_core->ui->label($color)->$color() ?>
<? endforeach ?>

<? foreach ($accent_colors as $color): ?>
	<?=$nc_core->ui->label($color)->$color() ?>
<? endforeach ?>


<? example('nc-label nc--rounded') ?>

<? foreach ($bw_colors as $color): ?>
	<?=$nc_core->ui->label($color)->rounded()->$color() ?>
<? endforeach ?>

<? foreach ($accent_colors as $color): ?>
	<?=$nc_core->ui->label($color)->rounded()->$color() ?>
<? endforeach ?>

<? example('nc-label | дополнительно') ?>

<?=$nc_core->ui->label('link label')->blue()->href('#') ?>


<? $label = $nc_core->ui->label->yellow()->href('#')->rounded() ?>

<?=$label->icon('edit') ?>

<?=$label->icon('field-string') ?>

<?=$label->icon('field-text') ?>

<?=$label->icon('field-int') ?>

<?=$label->icon('field-bool') ?>


<?=$nc_core->ui->label('icon link label')->red()->icon('settings nc--white') ?>

