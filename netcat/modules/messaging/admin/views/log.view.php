<?php if (!class_exists("nc_core")) {
    die;
}

/** @var int $site_id */
/** @var ui_config $ui */
/** @var array $logs */
/** @var array $current_url */

$class_names = array(
    "success" => "nc--green",
    "warning" => "nc--yellow",
    "error" => "nc--red",
    "" => "",
);
?>

<?= $ui->controls->site_select($site_id) ?>

<?php if (!$logs): ?>
    <h3><?= NETCAT_MODULE_SEARCH_ADMIN_EVENT_LOG_EMPTY ?></h3>
    <?php return; endif;
?>

<div class="nc--page-loader"></div>
<table class="nc-table nc--bordered nc--wide">
    <thead>
    <tr>
        <th><?= NETCAT_MODULE_SEARCH_ADMIN_EVENT_LOG_TIME ?></th>
        <th><?= NETCAT_MODULE_MESSAGING_SERVICE_NAME ?></th>
        <th><?= NETCAT_MODULE_SEARCH_ADMIN_EVENT_LOG_TYPE ?></th>
        <th><?= NETCAT_MODULE_MESSAGING_LOG_STATUS ?></th>
        <th><?= NETCAT_MODULE_MESSAGING_LOG_RESPONSE_TEXT ?></th>
        <th><?= NETCAT_MODULE_MESSAGING_LOG_RECIPIENTS ?></th>
        <th><?= NETCAT_MODULE_MESSAGING_MESSAGE ?></th>
    </tr>
    </thead>
    <tbody>

    <?php foreach ($logs as $log): ?>
        <tr class="<?= $class_names[$log["Status"]] ?>">
            <td><?= $log["Created"] ?: "-" ?></td>
            <td><?= $log["Service_Name"] ?: "-" ?></td>
            <td><?= $log["Event_Type"] ?></td>
            <td><?= $log["Status"] ?></td>
            <td style="max-width:300px;"><?= $log["Log_Message"] ?></td>
            <td><?= $log["Recipients"] ?: "-" ?></td>
            <td style="max-width:500px;"><?= $log["Message"] ?></td>
        </tr>
    <?php endforeach ?>
    </tbody>
</table>

<script>
    async function clearLogs() {
        if (!$nc("tbody tr").length) {
            return;
        }

        try {
            $nc(".nc--page-loader").show();

            if (confirm('<?= CONTROL_FIELD_CONFIRM_REMOVAL ?>')) {
                await nc_http_post("<?= $current_url . "clear"?>");
                $nc("tbody").empty();
            }
        } catch (e) {
            console.error(e)
        } finally {
            $nc(".nc--page-loader").hide();
        }
    }
</script>
