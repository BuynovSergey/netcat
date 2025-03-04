<?php

if (!class_exists('nc_core')) {
    die;
}

/** @var int $infoblock_id */
/** @var string $infoblock_name */
/** @var nc_core $nc_core */

$header = $nc_core->get_settings('StoreVersions')
    ? NETCAT_MODERATION_REMOVE_INFOBLOCK_CONFIRMATION_HEADER_CAN_RESTORE
    : NETCAT_MODERATION_REMOVE_INFOBLOCK_CONFIRMATION_HEADER;

?>
<div class="nc-modal-dialog" data-width="300" data-height="auto">
    <div class="nc-modal-dialog-header">
        <h2><?= $header ?></h2>
    </div>
    <div class="nc-modal-dialog-body">
        <form action="<?= $nc_core->SUB_FOLDER . $nc_core->HTTP_ROOT_PATH ?>action.php" method="post" class="nc-form">
            <input type="hidden" name="ctrl" value="admin.infoblock">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="infoblock_id" value="<?= $infoblock_id ?>">

            <?= sprintf(NETCAT_MODERATION_REMOVE_INFOBLOCK_CONFIRMATION_BODY, $infoblock_name); ?>

        </form>
    </div>
    <div class="nc-modal-dialog-footer">
        <button data-action="submit"><?= NETCAT_MODERATION_DELETE_BLOCK ?></button>
        <button data-action="close"><?= CONTROL_BUTTON_CANCEL ?></button>
    </div>

    <script>
    (function() {
        var dialog = nc.ui.modal_dialog.get_current_dialog();
        dialog.set_option('on_submit_response', function(response) {
            var error = nc_check_error(response);
            if (error) {
                dialog.show_error(error);
            } else {
                location.href = location.toString().replace(/&cc=<?= $infoblock_id ?>\b/, '');
                location.reload();
            }
        });
    })();
    </script>

</div>
