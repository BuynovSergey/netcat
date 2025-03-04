<?php
// общий заголовок для select_*

$NETCAT_FOLDER = join(strstr(__FILE__, "/") ? "/" : "\\", array_slice(preg_split("/[\/\\\]+/", __FILE__), 0, -4)) . (strstr(__FILE__, "/") ? "/" : "\\");
include_once($NETCAT_FOLDER . "vars.inc.php");
require($ADMIN_FOLDER . "function.inc.php");
$nc_core = nc_core::get_object();
?>
<html>
<head>
    <title><?= WIZARD_PARENTSUB_SELECT_POPUP_TITLE ?></title>
    <link rel='stylesheet' type='text/css' media='screen' href='<?= nc_add_revision_to_url($ADMIN_TEMPLATE . 'css/sprites.css') ?>'>
    <link rel='stylesheet' type='text/css' media='screen' href='<?= nc_add_revision_to_url($ADMIN_TEMPLATE . 'css/main.css') ?>'>
    <link rel='stylesheet' type='text/css' media='screen' href='<?= nc_add_revision_to_url($ADMIN_TEMPLATE . 'css/admin.css') ?>'>
    <script type='text/javascript' src='<?= nc_add_revision_to_url($ADMIN_PATH . 'js/lib.js') ?>'></script>
    <script type='text/javascript' src='<?= nc_add_revision_to_url($ADMIN_PATH . 'js/container.js') ?>'></script>
    <script>
        function loadSubClasses(subId, ccId, classId, messageId) {
            document.getElementById('subViewIframe').contentWindow.location = '<?= $ADMIN_PATH; ?>objects/select_subclass_list.php?sub=' + subId + '&cc=' + ccId + '&classID=' + classId + '&message=' + messageId;
        }

        function copyItem(subId, ccId, classId, messageId, move) {
            if (confirm('<?= NETCAT_MODERATION_CONFIRM_COPY_RELATED; ?>')) {
                window.location = '<?= $ADMIN_PATH; ?>objects/save.php?<?= $nc_core->token->get_url(); ?>&sub=' + subId + '&cc=' + ccId + '&classID=' + classId + '&message=' + messageId + '&move=' + move;
            }
        }
    </script>
</head>
