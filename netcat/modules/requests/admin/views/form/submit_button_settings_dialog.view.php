<?php
if (!class_exists('nc_core')) {
    die;
}
?>

<div class="nc-modal-dialog" data-width="750" data-height="auto">
    <div class="nc-modal-dialog-header">
        <h2>
            <?= NETCAT_MODULE_REQUESTS_FORM_SUBMIT_BUTTON_SETTINGS_HEADER ?>
        </h2>
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
                    'class' => 'nc--column-1-of-2',
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
                        NETCAT_MODULE_REQUESTS_FORM_SUBMIT_EVENT_CATEGORIES . '</span></div>',
                ),
                "Subdivision_SubmitButton_AnalyticsCategories" => array(
                    'caption' => NETCAT_MODULE_REQUESTS_FORM_BUTTON_SCOPE_SUBDIVISION,
                    'type' => 'string',
                    'class' => 'nc--column-1-of-2',
                ),
                "Infoblock_SubmitButton_AnalyticsCategories" => array(
                    'caption' => NETCAT_MODULE_REQUESTS_FORM_BUTTON_SCOPE_INFOBLOCK,
                    'type' => 'string',
                    'class' => 'nc--column-2-of-2',
                ),

                // --- Ярлыки GA, ЯМ ---
                "AnalyticsLabels" => array(
                    'type' => 'custom',
                    'html' => '<div><span class="nc-field-caption nc-margin-vertical-small">' .
                        NETCAT_MODULE_REQUESTS_FORM_SUBMIT_EVENT_LABELS . '</span></div>',
                ),
                "Subdivision_SubmitButton_AnalyticsLabels" => array(
                    'caption' => NETCAT_MODULE_REQUESTS_FORM_BUTTON_SCOPE_SUBDIVISION,
                    'type' => 'string',
                    'class' => 'nc--column-1-of-2',
                ),
                "Infoblock_SubmitButton_AnalyticsLabels" => array(
                    'caption' => NETCAT_MODULE_REQUESTS_FORM_BUTTON_SCOPE_INFOBLOCK,
                    'type' => 'string',
                    'class' => 'nc--column-2-of-2',
                ),

                'Subdivision_NotificationEmail' => array(
                    'caption' => NETCAT_MODULE_REQUESTS_FORM_NOTIFICATION_EMAIL_CAPTION,
                    'type' => 'string',
                    'placeholder' => NETCAT_MODULE_REQUESTS_FORM_NOTIFICATION_EMAIL_PLACEHOLDER,
                    'class' => 'nc-margin-top-small',
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

            <div class="nc-hint">
                <?php
                $subdivision_id = nc_core::get_object()->sub_class->get_by_id($infoblock_id, 'Subdivision_ID');
                if (nc_subdivision_goods_data::for_subdivision($subdivision_id)->are_netshop_items()) {
                    echo NETCAT_MODULE_REQUESTS_FORM_BUTTON_ACTION_HINT_CREATE_ORDER;
                } else {
                    echo NETCAT_MODULE_REQUESTS_FORM_BUTTON_ACTION_HINT_CREATE_REQUEST;
                }
                ?>
            </div>

        </form>
    </div>
    <div class="nc-modal-dialog-footer">
        <button data-action="submit"><?= NETCAT_REMIND_SAVE_SAVE ?></button>
        <button data-action="close"><?= CONTROL_BUTTON_CANCEL ?></button>
    </div>

    <?php if ($button_type == 'StandaloneForm_SubmitButton'): ?>
        <script>
            (function () {
                var dialog = nc.ui.modal_dialog.get_current_dialog();
                dialog.set_option("on_submit_response", function () {
                    if (window.nc_requests_form_popup_reload) {
                        nc_requests_form_popup_reload();
                        $nc(document).one("nc_requests_form_popup_loaded", $nc.proxy(dialog, "destroy"));
                    } else {
                        dialog.destroy();
                    }
                });
            })();
        </script>
    <?php endif; ?>

</div>
