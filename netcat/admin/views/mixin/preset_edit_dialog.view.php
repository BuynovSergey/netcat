<?php

if (!class_exists('nc_core')) {
    die;
}

/** @var array $data */
/** @var nc_core $nc_core */
/** @var array $data */
/** @var array $component_select_options */
/** @var int $current_component_template_id */
/** @var array $is_default_for_current_component_template */

$mixin_preset_id = (int)nc_array_value($data, 'Mixin_Preset_ID');
$mixin_component_template_id = (int)nc_array_value($data, 'Class_Template_ID');

?>
<div class="nc-modal-dialog">
    <div class="nc-modal-dialog-header">
        <h2><?= $mixin_preset_id ? NETCAT_MIXIN_PRESET_TITLE_EDIT : NETCAT_MIXIN_PRESET_TITLE_ADD ?></h2>
    </div>
    <div class="nc-modal-dialog-body">
        <form action="<?= $nc_core->SUB_FOLDER . $nc_core->HTTP_ROOT_PATH ?>action.php" method="post" class="nc-form">

            <input type="hidden" name="ctrl" value="admin.mixin_preset">
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="component_template_id" value="<?= $current_component_template_id ?>">
            <input type="hidden" name="data[Mixin_Preset_ID]" value="<?= $mixin_preset_id ?>">
            <input type="hidden" name="data[Scope]" value="<?= htmlspecialchars($data['Scope']) ?>">

            <div style="display: flex">
                <label style="margin: 0 10px 10px 0; width: calc(50% - 10px)">
                    <?= NETCAT_MIXIN_PRESET_NAME ?>:<br>
                    <input type="text" required
                           class="nc-input nc--wide"
                           name="data[Mixin_Preset_Name]"
                           value="<?= htmlspecialchars(nc_array_value($data, 'Mixin_Preset_Name')) ?>">
                </label>

                <label style="margin: 0 0 10px 10px; width: calc(50% - 10px)">
                    <?= NETCAT_MIXIN_PRESET_AVAILABILITY ?>:<br>
                    <select name="data[Class_Template_ID]"
                            class="nc-select nc-mixin-preset-dialog-component-template-select"
                            style="max-width: 100%">
                        <?php foreach ($component_select_options as $key => $value): ?>
                        <option value="<?= $key ?>"<?= $mixin_component_template_id == $key ? ' selected' : ''?>><?= $value ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>

            </div>

            <?php if (isset($component_select_options[$current_component_template_id])): ?>
                <?php if ($is_default_for_current_component_template): ?>
                    <input type="hidden" name="set_as_default" value="0">
                <?php endif; ?>
                <label>
                    <input type="checkbox" name="set_as_default" value="1"<?= $is_default_for_current_component_template ? ' checked' : '' ?>>
                    <?= NETCAT_MIXIN_PRESET_USE_AS_DEFAULT_FOR ?>
                    <?= $component_select_options[$current_component_template_id] ?>
                </label>
            <?php endif; ?>

            <?= $this->include_view('mixin_editor', array(
                    'data' => $data,
                    'scope' => nc_array_value($data, 'Scope'),
            )) ?>

        </form>
    </div>
    <div class="nc-modal-dialog-footer">
        <button data-action="submit"><?= NETCAT_REMIND_SAVE_SAVE ?></button>
        <button data-action="close"><?= CONTROL_BUTTON_CANCEL ?></button>
    </div>
</div>
