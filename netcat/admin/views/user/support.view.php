<?php

if (!class_exists('nc_core')) {
    die;
}

/** @var nc_core $nc_core */
/** @var int $duration */
/** @var array|null $user */

$expires_at = nc_array_value($user, 'SupervisorPermissionsExpireAt');
$has_perpetual_access = $expires_at === false;

?>
<form method="post" style="max-width: 1100px">
    <input type="hidden" name="phase" value="31">
    <?= $nc_core->token->get_input() ?>

    <?php

    if ($_POST) {
        nc_print_status(CONTROL_USER_SUPPORT_GRANTED, 'ok');
    } else {
        nc_print_status(CONTROL_USER_SUPPORT_WARNING, 'warning');
    }

    if (nc_array_value($user, 'HasSupervisorPermissions')) {
        if ($has_perpetual_access) {
            $access_info = CONTROL_USER_SUPPORT_PERPETUAL;
        } else {
            $access_expiration = date(NETCAT_CONDITION_DATETIME_FORMAT, $expires_at);
            $access_info = sprintf(CONTROL_USER_SUPPORT_EXPIRES, $access_expiration);
        }

        $access_info .=
            "<a href='$nc_core->ADMIN_PATH#user.rights($user[User_ID])' target='_top'>" .
            CONTROL_USER_SUPPORT_MANAGE .
            "</a>";

        nc_print_status($access_info, 'info');
    }

    if (!$has_perpetual_access) {
        $days_forms = "'" . implode("', '", explode('/', CONTROL_USER_SUPPORT_DAYS)) . "'";
        ?>
            <?= CONTROL_USER_SUPPORT_GRANT_FOR ?>
            <input type="number" name="duration" min="1" max="7" class="nc-input nc--small nc-user-support-days-value" value="<?= (int)$duration ?: 1 ?>">
            <span class="nc-user-support-days-caption"></span>
            <script>
                (function() {
                    $nc('.nc-user-support-days-value').change(function() {
                        $nc('.nc-user-support-days-caption').text(nc_plural_form(this.value, <?= $days_forms ?>));
                    }).change();
                })();
            </script>
        <?php
    }

    ?>

</form>
