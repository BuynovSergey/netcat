<?php

if (!class_exists("nc_core")) {
    die;
}

/** @var int $site_id */
/** @var ui_config $ui */
/** @var array $settings */
/** @var array $prefixes */
/** @var array $services */
/** @var string $current_url */

$has_any_configured_service = $services["all"];
$is_enabled = nc_array_value($settings, "Enabled");
?>

<?= $ui->controls->site_select($site_id) ?>

<form method="POST" class="nc-form nc--vertical">
    <div class="nc-field">
        <?php if ($has_any_configured_service): ?>
            <label>
                <input type="hidden" name="settings[Enabled]" value="0">
                <input type="checkbox" name="settings[Enabled]" class="ncf_value_checkbox" value="1" <?= $is_enabled ?
                    "checked" : "" ?>>
                <?= NETCAT_MODULE_MESSAGING_ENABLED ?>
            </label>
        <?php else: ?>
            <label>
                <input type="checkbox" class="ncf_value_checkbox" disabled/>
                <?= NETCAT_MODULE_MESSAGING_ENABLED ?>
            </label>
        <?php endif ?>
    </div>

    <?php if (!$has_any_configured_service): ?>
        <div class="nc-alert nc--blue"><i class="nc-icon-l nc--status-info"></i>
            <p><?= NETCAT_MODULE_MESSAGING_SERVICE_NO_AVAILABLE_SERVICES_ERROR ?></p>
            <p><?= NETCAT_MODULE_MESSAGING_SERVICE_CONFIGURE_SERVICE_TEXT ?></p>
        </div>
        <?php return ?>
    <?php endif ?>

    <h2><?= NETCAT_MODULE_MESSAGING_PROVIDERS ?></h2>

    <?php
    $make_select = function ($settings_type, $services_type) use ($settings, $services) {
        ?>
        <select name="settings[ServicesMapping][<?= $settings_type ?>][id]" class="ncf_value_select">
            <option <?= empty($settings["ServicesMapping"][$settings_type]["id"]) ? "selected" : "" ?> value="0">
                <?= CONTROL_OBJECT_NAME_NOT_SELECTED ?>
            </option>

            <?php foreach ($services[$services_type] as $service): ?>
                <?php
                    $selected =
                        isset($settings["ServicesMapping"][$settings_type]["id"]) &&
                        $settings["ServicesMapping"][$settings_type]["id"] == $service["id"];
                ?>
                <option <?= $selected ? "selected" : "" ?>
                    value="<?= $service["id"] ?>"><?= $service["name"] ?></option>
            <?php endforeach; ?>
        </select>
        <?php
    };

    ?>

    <div class="services-mapping">
        <div class="nc-field">
            <span class="nc-field-caption"><?= NETCAT_MODULE_MESSAGING_AUTH ?>:</span>
            <?= $make_select(nc_messaging_settings::SERVICE_TYPE_AUTH, 'sms') ?>
        </div>

        <div class="nc-field">
            <span class="nc-field-caption"><?= NETCAT_MODULE_MESSAGING_SECURITY ?>:</span>
            <?= $make_select(nc_messaging_settings::SERVICE_TYPE_SECURITY, 'sms') ?>
        </div>

        <div class="nc-field">
            <span class="nc-field-caption"><?= NETCAT_MODULE_NETSHOP ?>:</span>
            <?= $make_select(nc_messaging_settings::SERVICE_TYPE_NETSHOP, 'sms') ?>
        </div>

        <div class="nc-field">
            <span class="nc-field-caption"><?= NETCAT_MODULE_MESSAGING_REQUESTS ?>:</span>
            <?= $make_select(nc_messaging_settings::SERVICE_TYPE_REQUESTS, 'sms') ?>
        </div>

        <div class="nc-field">
            <span class="nc-field-caption"><?= NETCAT_MODULE_MESSAGING_DEFAULT ?>:</span>
            <?= $make_select(nc_messaging_settings::SERVICE_TYPE_DEFAULT, 'sms') ?>
        </div>
    </div>
</form>
