<?php if ( ! defined('NC')) exit ?>
<?/*------------------------------------------------------------------------*/?>

<? example('nc-toolbar') ?>

<?php
$toolbar = $nc_core->ui->toolbar();
$toolbar->add_text('Toolbar');
$toolbar->add_btn('#', 'Action');
$toolbar->add_divider();
$toolbar->add_btn('#', 'Edit')->icon('edit');
$toolbar->add_btn('#', 'Copy')->icon('copy');
$toolbar->add_btn('#', 'Remove')->icon('remove');
$toolbar->add_divider();
$toolbar[] = $nc_core->ui->html('li')->div()->code('CUSTOM ITEM');
?>
<?=$toolbar ?>

<br>

<div class="nc-admin-mode-content-box">

    <?php
    $toolbar = $nc_core->ui->toolbar()->right();
    $toolbar->add_text('Toolbar');
    $toolbar->add_btn('#', 'Добавить');
    $toolbar->add_btn('#')->icon('dev-components');
    $toolbar->add_btn('#')->icon('settings');
    $toolbar->add_btn('#')->icon('remove');
    $toolbar->add_divider();
    $toolbar->add_btn('#')->alt()->icon('selected-on');
    $toolbar->add_btn('#')->alt()->icon('selected-off');
    $toolbar->add_btn('#')->alt()->icon('selected-remove');
    ?>
    <?=$toolbar ?>

    <?php
    $toolbar = $nc_core->ui->toolbar()->left();
    $toolbar->add_move_place();
    $toolbar->add_text('127');
    $toolbar->add_btn('#', 'Вкл')->on();
    $toolbar->add_btn('#')->icon('edit');
    $toolbar->add_btn('#')->icon('copy');
    $toolbar->add_btn('#')->icon('remove');
    ?>
    <?=$toolbar ?>

    <?=$nc_core->ui->helper->clearfix() ?>
    Lorem ipsum dolor sit amet, consectetur adipiscing elit.

    <hr>

    <?php
    $toolbar = $nc_core->ui->toolbar()->left();
    $toolbar->add_move_place();
    $t_id     = $toolbar->add_text(1);
    $t_status = $toolbar->add_btn('#off.1', 'Выкл')->off();
    ?>

    <?=$toolbar->disabled(true) ?><?=$nc_core->ui->helper->clearfix() ?>


    <? $t_id->text(2) ?>
    <? $t_status->text('Вкл')->href('#on.2')->on() ?>
    <?=$toolbar->disabled(false) ?><?=$nc_core->ui->helper->clearfix() ?>


    <? $t_id->text(3) ?>
    <? $t_status->text('Вкл')->href('#on.3')->on() ?>
    <?=$toolbar->disabled(false) ?><?=$nc_core->ui->helper->clearfix() ?>

</div>
<!-- /.nc-admin-mode-content-box -->