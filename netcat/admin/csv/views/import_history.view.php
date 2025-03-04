<div class="nc_admin_fieldset_head"><?= TOOLS_CSV_IMPORT_HISTORY ?></div>

<?php
if (count($data) > 0) {
$table = $ui->table()->wide()->striped()->bordered()->hovered();

$thead = $table->thead(); // chaining produces invalid code

$thead->th(TOOLS_CSV_HISTORY_ID)->compact()->text_center();
$thead->th(TOOLS_CSV_HISTORY_CLASS_NAME)->text_center();
$thead->th(TOOLS_CSV_HISTORY_CREATED)->compact()->text_center();
$thead->th(TOOLS_CSV_HISTORY_ROWS)->compact()->text_center();
$thead->th()->compact();

$tr = $table->row();
$tr->history_id = $tr->td();

$tr->c_name = $tr->td()->text_center();
$tr->created = $tr->td()->text_center();
$tr->r_num = $tr->td()->text_center();
$tr->rollback = $tr->td()->text_center();
foreach ($data as $id => $row) {
    $rollback_link = nc_core()->ADMIN_PATH."/#tools.csv.rollback(".$id.")";
    $tr->history_id->text($id);
    $tr->c_name->text($row['Class_Name']);
    $tr->created->text($row['Created']);
    $tr->r_num->text($row['Rows']);
    $tr->rollback->text($row['Rollbacked'] == 0 ? "<a href='" . $rollback_link . "' target='_top'>" . TOOLS_CSV_HISTORY_ROLLBACK . "</a>" : TOOLS_CSV_HISTORY_ROLLBACKED);
    $table->add_row($tr);
}

echo $table, "<br>";
} else {
    echo $ui->alert->success(TOOLS_CSV_HISTORY_EMPTY);
}
?>

<script>
    (function() {
    })();
</script>