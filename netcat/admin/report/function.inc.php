<?php
/* $Id */
if (!class_exists("nc_System")) die("Unable to load file.");

/**
 * Вывод формы для выбора типов показываемых классов
 *
 * @param int type: all, used, unused
 */
function show_form_for_select($type = 0) {
    global $UI_CONFIG;

    $UI_CONFIG->actionButtons[] = array("id" => "submit",
            "align" => "left",
            "caption" => REPORTS_STAT_CLASS_DOGET,
            "action" => "mainView.submitIframeForm('sel')");
    $UI_CONFIG->actionButtons[] = array(
        "id" => "submit",
        "caption" => NETCAT_MODERATION_COMMON_KILLALL,
        "action" => "mainView.submitIframeForm('del')",
        "red_border" => true,
    );
?>
    <form method='POST' action='index.php' name='sel' id='sel'>
          <?=REPORTS_STAT_CLASS_SHOW
?> <select name='type' onchange='javascript: top.mainView.submitIframeForm("sel")'>
        <option value='0' <?=($type == 0 ? ' selected' : '')
?>><?=REPORTS_STAT_CLASS_ALL
?></option>
        <option value='1' <?=($type == 1 ? ' selected' : '')
?>><?=REPORTS_STAT_CLASS_USE ?></option>
            <option value='2' <?=($type == 2 ? ' selected' : '') ?>><?=REPORTS_STAT_CLASS_NOTUSE ?></option>
    </select>
    <input type='hidden' name='phase' value='2'>
</form>
<?
return;
}

/**
 * Show confirm deleting object
 *
 * @param array post
 */
function confim_delete_sub_class_object($array) {
global $UI_CONFIG, $db, $nc_core;
$in = array();

foreach ($array as $key => $val) {
    if (nc_substr($key, 0, 6) === 'Delete') {
        $in[] = intval($val);
    }
}
$in_array = join(',', $in);
$sub_class = $db->get_col("SELECT `Sub_Class_Name` FROM `Sub_Class` WHERE Sub_Class_ID IN(".$in_array.")");
if ($db->num_rows) {
    print "<form action='index.php' method='post'>\n
             <input type='hidden' name='phase' value='4'>\n
             <input type='hidden' name='sub_class' value='".$in_array."'>\n
             ".$nc_core->token->get_input()."
           </form>\n";

    nc_print_status(REPORTS_STAT_CLASS_CONFIRM, 'ok');
    print "<ui>";
    foreach ($sub_class as $v)
        print "<li>".$v;

    $UI_CONFIG->actionButtons[] = array("id" => "submit",
            "caption" => REPORTS_STAT_CLASS_CONFIRM_OK,
            "action" => "mainView.submitIframeForm()");
} else {
    nc_print_status(REPORTS_STAT_CLASS_NOT_CC, 'error');
}
}

function nc_report_status() {
    global $perm, $LAST_LOCAL_PATCH, $LinkID;

    $nc_core = nc_Core::get_object();

    $mysqlversion_ok = false;
    if (preg_match("/^(\d+)\.(\d)/is", mysqli_get_server_info($LinkID), $matches)) {
        if (intval($matches[1].$matches[2]) >= 41) $mysqlversion_ok = true;
    }

    $phpversion_ok = false;
    if (preg_match("/^(\d+)\.(\d)/is", phpversion(), $matches)) {
        if (intval($matches[1].$matches[2]) >= 53) $phpversion_ok = true;
    }

    $allow_url_fopen = ini_get('allow_url_fopen');
    $short_open_tag = ini_get('short_open_tag');
    $safe_mode = ini_get('safe_mode');
    $register_globals = ini_get('register_globals');
    $magic_quotes_gpc = ini_get('magic_quotes_gpc');
    $mbstring_func_overload = ini_get('mbstring.func_overload');
    $zend_ze1_compatibility_mode = ini_get('zend.ze1_compatibility_mode');

    $memory_limit = ini_get('memory_limit');
    $post_max_size = ini_get('post_max_size');
    $upload_max_filesize = ini_get('upload_max_filesize');
    $max_file_uploads = ini_get('max_file_uploads');
    // search module
    $add_memory_limit = ( $nc_core->modules->get_by_keyword('search') ? 64 : 0 );

    $upload_tmp_dir = '';
    /*if ( function_exists('sys_get_temp_dir') ) {
        $upload_tmp_dir = sys_get_temp_dir();
    }*/

    $extensions_req = array(
        'session' => 'Session',
        'mbstring' => 'mbstring',
        'iconv' => 'iconv',
        'tokenizer' => 'Tokenizer',
        'ctype' => 'Ctype',
        'dom' => 'DOM',
        'json' => 'JSON',
        'libxml' => 'libxml',
        'simplexml' => 'SimpleXML',
        'openssl' => 'OpenSSL',
    );

    $extensions_opt = array(
        'curl' => 'cURL',
        'gmp' => 'GMP'
    );

    $gd = extension_loaded('gd');
    $gd_version = $gd ? gd_info() : 0;

    $gd_ok = false;
    preg_match('/\d/', $gd_version['GD Version'], $match);
    if ( isset($match[0]) && $match[0] >= 2 ) {
        $gd_ok = true;
    }

    $pr = $nc_core->DOCUMENT_ROOT . $nc_core->SUB_FOLDER;

    $folder = array(
        $nc_core->HTTP_FILES_PATH => $pr.$nc_core->HTTP_FILES_PATH,
        $nc_core->HTTP_DUMP_PATH => $pr.$nc_core->HTTP_DUMP_PATH,
        $nc_core->HTTP_CACHE_PATH => $pr.$nc_core->HTTP_CACHE_PATH,
        $nc_core->HTTP_TRASH_PATH => $pr.$nc_core->HTTP_TRASH_PATH,
        $nc_core->HTTP_TEMPLATE_PATH => $pr.$nc_core->HTTP_TEMPLATE_PATH,
        $nc_core->HTTP_ROOT_PATH.'tmp/' => $pr.$nc_core->HTTP_ROOT_PATH.'tmp/'
    );

    if ($upload_tmp_dir) $folder[$upload_tmp_dir] = $upload_tmp_dir;

    $ok = '<span style="color: #0A0">Ok</span>';
    $error = '<span style="color: #A00">Error</span>';
    $low = '<span style="color: #F90">Low</span>';

    list($system_name, $system_color) = nc_system_name_by_id( $nc_core->get_settings('SystemID') );

    $system_name_formatted = '<span style="color: ' . $system_color . '">' . $system_name . '</span>';

    $html = "<br style='clear:both;'/><br />
      <div class='reportstatus'>
        <div class='title'>" . CMS_SYSTEM_NAME . "</div>
        <div class='name'>" . SECTION_INDEX_ADMIN_PATCHES_INFO_VERSION . "</div>
        <div class='value'>" . $nc_core->get_settings('VersionNumber') . "</div>
        <div class='req'>&mdash;</div>
        <div style='clear:both;'></div>";

    if (CMS_SYSTEM_NAME === 'Netcat') {
        $html .= "<div class='name'>" . SECTION_INDEX_ADMIN_PATCHES_INFO_REDACTION . "</div>" . PHP_EOL;
        $html .= "<div class='value'>" . $system_name_formatted . "</div>" . PHP_EOL;
        $html .= "<div class='req'>&mdash;</div>" . PHP_EOL;
        $html .= "<div style='clear:both;'></div>" . PHP_EOL;
    }

    $html .="
        <div class='name'>" . SECTION_INDEX_ADMIN_PATCHES_INFO_LAST_PATCH . "</div>
        <div class='value'>" . ($LAST_LOCAL_PATCH ?: "&mdash;") . "</div>
        <div class='req'>&mdash;</div>
        <div style='clear:both;'></div>" . ( $perm instanceof Permission && ( $perm->isDirector() || $perm->isSupervisor() ) ? "
        <br />
        <div class='name'>" . TOOLS_ACTIVATION_OWNER . "</div>
        <div class='value'>" . ( $nc_core->get_settings('Owner') ?: "&mdash;" ) . "</div>
        <div class='req'>&mdash;</div>
        <div style='clear:both;'></div>     
        <div class='name'>" . TOOLS_ACTIVATION_LICENSE . "</div>
        <div class='value'>" . ( $nc_core->get_settings('ProductNumber') ?: "&mdash;" ) . "</div>
        <div class='req'>&mdash;</div>
        <div style='clear:both;'></div>
        <div class='name'>" . TOOLS_ACTIVATION_CODE . "</div>
        <div class='value'>" . ( $nc_core->get_settings('Code') ?: "&mdash;" ) . "</div>
        <div class='req'>&mdash;</div>
        <div style='clear:both;'></div>" : "") . "
      </div>
      <div class='reportstatus'>
        <div class='title'>MySQL Server</div>
        <div class='name'>mysqli_get_server_info</div>
        <div class='value'>" . mysqli_get_server_info($LinkID) . "</div>
        <div class='req'>" . (!$mysqlversion_ok ? $error : $ok) . "</div>
        <div style='clear:both;'></div>
    </div>
      <div class='reportstatus'>
        <form method='post' target='_blank' id='show_phpinfo'>
            <input type='hidden' name='phase' value='2'>
            " . $nc_core->token->get_input() . "
            <div class='title'>PHP (
                <a href='javascript:\$nc(\"#show_phpinfo\").submit()'>phpinfo</a>
            )
            </div>
        </form> 
        <div class='name'>phpversion</div>
        <div class='value'>" . phpversion() . "</div>
        <div class='req'>" . (!$phpversion_ok ? $error : $ok) . "</div>
        <div style='clear:both;'></div>
        <br />
        <div class='name'>allow_url_fopen</div>
        <div class='value'>" . ($allow_url_fopen ? NETCAT_MODERATION_ISON : NETCAT_MODERATION_ISOFF) . "</div>
        <div class='req'>" . (!$allow_url_fopen ? $error : $ok) . "</div>
        <div style='clear:both;'></div>
        <div class='name'>short_open_tag</div>
        <div class='value'>".($short_open_tag ? NETCAT_MODERATION_ISON : NETCAT_MODERATION_ISOFF) . "</div>
        <div class='req'>" . (!$short_open_tag ? $error : $ok) . "</div>
        <div style='clear:both;'></div>
        <div class='name'>safe_mode</div>
        <div class='value'>" . ($safe_mode ? NETCAT_MODERATION_ISON : NETCAT_MODERATION_ISOFF) . "</div>
        <div class='req'>" . ($safe_mode ? $error : $ok) . "</div>
        <div style='clear:both;'></div>
        <div class='name'>register_globals</div>
        <div class='value'>" . ($register_globals ? NETCAT_MODERATION_ISON : NETCAT_MODERATION_ISOFF) . "</div>
        <div class='req'>" . ($register_globals ? $error : $ok) . "</div>
        <div style='clear:both;'></div>
        <div class='name'>magic_quotes_gpc</div>
        <div class='value'>".($magic_quotes_gpc ? NETCAT_MODERATION_ISON : NETCAT_MODERATION_ISOFF) . "</div>
        <div class='req'>" . ($magic_quotes_gpc ? $error : $ok) . "</div>
        <div style='clear:both;'></div>
        <div class='name'>mbstring.func_overload</div>
        <div class='value'>".($mbstring_func_overload ? NETCAT_MODERATION_ISON : NETCAT_MODERATION_ISOFF) . "</div>
        <div class='req'>" . ($mbstring_func_overload ? $error : $ok) . "</div>
        <div style='clear:both;'></div>
        <div class='name'>zend.ze1_compatibility_mode</div>
        <div class='value'>".($zend_ze1_compatibility_mode ? NETCAT_MODERATION_ISON : NETCAT_MODERATION_ISOFF) . "</div>
        <div class='req'>" . ($zend_ze1_compatibility_mode ? $error : $ok) . "</div>
        <div style='clear:both;'></div>
        <br />
        <div class='name'>memory_limit</div>
        <div class='value'>" . $memory_limit . "</div>
        <div class='req'>" . ( (int)$memory_limit > 0 && (int)$memory_limit < (64 + $add_memory_limit) ? $error : ( (int)$memory_limit > 0 && (int)$memory_limit >= (64 + $add_memory_limit) && (int)$memory_limit < (128 + $add_memory_limit) ? $low : $ok ) ) . "</div>
        <div style='clear:both;'></div>
        <div class='name'>post_max_size</div>
        <div class='value'>" . $post_max_size . "</div>
        <div class='req'>" . ( (int)$post_max_size < 8 ? $error : ( (int)$post_max_size >= 8 && (int)$post_max_size < 16 ? $low : $ok ) ) . "</div>
        <div style='clear:both;'></div>
        <div class='name'>upload_max_filesize</div>
        <div class='value'>" . $upload_max_filesize . "</div>
        <div class='req'>" . ( (int)$upload_max_filesize < 2 ? $error : ( (int)$upload_max_filesize >= 2 && (int)$upload_max_filesize < 4 ? $low : $ok ) ) . "</div>
        <div style='clear:both;'></div>
        <div class='name'>max_file_uploads</div>
        <div class='value'>" . $max_file_uploads . "</div>
        <div class='req'>" . ( $max_file_uploads < 10 ? $error : ( $max_file_uploads >= 10 && $max_file_uploads < 20 ? $low : $ok ) ) . "</div>
        <div style='clear:both;'></div>
        <br />";

        foreach ($extensions_req as $key => $value) {
            $loaded = extension_loaded($key);
            $html.= "<div class='name'>" . $value . "</div>
                <div class='value'>" . ($loaded ? NETCAT_MODERATION_ISON : NETCAT_MODERATION_ISOFF) . "</div>
                <div class='req'>" . (!$loaded ? $error : $ok) . "</div>
                <div style='clear:both;'></div>";
        }

        $html.= "<div class='name'>GD library</div>
        <div class='value'>".($gd_version ? $gd_version['GD Version'] : "&mdash;")."</div>
        <div class='req'>" . (!$gd_ok ? $error : $ok) . "</div>
        <div style='clear:both;'></div>
        <br />";

        foreach ($extensions_opt as $key => $value) {
            $loaded = extension_loaded($key);
            $html.= "<div class='name'>" . $value . "</div>
                <div class='value'>" . ($loaded ? NETCAT_MODERATION_ISON : NETCAT_MODERATION_ISOFF) . "</div>
                <div class='req'>" . (!$loaded ? "&mdash;" : $ok) . "</div>
                <div style='clear:both;'></div>";
        }

      $html.= "</div>
      <div class='reportstatus'>
        <div class='title'>" . TOOLS_PATCH_IS_WRITABLE . "</div>";

    foreach ($folder as $k => $v) {
        $is_writable = is_writable($v);
        $html .= "
          <div class='name'>".$k."</div>
          <div class='value'>" . TOOLS_PATCH_IS_WRITABLE . "</div>
          <div class='req'>" . (!$is_writable ? $error : $ok) . "</div>
          <div style='clear:both;'></div>
        ";
    }
    $html .= "
       </div><br />";

    return $html;
}

class ui_config_general_stat extends ui_config {

function __construct($active = 'totalstat') {

    global $db;



    $this->headerText = SECTION_INDEX_REPORTS_STATS;

    $this->tabs[] = array('id' => 'totalstat',
            'caption' => REPORTS,
            'location' => "tools.totalstat");
    $this->tabs[] = array('id' => 'class_stat',
            'caption' => REPORTS_CLASS,
            'location' => "tools.totalstat(2)");


    $this->activeTab = $active;
    $this->locationHash = "#tools.totalstat(".($active == 'totalstat' ? 1 : 2).")";
}

}
