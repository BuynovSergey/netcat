<?php
if (!class_exists('nc_core')) {
    die;
}

if ($error) {
    echo $ui->alert->error($error);
}

?>

<form method='post' action='<?=$action_url.'move_finish'?>'>

    <?= TOOLS_REDIRECT_GROUP ?>:<br/>
    <?= nc_admin_select_simple('', 'group', $groups, $group) ?><br/>
    
<h3><?= TOOLS_REDIRECT_MOVE_CONFIRM_REDIRECTS ?></h3>

    <table class='nc-table nc--striped nc--small' width='100%'>
        <tr>
            <th >ID</th>
            <th width=35%><?= TOOLS_REDIRECT_OLDURL ?></th>
            <th width=35%><?= TOOLS_REDIRECT_NEWURL ?></th>
            <th class='nc-text-center'><?= TOOLS_REDIRECT_HEADER ?></th>        </tr>
        <?
        foreach ($redirects as $redirect) {
            print "<tr>";
            print "<td >{$redirect['id']}</td>\n";
            print "<td>{$redirect['old_url']}</a></td>";
            print "<td>{$redirect['new_url']}</td>";
            print "<td class='nc-text-center'>{$redirect['header']}
                <input type=hidden name='redirect[]' value={$redirect['id']}></td>";
            print "</tr>";
        }
        ?>
    </table><br>
    <input type='submit' class='hidden'>
    <?=nc_core('token')->get_input()?>
</form>