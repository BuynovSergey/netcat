<div class="nc_admin_fieldset_head"><?php echo TOOLS_CSV_DELETE_FINISHED_HEADER; ?></div>
<?php
if (empty($data['error'])) {
    echo $ui->alert->success(sprintf(TOOLS_CSV_DELETE_FINISHED, nc_core()->ADMIN_PATH."/#tools.csv.export"));
} else {
    echo $ui->alert->error($data['error']);
}
?>