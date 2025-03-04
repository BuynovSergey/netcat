<?php if ( ! defined('NC')) exit ?>
<?/*------------------------------------------------------------------------*/?>

<? example('nc-tabs') ?>
<?php
$tabs = $nc_core->ui->tabs();
$tabs->add_btn('#', 'Настройки');
$tabs->add_btn('#', 'Инфоблоки');
$tabs->add_btn('#', 'Редактирование')->active();
$tabs->add_btn('#', 'Просмотр')->disabled();
?>
<?=$tabs ?>


<? example('nc-tabs nc--small') ?>
<?php
$tabs = $nc_core->ui->tabs()->small();
$tabs->add_btn('#', 'Настройки')->active(false);
$tabs->add_btn('#', 'Инфоблоки')->active(false);
$tabs->add_btn('#', 'Редактирование')->active(true);
$tabs->add_btn('#', 'Просмотр')->active(false);
?>
<?=$tabs ?>