<? if ( ! $catalogue_id): ?>

    <?=$ui->form->add_row(CONTROL_CONTENT_CATALOUGE_ONESITE)->select('catalogue_id', $catalogues)->id('catalogue_id'); ?>
    <div id="subdivisions"></div>

    <script>
    nc('#catalogue_id').change(function(){
        nc('#subdivisions').html('');
        if (!this.value) return;
        nc.process_start('backup.export_form');
        nc.$.ajax({
            url: '<?=$action_url ?>export_form&type=data&catalogue_id=' + this.value,
        }).done(function(data){
            nc.process_stop('backup.export_form');
            nc('#subdivisions').html(data);
        });
        // console.log(this.value);
    });
    </script>

<? else: ?>

    <?=$ui->form->add_row(NETCAT_TRASH_FILTER_SUBDIVISION)->select('id', $subdivisions) ?>

<? endif ?>