<?php
if (!class_exists('nc_core')) {
    die;
}

if ($redirect->get_last_error()) {
    echo $ui->alert->error($redirect->get_last_error());
}
?>
<form method='post' action='<?=$action_url.'save'?>'>
    <br/>
    <?= nc_admin_checkbox_simple("checked", 1, NETCAT_MODERATION_TURNTOON, $redirect['checked']) ?>
    <br/><br/>
    <?= TOOLS_REDIRECT_OLDLINK ?>:<br/>
        <?= nc_admin_input_simple('old_url', $redirect['old_url'], 70, '', "maxlength='255'") ?><br/><br/>
    <?= TOOLS_REDIRECT_NEWLINK ?>:<br/>
        <?= nc_admin_input_simple('new_url', $redirect['new_url'], 70, '', "maxlength='255'") ?><br/><br/>
    <?= TOOLS_REDIRECT_HEADERSEND ?>:<br/>
        <?= nc_admin_select_simple('', 'header', array(301 => 301, 302 => 302), $redirect['header']) ?><br/><br/>
    <?= TOOLS_REDIRECT_GROUP ?>:<br/>
        <?= nc_admin_select_simple('', 'group', $groups, $group) ?>
    <?= nc_core('token')->get_input()?>
    
    <? if ($redirect->get_id()) { ?>
        <input type='hidden' name='id' value='<?= $redirect->get_id() ?>' />
    <? } ?>
    <input type='submit' class='hidden' />
</form>