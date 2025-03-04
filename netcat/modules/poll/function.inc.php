<?php

/* $Id: function.inc.php 6209 2012-02-10 10:28:29Z denis $ */

function poll_alreadyAnswered($classID, $PollID, $ProtectIP, $ProtectUsers) {
    global $db, $REMOTE_ADDR;

    $PollID = intval($PollID);
    $classID = intval($classID);

    if ($ProtectIP == 1) {
        $Result = $db->query("SELECT `Message_ID` FROM `Poll_Protect` WHERE `Message_ID` = '{$PollID}' AND `IP` = '" . $db->escape($REMOTE_ADDR) . "'");
        if ($Result) {
            return true;
        }
    }

    if ($ProtectUsers == 1) {
        $User_ID = Authorize();
        if (!$User_ID) {
            return true;
        }

        $Result = $db->query("SELECT `Message_ID` FROM `Poll_Protect` WHERE `Message_ID` = '{$PollID}' AND `User_ID` = '" . intval($User_ID) . "'");
        if ($Result) {
            return true;
        }
    }

    if ($_COOKIE["Poll".$PollID."class".$classID]) {
        return true;
    }

    return false;
}

function poll_percentLine($classID, $PollID, $AnswerCount, $MaxWidth, $template) {
    global $db, $MODULE_VARS;

    $PollID = intval($PollID);
    $classID = intval($classID);

    static $storage_votesum = array(), $storage_votemax = array();
    if (!$storage_votesum[$PollID]) {
        $res = $db->get_row("SELECT * FROM `Message{$classID}` WHERE `Message_ID` = '{$PollID}'", ARRAY_A);
        $votes = array('Count1' => 0);
        if ($res) {
            for ($i = 1; $i <= 11; $i++) {
                if ($res["Answer{$i}"] === '') {
                    continue;
                }
                $votes["Count{$i}"] = +$res["Count{$i}"];
            }
        }
        $storage_votesum[$PollID] = array_sum($votes);
        $storage_votemax[$PollID] = max($votes);
    }

    $votesum = $storage_votesum[$PollID];
    $votemax = $storage_votemax[$PollID];

    if (!$votemax) {
        $votemax = 1;
    }
    if (!$votesum) {
        $votesum = 1;
    }

    $line_width = round(($MaxWidth / $votemax) * $AnswerCount);
    $line_percent = round(($AnswerCount / $votesum) * 100);

    $template = str_replace(array('%PERCENT', '%WIDTH'), array($line_percent, $line_width), $template);
    $result = '';
    eval(nc_check_eval("\$result = \"".$template."\";"));

    return $result;
}

function poll_alternativeAnswer($classID, $PollID) {
    global $db, $MODULE_VARS;

    $PollID = intval($PollID);
    $classID = intval($classID);

    $Answers = htmlspecialchars($db->get_var("SELECT `AltAnswer` FROM `Message{$classID}` WHERE `Message_ID` = '{$PollID}'"), ENT_QUOTES);
    $Answers = explode("\r\n", $Answers);
    $result = "<ol type='1'>";

    for ($i = 0; $i < count($Answers); $i++) {
        $result.= "<li>" . $Answers[$i];
    }

    return $result . "</ol>";
}
?>