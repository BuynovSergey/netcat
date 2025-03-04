<?php
if (!class_exists('nc_core')) {
    die;
}

/** @var callable $site_filter */
?>

<?= $ui->controls->site_select($catalogue_id, false, $site_filter) ?>

<div class="nc_admin_mode_content"><?=$request_list ?></div>