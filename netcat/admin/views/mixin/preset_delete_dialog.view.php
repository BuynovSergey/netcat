<?php

if (!class_exists('nc_core')) {
    die;
}

/** @var nc_core $nc_core */
/** @var int $mixin_preset_id */
/** @var string $mixin_preset_name */
/** @var int $number_of_blocks */
/** @var int $number_of_templates */

?>
<div class="nc-modal-dialog" data-height="auto" data-width="500">
    <div class="nc-modal-dialog-header">
        <h2><?= NETCAT_MIXIN_PRESET_TITLE_DELETE ?></h2>
    </div>
    <div class="nc-modal-dialog-body">
        <form action="<?= $nc_core->SUB_FOLDER . $nc_core->HTTP_ROOT_PATH ?>action.php" method="post" class="nc-form">

            <input type="hidden" name="ctrl" value="admin.mixin_preset">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="mixin_preset_id" value="<?= (int)$mixin_preset_id ?>">

            <p><?= sprintf(NETCAT_MIXIN_PRESET_DELETE_WARNING, htmlspecialchars($mixin_preset_name)) ?></p>

            <? // @todo: перечисление шаблонов и блоков ?>
            <?php if ($number_of_templates): ?>
            <p>
                <?= NETCAT_MIXIN_PRESET_USED_FOR_COMPONENT_TEMPLATES ?>
                <?= $number_of_templates ?>
                <?= $nc_core->lang->get_numerical_inclination($number_of_templates, explode('/', NETCAT_MIXIN_PRESET_COMPONENT_TEMPLATES_COUNT_FORMS)) ?>.
            </p>
            <?php endif; ?>

            <?php if ($number_of_blocks): ?>
            <p>
                <?= NETCAT_MIXIN_PRESET_USED_FOR_BLOCKS ?>
                <?= $number_of_blocks ?>
                <?= $nc_core->lang->get_numerical_inclination($number_of_blocks, explode('/', NETCAT_MIXIN_PRESET_BLOCKS_COUNT_FORMS)) ?>.
            </p>
            <?php endif; ?>
        </form>
    </div>
    <div class="nc-modal-dialog-footer">
        <button data-action="submit"><?= NETCAT_MODERATION_DELETE ?></button>
        <button data-action="close"><?= CONTROL_BUTTON_CANCEL ?></button>
    </div>
</div>
