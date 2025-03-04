<?php if ( ! defined('NC')) exit ?>
<?/*------------------------------------------------------------------------*/?>

<?php
    $data = array(
        array('id'=>'1', 'status'=>1, 'name'=>'John Smith', 'email'=>'smith@mail.local'),
        array('id'=>'2', 'status'=>1, 'name'=>'Ivan Sidorov', 'email'=>'sidorov@mail.local'),
        array('id'=>'3', 'status'=>0, 'name'=>'Olga Borisova', 'email'=>''),
        array('id'=>'4', 'status'=>1, 'name'=>'Vova Pu', 'email'=>'vova@pu.local'),
        array('id'=>'555', 'status'=>1, 'name'=>'Neo', 'email'=>'neo@ma.xxx'),
    );
?>

<? example('nc-table') ?>

<?=$nc_core->ui->table($data)->set_heading('#', 'Status', 'Name', 'Email') ?>



<? example('nc-table nc--wide nc--striped nc--hovered nc--bordered nc--small') ?>

<?php
    $table = $nc_core->ui->table()->wide()->striped()->hovered()->bordered()->small();
    $table->thead()
        ->th('#')->text('#')->compact()
        ->th('Name')
        ->th('Email');

    foreach ($data as $row) {
        $table->add_row($row['id'], $row['name'], $row['email']);
    }
?>
<?=$table ?>


<? example('nc-table | advanced') ?>

<?php
    $table = $nc_core->ui->table()->bordered()->small();
    $table->thead()
        ->th('#')->text_right()
        ->th('Name')
        ->th('Email');

    foreach ($data as $row) {
        $table->add_row()->modificator($row['status'] ? 'green' : 'red')
            ->td($row['id'])->text_right()
            ->td($row['name'])->href('#user-' . $row['id'])
            ->td($row['email']);
    }
?>
<?=$table ?>


<? example('nc-table | advanced 2') ?>

<?php
    $table = $nc_core->ui->table()->bordered()->small()->wide();
    $table->thead()
        ->th('')->compact()
        ->th('#')->text_center()->compact()
        ->th('Name')
        ->th('Email');

    $t_row = $table->row();
    $t_row->status = $t_row->td()->href('#')->icon('off');;
    $t_row->id     = $t_row->td()->text_center();
    $t_row->name   = $t_row->td();
    $t_row->email  = $t_row->td();

    foreach ($data as $row) {
        $t_row->status->icon($row['status'] ? 'on' : 'off');
        $t_row->id->text($row['id']);
        $t_row->name->text($row['name']);
        $t_row->email->text($row['email']);
        $table->add_row($t_row);
    }

?>
<?=$table ?>