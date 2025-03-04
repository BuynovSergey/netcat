<?php

if (!class_exists('nc_core')) {
    die;
}

/** @var nc_core $nc_core */
/** @var nc_ui $ui */
/** @var array $method_classes */
/** @var nc_ui_view $this */
/** @var nc_ui $ui */
/** @var bool $saved */
/** @var string $ctrl */

$get_setting = function ($key) use ($nc_core) {
    // важен параметр $catalogue_id = 0
    return $nc_core->get_settings($key, 'system', false, 0);
};

?>

<div style="max-width: 1000px; padding-bottom: 30px">

    <?php if ($saved): ?>
        <?= $ui->alert->success(NETCAT_SECURITY_SETTINGS_SAVED) ?>
    <?php endif; ?>

    <form method="post">
        <input type="hidden" name="ctrl" value="<?= $ctrl ?>">
        <input type="hidden" name="action" value="check_settings">
        <?= $nc_core->token->get_input() ?>

        <?php
        $mode_radio_button = function ($value, $caption) use ($get_setting) {
            $checked = (int)$get_setting('AuthCodeMode') == $value ? ' checked' : '';
            ?>
            <div>
                <label>
                    <input type="radio" name="settings[AuthCodeMode]" value="<?= $value ?>"<?= $checked ?>>
                    <?= $caption ?>
                </label>
            </div>
            <?php
        };
        ?>

        <div>
            <?= $mode_radio_button(nc_security_2fa::DISABLED, NETCAT_SECURITY_SETTINGS_2FA_MODE_DISABLED) ?>
            <?= $mode_radio_button(nc_security_2fa::REQUIRED_FOR_SUPERVISORS, NETCAT_SECURITY_SETTINGS_2FA_MODE_FOR_SUPERVISORS) ?>
            <?= $mode_radio_button(nc_security_2fa::REQUIRED_FOR_ADMIN_MODE_USERS, NETCAT_SECURITY_SETTINGS_2FA_MODE_FOR_ADMIN_MODE_USERS) ?>
            <?= $mode_radio_button(nc_security_2fa::REQUIRED_FOR_EDITORS, NETCAT_SECURITY_SETTINGS_2FA_MODE_FOR_EDITORS) ?>
        </div>

        <div class="nc-security-auth-code-settings nc--hide">

            <div class="nc-margin-vertical-medium">
                <div>
                    <?= NETCAT_SECURITY_SETTINGS_2FA_VALIDITY ?>
                    &nbsp;
                    <input type="number" class="nc-input nc--small" name="settings[AuthCodeValidityMinutes]"
                        value="<?= $get_setting('AuthCodeValidityMinutes') ?>" min="1">
                    &nbsp;
                    <?= NETCAT_SECURITY_SETTINGS_2FA_MINUTES ?>
                </div>
                <div>
                    <?= NETCAT_SECURITY_SETTINGS_2FA_REFRESH_IN ?>
                    &nbsp;
                    <input type="number" class="nc-input nc--small" name="settings[AuthCodeRefreshInSeconds]"
                        value="<?= $get_setting('AuthCodeRefreshInSeconds') ?>" min="1">
                    &nbsp;
                    <?= NETCAT_SECURITY_SETTINGS_2FA_SECONDS ?>
                </div>

                <div>
                    <?= NETCAT_SECURITY_SETTINGS_2FA_MAX_ATTEMPTS_ALLOW ?>
                    &nbsp;
                    <input type="number" class="nc-input nc--small" name="settings[AuthCodeMaxAttempts]"
                        value="<?= $get_setting('AuthCodeMaxAttempts') ?>" min="1">
                    &nbsp;
                    <?= NETCAT_SECURITY_SETTINGS_2FA_MAX_ATTEMPTS_TIMES ?>,
                    <div class="nc-margin-vertical-small" style="margin-top: -10px">
                        <?= NETCAT_SECURITY_SETTINGS_2FA_MAX_THEN_BLOCK ?>:
                    </div>
                </div>
                <div class="nc-form-checkbox-block">
                    <label>
                        <input type="radio" name="settings[AuthCodeUnblockManually]" value="1"<?=
                        $get_setting('AuthCodeUnblockManually') ? " checked" : "" ?>>
                        <?= NETCAT_SECURITY_SETTINGS_2FA_UNBLOCK_MANUALLY ?>
                    </label>
                </div>
                <div class="nc-form-checkbox-block">
                    <label>
                        <input type="radio" name="settings[AuthCodeUnblockManually]" value="0"<?=
                        !$get_setting('AuthCodeUnblockManually') ? " checked" : "" ?>>
                        <?= NETCAT_SECURITY_SETTINGS_2FA_UNBLOCK_IN ?>
                        &nbsp;
                        <input type="number" class="nc-input nc--small" name="settings[AuthCodeUnblockMinutes]"
                            value="<?= $get_setting('AuthCodeUnblockMinutes') ?>" min="1">
                        &nbsp;
                        <?= NETCAT_SECURITY_SETTINGS_2FA_MINUTES ?>
                    </label>
                </div>
            </div>

            <div class="nc-margin-vertical-small">
                <div>
                    <?= NETCAT_SECURITY_SETTINGS_2FA_METHOD ?>:
                </div>
                <select name="settings[AuthCodeMethod]">
                    <?php
                    $selected_method = $get_setting('AuthCodeMethod');
                    foreach ($method_classes as $class) {
                        /** @var class-string<nc_security_2fa_method> $class */
                        echo "<option value='$class'" . ($selected_method === $class ? " selected" : "") . ">" .
                            $class::get_method_name() .
                            "</option>";
                    }
                    ?>
                </select>
            </div>

            <?php foreach ($method_classes as $class): ?>
                <div class="nc-margin-vertical-small nc--hide nc-security-2fa-method" data-class="<?= $class ?>">
                    <?php /** @var class-string<nc_security_2fa_method> $class */ ?>
                    <?= $class::get_settings_form() ?>
                </div>
            <?php endforeach; ?>

        </div>

    </form>

    <script>
        (function () {
            function toggle(el, on) {
                on ? $nc(el).slideDown() : $nc(el).slideUp();
            }

            // Включение и выключение 2ФА
            const
                $mode_radios = $nc('input[name="settings[AuthCodeMode]"]'),
                $settings = $nc('.nc-security-auth-code-settings'),
                is_enabled = () => $mode_radios.filter(':checked').val() > 0,
                show_settings_without_animation = () => $settings.toggle(is_enabled());

            $mode_radios.change(() => toggle($settings, is_enabled()));
            show_settings_without_animation();

            // Установка времени блокировки пользователя
            $nc('input[name="settings[AuthCodeUnblockMinutes]"]').on('input', function() {
                $nc(this).parent().find(':radio').click();
            });

            // Выбор метода получения кода
            const
                $method_select = $nc('select[name="settings[AuthCodeMethod]"]'),
                $method_settings = $nc('.nc-security-2fa-method');

            $method_select.change(function() {
                $method_settings.hide()
                    .filter(`[data-class="${$method_select.val()}"]`).show();
                show_settings_without_animation();
            }).change();

            $nc(window).on('popstate pageshow', function() {
                $method_select.change();
            });

        })();
    </script>

</div>