<?php

if (!class_exists('nc_core')) {
    die;
}

/** @var $nc_core nc_core */
/** @var $this nc_ui_view */
/** @var $ui nc_ui */

$get_setting = function ($key) use ($nc_core) {
    // важен параметр $catalogue_id = 0
    return $nc_core->get_settings($key, 'system', false, 0);
};

$messaging_available =
    nc_module_path('messaging') &&
    nc_messaging::get_instance()->can_send(nc_messaging_settings::SERVICE_TYPE_AUTH);

$user_phone_fields = array_values(array_filter(
    $nc_core->get_component('User')->get_fields(NC_FIELDTYPE_STRING),
    function ($field) {
        return preg_match('/^phone\b/', $field['format']);
    }
));

$selected_phone_field = $get_setting('AuthCodePhoneField');
if (!$selected_phone_field && isset($user_phone_fields[0]['name'])) {
    $selected_phone_field = $user_phone_fields[0]['name'];
}

$field_add_link = $nc_core->ADMIN_PATH . '#systemfield_fs.add(3)';

$field_edit_description = array(
    1 => NETCAT_SECURITY_SETTINGS_2FA_FIELD_EDITABLE_BY_USER,
    2 => NETCAT_SECURITY_SETTINGS_2FA_FIELD_EDITABLE_BY_ADMIN,
    3 => NETCAT_SECURITY_SETTINGS_2FA_FIELD_EDITABLE_BY_NO_ONE,
);

?>

<?php if (!$messaging_available): ?>
    <?= $ui->alert->warning(sprintf(NETCAT_SECURITY_SETTINGS_2FA_SMS_DISABLED, $nc_core->ADMIN_PATH . '#module.messaging.settings')) ?>
<?php endif; ?>

<?php if (!$user_phone_fields): ?>
    <?= $ui->alert->warning(sprintf(NETCAT_SECURITY_SETTINGS_2FA_SMS_NO_POSSIBLE_FIELD, $field_add_link)) ?>
<?php else: ?>
<!--
    <div class="nc-margin-vertical-small">
        <div class="nc-form-checkbox-block">
            <input type="hidden" name="settings[AuthCodePhoneAllowSetOnLogon]" value="0">
            <label>
                <input type="checkbox" name="settings[AuthCodePhoneAllowSetOnLogon]" value="1"<?= 
                    $get_setting('AuthCodePhoneAllowSetOnLogon') ? " checked" : "" ?>>
                <?= NETCAT_SECURITY_SETTINGS_2FA_SMS_ALLOW_SET_ON_LOGON ?>
            </label>
            <div class="nc-form-checkbox_block">
                <input type="hidden" name="settings[AuthCodePhoneAllowSetOnLogonWithin]" value="0">
                <label>
                    <input type="checkbox" name="settings[AuthCodePhoneAllowSetOnLogonWithin]" value="1"<?= 
                    $get_setting('AuthCodePhoneAllowSetOnLogonWithin') ? " checked" : "" ?>>
                    <?= NETCAT_SECURITY_SETTINGS_2FA_ALLOW_SET_ON_LOGON_WITHIN ?>
                    &nbsp;
                    <input type="number" class="nc-input nc--small" name="settings[AuthCodePhoneAllowSetOnLogonDays]"
                        value="<?= $get_setting('AuthCodePhoneAllowSetOnLogonDays') ?>" min="1">
                    &nbsp;
                    <?= NETCAT_SECURITY_SETTINGS_2FA_ALLOW_SET_ON_LOGON_WITHIN_DAYS ?>
                </label>
            </div>
        </div>
    </div>
-->

    <div class="nc-margin-vertical-small">
        <div>
            <?= NETCAT_SECURITY_SETTINGS_2FA_SMS_FIELD ?>:
        </div>
    </div>
    <div class="nc-margin-vertical-small nc-security-2fa-fields">
        <?php foreach ($user_phone_fields as $field): ?>
            <?php $is_selected = $selected_phone_field === $field['name']; ?>
            <div class="nc-form-checkbox-block">
                <label>
                    <input type="radio" name="settings[AuthCodePhoneField]" value="<?= $field['name'] ?>"<?=
                    $is_selected ? " checked" : "" ?>>
                    <?= $field['description'] ?> (<?= $field['name'] ?>) —
                    <?= $field_edit_description[$field['edit_type']] ?>
                </label>
                <?php if (!$nc_core->user->get_current($field['name'])): ?>
                    <?= $ui->alert->error(sprintf(
                            NETCAT_SECURITY_SETTINGS_2FA_USER_HAS_NO_VALUE,
                            $field['description'] ?: $field['name'],
                            $nc_core->ADMIN_PATH . "#user.edit(" . $nc_core->user->get_current('User_ID') . ")"
                        ))
                        ->class_name('nc-security-2fa-field-hint')
                        ->class_name($is_selected ? '' : 'nc--hide')
                    ?>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
        <div class="nc-form-checkbox-block">
            <div class="nc-margin-vertical-small">
                <em><?= sprintf(NETCAT_SECURITY_SETTINGS_2FA_SMS_ADD_FIELD, $field_add_link) ?></em>
            </div>
        </div>
    </div>
<?php endif; ?>

<script>
    (function () {

        // «Разрешить самим» — чекбоксы и строковое поле
        const
            $allow_checkbox = $nc(':checkbox[name="settings[AuthCodePhoneAllowSetOnLogon]"]'),
            $allow_within_checkbox = $nc(':checkbox[name="settings[AuthCodePhoneAllowSetOnLogonWithin]"]');

        $nc('input[name="settings[AuthCodePhoneAllowSetOnLogonDays]"]').on('input', function () {
            $allow_checkbox.prop('checked', true);
            $allow_within_checkbox.prop('checked', true);
        });

        $allow_checkbox.change(function () {
            if (!this.checked) {
                $allow_within_checkbox.prop('checked', false);
            }
        });

        $allow_within_checkbox.change(function() {
            if (this.checked) {
                $allow_checkbox.prop('checked', true);
            }
        });

        // Предупреждения о пустом поле
        const
            $fields = $nc('.nc-security-2fa-fields'),
            $field_radios = $fields.find(':radio[name="settings[AuthCodePhoneField]"]'),
            $field_alerts = $fields.find('.nc-security-2fa-field-hint');

        $field_radios.change(function() {
            $field_alerts.hide();
            $nc(this).closest('div').find('.nc-security-2fa-field-hint').show();
        });

    })();
</script>