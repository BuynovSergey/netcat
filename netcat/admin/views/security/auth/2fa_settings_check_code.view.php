<?php

if (!class_exists('nc_core')) {
    die;
}

/** @var string $ctrl */
/** @var string $method_checks */
/** @var nc_ui $ui */
/** @var nc_security_2fa_code $code */

?>

<?php if (isset($code) && $code instanceof nc_security_2fa_code): ?>

    <?php if ($code->get('IsSent')): ?>
        <?= $ui->alert->info($code->get('Hint')); ?>
        <input type="hidden" name="params[code_purpose]" value="<?= htmlspecialchars($code->get('Purpose')) ?>">
        <div>
            <label>
                <?= NETCAT_2FA_ENTER_CODE ?>:
                <div>
                    <input type="text" class="nc-input" name="params[code]" required pattern="\d+">
                </div>
            </label>
        </div>
    <?php else: ?>
        <?= $ui->alert->error($code->get('Hint')) ?>
    <?php endif; ?>

<?php endif; ?>
