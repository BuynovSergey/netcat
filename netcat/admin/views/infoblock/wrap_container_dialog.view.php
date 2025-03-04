<?php
if (!class_exists('nc_core')) {
    die;
}

$position_keyword = "";
$preview_wrapper_position_class = "";

if ($position == "wrap_after" && $main_axis == "vertical") {
    $position_keyword = BLOCK_ORIENTATION_RIGHT;
}

if ($position == "wrap_before" && $main_axis == "vertical") {
    $position_keyword = BLOCK_ORIENTATION_LEFT;
}

if ($position == "wrap_after" && $main_axis == "horizontal") {
    $position_keyword = BLOCK_ORIENTATION_BOTTOM;
}

if ($position == "wrap_before" && $main_axis == "horizontal") {
    $position_keyword = BLOCK_ORIENTATION_TOP;
}

$layout_keyword = $main_axis == "horizontal" ? NETCAT_MODAL_DIALOG_WRAP_CONTAINER_LAYOUT_Y_AXIS :
    NETCAT_MODAL_DIALOG_WRAP_CONTAINER_LAYOUT_X_AXIS;
?>

<div class="nc-modal-dialog" data-confirm-close="false" data-width="756" data-height="auto" data-focus="false">
    <div class="nc-modal-dialog-header">
        <h2><?= NETCAT_MODERATION_ADD_BLOCK_WRAP_TITLE ?></h2>
    </div>

    <div class="nc-modal-dialog-body nc-wrap-container-dialog">
        <form action="<?= $nc_core->SUB_FOLDER . $nc_core->HTTP_ROOT_PATH; ?>action.php" method="get" class="nc-form">
            <input type="hidden" name="action" value="show_new_infoblock_simple_dialog">
            <input type="hidden" name="ctrl" value="admin.infoblock">
            <input type="hidden" name="site_id" value="<?= $site_id ?>">
            <input type="hidden" name="subdivision_id" value="<?= $subdivision_id; ?>">
            <input type="hidden" name="container_id" value="<?= $container_id ?>">
            <input type="hidden" name="infoblock_id" value="<?= $infoblock_id; ?>">
            <input type="hidden" name="area_keyword" value="<?= htmlspecialchars($area_keyword); ?>">
            <input type="hidden" name="position" value="<?= htmlspecialchars($position); ?>">
            <input type="hidden" name="main_axis" value="<?= htmlspecialchars($main_axis); ?>">
            <input type="hidden" name="notice_accepted" value="1">
            <input type="hidden" name="isNaked" value="1">
            <input type="hidden" name="admin_modal" value="1">
        </form>

        <div class="nc-content-wrapper">
            <div class="nc-description">
                <?= sprintf(
                    NETCAT_MODAL_DIALOG_WRAP_CONTAINER_BODY_CONTENT,
                    $position_keyword,
                    $infoblock_name ?
                        sprintf(NETCAT_MODERATION_ADD_BLOCK_RELATIVE_TO_BLOCK, $infoblock_name) :
                        NETCAT_MODERATION_ADD_BLOCK_RELATIVE_TO_THIS_CONTAINER,
                    $layout_keyword
                ) ?>
            </div>

            <div class="nc-preview-container nc--<?= $main_axis ?>">
                <div class="nc-preview-wrapper nc--<?= $main_axis . " nc--$position" ?>">
                    <div class="nc-sibling-block-image" <?= $component_preview_image ?
                        "style='background-image:url($component_preview_image);'" : "" ?>></div>
                    <div class="nc-embedded-block">
                        <div class="nc-plus-icon"></div>
                        <div class="nc-title"><?= NETCAT_MODERATION_NEW_BLOCK_CAPTION ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="nc-modal-dialog-footer">
        <button data-action="submit"><?= NETCAT_MODAL_DIALOG_WRAP_CONTAINER_SUBMIT_BUTTON_CAPTION ?></button>
        <button data-action="close"><?= CONTROL_BUTTON_CANCEL ?></button>
    </div>
</div>

<script>
    (function () {
        const form = $nc(".nc-wrap-container-dialog form");
        const dialog = nc.ui.modal_dialog.get_current_dialog();

        dialog.set_option("on_submit_response", () => {
            dialog.destroy();
            nc.load_dialog(form.attr("action") + "&" + form.serialize());
        });
    })()
</script>
