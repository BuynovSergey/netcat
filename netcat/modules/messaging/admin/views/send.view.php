<?php

if (!class_exists("nc_core")) {
    die;
}

/** @var int $site_id */
/** @var ui_config $ui */
/** @var array $settings */
/** @var array $services */
/** @var string $current_url */

?>
<?= $ui->controls->site_select($site_id) ?>

<?php if (empty($settings["Enabled"])): ?>
    <div class="nc-alert nc--red"><i class="nc-icon-l nc--status-error"></i>
        <p><?= NETCAT_MODULE_MESSAGING_DISABLED_ERROR ?></p>
    </div>
    <?php return ?>
<?php endif ?>

<div class="nc--page-loader"></div>
<h2><?= NETCAT_MODULE_MESSAGING_SERVICE_MANUAL_SEND ?></h2>

<?php if (!$services): ?>
    <div class="nc-alert nc--blue"><i class="nc-icon-l nc--status-info"></i>
        <p><?= NETCAT_MODULE_MESSAGING_SERVICE_NO_AVAILABLE_SERVICES_ERROR ?></p>
        <p><?= NETCAT_MODULE_MESSAGING_SERVICE_CONFIGURE_SERVICE_TEXT ?></p>
    </div>
    <?php return ?>
<?php endif ?>

<form method="post" class="nc-form nc--vertical">
    <div class="nc-form-row">
        <span class="nc-field-caption"><?= NETCAT_MODULE_MESSAGING_PROVIDER ?>:</span>
        <select name="service_id" class="ncf_value_select">
            <?php foreach ($services as $service): ?>
                <option value="<?= $service["id"] ?>"><?= $service["name"] ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="nc-form-row">
        <span class="nc-field-caption"><?= NETCAT_MODULE_MESSAGING_PHONE ?>:</span>

        <div class="phone-group">
            <input name="phone[]" type="text" class="ncf_value_text"/>
        </div>
        
        <div id="add-phone" class="nc-text-blue" style="cursor: pointer"><?= NETCAT_MODULE_MESSAGING_ADD_NUMBER ?></div>
    </div>

    <div class="nc-form-row">
        <span class="nc-field-caption"><?= NETCAT_MODULE_MESSAGING_MESSAGE ?>:</span>
        <textarea name="message" rows="4" class="nc--large no_cm"></textarea>
    </div>
</form>

<script>
    (function () {
        const $ = $nc;
        const form = $("form")
        const phoneRegexp = /^[+]?\d+$/;
        const rules = {
            service_id: {
                numericality: {
                    onlyInteger: true,
                    greaterThan: 0,
                    message: "<?= NETCAT_MODULE_MESSAGING_SERVICE_NO_SELECTED_ERROR ?>"
                }
            },
            "phone[]": {
                presence: true,
                length: {
                    minimum: 6,
                    maximum: 20,
                    message: "<?= NETCAT_MODULE_MESSAGING_INVALID_PHONE_FORMAT ?>"
                }
            },
            message: {
                presence: true
            }
        }

        $("input[name='phone[]']").on("input", function (e) {
            if (!phoneRegexp.test(e.target.value)) {
                e.target.value = e.target.value.replace(/[^0-9+-]/g, "");
            }
        })

        $("#add-phone").on("click", () => {
            $(".phone-group").append("<div class='phone-wrapper'>" +
                "<input name='phone[]' type='text' class='ncf_value_text'/>" +
                "<span class='remove-phone'></span>" +
                "</div>");
        })

        $(".phone-group").on("click", ".remove-phone", function () {
            $(this).parent().remove();
        })

        submitForm = async function () {
            await nc_form_validate(form, rules);

            nc_show_page_loader();
            $(".nc-alert-js").remove();

            try {
                const formData = form.serializeArray();
                await nc_http_post("<?= $current_url . "send_message"?>", formData);
                nc_print_status(form, "<?= NETCAT_MODULE_MESSAGING_MESSAGE_SENT_SUCCESS ?>")
            } catch (e) {
                nc_print_status(form, "responseJSON" in e ? e.responseJSON.message : "<?= NETCAT_MODULE_MESSAGING_MESSAGE_SENT_ERROR ?>", "error")
                console.error(e);
            } finally {
                nc_show_page_loader(false)
            }
        }
    })()
</script>
