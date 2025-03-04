<?php if (!class_exists("nc_core")) {
    die;
}

/** @var ui_config $ui */
/** @var array $service */
/** @var int $site_id */
/** @var array $prefixes */

?>

<?= $ui->controls->site_select($site_id); ?>

<form method="POST" class="nc-form nc--vertical service-settings">
    <input type="hidden" name="id" value="<?= $service["id"] ?>">

    <div class="primary-settings settings-col">
        <div class="nc-form-row">
            <label><?= NETCAT_MODULE_MESSAGING_SERVICE_NAME ?></label>
            <input name="name" type="text" value="<?= $service["name"] ?>" required class="ncf_value_text">
        </div>

        <div class="nc-form-row">
            <label><?= NETCAT_MODERATION_DESCRIPTION ?></label>
            <textarea name="description" rows="4" class="nc--large no_cm"><?= $service["description"] ?></textarea>
        </div>
    </div>

    <div class="custom-settings settings-col">
        <?php if ($service["type"] == nc_messaging_service_record::MESSAGING_TYPE_SMS): ?>
            <div class="nc-form-row">
                <span class="nc-field-caption"><?= NETCAT_MODULE_MESSAGING_SETTINGS_ALLOWED_PHONE_PREFIXES ?>:</span>
                <select id="allowed_prefixes" multiple required class="chosen-select ncf_value_select">
                    <?php foreach ($prefixes as $prefix): ?>
                        <?= nc_messaging_render_number_prefix_option($prefix, $service, "allowed_prefixes") ?>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="nc-form-row">
                <span class="nc-field-caption"><?= NETCAT_MODULE_MESSAGING_SETTINGS_FORBIDDEN_PHONE_PREFIXES ?>:</span>
                <select id="forbidden_prefixes" multiple required class="chosen-select ncf_value_select">
                    <?php foreach ($prefixes as $prefix): ?>
                        <?= nc_messaging_render_number_prefix_option($prefix, $service, "forbidden_prefixes") ?>
                    <?php endforeach; ?>
                </select>
            </div>
        <?php endif ?>

        <?= $this->include_view("custom_settings", array("settings" => $service["settings_for_form"])) ?>
    </div>

    <input type="hidden" name="site_id" value="<?= $site_id ?>">
</form>

<script>
    (function () {
        const form = $nc("form");

        document.addEventListener("DOMContentLoaded", () => {
            $nc(".chosen-select").chosen({
                search_contains: true,
                allow_single_deselect: false,
                placeholder_text_single: "<?= NETCAT_MODULE_MESSAGING_SINGLE_FIELD_SELECT_PLACEHOLDER ?>",
                placeholder_text_multiple: "<?= NETCAT_MODULE_MESSAGING_FIELD_SELECT_PLACEHOLDER ?>",
                no_results_text: "<?= NETCAT_MODULE_MESSAGING_FIELD_NOT_FOUND ?>",
            });
        })

        submitForm = async function () {
            await nc_form_validate($nc("form"), {
                name: {
                    presence: true,
                },
            })

            if (<?= $service["type"] == nc_messaging_service_record::MESSAGING_TYPE_SMS ? "true" : "false" ?>) {
                const selectedAllowed = $nc("#allowed_prefixes").find(":selected");
                const selectedForbidden = $nc("#forbidden_prefixes").find(":selected")

                if (selectedAllowed.length === 0) {
                    form.append("<input type='hidden' name='settings[allowed_prefixes][]' value=''/>");
                } else {
                    selectedAllowed.each((i, option) => form.append(`<input type='hidden'
                                                                name='settings[allowed_prefixes][${$nc(option).attr("name")}][]'
                                                                value='${$nc(option).val()}'/>`));
                }

                if (selectedForbidden.length === 0) {
                    form.append("<input type='hidden' name='settings[forbidden_prefixes][]' value=''/>");
                } else {
                    selectedForbidden.each((i, option) => form.append(`<input type='hidden'
                                                                name='settings[forbidden_prefixes][${$nc(option).attr("name")}][]'
                                                                value='${$nc(option).val()}'/>`));
                }
            }

            form.submit();
        }
    })()
</script>
