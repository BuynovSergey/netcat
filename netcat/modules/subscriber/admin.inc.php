<?php

function SearchSubscriberForm() {
    global $ROOT_FOLDER, $INCLUDE_FOLDER, $admin_mode;
    global $systemTableID, $systemMessageID, $systemTableName, $UI_CONFIG;
?>

    <form method='get' action='admin.php' id='SearchSubscriberForm'>
        <fieldset>
            <legend><?= NETCAT_MODULE_SUBSCRIBE_ADM_GETUSERS ?></legend>
            <table border='0' cellpadding='6' cellspacing='0' width='100%'>
                <tr>
                    <td>
                        <nobr><font color='gray'><?= NETCAT_MODULE_SUBSCRIBE_ADM_USERID; ?></font>: <input type='text' name='UserID' size='5' maxlength='10' value=''></nobr>
                        <nobr><?= NETCAT_MODULE_SUBSCRIBE_ADM_CLASSID; ?>: <input type='text' name='SubClassID' size='5' maxlength='10' value=''></nobr>
                        <nobr>
                            <?= NETCAT_MODULE_SUBSCRIBE_ADM_STATUS; ?>:
                            <input checked id='chk1' type='radio' name='Checked' value=''>
                            <label for='chk1'><?= NETCAT_MODULE_SUBSCRIBE_ADM_ALLUSERS; ?></label>
                            <input id='chk2' type='radio' name='Checked' value='1'>
                            <label for='chk2'><?= NETCAT_MODULE_SUBSCRIBE_ADM_TURNEDON; ?></label>
                            <input id='chk3' type='radio' name='Checked' value='2'>
                            <label for='chk3'><?= NETCAT_MODULE_SUBSCRIBE_ADM_TURNEDOFF; ?></label>
                        </nobr>
                    </td>
                </tr>
                <tr>
                    <td>
                        <div align='right'>
                                <?php
                                $UI_CONFIG->actionButtons[] = array(
                                    "id" => "submit",
                                    "caption" => NETCAT_MODULE_SUBSCRIBE_BUT_GETIT,
                                    "action" => "mainView.submitIframeForm('SearchSubscriberForm')")
                                ;
                                ?>
                                <input type='submit' class='hidden'>
                        </div>
                    </td>
                </tr>
            </table>
        </fieldset>
        <input type='hidden' name='phase' value='2'>
    </form><?php
}

function ListSubscriberPages($totRows, $queryStr) {
    global $curPos;

    $range = 15;
    $maxRows = 20;

    $curPos = (int) $curPos;
    if ($curPos < 0) {
        $curPos = 0;
    }

    if (!$maxRows || !$totRows) {
        return;
    }

    $page_count = ceil($totRows / $maxRows);
    $half_range = ceil($range / 2);
    $cur_page = ceil($curPos / $maxRows) + 1;

    if ($page_count < 2) {
        return;
    }

    $maybe_from = $cur_page - $half_range;
    $maybe_to = $cur_page + $half_range;

    if ($maybe_from < 0) {
        $maybe_to -= $maybe_from;
        $maybe_from = 0;

        if ($maybe_to > $page_count) {
            $maybe_to = $page_count;
        }
    }

    if ($maybe_to > $page_count) {
        $maybe_from = $page_count - $range;
        $maybe_to = $page_count;

        if ($maybe_from < 0) {
            $maybe_from = 0;
        }
    }

    echo "";

    for ($i = $maybe_from; $i < $maybe_to; $i++) {
        $page_number = $i + 1;
        $page_from = $i * $maxRows;
        $page_to = $page_from + $maxRows;
        $url = "?phase=2".$queryStr."&curPos=".$page_from;

        if ($curPos == $page_from) {
            echo "<b>$page_number</b>";
        } else {
            echo "<a href='$url'>$page_number</a>";
        }

        if ($i != ($maybe_to - 1)) {
            echo " | ";
        }
    }
    echo '</font>';
}

function SearchSubscriberResult() {
    global $ROOT_FOLDER, $INCLUDE_FOLDER;
    global $db, $SubClassID, $Checked;
    global $admin_mode, $curPos;
    global $AUTHORIZE_BY, $SUB_FOLDER, $ADMIN_PATH, $ADMIN_TEMPLATE;
    global $UI_CONFIG;
    $nc_core = nc_Core::get_object();

    $curPos += 0;

    $select = "SELECT COUNT(*) FROM Subscriber WHERE 1";
    if ($UserID) {
        $select .= " AND User_ID=".$UserID;
    }
    if ($SubClassID) {
        $select .= " AND Sub_Class_ID=" . $SubClassID;
    }
    if ($Checked != "") {
        $select .= " AND Status='" . $Checked . "'";
    }

    $totRows = $db->get_var($select);
    ListSubscriberPages($totRows, ($curPos ? "?curPos=".$curPos : ""));

    $select = "SELECT
                   a.Subscriber_ID,
                   a.User_ID,
                   b.$AUTHORIZE_BY AS 'AUTHORIZE_BY',
                   a.Sub_Class_ID,
                   c.Sub_Class_Name,
                   d.Subdivision_Name,
                   a.Status,
                   d.Subdivision_ID,
                   d.Catalogue_ID
               FROM
                   Subscriber AS a,
                   User AS b,
                   Sub_Class AS c,
                   Subdivision AS d
               WHERE
                   a.User_ID = b.User_ID
               AND
                   a.Sub_Class_ID = c.Sub_Class_ID
               AND
                   c.Subdivision_ID = d.Subdivision_ID";
    if ($UserID) {
        $select .= " AND a.User_ID=" . $UserID;
    }
    if ($SubClassID) {
        $select .= " AND a.Sub_Class_ID=" . $SubClassID;
    }
    if ($Checked != "") {
        $select .= " AND a.Status=" . $Checked;
    }
    $select .= " LIMIT $curPos,20";

    if ($Result = $db->get_results($select, ARRAY_A)) { ?>
        <?
            $UI_CONFIG->actionButtons[] = array(
                "id" => "submit",
                "caption" => NETCAT_MODULE_SUBSCRIBE_ADM_SAVE,
                "action" => "mainView.submitIframeForm('deleteSubscriptionsForm')"
            );
        ?>

        <form method='post' action='admin.php' id='deleteSubscriptionsForm'>
            <table border='0' cellpadding='0' cellspacing='0' width='100%'>
                <tr>
                    <td>
                        <table class='admin_table' width='100%'>
                            <tr>
                                <td><b>ID</b></td>
                                <td width='40%'><b><?=NETCAT_MODULE_SUBSCRIBE_ADM_CLASSINSECTION ?></b></td>
                                <td><b><?=NETCAT_MODULE_SUBSCRIBE_ADM_USER ?></b></td>
                                <td align='center'><b><?=NETCAT_MODULE_SUBSCRIBE_ADM_STATUS ?></b></td>
                                <td  align='center'><div class='icons icon_delete' title='<?=NETCAT_MODULE_SUBSCRIBE_ADM_DELETE ?>'></div></td>
                            </tr>
                            <? foreach ($Result as $Array): ?>
                                <? $catalogue_url = $nc_core->catalogue->get_url_by_id($Array['Catalogue_ID']); ?>
                                <tr>
                                    <td>
                                        <b>
                                            <? if (!$Array['Status']): ?>
                                                <font color='<?= NETCAT_MODULE_SUBSCRIBE_ADM_TURNOFFCLR; ?>'><?= $Array['Subscriber_ID']; ?></font>
                                            <? else: ?>
                                                <?= $Array['Subscriber_ID']; ?>
                                            <? endif; ?>
                                        </b>
                                    </td>
                                    <td>
                                        <a href="<?= $catalogue_url . $ADMIN_PATH; ?>subdivision/SubClass.php?phase=3&SubdivisionID=<?= $Array['Subdivision_ID']; ?>&CatalogueID=<?= $Array['Catalogue_ID']; ?>&SubClassID=<?= $Array['Sub_Class_ID']; ?>">
                                            <? if (!$Array['Status']): ?>
                                                <font color='<?= NETCAT_MODULE_SUBSCRIBE_ADM_TURNOFFCLR; ?>'><?= "$Array[Sub_Class_ID] ($Array[Sub_Class_Name])"; ?></font>
                                            <? else: ?>
                                                <?= "$Array[Sub_Class_ID] ($Array[Sub_Class_Name])"; ?>
                                            <? endif; ?>
                                        </a>
                                        <br><?= NETCAT_MODULE_SUBSCRIBE_ADM_SECTION; ?>:
                                        <a href="<?= $catalogue_url . $ADMIN_PATH; ?>subdivision/index.php?phase=5&SubdivisionID=<?= $Array['Subdivision_ID']; ?>">
                                            <? if (!$Array['Status']): ?>
                                                <font color='<?= NETCAT_MODULE_SUBSCRIBE_ADM_TURNOFFCLR; ?>'><?= "$Array[Subdivision_ID] ($Array[Subdivision_Name])"; ?></font>
                                            <? else: ?>
                                                <?= "$Array[Subdivision_ID] ($Array[Subdivision_Name])"; ?>
                                            <? endif; ?>
                                        </a>
                                    </td>

                                    <td>
                                        <a href="<?= $catalogue_url . $ADMIN_PATH; ?>user/index.php?phase=4&UserID=<?= $Array['User_ID']; ?>">
                                            <? if (!$Array['Status']): ?>
                                                <font color='<?= NETCAT_MODULE_SUBSCRIBE_ADM_TURNOFFCLR; ?>'><?= $Array['User_ID'] . ($AUTHORIZE_BY !== 'User_ID' ? " ($Array[AUTHORIZE_BY])" : ''); ?></font>
                                            <? else: ?>
                                                <?= $Array['User_ID'] . ($AUTHORIZE_BY !== 'User_ID' ? " ($Array[AUTHORIZE_BY])" : ''); ?>
                                            <? endif; ?>
                                        </a>
                                    </td>

                                    <td align='center'>
                                        <a href="<?= nc_module_path('subscriber'); ?>admin.php?phase=3&SubscriberID=<?= $Array['Subscriber_ID']; ?>">
                                            <? if (!$Array['Status']): ?>
                                                <font color='<?= NETCAT_MODULE_SUBSCRIBE_ADM_TURNOFFCLR; ?>'><?= NETCAT_MODULE_SUBSCRIBE_ADM_TURNON; ?></font>
                                            <? else: ?>
                                                <?= NETCAT_MODULE_SUBSCRIBE_ADM_TURNOFF; ?>
                                            <? endif; ?>
                                        </a>
                                    </td>
                                    <td align='center'><input type='checkbox' name="Delete<?= $Array['Subscriber_ID']; ?>" value="<?= $Array['Subscriber_ID']; ?>"></td>
                                </tr>
                            <? endforeach; ?>
                        </table>
                    </td>
                </tr>
            </table>
            <br>
            <input type='hidden' name='phase' value='4'>
            <input type='submit' class='hidden'>
        </form><?
    } else {
        echo NETCAT_MODULE_SUBSCRIBE_MSG_NOSUBSCRIBER;
    }
}

function ToggleSubscriber($SubscriberID) {
    global $db;

    return $db->query("UPDATE Subscriber SET Status = (1 - Status) WHERE Subscriber_ID = " . (int)$SubscriberID);
}

function DeleteSubscribers() {
    global $db;

    foreach ($_POST as $key => $val) {
        if ($key === 'Submit') {
            continue;
        }
        if ($key === 'phase') {
            continue;
        }

        $db->query("DELETE FROM Subscriber WHERE Subscriber_ID =". (int)$val);
    }
}
?>