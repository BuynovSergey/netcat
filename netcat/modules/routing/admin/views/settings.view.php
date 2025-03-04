<?php if (!class_exists('nc_core')) { die; } ?>

<?= $ui->controls->site_select($site_id) ?>

<div>&nbsp;</div>
<form action="?controller=settings&action=save" method="POST">
    <?= $nc_core->token->get_input() ?>
    <div>
        <?= NETCAT_MODULE_ROUTING_DUPLICATE_ROUTE_ACTION ?>
        <blockquote>
            <?php

            $options = array(
                nc_routing::DUPLICATE_ROUTE_REDIRECT => NETCAT_MODULE_ROUTING_DUPLICATE_ROUTE_REDIRECT,
                nc_routing::DUPLICATE_ROUTE_ADD_CANONICAL => NETCAT_MODULE_ROUTING_DUPLICATE_ROUTE_ADD_CANONICAL,
                nc_routing::DUPLICATE_ROUTE_NO_ACTION => NETCAT_MODULE_ROUTING_DUPLICATE_ROUTE_NO_ACTION,
            );

            foreach ($options as $value => $caption) {
                echo '<div><label>',
                     '<input type="radio" name="settings[DuplicateRouteAction]" value="', $value, '"',
                     ($duplicate_route_action == $value ? " checked" : ""),
                     '>&nbsp; ',
                     $caption,
                     "</label></div>\n";
            }

            ?>

        </blockquote>
    </div>
</form>