<?php
/*$Id$*/

$NETCAT_FOLDER = join( strstr(__FILE__, "/") ? "/" : "\\", array_slice( preg_split("/[\/\\\]+/", __FILE__), 0, -4 ) ).( strstr(__FILE__, "/") ? "/" : "\\" );
require_once ($NETCAT_FOLDER."vars.inc.php");

// for IE
if ( !isset($NC_CHARSET) ) $NC_CHARSET = "windows-1251";

// header with correct charset
//header("Content-type: text/plain; charset=".$NC_CHARSET);
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);

// esoteric method...
//ob_start("ob_gzhandler");

// disable auth screen
define("NC_AUTH_IN_PROGRESS", 1);
define("NC_ADDED_BY_AJAX", 1);

// include system
require $INCLUDE_FOLDER . 'index.php';
require_once nc_module_folder('comments') . 'nc_commsubs.class.php';

$cc_id = intval($_POST['message_cc']);
$message_id = intval($_POST['message_id']);
$user_id = $AUTH_USER_ID;


if ( !$cc_id || !$message_id || !$user_id ) { ;
    die("{'error':'incorrect param'}");
}

$nc_comments = new nc_comments($cc_id);
$nc_commsubs = new nc_commsubs();

if ( !$nc_comments->isRightsToSubscribe(0, $message_id) ) {
  die("{'error':'insufficient rights'}");
}

// отписка
if ( $_POST['unsubscribe'] ) {
  $nc_commsubs->unsubscribe ( $user_id, $cc_id, $message_id, 0 );
  echo "{'unsubscribe':'1'}";
}
//подписка
else {
  // уже подписан ?
  if ( $nc_commsubs->is_subscribe ( $user_id, $cc_id, $message_id, 0 ) ) {
    die("{'error':'already subscribe'}");
  }

  $nc_commsubs->subscribe ( $user_id, $cc_id, $message_id, 0 );
  echo "{'subscribe':'1'}";

}
