<?php if (!class_exists('nc_core')) { die; } ?>

<?php
/** @var nc_ui $ui */
/** @var int $site_id */
/** @var string $default_domain */
/** @var string $default_email */
/** @var array $errors */
?>

<h2><?= NETCAT_MODULE_AIREE_INSTALLATION; ?></h2>

<? if (!empty($errors)): ?>
    <?= $ui->alert->error(implode('<br>', $errors)); ?>
<? endif; ?>
<form method="post" class="nc-margin-top-medium">
    <?= $ui->html->input('hidden', 'action', 'install'); ?>
    <?= $ui->html->input('hidden', 'site_id', $site_id); ?>

    <div class="nc-field">
        <label>
            <span class="nc-field-caption"><?= NETCAT_MODULE_AIREE_SETTINGS_DOMAIN; ?></span>
            <?= $ui->html->input('text', 'settings[Domain]', $settings['Domain'] ?: $default_domain)->large(); ?>
        </label>
    </div>
    <div class="nc-field">
        <label>
            <span class="nc-field-caption"><?= NETCAT_MODULE_AIREE_SETTINGS_ADMINISTRATOR_EMAIL; ?></span>
            <?= $ui->html->input('text', 'settings[Email]', $settings['Email'] ?: $default_email)->large(); ?>
        </label>
    </div>
</form>