<?php

if (!class_exists('nc_core')) {
    die;
}

/** @var nc_ui $ui */
/** @var string[] $errors */
/** @var string $secret */
/** @var string $qr_uri */

?>

<form method="POST" class="nc-2fa-form">
    <input type="hidden" name="params[make_new_secret]" value="1">
    <input type="hidden" name="params[secret]" value="<?= $secret ?>">

    <h2><?= NETCAT_SECURITY_SETTINGS_2FA_TOTP_NEED_SECRET ?></h2>

    <?php foreach ($errors as $error): ?>
        <?= $ui->alert->error($error) ?>
    <?php endforeach; ?>

    <div><?= NETCAT_SECURITY_SETTINGS_2FA_TOTP_QR ?>.</div>

    <div style="display: flex; flex-direction: row; align-items: stretch" class="nc-margin-vertical-small">
        <div style="width: 200px; height: 200px;"><img src="<?= $qr_uri ?>"></div>
        <div style="display: flex; flex-direction: column; margin: 10px 30px; text-align: left">
            <div style="flex-grow: 1">
                <?= NETCAT_SECURITY_SETTINGS_2FA_TOTP_SECRET ?>:
                <div class="nc-h2"><?= $secret ?></div>
            </div>
            <div>
                <div>
                    <label><?= NETCAT_SECURITY_SETTINGS_2FA_TOTP_ENTER_CODE ?>:</label>
                    <div><input type="text" name="params[code]" value="" required class="nc-2fa-first-logon-code"></div>
                </div>
            </div>
        </div>
    </div>

    <div><?= NETCAT_SECURITY_SETTINGS_2FA_TOTP_USE_APP ?></div>

    <div>
        <button class="nc-btn nc--blue" type="submit">OK</button>
    </div>

    <script>
        $nc('.nc-2fa-first-logon-code').focus();
        $nc('.content').addClass('page_center');
    </script>

</form>
