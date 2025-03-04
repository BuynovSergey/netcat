<?php if (!class_exists('nc_core')) {
    die;
} ?>

<div class="nc-modal-dialog" data-width="750" data-height="auto" data-confirm-close="false">
    <div class="nc-modal-dialog-header">
        <h2><?= NETCAT_MODULE_REQUESTS_FORM_POPUP_BUTTON_SETTINGS_HEADER ?></h2>
    </div>
    <div class="nc-modal-dialog-body">
        <form class="nc-form nc--vertical" action="<?= $current_url ?>" method="POST">
            <input type="hidden" name="controller" value="form">
            <input type="hidden" name="action" value="save_settings">
            <input type="hidden" name="infoblock_id" value="<?= $infoblock_id ?>">
            <input type="hidden" name="form_type" value="<?= $form_type ?>">

            <?php

            /** @var string $button_type */
            /** @var nc_requests_form $form */

            $fields = array(
                "{$button_type}_Text" => array(
                    'caption' => NETCAT_MODULE_REQUESTS_FORM_BUTTON_TEXT,
                    'type' => 'string',
                    'required' => true,
                    'class' => 'nc--column-1-of-2',
                ),
                "{$button_type}_Type" => array(
                    'caption' => NETCAT_MODULE_REQUESTS_FORM_BUTTON_TYPE,
                    'type' => 'select',
                    'subtype' => 'static',
                    'default_value' => 'tpl-button tpl-button-primary',
                    'values' => array(
                        'tpl-button tpl-button-primary' => NETCAT_MODULE_REQUESTS_FORM_BUTTON_TYPE_PRIMARY,
                        'tpl-button tpl-button-secondary' => NETCAT_MODULE_REQUESTS_FORM_BUTTON_TYPE_SECONDARY,
                    ),
                    'class' => 'nc--column-2-of-2',
                ),
                "{$button_type}_BackgroundColor" => array(
                    'caption' => NETCAT_MODULE_REQUESTS_FORM_BUTTON_COLOR,
                    'type' => 'color',
                    'class' => 'nc--column-2-of-2',
                ),
                "{$button_type}_ShowPrice" => array(
                    'caption' => NETCAT_MODULE_REQUESTS_FORM_BUTTON_PRICE,
                    'type' => 'checkbox',
                    'default' => 'on',
                    'value_for_off' => 0,
                    'value_for_on' => 1,
                ),

                // --- Категории целей GA, ЯМ ---
                "AnalyticsCategories" => array(
                    'type' => 'custom',
                    'html' => '<div><span class="nc-field-caption nc-margin-top-medium nc-margin-bottom-small">' .
                        NETCAT_MODULE_REQUESTS_FORM_OPEN_EVENT_CATEGORIES . '</span></div>',
                ),
                "Subdivision_OpenPopupButton_AnalyticsCategories" => array(
                    'caption' => NETCAT_MODULE_REQUESTS_FORM_BUTTON_SCOPE_SUBDIVISION,
                    'type' => 'string',
                    'class' => 'nc--column-1-of-2',
                ),
                "Infoblock_OpenPopupButton_AnalyticsCategories" => array(
                    'caption' => NETCAT_MODULE_REQUESTS_FORM_BUTTON_SCOPE_INFOBLOCK,
                    'type' => 'string',
                    'class' => 'nc--column-2-of-2',
                ),

                // --- Ярлыки GA, ЯМ ---
                "AnalyticsLabels" => array(
                    'type' => 'custom',
                    'html' => '<div><span class="nc-field-caption nc-margin-vertical-small">' .
                        NETCAT_MODULE_REQUESTS_FORM_OPEN_EVENT_LABELS . '</span></div>',
                ),
                "Subdivision_OpenPopupButton_AnalyticsLabels" => array(
                    'caption' => NETCAT_MODULE_REQUESTS_FORM_BUTTON_SCOPE_SUBDIVISION,
                    'type' => 'string',
                    'class' => 'nc--column-1-of-2',
                ),
                "Infoblock_OpenPopupButton_AnalyticsLabels" => array(
                    'caption' => NETCAT_MODULE_REQUESTS_FORM_BUTTON_SCOPE_INFOBLOCK,
                    'type' => 'string',
                    'class' => 'nc--column-2-of-2',
                ),
            );

            foreach ($fields as $k => $v) {
                $fields[$k]['value'] = $form->get_setting($k);
            }

            $a2f = new nc_a2f($fields, 'settings');
            echo $a2f->render(
                false,
                array(
                    'divider' => '<hr>',
                    'checkbox' => '<div class="nc-field %CLASS"><label>%VALUE %CAPTION</label></div>',
                    'default' => '<div class="nc-field %CLASS"><span class="nc-field-caption">%CAPTION</span>%VALUE</div>',
                ),
                false,
                false
            );


            if ($analytics_notice) {
                echo '<div class="nc-hint nc--error">' . $analytics_notice . '</div>';
            }

            ?>

            <div class="nc-hint"><?= NETCAT_MODULE_REQUESTS_FORM_BUTTON_ACTION_HINT_OPEN_POPUP ?></div>

        </form>
    </div>
    <div class="nc-modal-dialog-footer">
        <div class="nc-modal-dialog-footer-text">
            <button data-action="show-requests-form" class="nc--grey">
                <?= NETCAT_MODULE_REQUESTS_FORM_OPEN_POPUP_FORM ?>
            </button>
        </div>
        <button data-action="submit"><?= NETCAT_REMIND_SAVE_SAVE ?></button>
        <button data-action="close"><?= CONTROL_BUTTON_CANCEL ?></button>
    </div>

    <script>
        (function () {
            var dialog = nc.ui.modal_dialog.get_current_dialog();
            dialog.get_part("footer").find("button[data-action=show-requests-form]").click(function () {
                $nc(this).addClass("nc--loading");

                var form = dialog.get_form(),
                    infoblock_id = form.find("input[name=infoblock_id]").val();

                $nc.ajax({
                    url: form.attr("action"),
                    method: form.attr("method"),
                    data: form.serialize()
                }).done(function () {
                    $nc('#<?= $button_id ?>').click();

                    if (window.nc_requests_form_init) {
                        $nc(document).one("nc_requests_form_popup_loaded", $nc.proxy(dialog, "destroy"));
                    } else {
                        dialog.destroy();
                    }

                    nc_update_admin_mode_infoblock(infoblock_id);
                });
            });
        })();
    </script>

</div>
