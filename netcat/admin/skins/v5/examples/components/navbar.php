<?php if ( ! defined('NC')) exit ?>
<?/*------------------------------------------------------------------------*/?>

<script type="text/javascript">
$nc().ready(function(){
    $nc('.nc-navbar>ul>li').mouseover(function(){
        $nc(this).addClass('nc--clicked');
        return false;
    }).mouseout(function(){
        $nc(this).removeClass('nc--clicked');
    });
});
</script>


<? example('nc-navbar') ?>
<?php
$navbar = $nc_core->ui->navbar();

// Menu
$menu = $navbar->menu();
$menu->add_btn('#')->icon_large('logo-white');
$menu->add_btn('#', 'просто');
$usermenu = $menu->add_btn('#', 'пользователи')->submenu();
    $usermenu->add_btn('#user.add', 'Регистрация пользователя')->icon('user-add');
    $usermenu->add_btn('#usergroup.list', 'Группы пользователей')->icon('user-add')->disabled();
    $usermenu->add_text('Без иконки:');
    $usermenu->add_btn('#', 'Без иконки 1')->icon('');
    $usermenu->add_btn('#', 'Без иконки 2')->icon('');
    $usermenu->add_divider();
    $usermenu->add_btn('#', 'Рассылка по базе')->icon('mod-subscriber');

$menu->add_btn('#', 'без иконок')->submenu()
    ->add_btn('#', 'Без иконки и отступа 1')
    ->add_btn('#', 'Без иконки и отступа 2')
    ->add_btn('#', 'Без иконки и отступа 3')->divider();

// Tray
$tray = $navbar->tray();

$tray->add_btn('#')->icon_large('navbar-loader');//->style('display:none');
$tray->add_btn('#', 'custom')->dropdown()->div(
    "Ваши права: <span class='nc-text-grey'>Директор</span><hr>"
    . $nc_core->ui->btn('#', 'Изменить пароль')->left()
    . $nc_core->ui->btn('#', 'Выход')->red()->right()
)->class_name('nc-padding-10');
?>

<?=$navbar ?>

<hr>

<? example('nc-navbar + quickmenu') ?>
<?php // Quickbar
$navbar->quickmenu()
    ->add_btn('#', 'просмотр')
    ->add_btn('#', 'редактирование')->active();
$navbar->menu()->reset();
?>

<?=$navbar->bordered() /* ->fixed() */ ?>