<?php

if (!class_exists('nc_core')) {
    die;
}

/** @var nc_ui_view $this */
/** @var nc_ui $ui */
/** @var nc_core $nc_core */

/** @var string[] $errors */
/** @var string $secret */
/** @var string $qr_uri */
/** @var int $make_new_secret */
?>

<input type="hidden" name="params[make_new_secret]" value="<?= $make_new_secret ?>">
<input type="hidden" name="params[secret]" value="<?= $secret ?>">

<?php foreach ($errors as $error): ?>
    <?= $ui->alert->error($error) ?>
<?php endforeach; ?>

<?php if ($make_new_secret): ?>
    <div><?= NETCAT_SECURITY_SETTINGS_2FA_TOTP_QR ?>:</div>

    <div style="display: flex; flex-direction: row; align-items: stretch" class="nc-margin-vertical-small">
        <div style="width: 200px; height: 200px;"><img src="<?= $qr_uri ?>"></div>
        <div style="display: flex; flex-direction: column; margin: 10px 30px">
            <div style="flex-grow: 1">
                <?= NETCAT_SECURITY_SETTINGS_2FA_TOTP_SECRET ?>:
                <div class="nc-h2"><?= $secret ?></div>
            </div>
            <div>
                <div>
                    <label><?= NETCAT_SECURITY_SETTINGS_2FA_TOTP_ENTER_CODE ?>:</label>
                    <div><input type="text" name="params[code]" value="" required></div>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<div class="nc-security-2fa-preview<?= ($make_new_secret ? ' nc-margin-vertical-large' : '') ?>">
    <i class="nc-icon nc--loading"></i>
</div>

<script>
    (function () {
        // Для каких пользователей будет действовать 2ФА
        function load_preview() {
            $nc.post(
                '<?= $nc_core->SUB_FOLDER . $nc_core->HTTP_ROOT_PATH ?>action.php',
                {
                    ctrl: 'security_2fa_admin',
                    action: 'preview_users',
                    mode: $nc('input[name="settings[AuthCodeMode]"]').val(),
                    field_name: null,
                }
            ).done(function (response) {
                $nc('.nc-security-2fa-preview').html(response);
            });
        }

        load_preview();

        $nc('input[name="params[code]"]').focus();
    })();
</script>
