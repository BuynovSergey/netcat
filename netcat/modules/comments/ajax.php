<?php
/* $Id: add.php 4308 2011-03-02 14:32:11Z gaika $ */

$NETCAT_FOLDER = join( strstr(__FILE__, "/") ? "/" : "\\", array_slice( preg_split("/[\/\\\]+/", __FILE__), 0, -4 ) ).( strstr(__FILE__, "/") ? "/" : "\\" );
require_once ($NETCAT_FOLDER."vars.inc.php");

// for IE
if ( !isset($NC_CHARSET) ) $NC_CHARSET = "windows-1251";

// header with correct charset
header("Content-type: text/plain; charset=".$NC_CHARSET);
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);

// esoteric method...
ob_start("ob_gzhandler");

// include system
require ($INCLUDE_FOLDER."index.php");
global $db, $AUTH_USER_ID;

$nc_core = nc_Core::get_object();
global $nc_core;

// componet id must be different as $cc for example $needcc
$message_cc  = $nc_core->input->fetch_get_post('message_cc');
$message_id  = $nc_core->input->fetch_get_post('message_id');
$template_id = $nc_core->input->fetch_get_post('template_id');
$comment_id  = $nc_core->input->fetch_get_post('comment_id');
$curPos      = $nc_core->input->fetch_get_post('curPos');
$show_all    = $nc_core->input->fetch_get_post('show_all');
$rating      = $nc_core->input->fetch_get_post('rating');
$settings    = $nc_core->get_settings('', 'comments');

$user_id = $AUTH_USER_ID ? $AUTH_USER_ID : 0;


// подключаем все Settings темплейтов, чтобы шаблоны навигации и пагинации были видны в s_list
if ($template_env['File_Mode']) {
    $template_view = new nc_tpl_template_view($nc_core->TEMPLATE_FOLDER, $nc_core->db);
    $template_view->load_template($template, $template_env['File_Path']);
    $array_settings_path = $template_view->get_all_settings_path_in_array();
    foreach ($array_settings_path as $path) {
        include $path;
    }
}
// initialize nc_comments
$nc_comments = new nc_comments($message_cc);

// get template data
$templateData = $nc_comments->_getTemplate($template_id);

$qty = $settings['Qty'];

if ($rating) {
    $res = $nc_comments->rating($comment_id, $rating, $message_id);
}
elseif (!$show_all) {
	$main_conteiner = $nc_comments->wall($message_id, $template_id, $curPos-$qty, $qty, 0, 1);
	$listing = $nc_comments->listing($message_id);
        $main_conteiner = str_replace(array('$', '"'), array('\$', '\"'), $main_conteiner);
	eval(nc_check_eval("\$main_conteiner = \"".$main_conteiner."\";"));
	eval(nc_check_eval("\$listing = \"".$listing."\";"));

	$res = "{main_conteiner:escape(\"".$nc_comments->commentValidateShow($main_conteiner, $template_id, 0, 1)."\"), listing:escape(\"".$nc_comments->commentValidateShow($listing, $template_id, 0, 1)."\")}";
}
else $res = $nc_comments->wall($message_id, $template_id, 0, 0, 0, 1, 1);

// return json result from ajax
echo $res;


?>