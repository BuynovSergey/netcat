<?php if (!class_exists("nc_core")) {
    die;
} ?>

<?php
/** @var int $site_id */
/** @var string $controller_name */
/** @var string $current_url */
/** @var ui_config $ui */
/** @var array $services */
?>

<?= $ui->controls->site_select($site_id) ?>

<table class="nc-table nc--bordered nc--wide">
    <thead>
    <tr>
        <th class="nc--compact"></th>
        <th><?= NETCAT_MODULE_MESSAGING_SERVICE_NAME ?></th>
        <th><?= NETCAT_MODERATION_DESCRIPTION ?></th>
        <th><?= NETCAT_MODULE_MESSAGING_PROVIDER_NAME ?></th>
        <th class="nc--compact"></th>
        <th class="nc--compact"></th>
    </tr>
    </thead>
    <tbody>

    <?php foreach ($services as $service): ?>
        <?php
        $id = $service["id"];
        $edit_hash = "#module.messaging.service.edit($id)";
        $remove_action = $current_url . "remove&id=" . $id;
        ?>

        <tr>
            <td><?= $ui->controls->toggle_button($service["checked"],
                    array("id" => $id, "controller" => $controller_name)) ?>
            </td>
            <td><?= $ui->helper->hash_link($edit_hash, $service["name"], 'nc-netshop-list-item-title') ?></td>
            <td><?= $service["description"] ?: "-" ?></td>
            <td><?= $service["provider_name"] ?></td>
            <td><?= $ui->helper->hash_link($edit_hash, '<i class="nc-icon nc--settings"></i>') ?></td>
            <td><a onclick="return confirm('<?= CONTROL_FIELD_CONFIRM_REMOVAL ?>')"
                        href="<?= $remove_action ?>"><i class="nc-icon nc--remove"></i></a></td>
        </tr>
    <?php endforeach ?>
    </tbody>
</table>
