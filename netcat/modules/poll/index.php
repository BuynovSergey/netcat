<?php

ob_start();

$NETCAT_FOLDER = join(strstr(__FILE__, "/") ? "/" : "\\", array_slice(preg_split("/[\/\\\]+/", __FILE__), 0, -4)).( strstr(__FILE__, "/") ? "/" : "\\" );
include_once ($NETCAT_FOLDER."vars.inc.php");

require ($INCLUDE_FOLDER."index.php");

$PollID = intval($PollID);
$classID = intval($classID);

list ($ProtectIP, $ProtectUsers) = $db->get_row("SELECT `ProtectIP`, `ProtectUsers` FROM `Message".intval($classID)."` WHERE `Message_ID` = '".intval($PollID)."'", ARRAY_N);

if (!poll_alreadyAnswered($classID, $PollID, $ProtectIP, $ProtectUsers)) {

    $cookie_time = time() + 3600 * 24;
    $nc_core->cookie->set("Poll" . $PollID . "class" . $classID, "1", $cookie_time);

    if ($ProtectIP != 0) {
        $Remote_Addr = $REMOTE_ADDR;
    } else {
        $Remote_Addr = null;
    }

    if ($ProtectUsers != 0) {
        $User_ID = Authorize();
    } else {
        $User_ID = null;
    }

    if ($ProtectUsers != 0 || $ProtectIP != 0) {
        $db->query("INSERT INTO `Poll_Protect`
      (`Message_ID`, `IP`, `User_ID`)
      VALUES
      ('".intval($PollID)."', '".$db->escape($Remote_Addr)."', '".intval($User_ID)."')");
    }

    reset($_POST);
    $update = "UPDATE `Message".intval($classID)."` SET ";
    $update.= "`TotalCount` = IF(`TotalCount` IS NULL, 1, `TotalCount` + 1),";

    if ($Answer) {
        $fieldname = "`Count".intval($Answer)."`";
        $update.= "$fieldname = IF($fieldname IS NULL, 1, $fieldname + 1),";
    } else {
        foreach ($_POST as $key => $val) {
            if (strpos($key, 'Answer') === 0) {
                $number = substr($key, 6);
                $fieldname = "`Count".intval($number)."`";
                if ($val) {
                    $update .= "$fieldname = IF($fieldname IS NULL, " . intval($val) . ", $fieldname + " . intval($val) . "),";
                }
            }
        }
    }

    if ($Answer11 || ($Answer == '11' && strlen($AltAnswer) > 0)) {
        $rs = $db->get_row("SELECT `AltAnswer` FROM `Message".intval($classID)."` WHERE `Message_ID` = '".intval($PollID)."'", ARRAY_A);
        if (strlen($rs['AltAnswer']) > 3) {
            $update .= "`AltAnswer` = CONCAT_WS('\r\n', `AltAnswer`, '".$db->escape($AltAnswer)."'),";
        } else {
            $update .= "`AltAnswer` = '".$db->escape($AltAnswer)."', ";
        }
        unset($res, $rs);
    }

    $update.= "`LastUpdated` = `LastUpdated` WHERE `Message_ID` = '".intval($PollID)."'";

    $db->query($update);
}

if (!isset($poll)) {
    echo s_list_class($sub, $cc, "&PollID=".$PollID);
} else {
    echo NETCAT_MODULE_POLL_MSG_POLLED;
}

$nc_result_msg = ob_get_clean();

if ($File_Mode) {
    require_once $INCLUDE_FOLDER.'index_fs.inc.php';
} else {
    nc_evaluate_template($template_header, $template_footer, false);
}

$nc_core->output_page($template_header, $nc_result_msg, $template_footer);