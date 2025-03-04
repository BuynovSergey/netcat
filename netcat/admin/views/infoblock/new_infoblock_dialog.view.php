<?php

if (!class_exists('nc_core')) {
    die;
}

/** @var nc_core $nc_core */
/** @var int $subdivision_id */
/** @var int $infoblock_id */
/** @var string $area_keyword */
/** @var int $container_id */
/** @var string $position */
/** @var array $components */

// Стили диалога находятся в _special_parts.scss
// Скрипты — в forms.js

?>
<div class="nc-modal-dialog" data-confirm-close="false">
    <div class="nc-modal-dialog-header">
        <h2><?= NETCAT_MODERATION_ADD_BLOCK_TITLE ?></h2>
    </div>
    <div class="nc-modal-dialog-body">
        <form action="<?= $nc_core->SUB_FOLDER . $nc_core->HTTP_ROOT_PATH ?>action.php" method="post" class="nc-form">

            <input type="hidden" name="ctrl" value="admin.infoblock">
            <input type="hidden" name="action" value="create">
            <input type="hidden" name="subdivision_id" value="<?= $subdivision_id ?>">
            <input type="hidden" name="position_infoblock_id" value="<?= $infoblock_id ?>">
            <input type="hidden" name="position" value="<?= htmlspecialchars($position) ?>">
            <input type="hidden" name="area_keyword" value="<?= htmlspecialchars($area_keyword) ?>">
            <input type="hidden" name="data[Parent_Sub_Class_ID]" value="<?= $container_id ?>">

            <div class="nc-infoblock-component">
                <div class="nc-infoblock-component-columns">
                    <!-- first 'column' -->
                    <div class="nc-infoblock-component-component">
                        <!-- поле поиска (фильтрации) компонента -->
                        <table class="nc-infoblock-component-filter"><tr>
                            <td><?= CONTROL_CONTENT_CLASS ?>:</td>
                            <td>
                                <input type="text" placeholder="<?= htmlspecialchars(NETCAT_MODERATION_COMPONENT_SEARCH_BY_NAME) ?>" value="" />
                                <i class="nc-icon-s nc--remove"></i>
                            </td>
                        </tr>
                        </table>

                        <!-- выбор компонента -->
                        <select size="10" name="data[Class_ID]" class="nc-infoblock-component-select">
                        <?php
                        $previous_group = null;
                        $has_components_for_multiple_mode = false;
                        foreach ($components as $component) {
                            if ($previous_group != $component['Class_Group']) {
                                echo ($previous_group ? '</optgroup>' : ''),
                                     '<optgroup label="' . htmlspecialchars($component['Class_Group']) . '">';
                            }

                            $is_optimized_for_multiple_mode = $nc_core->component->is_optimized_for_multiple_mode($component['Class_ID']);
                            if ($is_optimized_for_multiple_mode) {
                                $has_components_for_multiple_mode = true;
                            }

                            echo '<option value="' . $component['Class_ID'] . '"' .
                                 ($is_optimized_for_multiple_mode ? ' class="nc--component-multiple"' : ''),
                                 '>',
                                 $component['Class_ID'], '. ',
                                 htmlspecialchars($component['Class_Name']),
                                 '</option>';

                            $previous_group = $component['Class_Group'];
                        }
                        echo '</optgroup>';
                        ?>
                        </select>
                    </div>
                    <!-- second 'column' -->
                    <div class="nc-infoblock-template">
                        <!-- выбор шаблона компонента -->
                        <table class="nc-infoblock-template-list"><tr>
                            <td><?= NETCAT_MODERATION_COMPONENT_TEMPLATE ?>:</td>
                            <td class="nc-infoblock-template-select">
                            </td>
                            <td class="nc-infoblock-template-list-buttons">
                                <a class="nc-btn nc--small nc--light"
                                   title="<?= NETCAT_MODERATION_COMPONENT_TEMPLATE_PREV ?>">
                                   &lsaquo;
                                </a><a class="nc-btn nc--small nc--light"
                                    title="<?= NETCAT_MODERATION_COMPONENT_TEMPLATE_NEXT ?>">
                                    &rsaquo;
                                </a>
                            </td>
                        </tr>
                        </table>

                        <!-- предпросмотр компонента -->
                        <div class="nc-infoblock-template-preview">
                            <span><?= CONTROL_CLASS_LIST_PREVIEW_NONE ?></span>
                            <i class="nc-icon nc--loading"></i>
                        </div>
                    </div>
                </div>
                <div class="nc-infoblock-switch-component-list"
                    <?= $has_components_for_multiple_mode ? '' : ' style="display: none"' ?>>
                    <label>
                        <input type="checkbox" class="nc-infoblock-show-all-components" <?=
                            $has_components_for_multiple_mode ? '' : ' checked'
                        ?>>&nbsp;
                        <?= CONTROL_CONTENT_SUBCLASS_SHOW_ALL ?>
                        <?php // ↑ убрать константу, если больше не используется ?>
                    </label>
                </div>
                <div class="nc-infoblock-template-custom-settings"></div>
            </div>

            <div class="nc-field">
                <span class="nc-field-caption">
                    <?= CONTROL_CONTENT_SUBCLASS_CLASSNAME ?>:
                </span>
                <?= nc_admin_input_simple('data[Sub_Class_Name]', '', 50, '', "maxlength='255'"); ?>
            </div>

            <div class="nc-field">
                <span class="nc-field-caption">
                    <?= CONTROL_CONTENT_SUBDIVISION_FUNCS_MAINDATA_KEYWORD ?>:
                </span>
                <?= nc_admin_input_simple('data[EnglishName]', '', 50, '',
                        "maxlength='255' data-type='transliterate' data-from='data[Sub_Class_Name]' data-is-url='yes'"
                    );
                ?>
            </div>
        </form>
    </div>
    <div class="nc-modal-dialog-footer">
        <button data-action="submit"><?= NETCAT_MODERATION_BUTTON_ADD ?></button>
        <button data-action="close"><?= CONTROL_BUTTON_CANCEL ?></button>
    </div>
    <script>
        (function() {
            nc_component_select_init(nc.ui.modal_dialog.get_current_dialog().get_form());
        })();
    </script>
</div>
