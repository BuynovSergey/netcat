<?php

/**
 *    Filemanager PHP connector configuration
 *
 *    filemanager.config.php
 *    config for the filemanager.php connector
 *
 * @license    MIT License
 * @author        Riaan Los <mail (at) riaanlos (dot) nl>
 * @author        Simon Georget <simon (at) linea21 (dot) com>
 * @copyright    Authors
 */

date_default_timezone_set('Europe/Moscow');

$NETCAT_FOLDER = join(strstr(__FILE__, "/") ? "/" : "\\", array_slice(preg_split("/[\/\\\]+/", __FILE__), 0, -7)) . (strstr(__FILE__, "/") ? "/" : "\\");

include_once($NETCAT_FOLDER . "vars.inc.php");
require($ROOT_FOLDER . "connect_io.php");
$nc_core->modules->load_env();

$lang = $nc_core->lang->detect_lang();
if ($lang == 'Russian') {
    $lang = $nc_core->NC_UNICODE ? $lang . "_utf8" : $lang . "_cp1251";
}
require($ADMIN_FOLDER . "lang/" . $lang . ".php");

//require_once ($NETCAT_FOLDER."index.php");

/**
 *    Check if user is authorized
 *
 * @return boolean true is access granted, false if no access
 */
function auth() {

    global $perm, $AUTH_USER_ID;

    if (!isset($AUTHORIZE_BY)) $AUTHORIZE_BY = 'User_ID';
    $user = Authorize();

    if (!$user) {
        echo 'Not enough rights or Hack attempt!';
        exit;
    }
    if (!$perm instanceof Permission || !$perm->accessToCKEditor()) {
        echo 'Not enough rights or Hack attempt!';
        exit;
    }

    return $AUTH_USER_ID;
}

function nc_ckeditor_check_file_name($file_name) {
    $file_extension = strtolower(pathinfo(trim($file_name), PATHINFO_EXTENSION));
    // см. filemanager.config.js — uploadRestrictions
    $allowed_extensions = array(
        'jpg', 'jpeg', 'gif', 'png', 'svg', 'webp',
        'txt', 'pdf', 'odp', 'ods', 'odt', 'rtf', 'doc', 'docx',
        'xls', 'xlsx', 'ppt', 'pptx',
        'ogv', 'mp4', 'webm', 'ogg', 'mp3', 'wav'
    );
    if (in_array($file_extension, $allowed_extensions, true)) {
        return true;
    }
    return false;
}


/**
 *    Language settings
 */
$config['culture'] = 'ru';

/**
 *    PHP date format
 *    see http://www.php.net/date for explanation
 */
$config['date'] = 'd M Y H:i';

/**
 *    Icons settings
 */
$config['icons']['path'] = 'images/fileicons/';
$config['icons']['directory'] = '_Open.png';
$config['icons']['default'] = 'default.png';

/**
 *    Upload settings
 */
$config['upload']['overwrite'] = false; // true or false; Check if filename exists. If false, index will be added
$config['upload']['size'] = false; // integer or false; maximum file size in Mb; please note that every server has got a maximum file upload size as well.
$config['upload']['imagesonly'] = false; // true or false; Only allow images (jpg, gif & png) upload?

/**
 *    Images array
 *    used to display image thumbnails
 */
$config['images'] = array('jpg', 'jpeg', 'gif', 'png');


/**
 *    Files and folders
 *    excluded from filtree
 */
$config['unallowed_files'] = array('.htaccess');
$config['unallowed_dirs'] = array('_thumbs', '.CDN_ACCESS_LOGS', 'cloudservers');

/**
 *    FEATURED OPTIONS
 *    for Vhost or outside files folder
 */
// $config['doc_root'] = '/home/user/userfiles'; // No end slash


/**
 *    Optional Plugin
 *    rsc: Rackspace Cloud Files: http://www.rackspace.com/cloud/cloud_hosting_products/files/
 */
$config['plugin'] = null;
//$config['plugin'] = 'rsc';


//	not working yet
//$config['upload']['suffix'] = '_'; // string; if overwrite is false, the suffix will be added after the filename (before .ext)

$auth = auth();

$Array = $nc_core->get_settings();
if (!is_dir($DOCUMENT_ROOT . $SUB_FOLDER . $HTTP_FILES_PATH . "userfiles/")) {
    mkdir($DOCUMENT_ROOT . $SUB_FOLDER . $HTTP_FILES_PATH . "userfiles/", 0777);
}

if (!is_dir($DOCUMENT_ROOT . $SUB_FOLDER . $HTTP_FILES_PATH . "userfiles/" . $auth)) {
    mkdir($DOCUMENT_ROOT . $SUB_FOLDER . $HTTP_FILES_PATH . "userfiles/" . $auth, 0777);
}

if (!$Array['CKEditorFileSystem'] || ($auth && $perm->isSupervisor())) {
    $config['rel_path'] = $HTTP_FILES_PATH . "userfiles";
} else {
    $config['rel_path'] = $HTTP_FILES_PATH . "userfiles/" . $auth;
}

$config['doc_root'] = $DOCUMENT_ROOT . $SUB_FOLDER . $config['rel_path'];
//	not working yet
//$config['upload']['suffix'] = '_'; // string; if overwrite is false, the suffix will be added after the filename (before .ext)
