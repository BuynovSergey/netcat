<?php
if (!class_exists('nc_core')) {
    die;
}
?>

<?php if ($trashed_messages) { ?>
    <div id='nc_trahed_objects'>
        <div class='nc-form nc-panel nc--close'>

            <div class='nc-panel-header'>
                <div class="nc-panel-toggle"><i class="nc-caret"></i> <?= NETCAT_MODERATION_TRASHED_OBJECTS; ?></div>
            </div>

            <div id="nc_trashed_objects_contents" class='nc-panel-content nc-padding-15 nc-bg-lighten nc-hide'>
                <?php foreach($trashed_messages as $trashed_messsage) { ?>
                    <a href="<?= $trashed_messsage['link']; ?>"><?= $trashed_messsage['text']; ?></a>
                    <br>
                <?php } ?>
            </div>
        </div>
    </div>

    <script>
        $nc(function () {
            $nc('#nc_trahed_objects .nc-panel-toggle').on('click', function () {
                var $this = $nc(this);
                var $panel = $this.closest('.nc-panel');

                if ($panel.hasClass('nc--close')) {
                    $nc('#nc_trashed_objects_contents').hide();
                } else {
                    $nc('#nc_trashed_objects_contents').show();
                }

                return false;
            });
        });
    </script>
<?php } ?>