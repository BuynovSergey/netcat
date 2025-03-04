<style>
    SELECT { padding-right: 18px !important; }
</style>
<?php
$form = $ui->form($action_url . 'export_run')->multipart()->horizontal()->id('export');
$form->add_row(TOOLS_CSV_EXPORT_TYPE)->select('type', $types)->id('export_type');
$form[] = "<div id='type_form'></div>";
?>

<?= $form ?>

<script>
    (function() {
        var parentBody = window.parent.document.body;
        function disableNextBtn() {
            if (!$nc('.nc_csv_do_export', parentBody).addClass('nc--disabled')) {
                $nc('.nc_csv_do_export', parentBody).addClass('nc--disabled');
            }
            $nc('.nc_csv_do_export', parentBody).unbind("click");
        }

        function bindOnComponents() {
            $nc('#component_id').change(function() {
                disableNextBtn();
                if (!this.value) {
                    return;
                } else {
                    $nc('.nc_csv_do_export', parentBody).removeClass('nc--disabled');
                    $nc('.nc_csv_do_export', parentBody).click(function() {
                        nc.view.main('form').submit();
                        disableNextBtn();
                        return false;
                    });
                }
            });
        }
        function bindOnSubdivisions() {
            $nc('#subdivision_id').change(function() {
                disableNextBtn();

                $nc('#subdivision_id_after').remove();
                $nc(this).parent().parent().after("<div id='subdivision_id_after'></div>");
                if (!this.value)
                    return;
                nc.process_start('tools.csv.export_form');
                nc.$.ajax({
                    url: '<?= $action_url ?>export_form&type=component&object_id=' + this.value,
                }).done(function(data) {
                    nc.process_stop('tools.csv.export_form');
                    if (data.indexOf('nc--status-error') == -1) {
                        $nc('.nc_csv_do_export', parentBody).removeClass('nc--disabled');
                        $nc('.nc_csv_do_export', parentBody).click(function() {
                            nc.view.main('form').submit();
                            disableNextBtn();
                            return false;
                        });
                    }
                    nc('#subdivision_id_after').html(data);

                });
            });
        }
        function bindOnSites() {
            $nc('#site_id').change(function() {
                disableNextBtn();
                $nc('#site_id_after').remove();
                $nc('#subdivision_id_after').remove();
                $nc(this).parent().parent().after("<div id='site_id_after'></div>");
                if (!this.value)
                    return;
                nc.process_start('tools.csv.export_form');
                nc.$.ajax({
                    url: '<?= $action_url ?>export_form&type=subdivision&object_id=' + this.value,
                }).done(function(data) {
                    nc.process_stop('tools.csv.export_form');
                    nc('#site_id_after').html(data);
                    bindOnSubdivisions();
                });
            });
        }
        nc('#export_type').change(function() {
            nc('#type_form').html('');
            disableNextBtn();
            if (!this.value)
                return;
            nc.process_start('tools.csv.export_form');
            var selected_type = this.value;
            nc.$.ajax({
                url: '<?= $action_url ?>export_form&type=' + selected_type,
            }).done(function(data) {
                nc.process_stop('tools.csv.export_form');
                nc('#type_form').html(data);
                if (selected_type == 'component_type') {
                    bindOnComponents();
                } else {
                    bindOnSites();
                }

            });
        });
    })();


</script>
