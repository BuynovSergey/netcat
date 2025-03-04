<?php

if (!class_exists('nc_core')) {
    die;
}

/** @var string $error */
/** @var string $action_url */
/** @var string $action */
/** @var string $partial_keyword */
/** @var string $partial_description */
/** @var string $partial_source */
/** @var string $partial_enable_async_load */
/** @var nc_ui $ui */

if ($error) {
    nc_print_status($error, 'error');
}

if (!isset($partial_keyword)) {
    $partial_keyword = null;
}

?>

<form class="nc-form nc--vertical" action="<?=$action_url . $action . ($action == 'edit' && $partial_keyword ? "&partial={$partial_keyword}" : '')?>" method="post">
    
    <div class="nc-form-row">
        <label><?= CONTROL_TEMPLATE_PARTIALS_KEYWORD_FIELD ?>*</label>
        <input type="text" name="partial_keyword" value="<?= htmlspecialchars($partial_keyword) ?>" class='nc--xlarge'>
    </div>

    <div class="nc-form-row">
        <label><?= CONTROL_TEMPLATE_PARTIALS_DESCRIPTION_FIELD ?></label>
        <input type="text" name="partial_description" value="<?= htmlspecialchars(isset($partial_description) ? $partial_description : '') ?>" class='nc--xlarge'>
    </div>

    <div class="nc-form-row">
        <label><?= CONTROL_TEMPLATE_PARTIALS_SOURCE_FIELD ?></label>
        <textarea name="partial_source" cols="30" rows="15"><?= htmlspecialchars(isset($partial_source) ? $partial_source : '') ?></textarea>
    </div>

    <div class="nc-form-row">
        <label>
            <?= $ui->html->checkbox('partial_enable_async_load', !empty($partial_enable_async_load), CONTROL_TEMPLATE_PARTIALS_ENABLE_ASYNC_LOAD_FIELD)->value(1) ?>
        </label>
    </div>

</form>