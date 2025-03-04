<?php
$form = $ui->form($action_url . 'import_run')->multipart()->horizontal()->id('import');
$form->add_row(TOOLS_REDIRECT_GROUP)->select('data[group]', $groups, $group);
$form->add_row(TOOLS_REDIRECT)->checkbox('data[checked]', false, NETCAT_MODERATION_TURNTOON)->value(1);
$form->add_row(TOOLS_CSV_IMPORT_FILE)->file('file');

$form->add()->div($settings);
?>

<?= $form ?>




