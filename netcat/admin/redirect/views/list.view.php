<?php
if (!class_exists('nc_core')) {
    die;
}
$nc_core = nc_core::get_object();
?>

<script src='<?= nc_add_revision_to_url($nc_core->ADMIN_PATH . 'redirect/redirect.js') ?>'></script>
<script>
    check_text = ['<?=NETCAT_MODERATION_OBJ_OFF?>' , '<?=NETCAT_MODERATION_OBJ_ON?>'];
    nc_token = '<?=nc_core('token')->get_url()?>';
</script>

<form method=post id='redirect_list' action="<?=$action_url?>">
    <?php if ($status) { ?>
        <?=$ui->alert->info($status); ?>
    <?php } else {?>
    <table class='nc-table nc--striped nc--small' width='100%'>
        <tr>
            <th >ID</th>
            <th ><?= TOOLS_REDIRECT_STATUS ?></th>
            <th width=35%><?= TOOLS_REDIRECT_OLDURL ?></th>
            <th width=35%><?= TOOLS_REDIRECT_NEWURL ?></th>
            <th class='nc-text-center'><?= TOOLS_REDIRECT_HEADER ?></th>
            <th class='nc-text-center'><?= TOOLS_REDIRECT_SETTINGS ?></th>
            <th class='nc-text-center'>
                <?= nc_admin_checkbox_simple('', '' , '', false, 'checkall', 'onclick="check_all();" title="check all"') ?>
            </th>
        </tr>
        <? foreach ($redirects as $redirect) { ?>
            <tr>
            <td><?= $redirect['id'] ?></td>
            <td>
                <a href='<?=$action_url . 'check&group=' . $group . '&redirect[]=' . $redirect['id'] . '&check=' .((int)!$redirect['checked']) ?>'
                   onclick="nc_action_ajax(this.href, <?=$redirect['id']?>); return false;" id='redirect_<?=$redirect['id']?>'>
                        <?= ($redirect['checked'] ?
                        "<span onclick='return false;' class='nc-text-green nc-h4'>".NETCAT_MODERATION_OBJ_ON."</span>" :
                        "<span onclick='return false;' class='nc-text-red nc-h4'>".NETCAT_MODERATION_OBJ_OFF."</span>") ?>
                </a>
            </td>
            <td><?= htmlspecialchars($redirect['old_url']) ?></a></td>
            <td><?= htmlspecialchars($redirect['new_url']) ?></td>
            <td class='nc-text-center'><?= $redirect['header'] ?></td>
            <td class='nc-text-center'>
                <a href=<?= nc_core()->NETCAT_FOLDER."action.php?ctrl=admin.redirect.redirect&action=edit&id=" . $redirect['id'] ?>>
                    <i class='nc-icon nc--edit' title='<?= TOOLS_REDIRECT_EDIT ?>'>
                </a></td>
            <td class='nc-text-center'><?= nc_admin_checkbox_simple("redirect[]", $redirect['id'],0,0,0,"class='redirect_box'") ?></td>
            </tr>
        <? } ?>
    </table><br>
    <? } ?>
    <?= nc_core('token')->get_input()?>
    <input type='action' class='hidden'>
    <input type='submit' class='hidden'>
</form>