<?php

if (!class_exists('nc_core')) { die; }

/** @var nc_ui $ui */

// COMMON
/** @var int $site_id */
/** @var string $default_settings_link */

// CAPTCHA
/** @var bool $site_has_own_captcha_settings */
/** @var string $captcha_mode */
/** @var int $captcha_free_attempts */

$nc_core = nc_core::get_object();

$get_setting = function($setting) use ($site_id) {
    return nc_core::get_object()->get_settings($setting, 'system', false, $site_id);
};

?>

<?= $ui->controls->site_select($site_id, true) ?>

<? if ($saved): ?>
    <?= $ui->alert->success(NETCAT_SECURITY_SETTINGS_SAVED) ?>
<? endif; ?>

<form method="post">
    <input type="hidden" name="action" value="save_settings">
    <input type="hidden" name="site_id" value="<?= $site_id ?>">
    <?= $nc_core->token->get_input() ?>

    <? if ($site_id): ?>
        <div class="nc-margin-vertical-small">
            <label>
                <input type="checkbox" name="captcha_use_default_settings" value="1"
                        <?= $site_has_own_captcha_settings ? '' : ' checked' ?>
                        id="nc_security_captcha_use_default">
                <?= sprintf(NETCAT_SECURITY_SETTINGS_USE_DEFAULT, $default_settings_link) ?>
                <?= NETCAT_SECURITY_SETTINGS_AUTH_CAPTCHA_RECOMMEND_DEFAULT ?>
            </label>
        </div>
    <? endif; ?>

    <div id="nc_security_captcha_site_settings"<?= (!$site_id || $site_has_own_captcha_settings ? '' : ' style="display: none"') ?>>
        <?
        $captcha_radio = function($radio_value) use ($ui, $captcha_mode) {
            return $ui->html->input('radio', 'captcha_mode', $radio_value)->checked($radio_value === $captcha_mode);
        };
        ?>

        <div>
            <label><?= $captcha_radio('disabled') ?> <?= NETCAT_SECURITY_SETTINGS_AUTH_CAPTCHA_MODE_DISABLED ?></label>
        </div>
        <div>
            <label><?= $captcha_radio('always') ?> <?= NETCAT_SECURITY_SETTINGS_AUTH_CAPTCHA_MODE_ALWAYS ?></label>
        </div>
        <div class="nc-form-checkbox-block">
            <label><?= $captcha_radio('count') ?> <?= NETCAT_SECURITY_SETTINGS_AUTH_CAPTCHA_MODE_COUNT ?></label>
            <div id="nc_security_captcha_mode_count_attempts"<?= ($captcha_mode === 'count' ? '' : ' style="display: none"') ?>>
                <label>
                    <?= NETCAT_SECURITY_SETTINGS_AUTH_CAPTCHA_ATTEMPTS ?>:
                    <input type="number" min="1" name="captcha_free_attempts" class="nc--small"
                            value="<?= $captcha_free_attempts > 0 ? $captcha_free_attempts : 1 ?>">
                </label>
            </div>
        </div>
    </div>

    <script>
    (function() {
        function toggle(el, on) {
            on ? $nc(el).slideDown() : $nc(el).slideUp();
        }

        $nc('#nc_security_captcha_use_default').change(function() {
            toggle('#nc_security_captcha_site_settings', !$nc(this).prop('checked'));
        });

        $nc(':radio[name=captcha_mode]').change(function() {
            toggle('#nc_security_captcha_mode_count_attempts', $nc(this).val() === 'count');
        });
    })();
    </script>

</form>
