<?php

$form = $ui->form($action_url . 'import_run')->multipart()->horizontal()->id('import');
$form->add_row(TOOLS_CSV_IMPORT_FILE)->file('file');
$form[] = "<div id='type_form'></div>" . $settings;

?>

<?= $form ?>
<script>
    (function() {
        var parentBody = window.parent.document.body;

        function bindOnSubdivisions() {
            $nc('#subdivision_id').change(function() {

                $nc('#subdivision_id_after').remove();
                $nc(this).parent().parent().after("<div id='subdivision_id_after'></div>");
                if (!this.value)
                    return;
                nc.process_start('tools.csv.export_form');
                nc.$.ajax({
                    url: '<?= $action_url ?>export_form&type=import_component&object_id=' + this.value
                }).done(function(data) {
                    nc.process_stop('tools.csv.export_form');
                    if (data.indexOf('nc--status-error') == -1) {
                    }
                    nc('#subdivision_id_after').html(data);

                });
            });
        }
        function bindOnSites() {
            $nc('#site_id').change(function() {
                $nc('#site_id_after').remove();
                $nc('#subdivision_id_after').remove();
                $nc(this).parent().parent().after("<div id='site_id_after'></div>");
                if (!this.value)
                    return;
                nc.process_start('tools.csv.export_form');
                nc.$.ajax({
                    url: '<?= $action_url ?>export_form&type=subdivision&object_id=' + this.value
                }).done(function(data) {
                    nc.process_stop('tools.csv.export_form');
                    nc('#site_id_after').html(data);
                    bindOnSubdivisions();
                });
            });
        }
        nc.process_start('tools.csv.export_form');
        nc.$.ajax({
            url: '<?= $action_url ?>export_form&type=subclass_type'
        }).done(function(data) {
            nc.process_stop('tools.csv.export_form');
            nc('#type_form').html(data);
            bindOnSites();
        });
    })();


</script>