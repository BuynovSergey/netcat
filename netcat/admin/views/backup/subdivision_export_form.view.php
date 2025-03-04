<?php
if (!class_exists('nc_core')) {
    die;
}
?>
<? if ( ! $catalogue_id): ?>

    <?=$ui->form->add_row(CONTROL_CONTENT_CATALOUGE_ONESITE)->select('catalogue_id', $catalogues)->id('catalogue_id'); ?>
    <div id="subdivisions"></div>

    <script>
    nc('#catalogue_id').change(function(){
        nc('#subdivisions').html('');
        if (!this.value) return;
        nc.process_start('backup.export_form');
        nc.$.ajax({
            url: '<?=$action_url ?>export_form&type=subdivision&catalogue_id=' + this.value,
        }).done(function(data){
            nc.process_stop('backup.export_form');
            nc('#subdivisions').html(data);
        });
        // console.log(this.value);
    });
    </script>

<? else: ?>

    <?=$ui->form->add_row(NETCAT_TRASH_FILTER_SUBDIVISION)->select('id', $subdivisions) ?>
    <?//=$ui->form->add_row('&nbsp;')->checkbox('templates', true, CONTROL_TEMPLATE) ?>
    <?=$ui->form->add_row('&nbsp;')->checkbox('components', true, NETCAT_SETTINGS_COMPONENTS) ?>
    <?=$ui->form->add_row('&nbsp;')->checkbox('data', true, TOOLS_DATA_BACKUP_STEP_DATA) ?>

<? endif ?>