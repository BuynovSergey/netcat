<div class="nc_admin_fieldset_head"><?php echo TOOLS_CSV_EXPORT_FINISHED_HEADER; ?></div>
<?php
if (empty($data['error'])) {
    echo $ui->alert->success(sprintf(TOOLS_CSV_EXPORT_DONE, $file[1], $file[0], $file[2]));
} else {
    echo $ui->alert->error($data['error']);
}
?>