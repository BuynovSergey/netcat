<?php if (!class_exists('nc_core')) { die; } ?>

<div class="nc-modal-dialog nc-landing-create-dialog" data-width="800" data-show-close-button="no">

    <div class="nc-modal-dialog-header">
        <h2>
            <?= NETCAT_MODULE_LANDING_EXISTING_LANDING_PAGES_HEADER ?>
            <div class="nc-landing-create-dialog-switch">
                <?= NETCAT_MODULE_LANDING_CREATE_NEW_LINK ?>
            </div>
        </h2>
    </div>
    <div class="nc-modal-dialog-body">
        <div class="nc-landing-page-list">
            <? foreach ($existing_landing_pages as $page) : ?>
                <a class="nc-landing-page" href="<?= $page['href'] ?>" target="_blank">
                    <div class="nc-landing-page-title"><?= $page['name'] ?></div>
                    <div class="nc-landing-page-url"><?= $page['href'] ?></div>
                </a>
            <? endforeach; ?>
        </div>
    </div>

    <div class="nc-modal-dialog-footer">
        <button class="nc-landing-create-dialog-submit-button nc--blue">
            <?= NETCAT_MODULE_LANDING_CREATE_LANDING_BUTTON ?>
        </button>
        <button data-action="close"><?= CONTROL_BUTTON_CANCEL ?></button>
    </div>

    <script>
        (function() {
            var current_dialog = nc.ui.modal_dialog.get_current_dialog();
            current_dialog.find('.nc-landing-create-dialog-switch, .nc-landing-create-dialog-submit-button').click(function() {
                nc.load_dialog('<?= $object_landing_create_dialog_url ?>')
                  .set_option('on_show', function() {
                      current_dialog.destroy();
                  });
            });
        })();
    </script>

</div>