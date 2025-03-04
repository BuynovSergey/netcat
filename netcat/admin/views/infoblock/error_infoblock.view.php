<?php

if (!class_exists('nc_core')) {
    die;
}

?>
<div class="nc-modal-dialog" data-width="300" data-height="auto">
    <div class="nc-modal-dialog-header">
        <h2><?=CONTROL_CONTENT_SUBDIVISION_DISALLOW_ERROR_ACTION_TITLE ?></h2>
    </div>
    <div class="nc-modal-dialog-body">
        <div class="nc-form">
            <?=($type_action == 'delete' ? sprintf(CONTROL_CONTENT_SUBDIVISION_DISALLOW_ERROR_DELETE_SUB_CLASS, "") : "") ?>
            <?=($type_action == 'cut' ? sprintf(CONTROL_CONTENT_SUBDIVISION_DISALLOW_ERROR_CUT_SUB_CLASS, "") : "") ?>
            <?=($type_action == 'hide' ? CONTROL_CONTENT_SUBDIVISION_DISALLOW_ERROR_HIDE_SUB_CLASS : "") ?>
        </div>
    </div>
    <div class="nc-modal-dialog-footer">       
        <button data-action="close"><?= CONTROL_BUTTON_CANCEL ?></button>
    </div>
</div>
