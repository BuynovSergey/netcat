<?php
if (!class_exists('nc_core')) {
    die;
}

if ($error) {
    echo $ui->alert->error($error);
}

?>

<form method='post' action='<?=$action_url.'save_group'?>'>
    <br/><?= TOOLS_REDIRECT_GROUP_NAME ?>:<br/>
    <?= nc_admin_input_simple('group_name', $group_name, 70, '', "maxlength='255'") ?>
    <?= nc_core('token')->get_input() ?>
    <input type='submit' class='hidden' />
    <input type='hidden' name='group' value='<?= $group ?>' />
</form>
