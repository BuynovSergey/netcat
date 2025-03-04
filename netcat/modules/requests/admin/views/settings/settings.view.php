<?php
if (!class_exists('nc_core')) {
    die;
}
if (!isset($site_id)) {
    $site_id = 0;
}
/** @var callable $site_filter */
?>

<?= $ui->controls->site_select($site_id, false, $site_filter) ?>

<? /** @var nc_requests $requests */ ?>

<? if ($after_save): ?>
    <div style="margin-top: 20px">
        <?= $ui->alert->info(NETCAT_MODULE_REQUESTS_SETTINGS_SAVED) ?>
    </div>
<? endif; ?>


<form style="padding-top: 20px" method="POST">
    <input type="hidden" name="controller" value="<?= $controller_name ?>">
    <input type="hidden" name="action" value="save_settings">

    <div class="nc-field">
        <span class="nc-field-caption"><?= NETCAT_MODULE_REQUESTS_DEFAULT_NOTIFICATION_EMAIL ?></span>
        <input type="text" name="settings[NotificationEmail]"
                value="<?= htmlspecialchars($requests->get_setting('NotificationEmail')) ?>">
    </div>

    <?php if (nc_module_check_by_keyword("messaging")): ?>
        <div class="nc-field">
            <span class="nc-field-caption"><?= NETCAT_MODULE_REQUESTS_DEFAULT_NOTIFICATION_PHONE ?></span>
            <input type="text" name="settings[NotificationPhone]"
                    value="<?= htmlspecialchars($requests->get_setting('NotificationPhone')) ?>">
        </div>
    <?php endif ?>
</form>
