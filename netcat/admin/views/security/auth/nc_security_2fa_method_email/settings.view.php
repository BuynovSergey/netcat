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

$user_email_fields = array_values(array_filter(
    $nc_core->get_component('User')->get_fields(NC_FIELDTYPE_STRING),
    function ($field) {
        return preg_match('/^email\b/', $field['format']);
    }
));

$selected_email_field = $get_setting('AuthCodeEmailField');
if (!$selected_email_field && isset($user_email_fields[0]['name'])) {
    $selected_email_field = $user_email_fields[0]['name'];
}

$field_add_link = $nc_core->ADMIN_PATH . '#systemfield_fs.add(3)';

$field_edit_description = array(
    1 => NETCAT_SECURITY_SETTINGS_2FA_FIELD_EDITABLE_BY_USER,
    2 => NETCAT_SECURITY_SETTINGS_2FA_FIELD_EDITABLE_BY_ADMIN,
    3 => NETCAT_SECURITY_SETTINGS_2FA_FIELD_EDITABLE_BY_NO_ONE,
);

?>

<?php if (!$user_email_fields): ?>
    <?= $ui->alert->warning(sprintf(NETCAT_SECURITY_SETTINGS_2FA_EMAIL_NO_POSSIBLE_FIELD, $field_add_link)) ?>
<?php else: ?>
    <div class="nc-margin-vertical-small">
        <div>
            <?= NETCAT_SECURITY_SETTINGS_2FA_EMAIL_FIELD ?>:
        </div>
    </div>
    <div class="nc-margin-vertical-small nc-security-2fa-fields">
        <?php foreach ($user_email_fields as $field): ?>
            <?php $is_selected = $selected_email_field === $field['name']; ?>
            <div class="nc-form-checkbox-block">
                <label>
                    <input type="radio" name="settings[AuthCodeEmailField]" value="<?= $field['name'] ?>"<?=
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
                <em><?= sprintf(NETCAT_SECURITY_SETTINGS_2FA_EMAIL_ADD_FIELD, $field_add_link) ?></em>
            </div>
        </div>
    </div>
<?php endif; ?>

<script>
    (function () {

        // «Разрешить самим» — чекбоксы и строковое поле
        const
            $allow_checkbox = $nc(':checkbox[name="settings[AuthCodeEmailAllowSetOnLogon]"]'),
            $allow_within_checkbox = $nc(':checkbox[name="settings[AuthCodeEmailAllowSetOnLogonWithin]"]');

        $nc('input[name="settings[AuthCodeEmailAllowSetOnLogonDays]"]').on('input', function () {
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
            $field_radios = $fields.find(':radio[name="settings[AuthCodeEmailField]"]'),
            $field_alerts = $fields.find('.nc-security-2fa-field-hint');

        $field_radios.change(function() {
            $field_alerts.hide();
            $nc(this).closest('div').find('.nc-security-2fa-field-hint').show();
        });

    })();
</script>