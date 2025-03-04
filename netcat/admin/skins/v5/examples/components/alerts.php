<?php if ( ! defined('NC')) exit ?>
<?/*------------------------------------------------------------------------*/?>

<? example('nc-alert') ?>

<?=$nc_core->ui->alert('Серенькое предупреждение') ?>

<?=$nc_core->ui->alert->error('Предупреждение об ошибке') ?>

<?=$nc_core->ui->alert->info('Информационное сообщение') ?>

<?=$nc_core->ui->alert->success('Сообщение об успешном выполнении операции') ?>

<? example('nc-alert | colors') ?>

<? foreach ($accent_colors as $color): ?>
	<?=$nc_core->ui->alert('Просто предупреждение: ' . $color)->$color() ?>
<? endforeach ?>