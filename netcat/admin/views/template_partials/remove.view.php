<?php

if (!class_exists('nc_core')) {
    die;
}

/** @var string $error */
/** @var string $action_url */
/** @var string $action */
/** @var string $partial_keyword */
/** @var string $partial_description */
/** @var nc_ui $ui */

if ($error) {
    nc_print_status($error, 'error');
}

?>

<form class="nc-form nc--vertical" action="<?=$action_url . $action . "&partial={$partial_keyword}" ?>" method="post">
    <input type="hidden" name="confirmed" value="true">
    
    <p>Будет удалена врезка <b><?= $partial_description; ?> (<?= $partial_keyword; ?>)</b></p>

</form>