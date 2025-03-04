<?php

if (!class_exists('nc_core')) { die; }

/** @var nc_ui $ui */

// COMMON
/** @var int $site_id */
/** @var string $default_settings_link */

// FILTER
/** @var bool $site_has_own_filter_settings */
/** @var array $filter_configuration_errors */

$nc_core = nc_core::get_object();

$get_setting = function($setting) use ($site_id) {
    return nc_core::get_object()->get_settings($setting, 'system', false, $site_id);
};

$filter_radio_cell = function($input_name, $input_value) use ($get_setting) {
    $current_value = $get_setting($input_name);
    return '<td class="nc-text-center">' .
           '<input type="radio" name="filter_settings[' . $input_name . ']"' .
           ' value="' . $input_value .'"' .
           ($input_value == $current_value ? ' checked' : '') .
           ' class="nc--wide"></td>';
};

$filter_row = function($mode) use ($filter_radio_cell) {
    return $filter_radio_cell('SecurityInputFilterSQL', $mode) .
           $filter_radio_cell('SecurityInputFilterPHP', $mode) .
           $filter_radio_cell('SecurityInputFilterXSS', $mode);
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

    <!-- INPUT FILTERS -->

    <? if ($site_id): ?>
        <div class="nc-margin-vertical-small">
            <label>
                <input type="checkbox" name="filters_use_default_settings" value="1"
                        <?= $site_has_own_filter_settings ? '' : ' checked' ?>
                        id="nc_security_filter_use_default">
                <?= sprintf(NETCAT_SECURITY_SETTINGS_USE_DEFAULT, $default_settings_link) ?>
            </label>
        </div>
    <? endif; ?>

    <div id="nc_security_filter_site_settings" <?= (!$site_id || $site_has_own_filter_settings ? '' : ' style="display: none"') ?>>

        <? if (!empty($filter_configuration_errors)): ?>
            <?= $ui->alert->error(implode('<br>', $filter_configuration_errors)) ?>
        <? endif; ?>

        <table class="nc-table">
            <tr>
                <th><?= NETCAT_SECURITY_SETTINGS_INPUT_FILTER_MODE ?></th>
                <th width="15%" class="nc-text-center">SQL</th>
                <th width="15%" class="nc-text-center">PHP</th>
                <th width="15%" class="nc-text-center">HTML (XSS)</th>
            </tr>
            <tr>
                <td><?= NETCAT_SECURITY_SETTINGS_INPUT_FILTER_MODE_DISABLED ?></td>
                <?= $filter_row(nc_security_filter::MODE_DISABLED) ?>
            </tr>
            <tr>
                <td><?= NETCAT_SECURITY_SETTINGS_INPUT_FILTER_MODE_LOG_ONLY ?></td>
                <?= $filter_row(nc_security_filter::MODE_LOG_ONLY) ?>
            </tr>
            <tr>
                <td><?= NETCAT_SECURITY_SETTINGS_INPUT_FILTER_MODE_RELOAD_ESCAPE_INPUT ?></td>
                <?= $filter_row(nc_security_filter::MODE_RELOAD_ESCAPE_INPUT) ?>
            </tr>
            <tr>
                <td><?= NETCAT_SECURITY_SETTINGS_INPUT_FILTER_MODE_RELOAD_REMOVE_INPUT ?></td>
                <?= $filter_row(nc_security_filter::MODE_RELOAD_REMOVE_INPUT) ?>
            </tr>
            <tr>
                <td><?= NETCAT_SECURITY_SETTINGS_INPUT_FILTER_MODE_EXCEPTION ?></td>
                <?= $filter_row(nc_security_filter::MODE_EXCEPTION) ?>
            </tr>
        </table>

        <div class="nc-form-checkbox-block nc-margin-top-medium">
            <input type="hidden" name="filter_settings[SecurityFilterEmailAlertEnabled]" value="0">
            <label>
                <input type="checkbox" name="filter_settings[SecurityFilterEmailAlertEnabled]"
                   <?= ($get_setting('SecurityFilterEmailAlertEnabled') ? 'checked' : '') ?>
                   value="1">
                <?= NETCAT_SECURITY_FILTER_EMAIL_ENABLED ?>
            </label>
            <div id="nc_security_email_alert">
                <input type="text" name="filter_settings[SecurityFilterEmailAlertAddress]"
                   value="<?= htmlspecialchars($get_setting('SecurityFilterEmailAlertAddress')) ?>"
                   placeholder="<?= htmlspecialchars(
                       $get_setting('NotificationEmail') ?: NETCAT_SECURITY_FILTER_EMAIL_PLACEHOLDER
                   ) ?>"
                   size="50">
            </div>
        </div>

        <?php if (nc_module_check_by_keyword("messaging")): ?>
            <div class="nc-form-checkbox-block nc-margin-top-medium">
                <input type="hidden" name="filter_settings[SecurityFilterSmsAlertEnabled]" value="0">
                <label>
                    <input type="checkbox" name="filter_settings[SecurityFilterSmsAlertEnabled]"
                        <?= ($get_setting('SecurityFilterSmsAlertEnabled') ? 'checked' : '') ?>
                            value="1">
                    <?= NETCAT_SECURITY_FILTER_SMS_ENABLED ?>
                </label>

                <div id="nc_security_sms_alert">
                    <input type="text" name="filter_settings[SecurityFilterSmsAlertPhone]"
                            value="<?= htmlspecialchars($get_setting('SecurityFilterSmsAlertPhone')) ?>"
                            placeholder="<?= NETCAT_SECURITY_FILTER_SMS_PLACEHOLDER ?>"
                            size="50">
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script>
    (function() {
        function toggle(el, on) {
            on ? $nc(el).slideDown() : $nc(el).slideUp();
        }

        $nc('#nc_security_filter_use_default').change(function() {
            toggle('#nc_security_filter_site_settings', !$nc(this).prop('checked'));
        });
    })();
    </script>

</form>
