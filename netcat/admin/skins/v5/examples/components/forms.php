<?php if ( ! defined('NC')) exit ?>
<?/*------------------------------------------------------------------------*/?>

<? example('nc-form | Размеры и цвета') ?>
<?php
$form = $nc_core->ui->form('#submit')->vertical();
$form->add_row()->string('fname', 21)->mini();
$form->add_row()->string('fname', 'small red')->small()->red();
$form->add_row()->string('fname', 'medium blue')->medium()->blue();
$form->add_row()->string('fname', 'large green')->large()->green();
$form->add_row()->string('fname', 'xlarge yellow')->xlarge()->yellow();
$form->add_row()->string('fname', 'blocked grey')->blocked()->grey();
?>
<?=$form ?>



<? example('nc-form  nc--vertical | Все поля') ?>
<?php
$select_options = array(1=>'Администраторы', 2=>'Супервизоры', 3=>'Модераторы', 4=>'Пользователи', 5=>'Гости');

$form = $nc_core->ui->form('#submit')->vertical();
$form->add_row('Название поля 1')->string('field1')->large();
$form->add_row('Название поля 2')->textarea('field2')->rows(3)->large();
$form->add_row('Название поля 3')->select('field3', $select_options, 3)->large();
$form->add_row('Название поля 4')->multiple('field4', $select_options, array(1,3))->large();
$form->add_row('Название поля 5')->horizontal()->checkbox('field5', true, 'Label');
$form->add_row('Название поля 6')->horizontal()->radiogroup('field6', array('Нет', 'Да', 'Не задано'), 2);
$form->actions()
    ->button('button', 'Отменить')->red()->click('alert(1)')
    ->button('submit', 'Отправить')->blue()->right()
    ->button('button', 'Просмотр')->right();
?>
<?=$form ?>



<? example('nc-form nc--horizontal') ?>
<?php
$form = $nc_core->ui->form('#submit')->multipart()->horizontal();
$form->add_row('Название поля 1')->string('field1')->large();
$form->add_row('Название поля 2')->textarea('field2')->rows(3)->large();
$form->add_row('Название поля 3')->select('field3', $select_options, 3)->large();
$form->add_row('Название поля 4')->multiple('field4', $select_options, array(1,3))->large();
$form->add_row('Название поля 5')->vertical()->checkbox('field5', true, 'Label');
$form->add_row('Название поля 6')->vertical()->radiogroup('field6', array('Нет', 'Да', 'Не задано'), 2);
$form->actions()
    ->button('button', 'Отменить')->red()->click('alert(1)')
    ->button('submit', 'Отправить')->blue()->right()
    ->button('button', 'Просмотр')->right();
?>
<?=$form ?>




<? example('nc-form') ?>
<?php
$form = $nc_core->ui->form('#submit', 'get');
$form->add()->label('Название поля');
$form->add()->string('search')->placeholder('Поиск...')->large();
$form->add()->button('submit', 'Найти')->red();
?>


<?=$form ?>