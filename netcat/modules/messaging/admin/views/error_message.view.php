<?php if (!class_exists("nc_core")) {
    die;
}

/** @var string $message */
/** @var nc_ui $ui */
?>

<?= $ui->alert->error($message) ?>
