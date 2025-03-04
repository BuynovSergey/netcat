<?php
if (!class_exists('nc_core')) {
    die;
}
$nc_core = nc_core::get_object();
?>
<div id="<?=$field_name ?>_container">

    <input type="hidden" name="<?=$field_name ?>" id="<?=$field_name ?>_field" value="<?=htmlspecialchars($field_value_json) ?>" />

    <div class="nc-panel">

        <ul id='<?=$field_name ?>_tabs' class='nc-tabs nc--small'>
        <? foreach ($tabs as $key => $title): ?>
            <li class='nc--disabled' data-keyword='<?=$key ?>'><a href='#' onclick="return nc.data_source('<?=$field_name ?>').select_tab('<?=$key ?>', this)"><?=$title ?></a></li>
        <? endforeach ?>
        </ul>

        <div class='nc-panel-content' id='<?=$field_name ?>_contents'>
            <? if (isset($tabs['source'])): ?>
                <div data-keyword='source' style='display:none'>
                    <div id='<?=$field_name ?>_source_path'></div>
                    <table cellpadding='0' cellspacing='0'  width='100%'>
                        <tr>
                            <td width='50%'><div id='<?=$field_name ?>_source_list' style='height:250px; overflow:auto'></div></td>
                            <td width='50%'><div id='<?=$field_name ?>_source_subclass' class='nc-bg-lighten' style='height:250px; overflow:auto'></div></td>
                        </tr>
                    </table>
                </div>
            <? endif ?>

            <? if (isset($tabs['filter'])): ?>
                <div data-keyword='filter' style='display:none' class='nc-padding-20'>
                    <div class="nc-form-row">
                        <div class="nc-form-label">
                            <label>Условия выборки объектов (SQL WHERE):</label>
                        </div>
                        <div class="nc-form-field">
                            <input id="<?=$field_name ?>_filter_field" value="" type="text" size="80">
                        </div>
                        <small class="nc-text-grey">Пример: `Favorite` = 1 AND `Status`>1</small>
                    </div>
                </div>
            <? endif ?>

            <? if (isset($tabs['ordering'])): ?>
                <div data-keyword='ordering' style='display:none' class='nc-padding-20'>
                    <div class="nc-form-row">
                        <div class="nc-form-label">
                            <label><?=CONTROL_CLASS_CLASS_OBJECTSLIST_SORT ?>:</label>
                        </div>
                        <div class="nc-form-field">
                            <input id="<?=$field_name ?>_ordering_field" value="" type="text" size="80">
                        </div>
                        <small class="nc-text-grey"><?=CONTROL_CLASS_CLASS_OBJECTSLIST_SORTNOTE ?></small>
                    </div>
                </div>
            <? endif ?>

            <? if (isset($tabs['bindings'])): ?>
                <div data-keyword='bindings' style='display:none'>
                    <table class='nc-table nc--small'>
                        <thead>
                            <tr>
                                <th><?=CONTROL_FIELD_LIST_NAME ?></th>
                                <th><?=CONTROL_FIELD_LIST_DESCRIPTION ?></th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody id="<?=$field_name ?>_bindings_tbody"></tbody>
                    </table>
                </div>
            <? endif ?>
        </div>

    </div>
</div>


<script src='<?= nc_add_revision_to_url($nc_core->ADMIN_PATH . 'js/nc/nc.data_source.min.js') ?>'></script>
<script>
nc(function(){
    nc.data_source.config(<?=json_encode($data_source_config) ?>);
    nc.data_source('<?=$field_name ?>').init(<?=$field_value_json[0] =='{'?$field_value_json:'{}' ?>);
});
</script>