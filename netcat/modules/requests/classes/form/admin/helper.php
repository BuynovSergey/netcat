<?php

/**
 * Replace me with something that makes more sense.
 */
class nc_requests_form_admin_helper {

    static public function make_alternate_input(nc_requests_form $form, $field, $caption) {
        $subdivision_value = htmlspecialchars($form->get_setting("Subdivision_$field"));
        $infoblock_value = htmlspecialchars($form->get_setting("Infoblock_$field"));

        $subdivision_scope = NETCAT_MODULE_REQUESTS_FORM_BUTTON_SCOPE_SUBDIVISION;
        $infoblock_scope = NETCAT_MODULE_REQUESTS_FORM_BUTTON_SCOPE_INFOBLOCK;

        $result = <<<END
        <div>
            <span class="nc-field-caption">$caption</span>
        </div>
        <div class="nc-field nc--column-1-of-2">
            <span class="nc-field-caption">$subdivision_scope</span>
            <input type="text" name="settings[Subdivision_$field]" value="$subdivision_value">
        </div>
        <div class="nc-field nc--column-2-of-2">
            <span class="nc-field-caption">$infoblock_scope</span>
            <input type="text" name="settings[Infoblock_$field]" value="$infoblock_value">
        </div>

END;
        return $result;
    }

}