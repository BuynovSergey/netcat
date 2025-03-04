<?php

if (!class_exists('nc_core')) {
    die;
}

/** @var string $ctrl */
/** @var array $settings */
/** @var array $errors */
/** @var string $code_checks */
/** @var string $method_checks */
/** @var nc_ui $ui */

?>

<div style="max-width: 1000px">
    <form method="post">
        <input type="hidden" name="ctrl" value="<?= $ctrl ?>">
        <input type="hidden" name="action" value="check_settings_code">
        <?= $nc_core->token->get_input() ?>

        <?php foreach ($settings as $k => $v): ?>
            <input type="hidden" name="settings[<?= htmlspecialchars($k) ?>]" value="<?= htmlspecialchars($v) ?>">
        <?php endforeach ?>

        <?php foreach ($errors as $error): ?>
            <?= $ui->alert->error($error) ?>
        <?php endforeach ?>

        <div><?= $code_checks ?></div>
        
        <div><?= $method_checks ?></div>

    </form>
</div>