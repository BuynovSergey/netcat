<?php
if (!class_exists('nc_core')) {
    die;
}
?>
<div class="nc_admin_form_menu" style='top:-77px'>
    <h2><?=WIDGET_DELETE_CONFIRMDELETE ?></h2>
</div>
<div class='nc_admin_form_body'>
    <form id='adminForm' class='nc-form' name='adminForm' method="post" action="<?=$post_url ?>">
        <input type="hidden" name="block_widget_id" value="<?=$block_widget_id ?>">
        <input type="hidden" name="back_link" value="<?=$back_link ?>">
        <input type="hidden" name="confirmed" value="1">
        <?=WIDGET_DELETE ?>
    </form>
</div>
<div class='nc_admin_form_buttons'>
    <button type='button' class='nc_admin_metro_button nc-btn nc--blue' disable><?=WIDGET_LIST_DELETE ?></button>
    <button type='button' class='nc_admin_metro_button_cancel nc-btn nc--red nc--bordered nc--right'><?=CONTROL_BUTTON_CANCEL ?></button>
</div>

<script>
prepare_message_form();
</script>