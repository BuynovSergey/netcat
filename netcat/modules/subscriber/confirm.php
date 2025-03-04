<?php

$NETCAT_FOLDER = join(strstr(__FILE__, "/") ? "/" : "\\", array_slice(preg_split("/[\/\\\]+/", __FILE__), 0, -4)).( strstr(__FILE__, "/") ? "/" : "\\" );
include_once ($NETCAT_FOLDER."vars.inc.php");
require_once ($ROOT_FOLDER."connect_io.php");

$MODULE_VARS = $nc_core->modules->load_env();

$nc_subscriber = nc_subscriber::get_object();
$res = $nc_subscriber->tools->get_subscribe_sub();
$catalogue = $res['Catalogue_ID'];
$sub = isset($_GET['sub']) ? $_GET['sub'] : $res['Subdivision_ID'];

unset($res);

require($INCLUDE_FOLDER."index.php");

$hash = $db->escape($nc_core->input->fetch_get_post('hash'));

$subsc = false;
if ($hash) {
    $subsc = $db->get_row("SELECT `ID`, `Status` FROM `Subscriber_Subscription` WHERE `Hash` = '".$hash."'", ARRAY_A);
}


if ($subsc) {
    // подтверждение подписки
    if ($subsc['Status'] == 'wait') {
        $nc_subscriber->subscription_confirm($subsc['ID']);
        $nc_text = $nc_subscriber->tools->get_settings('TextConfirm');
    } else { // удаление подписки
        $nc_subscriber->subscription_delete($subsc['ID']);
        $nc_text = $nc_subscriber->tools->get_settings('TextUnscribe');
    }
} else {
    $nc_text = $nc_subscriber->tools->get_settings('TextError');
}

unset($subsc);
unset($hash);


ob_start();
eval(nc_check_eval("echo \"$nc_text\";"));
$nc_result_msg = ob_get_clean();

if ($File_Mode) {
    require_once $INCLUDE_FOLDER.'index_fs.inc.php';
} else {
    nc_evaluate_template($template_header, $template_footer, false);
}

$nc_core->output_page($template_header, $nc_result_msg, $template_footer);
