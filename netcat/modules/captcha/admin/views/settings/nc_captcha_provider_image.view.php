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

$number_field = function($caption, $name, $min) use ($ui, $settings, $field) {
    return $field($caption, $ui->html->input('number', "settings[$name]", $settings[$name])->medium()->attr('min', $min));
};

$text_field = function($caption, $name, $size = 'medium') use ($ui, $settings, $field) {
    return $field($caption, $ui->html->input('text', "settings[$name]", $settings[$name])->$size());
};

?>
<?= $text_field(NETCAT_MODULE_CAPTCHA_SETTINGS_IMAGE_CHARACTERS, 'Image_Characters', 'xlarge'); ?>
<?= $text_field(NETCAT_MODULE_CAPTCHA_SETTINGS_IMAGE_LENGTH, 'Image_Length'); ?>
<?= $number_field(NETCAT_MODULE_CAPTCHA_SETTINGS_IMAGE_WIDTH, 'Image_Width', 30); ?>
<?= $number_field(NETCAT_MODULE_CAPTCHA_SETTINGS_IMAGE_HEIGHT, 'Image_Height', 30); ?>
<?= $number_field(NETCAT_MODULE_CAPTCHA_SETTINGS_IMAGE_LINES, 'Image_Lines', 0); ?>
<?= $number_field(NETCAT_MODULE_CAPTCHA_SETTINGS_IMAGE_EXPIRES, 'Image_ExpiresIn', 60); ?>

<div class="nc-field">
    <input type="hidden" name="settings[Image_Audio]" value="0">
    <?= $ui->html->checkbox('settings[Image_Audio]', (bool)$settings['Image_Audio'], NETCAT_MODULE_CAPTCHA_SETTINGS_IMAGE_AUDIO_ENABLED)->value(1) ?>
</div>

<?= $text_field(NETCAT_MODULE_CAPTCHA_SETTINGS_IMAGE_AUDIO_VOICE, 'Image_Voice', 'large'); ?>
