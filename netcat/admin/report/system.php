<?php

$NETCAT_FOLDER = join(strstr(__FILE__, "/") ? "/" : "\\", array_slice(preg_split("/[\/\\\]+/", __FILE__), 0, -4)).( strstr(__FILE__, "/") ? "/" : "\\" );
include_once ($NETCAT_FOLDER."vars.inc.php");
require ($ADMIN_FOLDER."function.inc.php");
require ($ADMIN_FOLDER."report/system.inc.php");

$Delimeter = " &gt ";
$main_section = "report";
$item_id = 3;
$Title1 = BEGINHTML_ALARMOFF;
$Title2 = BEGINHTML_ALARMVIEW;
$Title3 = "<a href=".$ADMIN_PATH."report/system.php>".SECTION_INDEX_REPORTS_SYSTEM."</a>";

$UI_CONFIG = new ui_config_tool(SECTION_INDEX_REPORTS_SYSTEM, SECTION_INDEX_REPORTS_SYSTEM, 'i_netcat_big.gif', 'tools.systemmessages');

if (!isset($SystemMessageID)) {
    $SystemMessageID = null;
}
if (!isset($phase)) {
    $phase = null;
}
$perm->ExitIfNotAccess(NC_PERM_REPORT, 0, 0, 0, 1);

if ($SystemMessageID && $phase == 1) {
    $res = $db->query("UPDATE SystemMessage SET Date=Date,Checked=1 WHERE SystemMessage_ID=".($SystemMessageID + 0));
    $ANY_SYSTEM_MESSAGE = $db->get_var("SELECT count(*) FROM SystemMessage WHERE Checked=0");
}

LoadSettings();

if ($SystemMessageID && $phase != 1) {
    $UI_CONFIG->locationHash = "tools.systemmessages($SystemMessageID)";

    BeginHtml($Title2, $Title3.$Delimeter.$Title2, "http://".$DOC_DOMAIN."/reports/sysmessages/");
    if ($res = $db->get_row("SELECT * FROM SystemMessage WHERE SystemMessage_ID=".($SystemMessageID + 0), ARRAY_A)) {
        $Array = $res;
    }
    //  In MySQL 4.1, TIMESTAMP display format changes to be the same as DATETIME.
    if (substr($Array['Date'], 4, 1) != '-') {
        $Array['Date'] = nc_substr($Array['Date'], 0, 4)."-".nc_substr($Array['Date'], 4, 2)."-".nc_substr($Array['Date'], 6, 2)." ".nc_substr($Array['Date'], 8, 2).":".nc_substr($Array['Date'], 10, 2).":".nc_substr($Array['Date'], 12, 2);
    }
?>
    <table class="admin_table" width='100%'>
        <tr>
            <td><b><?=$Array["Description"]?></td>
            <td nowrap><?=$Array["Date"]?></td>
        </tr>
    </table>
    <br />
    <table class="admin_table" width='100%'>
        <tr>
            <td><?=nl2br($Array["Message"])?></td>
        </tr>
    </table>
    <center>
        <form action='system.php' method='post'>
            <input type='hidden' name='phase' value='1'>
            <input type='hidden' name='SystemMessageID' value='<?= (int)$SystemMessageID ?>'>
        </form>
    </center>
<?
    $UI_CONFIG->actionButtons[] = array("id" => "submit",
            "caption" => ($Array['Checked'] ? REPORTS_SYSMSG_BACK : REPORTS_SYSMSG_MARK),
            "action" => "mainView.submitIframeForm()"
    );
} else {
    $has_new_messages = $db->get_var("SELECT COUNT(*) FROM SystemMessage WHERE Checked=0");
    $page_title = $has_new_messages ? SECTION_INDEX_REPORTS_SYSTEM : BEGINHTML_ALARMOFF;

    BeginHtml($page_title, $page_title, "http://".$DOC_DOMAIN."/reports/sysmessages/");
    if ($res = $db->get_results("SELECT * FROM SystemMessage ORDER BY Date DESC LIMIT 20", ARRAY_A)) {
?>

	<table class='admin_table' width='100%'>
		<tr>
			<th><?=REPORTS_SYSMSG_DATE ?></th>
			<th width='80%'><?=REPORTS_SYSMSG_MSG ?></th>
		</tr>
<?
		foreach ($res as $Array) {
			//  In MySQL 4.1, TIMESTAMP display format changes to be the same as DATETIME.
			if (substr($Array['Date'], 4, 1) != '-') {
				$Array['Date'] = nc_substr($Array['Date'], 0, 4)."-".nc_substr($Array['Date'], 4, 2)."-".nc_substr($Array['Date'], 6, 2)." ".nc_substr($Array['Date'], 8, 2).":".nc_substr($Array['Date'], 10, 2).":".nc_substr($Array['Date'], 12, 2);
			}
?>
		<tr>
			<td nowrap><font color=gray><?= $Array["Date"] ?></td>
			<td><a href='system.php?SystemMessageID=<?= $Array["SystemMessage_ID"] ?>'><?= (!$Array["Checked"] ? "<b>" : "") . $Array["Description"] . (!$Array["Checked"] ? "</b>" : "") ?></a></td>
		</tr>
<?
		}
?>
	</table>

<?
	} else {
		echo REPORTS_SYSMSG_NONE;
	}
}

print "<script>top.updateSysMsgIndicator(".($ANY_SYSTEM_MESSAGE ? 'true' : 'false').")</script>";

EndHtml ();
