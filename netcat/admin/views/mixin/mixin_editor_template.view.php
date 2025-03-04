<?php

if (!class_exists('nc_core')) {
    die;
}

/** @var nc_core $nc_core */

$preset_controller_url = $nc_core->SUB_FOLDER . $nc_core->HTTP_ROOT_PATH . 'action.php?ctrl=admin.mixin_preset&action=';

$lang = array(
    'PRESET_DEFAULT' => NETCAT_MIXIN_PRESET_DEFAULT,
    'PRESET_DEFAULT_NONE' => NETCAT_MIXIN_PRESET_DEFAULT_NONE,
    'PRESET_CREATE' => NETCAT_MIXIN_PRESET_CREATE,
    'BREAKPOINT_ADD' => NETCAT_MIXIN_BREAKPOINT_ADD,
    'BREAKPOINT_ADD_PROMPT' => NETCAT_MIXIN_BREAKPOINT_ADD_PROMPT,
    'BREAKPOINT_ADD_PROMPT_RANGE' => NETCAT_MIXIN_BREAKPOINT_ADD_PROMPT_RANGE,
    'BREAKPOINT_CHANGE' => NETCAT_MIXIN_BREAKPOINT_CHANGE,
    'BREAKPOINT_CHANGE_PROMPT' => NETCAT_MIXIN_BREAKPOINT_CHANGE_PROMPT,
    'SELECTOR_ADD_PROMPT' => NETCAT_MIXIN_SELECTOR_ADD_PROMPT,
    'FOR_WIDTH_FROM' => NETCAT_MIXIN_FOR_WIDTH_FROM,
    'FOR_WIDTH_TO' => NETCAT_MIXIN_FOR_WIDTH_TO,
    'FOR_WIDTH_RANGE' => NETCAT_MIXIN_FOR_WIDTH_RANGE,
    'FOR_WIDTH_ANY' => NETCAT_MIXIN_FOR_WIDTH_ANY,
    'FOR_VIEWPORT_WIDTH_FROM' => NETCAT_MIXIN_FOR_VIEWPORT_WIDTH_FROM,
    'FOR_VIEWPORT_WIDTH_TO' => NETCAT_MIXIN_FOR_VIEWPORT_WIDTH_TO,
    'FOR_VIEWPORT_WIDTH_RANGE' => NETCAT_MIXIN_FOR_VIEWPORT_WIDTH_RANGE,
    'FOR_VIEWPORT_WIDTH_ANY' => NETCAT_MIXIN_FOR_VIEWPORT_WIDTH_ANY,
    'FOR_BLOCK_WIDTH_FROM' => NETCAT_MIXIN_FOR_BLOCK_WIDTH_FROM,
    'FOR_BLOCK_WIDTH_TO' => NETCAT_MIXIN_FOR_BLOCK_WIDTH_TO,
    'FOR_BLOCK_WIDTH_RANGE' => NETCAT_MIXIN_FOR_BLOCK_WIDTH_RANGE,
    'FOR_BLOCK_WIDTH_ANY' => NETCAT_MIXIN_FOR_BLOCK_WIDTH_ANY,
);

// При использовании в диалоге все <script>’ы (используются в настройках миксинов) будут
// перенесены из div.nc-mixins-editor (необходимо для корректной инициализации в диалоге),
// из-за чего простое клонирование редактора станет невозможным. Для предотвращения
// обработки <script> до инициализации редактора используется obsolete-тэг <xmp>.
?>
<xmp class="nc-mixins-editor-template" style="display: none">
    <div class="nc-mixins-editor"
         data-lang="<?= htmlspecialchars(nc_array_json($lang)) ?>"
         data-fonts="<?= htmlspecialchars(nc_array_json(nc_tpl_font::get_available_fonts())) ?>"
         data-preset-edit-dialog-url="<?= $preset_controller_url ?>show_edit_dialog"
         data-preset-delete-dialog-url="<?= $preset_controller_url ?>show_delete_dialog"
         data-upload-max-filesize="<?= nc_size2bytes(ini_get('upload_max_filesize')) ?>">

        <input type="hidden" class="nc-mixins-json" name="data[Mixin_Settings]" value="">

        <div class="nc-mixins-preset-container" style="display: none">
            <label>
                <?= NETCAT_MIXIN_PRESET_SELECT ?>:
                <select name="data[Mixin_Preset_ID]" class="nc-mixins-preset-select">
                    <option></option>
                    <option value="+"><?= NETCAT_MIXIN_PRESET_CREATE ?></option>
                </select>
            </label>
            <span class="nc-mixins-preset-actions" style="display: none">
                <i class="nc-icon nc--edit" title="<?= NETCAT_MIXIN_PRESET_EDIT_BUTTON ?>"></i>
                <i class="nc-icon nc--remove" title="<?= NETCAT_MIXIN_PRESET_REMOVE_BUTTON ?>"></i>
            </span>
        </div>

        <table class="nc-mixins-table">
            <thead>
            <tr>
                <td colspan="1" class="nc-mixins-width-head nc-mixins-width-icon" title="<?= NETCAT_MIXIN_WIDTH ?>">
                    <i class="nc-icon-block-width"></i>
                </td>
                <td rowspan="2" class="nc-mixins-head">
                    <div style="float: left; display: none" class="nc-mixins-breakpoint-type-container">
                        <div><?= NETCAT_MIXIN_BREAKPOINT_TYPE ?>:</div>
                        <select name="data[Mixin_BreakpointType]" class="nc-mixins-breakpoint-type-select">
                            <option value="block"><?= NETCAT_MIXIN_BREAKPOINT_TYPE_BLOCK ?></option>
                            <option value="viewport"><?= NETCAT_MIXIN_BREAKPOINT_TYPE_VIEWPORT ?></option>
                        </select>
                    </div>
                    <div style="float: right" class="nc-mixins-selector-container">
                        <div><?= NETCAT_MIXIN_SELECTOR ?>:</div>
                        <select class="nc-mixins-selector-select">
                            <option value=""><?= NETCAT_MIXIN_NONE ?></option>
                            <option value="+"><?= NETCAT_MIXIN_SELECTOR_ADD ?></option>
                        </select>
                        <i class="nc-icon nc--remove" title="удалить" style="display: none"></i>
                    </div>
                </td>
            </tr>
            <tr class="nc-mixins-width-ranges">
                <td class="nc-mixins-width nc-mixins-width nc-mixins-width--first nc-mixins-width--last nc-mixins-width-head" data-breakpoint="<?= nc_tpl_mixin::MAX_BLOCK_WIDTH ?>">
                    <div class="nc-mixins-breakpoint-add-button-container">
                        <div class="nc-mixins-breakpoint-add-button" title="<?= NETCAT_MIXIN_BREAKPOINT_ADD ?>">+</div>
                    </div>
                    <div class="nc-mixins-breakpoint"><span>&#x2731;<!-- ✱ --></span></div>
                </td>
            </tr>
            </thead>

            <tbody>
            <?php foreach (nc_tpl_mixin_type::get_all() as $mixin_type): ?>
                <?php
                /** @var nc_tpl_mixin_collection $mixins */
                $mixins = $mixin_type->get_mixins()->where('is_disabled', false); // значение для включённого миксина — null, но сравнение по умолчанию нестрогое
                ?>
                <tr class="nc-mixins-mixin-row" data-mixin-type="<?= $mixin_type->get('type') ?>" data-mixin-scopes="<?=
                    htmlspecialchars(nc_array_json($mixin_type->get('scopes') ?: array()))
                ?>">
                    <td class="nc-mixins-width nc-mixins-width--first nc-mixins-width--last" data-breakpoint="<?= nc_tpl_mixin::MAX_BLOCK_WIDTH ?>">
                    </td>
                    <td class="nc-mixins-settings-cell">
                        <span class="nc-mixins-mixin-type-name"><?= $mixin_type->get('name') ?></span>
                        <span class="nc-mixins-mixin-range-description"></span>
                        <span class="nc-mixins-mixin-remove">
                            <i class="nc-icon nc--remove" title="<?= NETCAT_MIXIN_SETTINGS_REMOVE ?>"></i>
                        </span>
                        <div class="nc-mixins-mixin-select-container">
                            <select name="mixin.mixin" class="nc-mixins-mixin-select">
                                <option value=""><?= NETCAT_MIXIN_NONE ?></option>
                                <?php foreach ($mixins->each('get', 'name') as $mixin_keyword => $mixin_name): ?>
                                <option value="<?= $mixin_keyword ?>"><?= $mixin_name ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="nc-mixins-mixin-settings-container">
                            <?php foreach ($mixins->each('get_block_settings_form') as $mixin_keyword => $block_settings_form): ?>
                                <?php if (trim($block_settings_form)): ?>
                                    <form class="nc-mixins-mixin-settings" data-mixin-keyword="<?= $mixin_keyword ?>">
                                        <?= $block_settings_form ?>
                                    </form>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>

        </table>
    </div>
</xmp>