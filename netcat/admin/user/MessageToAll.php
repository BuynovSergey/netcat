<?php
/* $Id */
$NETCAT_FOLDER = join(strstr(__FILE__, "/") ? "/" : "\\", array_slice(preg_split("/[\/\\\]+/", __FILE__), 0, -4)).( strstr(__FILE__, "/") ? "/" : "\\" );
include_once ($NETCAT_FOLDER."vars.inc.php");
require ($ADMIN_FOLDER."function.inc.php");


$main_section = "control";
$item_id = 7;
if (!isset($UserID)) {
    $UserID = null;
}
$systemMessageID = $UserID;
$systemTableName = "User";
$systemTableID = GetSystemTableID($systemTableName);

$Delimeter = " &gt ";
$Title2 = CONTROL_USER_MAIL;
$Title3 = "<a href=\"".$ADMIN_PATH."user/MessageToAll.php\">".$Title2."</a>".$Delimeter.CONTROL_USER_MAIL_TITLE_COMPOSE;

function ShowForm() {
    global $db, $ROOT_FOLDER, $INCLUDE_FOLDER;
    global $systemTableID, $systemMessageID, $systemTableName;
    global $SPAM_FROM_NAME, $SPAM_FROM, $ADMIN_PATH;
    $nc_core = nc_Core::get_object();
    ?>
    <form name='main' id='main' method=post action="MessageToAll.php">

        <fieldset>
            <legend><?= CONTROL_USER_MAIL_RULES
    ?></legend>
            <table width=100%><tr><td>

                        <?
                        $Result = $db->get_results("select PermissionGroup_ID, PermissionGroup_Name from PermissionGroup", ARRAY_N);
                        print "<font color=gray>".CONTROL_USER_MAIL_GROUP.":</font><br><SELECT NAME=PermissionGroupID>";
                        print "<OPTION VALUE=0>".CONTROL_USER_MAIL_ALLGROUPS;
                        print "</OPTION>\n";

                        foreach ($Result as $GroupArray) {
                            print "  <OPTION ";
                            print "VALUE=".$GroupArray[0].">";
                            print $GroupArray[0].": ".$GroupArray[1];
                            print "</OPTION>";
                        }
                        print "</SELECT><br><br>";
                        require $ROOT_FOLDER."message_fields.php";


                        if ($searchForm = showSearchForm($fldName, $fldType, $fldDoSearch, $fldFmt)) {
                            ?>
                            <fieldset>
                                <table width=100%><tr><td>
                                            <?
                                            echo $searchForm;
                                            ?>
                                        </td></tr></table>
                            </fieldset>
                            <?
                        }
                        ?>
                    </td></tr></table></fieldset>
        <fieldset>
            <legend><?= CONTROL_USER_MAIL_CONTENT
                        ?></legend>
            <table border=0 cellpadding=6 cellspacing=0 width=100%><tr><td>
                        <?= CONTROL_USER_MAIL_FROM ?>: <b><?= $SPAM_FROM_NAME ?></b> &lt;<?= $SPAM_FROM ?>&gt; <a href=<?= "".$ADMIN_PATH."settings.php?phase=1" ?>><?= CONTROL_USER_MAIL_CHANGE ?></a><br><br>

                        <?= CONTROL_USER_MAIL_SUBJECT
                        ?>:<br><?=nc_admin_input_simple('Subject', '', 60)?><br><br>
                        <?php echo nc_admin_checkbox_simple('is_html', 1, NETCAT_MODULE_HTML_MAIL, false, 'is_html')?><br />

                        <?php echo nc_admin_textarea(CONTROL_USER_MAIL_BODY, 'Message', '', 1, 1, 'width: 100%; height: 20em; line-height: 1em; ')?><br><br>
                        
                        <?php echo nc_admin_checkbox_simple('Attach', 1, CONTROL_USER_MAIL_ADDATTACHMENT, false, 'att')?>
                        
                    </td></tr></table>
        </fieldset>

        <?php
        /* <div align=right><input class=s type=submit value="<?=CONTROL_USER_MAIL_SEND?>"></div> */
        global $UI_CONFIG;
        $UI_CONFIG->actionButtons[] = array("id" => "submit",
                "caption" => CONTROL_USER_MAIL_SEND,
                "action" => "mainView.submitIframeForm()");
        ?>

        <input type=hidden name=phase value=2>
        <input type='submit' class='hidden'>
    </form>
    <?php
}

function SendMessage($PermissionGroupID, $Subject, $Message, $Attach, $is_html = 0) {
    global $db, $nc_core, $ROOT_FOLDER, $INCLUDE_FOLDER;
    global $FileToAttach, $FileToAttach_name, $FileToAttach_type;
    global $SPAM_FROM, $SPAM_FROM_NAME;
    global $systemTableID, $systemMessageID, $systemTableName;
    global $srchPat;

    require $ROOT_FOLDER."message_fields.php";
    require_once $INCLUDE_FOLDER."s_list.inc.php";

    $search_params = $nc_core->get_component('User')->get_search_query($srchPat);
    $fullSearchStr = $search_params['query'];

    $MyEmail = "info@".$HTTP_DOMAIN;
    $MyName = "Supervisor";

    $SPAM_MAIL = $nc_core->get_settings('UserEmailField');

    if (!$SPAM_MAIL) {
        nc_print_status(CONTROL_USER_MAIL_ERROR_EMAILFIELD, 'error');
        return;
    }

    $select = "SELECT `".$SPAM_MAIL."`
                   FROM `User` AS a,
                        `User_Group` AS ug
                       WHERE a.`User_ID` > 0
                         AND ug.`User_ID` = a.`User_ID`
                         AND a.`".$SPAM_MAIL."` <> ''
                         ".($PermissionGroupID ? " AND ug.`PermissionGroup_ID` = ".intval($PermissionGroupID) : "" ).
                         $fullSearchStr."
                             ORDER BY a.`".$SPAM_MAIL."`";

    if (($Result = $db->get_results($select, ARRAY_N))) {
        foreach ($Result as $Array) {
            $Email[] = $Array[0];
        }
        $Email = array_unique($Email);
    }

    $nc_core->mail->mailbody(strip_tags($Message), $is_html ? $Message : '');

    if (sizeof($Email)) {

        @set_time_limit(0);
        @ignore_user_abort(true);

        for ($i = 0; $i < sizeof($Email); $i++) {
            print ($i + 1)." . ".$Email[$i]."<br>\n";

            if ($Attach) {
                $FileToAttach_name = $_FILES['FileToAttach']['name'];
                $FileToAttach_type = $_FILES['FileToAttach']['type'];
                $nc_core->mail->attachFile($_FILES['FileToAttach']['tmp_name'], $FileToAttach_name, $FileToAttach_type);
            }

            $nc_core->mail->send($Email[$i], $SPAM_FROM, $SPAM_FROM, $Subject, $SPAM_FROM_NAME);
            ob_flush();
            flush();
        }

        nc_print_status(CONTROL_USER_MAIL_OK, 'ok');
    } else {
        nc_print_status(CONTROL_USER_MAIL_ERROR_NOONEEMAIL, 'error');
    }

    return false;
}

function AttachForm($is_html = 0) {
    global $db;
    global $Subject, $Message, $TmpID, $PermissionGroupID;
    global $srchPat;
    ?>

    <form enctype="multipart/form-data" action=MessageToAll.php method=post>
        <input type=hidden name=MAX_FILE_SIZE value=10000000>

        <fieldset>
            <legend><?= CONTROL_USER_MAIL_ATTCHAMENT ?></legend>
            <table border=0 cellpadding=6 cellspacing=0 width=100%><tr><td>
                        <input size=50 name=FileToAttach type=file>
                    </td></tr></table>
        </fieldset>

        <?php
        /*   <div align=right><input class=s type=submit value="<?=CONTROL_USER_MAIL_SEND?>"></div> */
        global $UI_CONFIG;
        $UI_CONFIG->actionButtons[] = array("id" => "submit",
                "caption" => CONTROL_USER_MAIL_SEND,
                "action" => "mainView.submitIframeForm()");

        for ($i = 0; $i < count($srchPat); $i++) {
            echo "<input type='hidden' name='srchPat[$i]' value='" . htmlspecialchars($srchPat[$i], ENT_QUOTES) . "'>";
        }
            ?>
            <input type=hidden name=phase value=3>
            <input type=hidden name=PermissionGroupID value=<? print $PermissionGroupID; ?>>
            <input type=hidden name=TmpID value=<? print $TmpID; ?>>
            <input type=hidden name=is_html value=<? print $is_html; ?>>
            <input type='submit' class='hidden'>
        </form>

        <?php
}

###########################################################################
# описание функций закончено
###########################################################################
// описание интерфейса
$UI_CONFIG = new ui_config();
$UI_CONFIG->headerText = SECTION_INDEX_USER_USER_MAIL;
$UI_CONFIG->tabs = array(
        array('id' => 'mail',
                'caption' => SECTION_INDEX_USER_USER_MAIL,
                'location' => "user.mail()"),
);
$UI_CONFIG->activeTab = "mail";
$UI_CONFIG->treeMode = "users";
$UI_CONFIG->treeSelectedNode = "usermail";
if (!isset($phase)) {
    $phase = null;
}
$UI_CONFIG->locationHash = "user.mail($phase)";

// действия
if (empty($phase)) {
    $phase = 1;
}

switch ($phase) {
    case 1:
        # надо просто показать форму
        BeginHtml($Title2, $Title2, "http://".$DOC_DOMAIN."/management/messages/");
        $perm->ExitIfNotAccess(NC_PERM_ITEM_GROUP, NC_PERM_ACTION_MAIL, 0, 0, 0);
        if (!$SPAM_MAIL) {
            nc_print_status(CONTROL_USER_MAIL_ERROR_ONE, 'error');
            break;
        }
        if (!$SPAM_FROM_NAME) {
            nc_print_status(CONTROL_USER_MAIL_ERROR_TWO, 'error');
            break;
        }
        if (!$SPAM_FROM) {
            nc_print_status(CONTROL_USER_MAIL_ERROR_THREE, 'error');
            break;
        }
        ShowForm();
        break;

    case 2:
        if (!$Message) {
            BeginHtml($Title2, $Title2, "http://".$DOC_DOMAIN."/management/messages/");
            $perm->ExitIfNotAccess(NC_PERM_ITEM_GROUP, NC_PERM_ACTION_MAIL, 0, 0, 1);
            nc_print_status(CONTROL_USER_MAIL_ERROR_NOBODY, 'error');
            ShowForm();
            break;
        }

        if (isset($Attach)) {
            BeginHtml($Title2, $Title3, "http://".$DOC_DOMAIN."/management/messages/");
            $perm->ExitIfNotAccess(NC_PERM_ITEM_GROUP, NC_PERM_ACTION_MAIL, 0, 0, 1);
            $Result = $db->query("INSERT INTO MailTmp (Subject,Message) VALUES ('".$Subject."','".$Message."')");
            $TmpID = $db->insert_id;
            AttachForm($is_html);
        } else {
            BeginHtml($Title2, $Title3, "http://".$DOC_DOMAIN."/management/messages/");
            $perm->ExitIfNotAccess(NC_PERM_ITEM_GROUP, NC_PERM_ACTION_MAIL, 0, 0, 1);
            SendMessage($PermissionGroupID, $Subject, $Message, 0, $is_html);
        }
        break;

    case 3:
        BeginHtml($Title2, $Title3, "http://".$DOC_DOMAIN."/management/messages/");
        $perm->ExitIfNotAccess(NC_PERM_ITEM_GROUP, NC_PERM_ACTION_MAIL, 0, 0, 1);
        $Array = $db->get_row("SELECT Subject, Message FROM MailTmp WHERE MailTmp_ID='".$TmpID."'", ARRAY_N);
        $Subject = $Array[0];
        $Message = $Array[1];

        $Result = $db->query("DELETE FROM MailTmp WHERE MailTmp_ID='".$TmpID."'");
        SendMessage($PermissionGroupID, $Subject, $Message, 1, $is_html);
        break;
}
EndHtml();
?>
