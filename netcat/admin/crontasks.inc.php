<?php

function CrontasksList() {
    ValidateHostname();

    global $db, $UI_CONFIG, $nc_core;

    $UI_CONFIG->actionButtons[] = array(
        "id" => "add",
        "caption" => TOOLS_CRON_ADDLINK,
        "location" => "cron.add",
        "align" => "left",
    );

    $tasks = $db->get_results("SELECT * FROM `CronTasks` ORDER BY `Cron_ID`", ARRAY_A);

    if ($tasks) {
        $UI_CONFIG->actionButtons[] = array(
            "id" => "submit",
            "caption" => NETCAT_ADMIN_DELETE_SELECTED,
            "action" => "mainView.submitIframeForm()",
            "align" => "right",
            "red_border" => true,
        );
    }
    ?>

    <?php if ($tasks): ?>
        <form method='post' action='crontasks.php'>
            <input type='hidden' name='phase' value='3'>
            <input type='submit' class='hidden'>
            <?= $nc_core->token->get_input() ?>

            <table class='nc-table nc--striped' width=100%>
                <tr>
                    <th width='3%'><?= CLASSIFICATORS_SORT_TYPE_ID ?></th>
                    <th width='14%' class='align-center'><?= TOOLS_CRON_INTERVAL ?></th>
                    <th width='16%'> <?= TOOLS_CRON_LAUNCHED ?></th>
                    <th width='45%'><?= TOOLS_CRON_SCRIPTURL ?></th>
                    <th class='nc-text-center'><?= TOOLS_CRON_CHANGE ?></th>
                    <th class='nc-text-center'><i class='nc-icon nc--remove'
                                title='<?= CONTROL_CONTENT_CATALOUGE_FUNCS_SHOWCATALOGUELIST_DELETE ?>'></i></th>
                </tr>

                <?php foreach ($tasks as $task): ?>
                    <tr>
                        <td><?= $task['Cron_ID'] ?></td>
                        <td class='nc-text-center'><?= "{$task['Cron_Minutes']}:{$task['Cron_Hours']}:{$task['Cron_Days']}" ?></a></td>

                        <?php if ($task['Cron_Launch']): ?>
                            <td><?= date("d.m.Y H:i:s", $task['Cron_Launch']) ?></td>
                        <?php else: ?>
                            <td><?= CONTROL_CONTENT_SUBDIVISION_FUNCS_OBJ_NO ?></td>
                        <?php endif ?>

                        <?php $url = htmlspecialchars($task['Cron_Script_URL'], ENT_QUOTES) ?>
                        <td><a href='<?= $url ?>' target=_blank><?= $url ?></a></font></td>
                        <td class='nc-text-center'>
                            <a href='crontasks.php?phase=4&CronID=<?= $task['Cron_ID'] ?>'>
                                <div class='icons icon_settings' title='<?= TOOLS_REDIRECT_CHANGEINFO ?>'></div>
                            </a>
                        </td>
                        <td class='nc-text-center'><?= nc_admin_checkbox_simple("Delete" . $task['Cron_ID'], $task['Cron_ID']) ?></td>
                    </tr>
                <?php endforeach ?>
            </table>
        </form>

    <?php else: nc_print_status(TOOLS_CRON_NOTASKS, 'info'); ?>
    <?php endif ?>
<?php } ?>


<?php
/**
 * @return void
 */
function ValidateHostname() {
    $nc_core = nc_core::get_object();
    $site = $nc_core->catalogue->get_current();
    $host = $site["Domain"];

    if (!$host || $host == "localhost" || preg_replace("/:\d+$/", "", $host) !== $_SERVER["HTTP_HOST"]) {
        nc_print_status(sprintf(TOOLS_CRON_WRONG_DOMAIN, $host, $nc_core->ADMIN_PATH . "#catalogue.edit($site[Catalogue_ID])"), "error");
    }
}

function CronForm($cron_id) {
    global $db, $Cron_Minutes, $Cron_Hours, $Cron_Days, $Cron_Script_URL;
    global $UI_CONFIG, $nc_core;

    if ($cron_id) {
        $rs = $db->get_row("SELECT * FROM `CronTasks` WHERE `Cron_ID` = '" . intval($cron_id) . "' LIMIT 1", ARRAY_A);
    } else {
        $rs = array();
    }
    ?>

    <form method="post" action="crontasks.php" style="color:gray;">
    <?= TOOLS_CRON_MINUTES ?>:
    <?= nc_admin_input_simple('Cron_Minutes', nc_array_value($rs, 'Cron_Minutes'), 3, '', '') ?>
    <?= TOOLS_CRON_HOURS ?>:
    <?= nc_admin_input_simple('Cron_Hours', nc_array_value($rs, 'Cron_Hours'), 3, '', '') ?>
    <?= TOOLS_CRON_DAYS ?>:
    <?= nc_admin_input_simple('Cron_Days', nc_array_value($rs, 'Cron_Days'), 3, '', '') ?></br></br>
    <?= TOOLS_CRON_SCRIPTURL ?>:</br>
    <?= nc_admin_input_simple('Cron_Script_URL', nc_array_value($rs, 'Cron_Script_URL'), 70, '', "maxlength='255'") ?>

    <?php
    $UI_CONFIG->actionButtons[] = array(
        'id' => 'history_back',
        'caption' => TOOLS_REDIRECT_BACK,
        'action' => 'history.back(1)',
        'align' => 'left',
    );

    if (!$cron_id) {
        echo '<input type="hidden" name="phase" value="2">';
        $UI_CONFIG->actionButtons[] = array(
            'id' => 'submit',
            'caption' => TOOLS_REDIRECT_ADDONLY,
            'action' => 'mainView.submitIframeForm()',
        );
    } else {
        echo "<input type='hidden' name='CronID' value='$cron_id'>";
        echo "<input type='hidden' name='phase' value='5'>";
        echo "<input type='submit' class='hidden'>";

        $UI_CONFIG->actionButtons[] = array(
            'id' => 'submit',
            'caption' => CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_SAVE,
            'action' => 'mainView.submitIframeForm()',
        );
    }

    echo $nc_core->token->get_input();
    echo '</form>';
}

function CronCompleted($cron_id, $minutes, $hours, $days, $script_url) {
    global $db;

    $minutes = intval($minutes);
    $hours = intval($hours);
    $days = intval($days);
    $script_url = $db->escape($script_url);
    $result = 0;

    if (($minutes == 0 && $hours == 0 && $days == 0) || ($script_url == '')) {
        nc_print_status(TOOLS_REDIRECT_CANTBEEMPTY, 'error');
        CronForm($cron_id);
    } elseif (!$cron_id) {
        $query = "INSERT INTO `CronTasks` (`Cron_Minutes`, `Cron_Hours`, `Cron_Days`, `Cron_Script_URL`) VALUES ('$minutes', '$hours', '$days', '$script_url')";
        $result = $db->query($query);
    } else {
        $query = "UPDATE `CronTasks` SET `Cron_Minutes` = '" . $minutes . "', `Cron_Hours`='" . $hours . "', `Cron_Days`='" . $days .
            "', `Cron_Script_URL`='" . $script_url . "' WHERE `Cron_ID`='" . $cron_id . "'";
        $result = $db->query($query);
    }

    return $result;
}

function IsCrontasksExist($old_url) {
    global $db;

    $old_url = $db->escape($old_url);
    $query = "SELECT `Cron_ID` FROM `CronTasks` WHERE `Cron_Script_URL` = '" . $old_url . "'";
    $db->get_results($query);

    return $db->num_rows > 0;
}

function DeleteCron($cron_id) {
    global $db;

    return $db->query("DELETE FROM `CronTasks` WHERE `Cron_ID` = '" . intval($cron_id) . "'");
}
