<?php if (!class_exists("nc_core")) {
    die;
}
?>

<?php
/** @var int $site_id */
/** @var string $current_url */
/** @var ui_config $ui */
/** @var array $providers */
/** @var array $prefixes */
?>

<?= $ui->controls->site_select($site_id) ?>

<form method="POST" id="service-settings" class="nc-form nc--vertical service-settings">
    <div class="primary-settings">
        <div class="nc-form-row">
            <label><?= NETCAT_MODULE_MESSAGING_PROVIDER_NAME ?></label>
            <select name="provider_id" class="nc--large">
                <option selected disabled><?= NETCAT_MODULE_MESSAGING_SERVICE_NAME ?></option>
                <?php foreach ($providers as $provider): ?>
                    <option value="<?= $provider["MessageProvider_ID"] ?>" data-type="<?= nc_array_value($provider,
                        "Type",
                        nc_messaging_service_record::MESSAGING_TYPE_SMS) ?>"><?= $provider["MessageProvider_Name"] ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="nc-form-row">
            <label><?= NETCAT_MODULE_MESSAGING_SERVICE_NAME ?></label>
            <input name="name" type="text" required class="ncf_value_text">
        </div>

        <div class="nc-form-row">
            <label><?= NETCAT_MODERATION_DESCRIPTION ?></label>
            <textarea name="description" rows="4" class="nc--large no_cm"></textarea>
        </div>
    </div>

    <div class="custom-settings" style="display:none;">
        <div id="prefixes">
            <div class="nc-form-row">
                <span class="nc-field-caption"><?= NETCAT_MODULE_MESSAGING_SETTINGS_ALLOWED_PHONE_PREFIXES ?>:</span>
                <select id="allowed_prefixes" multiple required class="chosen-select ncf_value_select">
                    <?php foreach ($prefixes as $prefix): ?>
                        <?= nc_messaging_render_number_prefix_option($prefix) ?>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="nc-form-row">
                <span class="nc-field-caption"><?= NETCAT_MODULE_MESSAGING_SETTINGS_FORBIDDEN_PHONE_PREFIXES ?>:</span>
                <select id="forbidden_prefixes" multiple required class="chosen-select ncf_value_select">
                    <?php foreach ($prefixes as $prefix): ?>
                        <?= nc_messaging_render_number_prefix_option($prefix) ?>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div id="loadable"></div>
    </div>

    <input type="hidden" name="site_id" value="<?= $site_id ?>">
    <input type="hidden" name="type" value="sms">
</form>

<script>
    (function () {
            const $ = $nc;
            const servicesCache = {};
            const settingsSelector = $(".custom-settings");
            const prefixesDOM = $("#prefixes");
            const form = $nc("form");
            const rules = {
                name: {
                    presence: true,
                },
                provider_id: {
                    numericality: {
                        onlyInteger: true,
                        greaterThan: 0,
                        message: "<?= NETCAT_MODULE_MESSAGING_SERVICE_NO_SELECTED_ERROR ?>"
                    }
                },
            }
            let selectedProviderType = "";

            document.addEventListener("DOMContentLoaded", () => {
                $(".chosen-select").chosen({
                    search_contains: true,
                    allow_single_deselect: false,
                    placeholder_text_single: "<?= NETCAT_MODULE_MESSAGING_SINGLE_FIELD_SELECT_PLACEHOLDER ?>",
                    placeholder_text_multiple: "<?= NETCAT_MODULE_MESSAGING_FIELD_SELECT_PLACEHOLDER ?>",
                    no_results_text: "<?= NETCAT_MODULE_MESSAGING_FIELD_NOT_FOUND ?>",
                    width: "300px"
                });

                $("select[name=provider_id").on("change", async function () {
                    const optionSelected = $(this).find(":selected");
                    const providerId = optionSelected.val();
                    selectedProviderType = optionSelected.data("type");

                    if (selectedProviderType == "<?= nc_messaging_service_record::MESSAGING_TYPE_SMS ?>") {
                        $("#loadable").before(prefixesDOM);
                    } else {
                        prefixesDOM.detach();
                    }

                    if (servicesCache[providerId] != null) {
                        return refreshCustomSettings(servicesCache[providerId])
                    }

                    let template = "";

                    try {
                        template = await nc_http_get("<?= $current_url?>" + "get_provider_settings", {
                            provider_id: providerId
                        })

                        servicesCache[providerId] = template;
                        refreshCustomSettings(template);

                        // Если имеется ошибка на сервере, то стереть кэш, чтобы попробовать снова запросить данные
                        if (template.includes("nc-alert")) {
                            servicesCache[providerId] = null;
                        }

                        $(".chosen-select").trigger("chosen:updated");
                    } catch (e) {
                        console.error(e)
                        servicesCache[providerId] = null;
                    }
                })
            })

            function refreshCustomSettings(template) {
                $("#loadable").html(template);

                if (settingsSelector.is(":hidden")) {
                    settingsSelector.fadeIn(150);
                }
            }

            submitForm = async function () {
                await nc_form_validate(form, rules);

                if (selectedProviderType == "<?= nc_messaging_service_record::MESSAGING_TYPE_SMS ?>") {
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

                $nc("input[name=type]").val(selectedProviderType)
                form.submit();
            }
        }
    )()
</script>
