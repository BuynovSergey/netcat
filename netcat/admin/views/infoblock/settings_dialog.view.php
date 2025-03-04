<?php

if (!class_exists('nc_core')) {
    die;
}

/** @var nc_core $nc_core */
/** @var array $infoblock_data */
/** @var array $visibility_conditions */
/** @var array $infoblock_data */
/** @var array $custom_settings */
/** @var bool $show_visibility_tab */
/** @var bool $show_conditions_tab */

global $perm;

$show_mixins_tab =
    !$infoblock_data['Class_ID'] ||
    $nc_core->component->can_add_block_list_markup($infoblock_data['Class_Template_ID'] ?: $infoblock_data['Class_ID'])

?>
<div class="nc-modal-dialog nc-infoblock-settings-dialog">
    <div class="nc-modal-dialog-header">
        <h2><?= $infoblock_data['Class_ID'] ? $infoblock_data['Sub_Class_Name'] : NETCAT_INFOBLOCK_SETTINGS_TITLE_CONTAINER ?></h2>
    </div>
    <div class="nc-modal-dialog-body">
        <form action="<?= $nc_core->SUB_FOLDER . $nc_core->HTTP_ROOT_PATH ?>action.php" method="post" class="nc-form">

            <input type="hidden" name="ctrl" value="admin.infoblock">
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="infoblock_id" value="<?= $infoblock_data['Sub_Class_ID'] ?>">

            <?php if ($custom_settings): ?>
            <div data-tab-id="settings" data-tab-caption="<?= NETCAT_INFOBLOCK_SETTINGS_TAB_CUSTOM ?>" class="custom_settings">
                <?= $custom_settings ?>
            </div>
            <?php endif; ?>

            <?php if ($show_mixins_tab): ?>
            <?php $show_index_item_mixins = $infoblock_data['Class_ID'] && $infoblock_data['MaxRecordsInInfoblock'] !== '0'; ?>
            <div data-tab-id="mixins" data-tab-caption="<?= NETCAT_MIXIN_TITLE ?>">
                <?php if ($show_index_item_mixins): ?>
                <ul class="nc-tabs nc--small nc-margin-bottom-medium nc-infoblock-settings-dialog-mixin-tabs-labels">
                    <li class="nc--active" data-mixin-tab-id="index">
                        <a><?= NETCAT_MIXIN_INDEX ?></a>
                    </li>
                    <li data-mixin-tab-id="indexitem">
                        <a><?= NETCAT_MIXIN_INDEX_ITEM ?></a>
                    </li>
                </ul>
                <?php endif; ?>
                <div class="nc-infoblock-settings-dialog-mixin-tabs">
                    <div data-mixin-tab-id="index">
                        <?= $this->include_view('../mixin/mixin_editor', array(
                                'show_preset_select' => true,
                                'show_breakpoint_type_select' => true,
                                'field_name_prefix' => 'Index',
                                'data' => $infoblock_data,
                        )) ?>
                    </div>
                    <?php if ($show_index_item_mixins): ?>
                    <div data-mixin-tab-id="indexitem" style="display: none">
                        <?= $this->include_view('../mixin/mixin_editor', array(
                                'show_preset_select' => true,
                                'show_breakpoint_type_select' => true,
                                'field_name_prefix' => 'IndexItem',
                                'data' => $infoblock_data,
                        )) ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <script>
                (function() {
                    var dialog = nc.ui.modal_dialog.get_current_dialog(),
                        labels = dialog.find('.nc-infoblock-settings-dialog-mixin-tabs-labels > li'),
                        tabs = dialog.find('.nc-infoblock-settings-dialog-mixin-tabs > div');
                    labels.click(function() {
                        var clicked_label = $nc(this);
                        labels.removeClass('nc--active');
                        clicked_label.addClass('nc--active');
                        tabs.hide().filter('[data-mixin-tab-id="' + clicked_label.data('mixin-tab-id') + '"]').show();
                        return false;
                    })
                })();
            </script>
            <?php endif; ?>

            <?php if (!$infoblock_data['Subdivision_ID'] && $show_visibility_tab): ?>
            <div data-tab-id="visibility" data-tab-caption="<?= NETCAT_INFOBLOCK_SETTINGS_TAB_VISIBILITY ?>">
                <?= $this->include_view('settings_dialog/visibility_conditions', array(
                        'infoblock_data' => $infoblock_data,
                        'visibility_conditions' => $visibility_conditions,
                )) ?>
            </div>
            <?php endif; ?>

            <div data-tab-id="others" data-tab-caption="<?= NETCAT_INFOBLOCK_SETTINGS_TAB_OTHERS ?>">
                <?= $this->include_view('settings_dialog/others', array(
                    'infoblock_data' => $infoblock_data,
                    'isSupervisor' => $perm->isSupervisor(),
                )) ?>
            </div>

            <?php if ($show_conditions_tab): ?>
            <div data-tab-id="conditions" data-tab-caption="<?= NETCAT_CONDITION_FIELD ?>">
                <?= $this->include_view('settings_dialog/conditions', array(
                    'infoblock_data' => $infoblock_data,
                )) ?>
            </div>
            <?php endif; ?>

<!--            <div data-tab-id="loading" data-tab-caption="--><?//= NETCAT_INFOBLOCK_SETTINGS_TAB_LOADING ?><!--">-->
<!--            </div>-->

        </form>
    </div>
    <div class="nc-modal-dialog-footer">
        <button data-action="submit"><?= NETCAT_REMIND_SAVE_SAVE ?></button>
        <button data-action="close"><?= CONTROL_BUTTON_CANCEL ?></button>
    </div>
</div>
