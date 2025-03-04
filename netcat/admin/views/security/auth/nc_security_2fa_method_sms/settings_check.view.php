<?php

if (!class_exists('nc_core')) {
    die;
}

/** @var nc_ui_view $this */
/** @var nc_ui $ui */
/** @var nc_core $nc_core */

?>

<div class="nc-security-2fa-preview nc-margin-vertical-medium">
    <i class="nc-icon nc--loading"></i>
</div>

<script>
(function() {
    // Для каких пользователей будет действовать 2ФА
    function load_preview() {
        $nc.post(
            '<?= $nc_core->SUB_FOLDER . $nc_core->HTTP_ROOT_PATH ?>action.php',
            {
                ctrl: 'security_2fa_admin',
                action: 'preview_users',
                mode: $nc('input[name="settings[AuthCodeMode]"]').val(),
                field_name: $nc('input[name="settings[AuthCodePhoneField]"]').val(),
            }
        ).done(function (response) {
            $nc('.nc-security-2fa-preview').html(response);
        });
    }

    load_preview();
})();
</script>
