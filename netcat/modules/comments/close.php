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
require ($INCLUDE_FOLDER."index.php");

$cc_id = intval($_GET['message_cc']);
$message_id = intval($_GET['message_id']);
$user_id = $AUTH_USER_ID;


if ( !$cc_id || !$message_id || !$user_id ) { ;
    die("{'error':'incorrect param'}");
}

$nc_comments = new nc_comments($cc_id);

if (!$nc_comments->isModerator()) {
  die("{'error':'insufficient rights'}");
}

// закрыть
if ($_GET['close']) {
  $nc_comments->close($cc_id, $message_id);
  header("Location: ".$_SERVER['HTTP_REFERER']);
  exit;
}
//открыть
elseif($_GET['open']) {
  $nc_comments->open($cc_id, $message_id);
  header("Location: ".$_SERVER['HTTP_REFERER']);
  exit;
}

