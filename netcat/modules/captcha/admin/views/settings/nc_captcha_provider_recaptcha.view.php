<?php if (!class_exists('nc_core')) { die; } ?>

<?php
/** @var nc_ui $ui */
/** @var array $settings */
/** @var array $errors */
?>

<? if (!empty($errors)): ?>
    <?= $ui->alert->error(join('<br>', $errors)); ?>
<? endif; ?>

<?php

$field = function($caption, $input) {
    return '<div class="nc-field"><label>' .
           '<span class="nc-field-caption">' . $caption . '</span>' .
           $input .
           '</label></div>';
};

$text_field = function($caption, $name) use ($ui, $settings, $field) {
    /** @var nc_ui_html $input */
    $input = $ui->html->input('text', "settings[$name]", $settings[$name])->xlarge();
    return $field($caption, $input);
};

$textarea_field = function($caption, $name) use ($ui, $settings, $field) {
    /** @var nc_ui_html $input */
    $input = $ui->html->textarea("settings[$name]", $settings[$name])->class_name('no_cm')->wide()->rows(4);
    return $field($caption, $input);
}

?>
<?= $text_field(NETCAT_MODULE_CAPTCHA_SETTINGS_RECAPTCHA_SITE_KEY, 'Recaptcha_SiteKey', 'text'); ?>
<?= $text_field(NETCAT_MODULE_CAPTCHA_SETTINGS_RECAPTCHA_SECRET_KEY, 'Recaptcha_SecretKey', 'text'); ?>

<h3><?= NETCAT_MODULE_CAPTCHA_SETTINGS_LEGACY_MODE ?></h3>

<?= $textarea_field(NETCAT_MODULE_CAPTCHA_SETTINGS_REMOVED_LEGACY_TEXT, 'RemovedLegacyText') ?>
<?= $textarea_field(NETCAT_MODULE_CAPTCHA_SETTINGS_REMOVED_LEGACY_BLOCKS, 'RemovedLegacyBlocks') ?>
