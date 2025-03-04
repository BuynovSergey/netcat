<?php

if (!class_exists('nc_core')) {
    die;
}

/** @var nc_core $nc_core */
/** @var int $blocked_permanently */
/** @var int $minutes_left */
?>

<div class="nc-2fa-form nc-2fa-form-blocked">

<?php

    if ($blocked_permanently) {
        echo "<div>" . sprintf(NETCAT_2FA_BLOCKED_ADMIN, '') . "</div><div>" . NETCAT_2FA_BLOCKED_PERMANENTLY . "</div>";
    } else {
        $minutes_left_text = $nc_core->lang->get_numerical_inclination($minutes_left, explode('/', NETCAT_2FA_BLOCK_FOR));
        $minutes_left_text = sprintf($minutes_left_text, $minutes_left);
        echo sprintf(NETCAT_2FA_BLOCKED_ADMIN, $minutes_left_text);
    }

?>

</div>
