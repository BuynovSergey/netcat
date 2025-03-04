<?php if (!class_exists('nc_core')) { die; } ?>

<?= $ui->controls->site_select($site_id) ?>

<style>
    .nc-table .nc--move { cursor: move; }
</style>

<table class="nc-table nc--bordered nc--wide" id="nc_route_list">
<thead><tr>
    <th class='nc--compact'></th>
    <th class='nc--compact'></th>
    <th><?=NETCAT_MODULE_ROUTING_ROUTE ?></th>
    <th><?=NETCAT_MODULE_ROUTING_ROUTE_RESOURCE_TYPE ?></th>
    <th class='nc--compact'></th>
    <th class='nc--compact'></th>
</tr></thead>
<tbody>

<?php

/** @var nc_routing_route_collection $routes */
/** @var nc_routing_route $route */
foreach ($routes as $route):
    $route_id = $route->get_id();

    $post_actions_params = array(
        'controller' => 'route',
        'route_id' => $route_id,
    );

    $edit_link_hash = "#module.routing.route.edit($route_id)";

    $can_edit = !$route->get('is_builtin');

    // id у tr нужны для работы плагина tableDnD
?>
<tr data-route-id="<?=$route_id ?>" id="nc_routing_route_<?=$route_id ?>">
    <td><i class="nc-icon nc--move" title="<?=htmlspecialchars(NETCAT_MODULE_ROUTING_ROUTE_DRAG_TO_REORDER) ?>"></i></td>

    <td><?= $ui->controls->toggle_button($route->get('enabled'), $post_actions_params); ?></td>

    <td>
        <?= $ui->helper->hash_link($edit_link_hash, $route->get('pattern')) ?>
        <br>
        <?= $route->get('description') ?>
    </td>

    <td><?= $route->get_resource_type_name() ?></td>

    <td>
        <?php
            if ($can_edit) {
                echo $ui->helper->hash_link($edit_link_hash, '<i class="nc-icon nc--settings"></i>');
            }
        ?>
    </td>

    <td>
        <?php
            if ($can_edit) {
                echo $ui->controls->delete_button(NETCAT_MODULE_ROUTING_ROUTE_DELETE_CONFIRM, $post_actions_params);
            }
        ?>
    </td>

</tr>

<?php endforeach; ?>

</tbody>
</table>
<br>

<script>
$nc('#nc_route_list').tableDnD({
    dragHandle: '.nc--move',
    onDragClass: 'nc--dragged',
    onDrop: function(table, tr) {
        var $tr = $nc(tr);
        $nc.post(
            window.location.pathname, {
                controller: 'route',
                action: 'change_priority',
                route_id: $tr.data('routeId'),
                priority: $nc(table.tBodies[0].rows).index($tr) + 1,
                nc_token: '<?= $nc_core->token->get() ?>'
            }
        );
    }
});
</script>