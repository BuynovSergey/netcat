<?php
if (!class_exists('nc_core')) {
    die;
}

echo include_cd_files();
echo nc_get_simple_modal_header($list['Classificator_Name']);
?>

<form method='post' id='adminForm' class='nc-form' action='<?=$nc_core->SUB_FOLDER . $nc_core->HTTP_ROOT_PATH?>action.php?ctrl=admin.list&action=update_item'>
    <input type='hidden' name='classificator_id' value='<?= $classificator_id; ?>'/>
    <input type='hidden' name='item_id' value='<?= $item_id; ?>'/>
    <div><?= CONTENT_CLASSIFICATORS_ELEMENT_NAME; ?>:</div>
    <div><?= nc_admin_input_simple('list_item_name', $item['Name'], 50, '', "maxlength='256'"); ?></div>
    <div><?= nc_admin_textarea_simple('list_item_value', $item['Value'], "<div>" . CONTENT_CLASSIFICATORS_ELEMENT_VALUE . ":</div>", 7, 0, '', 'soft'); ?></div>

    <?
    if ($custom_settings) {
        $a2f = new nc_a2f($custom_settings, 'custom_settings');
        $a2f->set_initial_values();
        $a2f->set_values($item['CustomSettings']);
        echo '<div class="list_custom_settings">';
        echo $a2f->render("<hr>", "<div>%CAPTION</div><div>%VALUE <em class='nc-text-grey'>%DEFAULT</em></div><div></div>", "", "");
        echo '</div>';
    }
    ?>
</form>
<script>prepare_message_form();</script>

<div class='nc_admin_form_buttons'>
    <button type='button' class='nc_admin_metro_button nc-btn nc--blue' disable><?= NETCAT_REMIND_SAVE_SAVE; ?></button>
    <button type='button' class='nc_admin_metro_button_cancel nc-btn nc--red nc--bordered nc--right'><?= CONTROL_BUTTON_CANCEL ?></button>
</div>

<style>
    .list_custom_settings .nc-text-grey{
        display: inline-block;
        margin-left:10px;
    }
    .list_custom_settings .nc-text-grey:before {
        display: inline-block;
        content: '<?=CONTENT_CLASSIFICATORS_CUSTOM_BY_DEFAULT?>';
        margin-right:5px;
    }
    .list_custom_settings .nc-text-grey:empty {
        display: none;
    }
    a { color: #1a87c2; }
    a:hover { text-decoration: none; }
    a img { border: none; }
    p { margin: 0px; padding: 0px 0px 18px 0px; }
    h2 { font-size: 20px; font-family: 'Segoe UI', SegoeWP, Arial; color: #333333; font-weight: normal; margin: 0px; padding: 20px 0px 10px 0px; line-height: 20px; }
    form { margin: 0px; padding: 0px; }
    input { outline: none; }
    .clear { margin: 0px; padding: 0px; font-size: 0px; line-height: 0px; height: 1px; clear: both; float: none; }
    select, input, textarea { border: 1px solid #dddddd; }
    :focus { outline: none; }
    .input { outline: none; border: 1px solid #dddddd; }
</style>
