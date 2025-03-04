<?php
if (!class_exists('nc_core')) {
    die;
}
?>
<? foreach ($fields as $name => $field): ?>
    <div class="nc-form-row">
        <div class="nc-form-label">
            <label><?=$field->get_caption() ?>:</label>
        </div>
        <div class="nc-form-field">
            <?=$field->render_value_field(false) ?>
        </div>
    </div>
<? endforeach ?>