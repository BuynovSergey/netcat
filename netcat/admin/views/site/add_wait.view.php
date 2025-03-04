<?php
if (!class_exists('nc_core')) {
    die;
}
?>
<!-- Промежуточный экран для вывода сообщения о скачивании и развёртывании сайта -->
<!-- (временный) -->

<? $form_id = "_form" . time(); ?>

<div class='nc-alert nc--blue'><i class='nc-icon-l nc--status-info'></i>
    <?= CONTROL_CONTENT_SITE_ADD_DOWNLOADING ?>
</div>

<form id="<?= $form_id ?>" method="POST">
    <? foreach ($hidden_inputs as $k => $v): ?>
        <input type="hidden" name="<?= htmlspecialchars($k) ?>" value="<?= htmlspecialchars($v) ?>">
    <? endforeach; ?>
</form>
<script>
    $nc('#<?= $form_id ?>').submit();
</script>
