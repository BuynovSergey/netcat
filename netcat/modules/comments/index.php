<?php
// результаты ajax
$NETCAT_FOLDER = realpath(dirname(__FILE__) . '/../../..') . DIRECTORY_SEPARATOR;
include_once ($NETCAT_FOLDER."vars.inc.php");
require_once($ROOT_FOLDER.'connect_io.php');
require_once ($ADMIN_FOLDER."function.inc.php");

$perm->ExitIfNotAccess(NC_PERM_ITEM_USER, NC_PERM_ACTION_RIGHT, -1, 0, 0);

require_once('nc_comments_admin.class.php');
$nc_comments_admin = new nc_comments_admin();

$browse_msg['prefix'] = "";
$browse_msg['suffix'] = "";
$browse_msg['active'] = "<b>%PAGE</b>";
$browse_msg['unactive'] = "<a href=%URL>%PAGE</a>";
$browse_msg['divider'] = " | ";

$res = '';

if (isset($site)) { // Получить cc
    $Result = $nc_comments_admin->get_subs($site);
    if (!empty($Result)) $res = nc_select_options($Result, '', 0, 0, 1);
}
else if (isset($subid)) {
    $Result = $nc_comments_admin->get_ccs($subid);
    if (!empty($Result)) $res = nc_select_options($Result, '', 0, 0, 1);
}
else if (isset($cc)) {
    echo "
        <html>
        <head>
         <title>" . sprintf(NETCAT_MODERATION_RELATED_POPUP_TITLE, isset($field_description) ? htmlspecialchars($field_description) : null) . "</title>
         " . nc_js() . "
         <link rel='stylesheet' type='text/css' media='screen' href='" . nc_add_revision_to_url($ADMIN_TEMPLATE . 'css/admin.css') . "'>
         <script type='text/javascript' src='" . nc_add_revision_to_url($ADMIN_PATH . 'js/lib.js') . "'></script>
         <script type='text/javascript' src='" . nc_add_revision_to_url($ADMIN_PATH . 'js/container.js') . "'></script>
         <script type='text/javascript'>
          function selectItem(messageId) {
            window.location = 'index.php?object_id='+messageId;
          }
         </script>
        </head>";
    $res = $nc_comments_admin->get_messages($cc);
}
else if (isset($object_id)) {
    ?>
    <html>
        <head>
            <title></title>
            <script type='text/javascript'>
                try {
                  window.opener.document.getElementById('input_message').value = '<?= (int)$object_id ?>';
                }
                catch(e) {
                  alert("<?=addslashes(NETCAT_MODERATION_RELATED_ERROR_SAVING)?>");
                }
                window.close();
            </script>
        </head>
        <body></body>
    </html>
    <?
}
elseif ($select_user) {
    echo "<html>
        <head>
         <title>" . sprintf(NETCAT_MODERATION_RELATED_POPUP_TITLE, isset($field_description) ? htmlspecialchars($field_description) : null) . "</title>
         " . nc_js() . "
         <link rel='stylesheet' type='text/css' media='screen' href='" . nc_add_revision_to_url($ADMIN_TEMPLATE . 'css/admin.css') . "'>
         <script type='text/javascript' src='" . nc_add_revision_to_url($ADMIN_PATH . 'js/lib.js') . "'></script>
         <script type='text/javascript' src='" . nc_add_revision_to_url($ADMIN_PATH . 'js/container.js') . "'></script>
         <script type='text/javascript'>
          function selectItem(messageId) {
            window.location = 'index.php?user_id='+messageId;
          }
         </script>
        </head>";
    echo "<body class='nc-admin nc-padding-10' style='overflow: auto !important;'>
        <table class='nc-table nc--bordered nc--small nc--hovered nc--wide nc--striped'>
          ".$nc_comments_admin->get_users()."
        </table>
        </body>
        </html>";
}
else if ($user_id) {

    $sql = "SELECT `".$AUTHORIZE_BY."` as user FROM User WHERE `User_ID` = ". (int)$user_id;
    $value = nc_core('db')->get_var($sql);
    ?>
    <html>
        <head>
            <title></title>
            <script type='text/javascript'>
                try {
                  window.opener.document.getElementById('input_user').value = '<?=$value?>';
                }
                catch(e) {
                  alert("<?=addslashes(NETCAT_MODERATION_RELATED_ERROR_SAVING)?>");
                }
                window.close();
            </script>
        </head>
        <body></body>
    </html>
    <?
}


print $res;
exit();

?>