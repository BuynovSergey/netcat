<div class="nc_admin_fieldset_head"><?php echo TOOLS_CSV_ROLLBACK_FINISHED_HEADER; ?></div>
<?php
if (empty($data['error']) && $data['rollbacked'] > 0) {
    echo $ui->alert->success(TOOLS_CSV_ROLLBACK_SUCCESS.$data['rollbacked']);
} else {
    echo $ui->alert->error($data['error']);
}
?>