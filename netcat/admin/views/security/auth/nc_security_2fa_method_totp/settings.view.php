<?php

if (!class_exists('nc_core')) {
    die;
}

/** @var nc_core $nc_core */
/** @var nc_ui_view $this */
/** @var nc_ui $ui */
/** @var bool $user_has_secret */

?>

<?php if ($user_has_secret): ?>
    <div class="nc-form-checkbox-block">
        <label>
            <input type="checkbox" name="params[make_new_secret]" value="1">
            <?= NETCAT_SECURITY_SETTINGS_2FA_TOTP_MAKE_NEW ?>
        </label>
    </div>
<?php else: ?>
    <input type="hidden" name="params[make_new_secret]" value="1">
<?php endif; ?>
