<?
/* $Id: save.php 5946 2012-01-17 10:44:36Z denis $ */

$NETCAT_FOLDER = join(strstr(__FILE__, "/") ? "/" : "\\", array_slice(preg_split("/[\/\\\]+/", __FILE__), 0, -4)) . (strstr(__FILE__, "/") ? "/" : "\\");
include_once($NETCAT_FOLDER . "vars.inc.php");
require($ADMIN_FOLDER . "function.inc.php");
require_once($INCLUDE_FOLDER . "s_common.inc.php");

$input = nc_core('input');

$classID = (int)$input->fetch_get('classID');
$message = (int)$input->fetch_get('message');
$cc = (int)$input->fetch_get('cc');
$move = (int)$input->fetch_get('move');

if (!$classID || !$message || !$cc) {
    trigger_error("Wrong params", E_USER_ERROR);
}

if (!$nc_core->token->verify()) {
    BeginHtml($Title2, $Title2, "http://" . $DOC_DOMAIN . "/management/class/");
    nc_print_status(NETCAT_TOKEN_INVALID, 'error');
    EndHtml();
    exit;
}

if ($move) {
    nc_move_message($classID, $message, $cc);
    $new_message_id = $message;
} else {
    $new_message_id = (int)nc_copy_message($classID, $message, $cc);
}

$sql = "SELECT `Sub_Class_ID` FROM `Message{$classID}` WHERE `Message_ID` = '{$message}' LIMIT 1";
$new_cc = (int)$db->get_var($sql);
?>
<html>
<head>
    <title></title>
    <script type="text/javascript">
        <?php if ($move) { ?>
        opener.window.location.reload();
        <?php } else if ($cc == $new_cc) { ?>
        var url_location = opener.window.location.href.replace(/[&?]highlight=([0-9]+)/i, '');
        opener.window.location = url_location + (url_location.indexOf('?') !== -1 ? '&' : '?') + 'highlight=<?= $new_message_id; ?>';
        <?php } ?>
        alert("<?= addslashes($move ? NETCAT_MODERATION_MOVE_SUCCESS : NETCAT_MODERATION_COPY_SUCCESS); ?>");
        window.close();
    </script>
</head>
<body></body>
</html>