<?php if (!class_exists('nc_core')) { die; } ?>

<div class="nc-modal-dialog nc-landing-create-dialog" data-width="800" data-show-close-button="no">

    <div class="nc-modal-dialog-header">
        <h2>
            <?= NETCAT_MODULE_LANDING_CREATE_LANDING_HEADER ?>
            <? if ($has_existing_landing_pages): ?>
                <div class="nc-landing-create-dialog-switch">
                    <?= NETCAT_MODULE_LANDING_EXISTING_LANDING_PAGES_LINK ?>
                </div>
            <? endif; ?>
        </h2>
    </div>

    <div class="nc-modal-dialog-body">

        <? if (count($user_presets)): ?><div data-tab-caption="<?= NETCAT_MODULE_LANDING_PRESETS_BUILT_IN ?>"><? endif; ?>
            <form class="nc-form" action="<?= $current_url ?>" method="POST">
                <input type="hidden" name="action" value="create_landing">
                <input type="hidden" name="response_type" value="json">
                <input type="hidden" name="site_id" value="<?= $site_id ?>">
                <input type="hidden" name="component_id" value="<?= $component_id ?>">
                <input type="hidden" name="object_id" value="<?= $object_id ?>">
                <input type="hidden" name="preset_keyword" value="<?= $presets->first()->get_keyword() ?>">
                <?= $this->include_view('preset_list')->with('presets', $presets) ?>
            </form>
        <? if (count($user_presets)): ?></div><? endif; ?>

        <? if (count($user_presets)): ?>
        <div data-tab-caption="<?= NETCAT_MODULE_LANDING_PRESETS_USER_DEFINED ?>">
            <form class="nc-form" action="<?= $current_url ?>" method="POST">
                <input type="hidden" name="action" value="create_landing">
                <input type="hidden" name="response_type" value="json">
                <input type="hidden" name="site_id" value="<?= $site_id ?>">
                <input type="hidden" name="component_id" value="<?= $component_id ?>">
                <input type="hidden" name="object_id" value="<?= $object_id ?>">
                <input type="hidden" name="preset_keyword" value="<?= $user_presets->first()->get_keyword() ?>">
                <?= $this->include_view('preset_list')->with('presets', $user_presets) ?>
            </form>
        </div>
        <? endif; ?>
    </div>

    <div class="nc-modal-dialog-footer">
        <button class="nc-landing-create-dialog-submit-button nc--blue"><?= NETCAT_MODULE_LANDING_CREATE_LANDING_BUTTON ?></button>
        <button data-action="close"><?= CONTROL_BUTTON_CANCEL ?></button>
    </div>

    <script>
        (function() {
            var dialog = nc.ui.modal_dialog.get_current_dialog();
            var landingWindow;

            setTimeout(function() {
                $nc('#simplemodal-container .simplemodal-wrap').scrollTop(0);
            }, 50);

            // Переключение на диалог со списком уже созданных лендингов
            dialog.find('.nc-landing-create-dialog-switch').click(function() {
                nc.load_dialog('<?= $object_landing_list_dialog_url ?>')
                  .set_option('on_show', function() {
                        dialog.destroy();
                   });
            });

            // Обработка нажатия на «Создать лендинг»
            dialog.find('.nc-landing-create-dialog-submit-button').click(function() {
                var button = $nc(this);
                if (button.hasClass('nc--loading')) { return false; }
                button.addClass('nc--loading');

                // в этом диалоге отдельные формы на каждой вкладке...
                dialog.submit_form(dialog.get_current_tab().find('form'));
                landingWindow = window.open('', '_blank');
            });

            // Обработка ответа после отправки формы
            dialog.set_option('on_submit_response', function(response) {
                try {
                    response = $nc.parseJSON(response);
                    if (response.error) {
                        alert(response.error);
                    }
                    if (response.url && landingWindow) {
                        landingWindow.location.href = response.url;
                        dialog.close();
                    }
                }
                catch (e) {
                    alert(response);
                }
            });
        })();
    </script>
 </div>
