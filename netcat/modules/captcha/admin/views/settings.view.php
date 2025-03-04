<?php if (!class_exists('nc_core')) { die; } ?>

<?php
/** @var nc_ui $ui */
/** @var int $site_id */
/** @var array $providers */
/** @var array $errors */
/** @var array $settings */
/** @var bool $has_own_settings */
/** @var bool $saved */
/** @var string $default_settings_link */
/** @var callable $site_filter */
?>

<div><?= $ui->controls->site_select($site_id, true, $site_filter) ?></div>

<? if ($saved): ?>
    <?= $ui->alert->success(NETCAT_MODULE_CAPTCHA_SETTINGS_SAVED) ?>
<? endif; ?>

<form method="post" class="nc-margin-top-medium">
    <input type="hidden" name="action" value="save_settings">
    <input type="hidden" name="site_id" value="<?= $site_id ?>">

    <? if ($this->site_id): ?>
        <div class="nc-field">
            <? $label = sprintf(NETCAT_MODULE_CAPTCHA_SETTINGS_USE_DEFAULT, $default_settings_link); ?>
            <?= $ui->html->checkbox('use_default_settings', !$has_own_settings, $label)->id('nc_captcha_settings_use_default'); ?>
        </div>
    <? endif; ?>

    <div id="nc_captcha_settings_proper" <?= (!$site_id || $has_own_settings ? '' : ' style="display: none"') ?>>
        <div class="nc-field">
            <label>
                <span class="nc-field-caption"><?= NETCAT_MODULE_CAPTCHA_SETTINGS_PROVIDER ?></span>
                <?= $ui->html->select('settings[Provider]', $providers, $settings['Provider'])->id('nc_captcha_settings_provider') ?>
            </label>
        </div>
        <div id="nc_captcha_settings_provider_specific">
            <?= $this->view("settings/$settings[Provider]", array(
                    'settings' => $settings,
                    'errors' => $errors,
            )); ?>
        </div>
    </div>

</form>

<script>
    (function() {
        function toggle(el, on) {
            on ? $nc(el).slideDown() : $nc(el).slideUp();
        }

        $nc('#nc_captcha_settings_use_default').change(function() {
            toggle('#nc_captcha_settings_proper', !this.checked);
        });

        $nc('#nc_captcha_settings_provider').change(function() {
            var div = $nc('#nc_captcha_settings_provider_specific');
            div.html('<div class="nc-label nc--white nc--loading">&nbsp;</div>');
            $nc.post(window.location.pathname, {
                action: 'get_provider_settings',
                site_id: <?= $site_id ?>,
                provider: $nc(this).val()
            }).done(function(response) {
                div.html(response);
            });
        });

    })();
</script>