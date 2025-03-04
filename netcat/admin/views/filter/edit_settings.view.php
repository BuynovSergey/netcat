<?php
if (!class_exists('nc_core')) {
    die;
}

/** @var nc_infoblock_filter $filter */

?>

<div class="nc-modal-dialog nc-infoblock-settings-dialog">
    <div class="nc-modal-dialog-header">
        <h2><?= $title ?></h2>
    </div>
    <div class="nc-modal-dialog-body">
        <form action="<?= $nc_core->SUB_FOLDER . $nc_core->HTTP_ROOT_PATH ?>action.php?ctrl=admin.infoblock_filter&action=save_filter" method="post" class="nc-form">
            <?php
            $options = array('' => NC_FILTER_NOT_SELECTED) + $subdivisions;
            ?>
            <input type="hidden" name="filter_id" value="<?= $filter->get_id() ?>">
            <input type="hidden" name="filter_infoblock_id" value="<?= $filter_infoblock_id ?>">
            <input type="hidden" name="filter_settings" value='<?= $filter->get_settings_string() ?>'>
            <table>
                <tr>
                    <td><label><?= NC_FILTER_SUBDIVISION ?></label></td>
                    <td>
                        <select name="subdivision_id">
                            <? foreach ($options as $key => $value) { ?>
                                <option value="<?= $key ?>"<?= ($key == $active_subdivision_id ? ' selected' : null) ?>><?= str_replace("[space]", "&nbsp;", htmlspecialchars($value)) ?></option>
                            <? } ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td><label><?= NC_FILTER_INFOBLOCKS ?> &nbsp;</label></td>
                    <td>
                        <select name="data_infoblock_id">
                            <?= $infoblock_options ?>
                        </select>
                    </td>
                </tr>
            </table>

            <div class="nc-filter-form"><?= ($filter_form ?: '') ?></div>

        </form>
    </div>
    <div class="nc-modal-dialog-footer">
        <button data-action="submit"><?= NETCAT_REMIND_SAVE_SAVE ?></button>
        <button data-action="close"><?= CONTROL_BUTTON_CANCEL ?></button>
    </div>

    <script>
        (function() {
            var dialog = nc.ui.modal_dialog.get_current_dialog(),
                subdivision_select = dialog.find('select[name="subdivision_id"]'),
                infoblock_select = dialog.find('select[name="data_infoblock_id"]'),
                filter_form = dialog.find('.nc-filter-form'),
                target_input = dialog.find('input[name="filter_settings"]');

            subdivision_select.change(function() {
                var option = $nc(this),
                    sub_class_row = dialog.find('.nc-sub-class-row');

                infoblock_select.show();

                if (!option.val()){
                    infoblock_select.html('');
                    sub_class_row.addClass('nc--hide');
                    filter_form.html('');
                    return;
                }
                nc.process_start('sub_class_load');
                nc.$.ajax({
                    url: '<?= $nc_core->SUB_FOLDER . $nc_core->HTTP_ROOT_PATH ?>action.php?ctrl=admin.infoblock_filter&action=get_infoblock_options&subdivision_id=' + option.val(),
                }).done(function(data) {
                    nc.process_stop('sub_class_load');
                    sub_class_row.toggleClass('nc--hide', !(data && data.length));
                    infoblock_select.html(data).trigger('change');
                });
            });

            infoblock_select.change(function(){
                var infoblock_id = nc.$(this).val();
                if (!infoblock_id) {
                    filter_form.html('');
                    return;
                }
                nc.process_start('filter_form_load');
                target_input.val('');
                nc.$.ajax({
                    url: '<?= $nc_core->SUB_FOLDER . $nc_core->HTTP_ROOT_PATH ?>action.php?ctrl=admin.infoblock_filter&action=get_filter_form&infoblock_id=' + infoblock_id,
                }).done(function(data) {
                    nc.process_stop('filter_form_load');
                    filter_form.html(data);
                });
            })
        })();

    </script>

</div>
