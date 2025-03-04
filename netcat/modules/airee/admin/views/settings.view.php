<?php if (!class_exists('nc_core')) {
    die;
} ?>

<?php
/** @var nc_ui $ui */
/** @var int $site_id */
/** @var string $api_key_description */
/** @var string $balance_description */
/** @var string $balance_label */
/** @var string $balance_add_funds_link */
/** @var array $errors */
/** @var array $settings */
/** @var bool $saved */
/** @var bool $installed */
?>

<h2><?= NETCAT_MODULE_AIREE_SETTINGS; ?></h2>

<?= $ui->controls->site_select($site_id) ?>

<? if (!empty($saved)): ?>
    <?= $ui->alert->success(NETCAT_MODULE_AIREE_SETTINGS_SAVED); ?>
<? endif; ?>

<? if (!empty($installed)): ?>
    <?= $ui->alert->success(NETCAT_MODULE_AIREE_INSTALLATION_COMPLETE); ?>
<? endif; ?>

<? if (!empty($errors)): ?>
    <?= $ui->alert->error(implode('<br>', $errors)); ?>
<? endif; ?>

<form method="post" class="nc-margin-top-medium">
    <?= $ui->html->input('hidden', 'action', 'save_settings'); ?>
    <?= $ui->html->input('hidden', 'site_id', $site_id); ?>
    <?= $ui->html->input('hidden', 'settings[Use_CSS_CDN]', 0); ?>
    <?= $ui->html->input('hidden', 'settings[Use_JavaScript_CDN]', 0); ?>
    <?= $ui->html->input('hidden', 'settings[Use_Images_CDN]', 0); ?>
    <?= $ui->html->input('hidden', 'settings[Use_Media_Files_CDN]', 0); ?>

    <div class="nc_field">
        <div><?= $nc_core->ui->label($balance_label)->blue(); ?></div>
        <? if (isset($settings['API_Key'])): ?>
            <?= $ui->html->input('text', 'balance', '')->id('js-balance-field')->medium(); ?>
            <?= $nc_core->ui->btn($balance_add_funds_link, NETCAT_MODULE_AIREE_SETTINGS_ADD_FUNDS)->id('js-add-funds')->attr('target', '_blank')->blue(); ?>
            <script>
                function insertParam(url, key, value) {
                    key = encodeURI(key);
                    value = encodeURI(value);
                    var a = document.createElement('a');
                    a.href = url;

                    var kvp = a.search.substr(1).split('&');
                    console.log(a.search, a.search.substr(1));

                    var i = kvp.length;
                    var x;
                    while (i--) {
                        x = kvp[i].split('=');

                        if (x[0] === key) {
                            x[1] = value;
                            kvp[i] = x.join('=');
                            break;
                        }
                    }

                    if (i < 0) {
                        kvp[kvp.length] = [key, value].join('=');
                    }

                    a.search = kvp.join('&');
                    return a.href;
                }

                var addFundsLink = $nc('#js-add-funds');

                $nc('#js-balance-field').on('input change', function () {
                    console.log($nc(this).val());
                    console.log(addFundsLink.attr('href'));
                    addFundsLink.attr('href', insertParam(addFundsLink.attr('href'), 'sum', $nc(this).val()));
                });

                addFundsLink.click(function (e) {
                    if (/&sum=[1-9]\d*/g.test($nc(this).attr('href'))) {
                        return true;
                    }

                    e.preventDefault();
                    return false;
                });
            </script>
        <? endif; ?>
        <p><?= $balance_description; ?><br><?= NETCAT_MODULE_AIREE_PROPOSAL; ?></p>
    </div>
    <div class="nc-field">
        <label>
            <span class="nc-field-caption"><?= NETCAT_MODULE_AIREE_SETTINGS_API_KEY; ?></span>
            <?= $ui->html->input('text', 'settings[API_Key]', nc_array_value($settings,'API_Key'))->xlarge(); ?>
        </label>
        <p><?= $api_key_description; ?></p>
    </div>

    <? if (isset($settings['API_Key'])): ?>
        <div class="nc-field">
            <?= $ui->html->checkbox('settings[Use_CSS_CDN]', (bool)$settings['Use_CSS_CDN'], NETCAT_MODULE_AIREE_SETTINGS_CSS_CDN); ?>
        </div>
        <div class="nc-field">
            <?= $ui->html->checkbox('settings[Use_JavaScript_CDN]', (bool)$settings['Use_JavaScript_CDN'], NETCAT_MODULE_AIREE_SETTINGS_JS_CDN); ?>
        </div>
        <div class="nc-field">
            <?= $ui->html->checkbox('settings[Use_Images_CDN]', (bool)$settings['Use_Images_CDN'], NETCAT_MODULE_AIREE_SETTINGS_IMG_CDN); ?>
        </div>
        <div class="nc-field">
            <?= $ui->html->checkbox('settings[Use_Media_Files_CDN]', (bool)$settings['Use_Media_Files_CDN'], NETCAT_MODULE_AIREE_SETTINGS_MEDIA_FILES_CDN); ?>
        </div>
    <? endif; ?>
</form>