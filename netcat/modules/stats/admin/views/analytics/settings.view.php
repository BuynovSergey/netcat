<?php if (!class_exists('nc_core')) { die; } ?>

<?= $ui->controls->site_select($site_id) ?>

<?php

if ($after_save) {
    echo $ui->alert->success(NETCAT_MODULE_STATS_CHANGES_SAVED);
}

?>

<div style="margin-top: 10px">
    <style scoped>
        .nc-stats-analytics-settings-form textarea { width: 100%; }
    </style>

    <form class="nc-form nc--vertical nc-stats-analytics-settings-form" method="post">
        <input type="hidden" name="action" value="save_settings">
        <?= $nc_core->token->get_input() ?>

        <?php

        /** @var nc_stats $stats */


        $fields = array(
            'Analytics_Enabled' => array(
                'caption' => NETCAT_MODULE_STATS_ADMIN_ANALYTICS_ENABLE,
                'type' => 'checkbox',
                'value_for_off' => 0,
                'value_for_on' => 1,
                'class' => 'nc-stats-analytics-settings-form-row-enabled'
            ),

            'Analytics_EcommerceEnabled' => array(
                'caption' => NETCAT_MODULE_STATS_ADMIN_ANALYTICS_ECOMMERCE_ENABLE,
                'type' => 'checkbox',
                'value_for_off' => 0,
                'value_for_on' => 1,
                'initial_value' => 1,
            ),

            'Analytics_ScriptPosition' => array(
                'caption' => NETCAT_MODULE_STATS_ADMIN_ANALYTICS_SCRIPT_POSITION,
                'type' => 'select',
                'subtype' => 'static',
                'values' => array(
                    NETCAT_MODULE_STATS_ADMIN_ANALYTICS_SCRIPT_POSITION_BODY_TOP,
                    NETCAT_MODULE_STATS_ADMIN_ANALYTICS_SCRIPT_POSITION_BODY_BOTTOM,
                ),
                'default_value' => 0,
            ),

            'Analytics_GA_Code' => array(
                'caption' => NETCAT_MODULE_STATS_ADMIN_ANALYTICS_GA_CODE,
                'type' => 'textarea',
                'codemirror' => false,
            ),

            'Analytics_YM_Code' => array(
                'caption' => NETCAT_MODULE_STATS_ADMIN_ANALYTICS_YM_CODE,
                'type' => 'textarea',
                'codemirror' => false,
            ),

        );

        $values = array();
        foreach ($fields as $name => $field_settings) {
            $values[$name] = $stats->get_setting($name);
        }

        $form = new nc_a2f($fields, 'settings');
        echo $form
                ->set_values($values)
                ->render(
                    false,
                    array(
                        'divider' => '<hr>',
                        'checkbox' => '<div class="nc-field %CLASS"><label>%VALUE %CAPTION</label></div>',
                        'default' => '<div class="nc-field %CLASS"><span class="nc-field-caption">%CAPTION</span>%VALUE</div>',
                    ),
                    false,
                    false
                );

        ?>

    </form>

    <script>
        (function() {
            var enabled_cb = $nc('.nc-stats-analytics-settings-form-row-enabled input:checkbox'),
                other_rows = $nc('.nc-stats-analytics-settings-form .nc-field').not('.nc-stats-analytics-settings-form-row-enabled');
            function toggle_rows() {
                other_rows.toggle(enabled_cb.is(':checked'));
            }
            enabled_cb.change(toggle_rows);
            toggle_rows();
        })();
    </script>

</div>