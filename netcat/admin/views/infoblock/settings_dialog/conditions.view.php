<?php

if (!class_exists('nc_core')) {
    die;
}

$condition_json = $infoblock_data['Condition'] ?: '{}';
$condition_groups = nc_array_json(array('GROUP_OBJECTS'));
$site_id = $infoblock_data['Catalogue_ID'];

?>
<div id='nc_condition_editor'></div>

<div class="nc-margin-top-medium">
    <div><?= CONTROL_CONTENT_SUBCLASS_CONDITION_OFFSET ?>:</div>
    <div>
        <input type="number" name="data[ConditionOffset]" class="nc--small" min="0"
                value="<?= htmlspecialchars($infoblock_data['ConditionOffset']) ?>">
    </div>
</div>

<div>
    <div><?= CONTROL_CONTENT_SUBCLASS_CONDITION_LIMIT ?>:</div>
    <div>
        <input type="number" name="data[ConditionLimit]" class="nc--small" min="0"
                value="<?= htmlspecialchars($infoblock_data['ConditionLimit']) ?>">
    </div>
</div>

<script>
    (function () {
        const dialog = nc.ui.modal_dialog.get_current_dialog();
        dialog.set_on_tab_change('conditions', function () {
            const container = $nc('#nc_condition_editor');

            if (container.data('initialized')) {
                return;
            }


            nc.load_script_if(!$nc.fn.chosen, '<?= nc_add_revision_to_url($ADMIN_PATH . 'js/chosen.jquery.min.js') ?>')
                .then(() => nc.load_class('nc_condition_editor', '<?= nc_add_revision_to_url($ADMIN_PATH . 'condition/js/editor.js')?>'))
                .then(() => nc.load_class('nc_condition_editor_infoblock_query', '<?= nc_add_revision_to_url($ADMIN_PATH . 'condition/js/editor_infoblock_query.js')?>'))
                .done(() => {
                    const condition_editor = new nc_condition_editor_infoblock_query({
                        container: container,
                        strings: <?= nc_condition_admin_helpers::get_strings() ?>,
                        input_name: 'data[Condition]',
                        conditions: <?= $condition_json ?>,
                        url_params: {
                            site_id: <?= $site_id ?>,
                            infoblock_id: <?= $infoblock_data['Sub_Class_ID']?>
                        },
                        groups_to_show: <?= $condition_groups ?>
                    });

                    container.closest('.ncf_value').removeClass('ncf_value');
                    container.closest('form').get(0).onsubmit = function () {
                        return condition_editor.onFormSubmit();
                    };
                    container.data('initialized', true);
                });
        });
    })();

</script>
