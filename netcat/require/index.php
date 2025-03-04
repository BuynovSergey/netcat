<?php

$NETCAT_FOLDER = realpath(__DIR__ . '/../..') . DIRECTORY_SEPARATOR;
require_once $NETCAT_FOLDER . 'vars.inc.php';
require_once $ROOT_FOLDER . 'connect_io.php';

$catalogue = isset($catalogue) ? (int)$catalogue : 0;
$sub = isset($sub) ? (int)$sub : 0;
$cc = isset($cc) ? (int)$cc : 0;
$classID = isset($classID) ? (int)$classID : 0;
$template = isset($template) ? (int)$template : 0;
$classPreview = isset($classPreview) ? (int)$classPreview : 0;
$templatePreview = isset($templatePreview) ? (int)$templatePreview : 0;
$is_there_any_files = 0;
$warnText = "";
$nc_core = nc_Core::get_object();
$nc_core->url->parse_url();
$nc_core->page->set_url($nc_core->url);
$admin_mode = isset($admin_mode) ? $admin_mode : $nc_core->admin_mode;
$action = $nc_core->action;

if (is_null($action)) {
    $action = $nc_core->input->fetch_get_post('action');
}

if (empty($message)) {
    $message = 0;
} elseif (is_array($message)) {
    $message = array_map('intval', $message);
} else {
    $message = (int)$message;
}

if ($templatePreview > 0) {
    require_once($INCLUDE_FOLDER."preview/templatePreview.php");
}

if ($classPreview > 0) {
    $file_action = array(
            'index' => 'classPreview',
            'add' => 'addPreview',
            'change' => 'messagePreview',
            'search' => 'searchPreview');
    require_once($INCLUDE_FOLDER."preview/".$file_action[$action].".php");
    unset($file_action);
}


if (strstr($nc_core->url->get_parsed_url('path'), nc_module_path())) {
    $posting = true;
}

$inside_admin = $nc_core->inside_admin = (empty($passed_thru_404) && $nc_core->input->fetch_get_post('inside_admin') );


if ($nc_core->inside_admin) {
    if ($cc && !($sub || $catalogue)) {
        try {
            $cc_data = $nc_core->sub_class->get_by_id($cc);
        } catch (Exception $e) {
            //будет сообщение при проверке в $nc_core->load_env($catalogue, $sub, $cc);
        }
        $catalogue = $cc_data["Catalogue_ID"];
        $sub = $cc_data["Subdivision_ID"];
        unset($cc_data);
    }

    if (!$cc && $classID && $message) {
        list($catalogue, $sub, $cc) = $db->get_row("SELECT s.`Catalogue_ID`, m.`Subdivision_ID`, m.`Sub_Class_ID`
      FROM `Message".intval($classID)."` as m, `Subdivision` as s
      WHERE m.`Message_ID` = '".intval($message)."'
      AND m.`Subdivision_ID` = s.`Subdivision_ID`", ARRAY_N);
    }
}


// set admin mode
switch (true) {
    case ($classPreview || $templatePreview):
        $nc_core->admin_mode = false;
        break;
    case $nc_core->inside_admin:
        $nc_core->admin_mode = true;
        break;
    case empty($passed_thru_404) && isset($posting): // add (edit) action
        $nc_core->admin_mode = (bool)(isset($admin_mode) ? $admin_mode : $nc_core->input->fetch_post_get('admin_mode'));
        break;
    case empty($passed_thru_404): //front-office
        $nc_core->admin_mode = true;
        break;
    case !empty($passed_thru_404):
        $nc_core->admin_mode = false;
        break;
    default:
        $nc_core->admin_mode = false;
}

// old value
$admin_mode = $nc_core->admin_mode;


if (isset($_GET['developer_mode'])) {
    $_SESSION['developer_mode'] = +$_GET['developer_mode'];
    $nc_core->developer_mode = $_SESSION['developer_mode'];
}

try {
    $nc_core->load_env($catalogue, $sub, $cc);
}
catch (Exception $e) {
    // нет сайтов в системе?
    trigger_error($e->getMessage(), E_USER_WARNING);
    if (empty($inside_admin)) {
        header("Location: " . $nc_core->ADMIN_PATH);
    } else {
        echo "<script>top.window.location.hash = '#index';</script>";
    }
    die;
}

$MODULE_VARS = $nc_core->modules->load_env();

$admin_url_prefix = $nc_core->SUB_FOLDER.$nc_core->HTTP_ROOT_PATH;
$current_catalogue = $nc_core->catalogue->get_current();
$catalogue = $nc_core->catalogue->get_current("Catalogue_ID");
$current_sub = $nc_core->subdivision->get_current();
$sub = $nc_core->subdivision->get_current("Subdivision_ID");
$current_cc = $nc_core->sub_class->get_current();
$cc_settings = nc_array_value($current_cc, 'Sub_Class_Settings');
$cc = $nc_core->sub_class->get_current("Sub_Class_ID");
$parent_sub_tree = $nc_core->subdivision->get_parent_tree($sub);
$sub_level_count = $nc_core->subdivision->get_level_count($sub);
// get system settings for compatibillity
$system_env = $nc_core->get_settings();
$subHost = $nc_core->catalogue->get_url_by_id($catalogue);
$subLink = nc_folder_path($sub);

$operation_array = "";
// simple cc variables
if (!empty($cc)) {
    $operation_array = "current_cc";
    $moderationID = nc_array_value($current_cc, "Moderation_ID");
    $classID = nc_array_value($current_cc, "Class_ID");
    $DeleteTemplate = nc_array_value($current_cc, "DeleteTemplate");
    $DeleteCond = nc_array_value($current_cc, "DeleteCond");
}

// user mode variables
if (!empty($user_table_mode)) {
    $systemTableID = nc_array_value($current_cc, "System_Table_ID");
}
// general user & cc variables
if (!empty($cc) || !empty($user_table_mode)) {
    $allowTags = nc_array_value($current_cc, "AllowTags");
    $titleTemplate = nc_array_value($current_cc, "TitleTemplate");
    $addTemplate = nc_array_value($current_cc, "AddTemplate");
    $addCond = nc_array_value($current_cc, "AddCond");
    $addActionTemplate = nc_array_value($current_cc, "AddActionTemplate");
    $editTemplate = nc_array_value($current_cc, "EditTemplate");
    $editCond = nc_array_value($current_cc, "EditCond");
    $editActionTemplate = nc_array_value($current_cc, "EditActionTemplate");
    $CheckActionTemplate = nc_array_value($current_cc, "CheckActionTemplate");
    $DeleteActionTemplate = nc_array_value($current_cc, "DeleteActionTemplate");
    $searchTemplate = nc_array_value($current_cc, "SearchTemplate");
    $subscribeCond = nc_array_value($current_cc, "SubscribeCond");
    $subscribeTemplate = nc_array_value($current_cc, "SubscribeTemplate");
}

$nc_core->user->attempt_to_authorize();

if (!$AUTH_USER_ID && $nc_core->modules->get_by_keyword('auth') && ($auth_hash = $nc_core->input->fetch_get_post('auth_hash'))) {
    $nc_auth->hash->authorize_by_hash($auth_hash);
}

if ($nc_core->admin_mode && !Authorize()) {
    Refuse();
}

$check_auth = s_auth($current_cc, isset($action) ? $action : '', isset($posting) ? $posting : 0);

$AUTH_USER_ID += 0;
$AUTH_USER_GROUP += 0;

/**
 * @var Permission $perm
 * @see nc_User::attempt_to_authorize()
 * @see nc_User::init_user()
 */
if ($GLOBALS['perm'] instanceof Permission) {
    $perm = $GLOBALS['perm'];
}
else {
    $perm = false; // :)
}

// site online?
if (!$current_catalogue["Checked"]
    && !(
        $perm instanceof Permission
        && (
            $perm->isInstanceModeratorAdmin('site')
            || $perm->isInstanceModeratorAdmin('sub')
            || $perm->isInstanceModeratorAdmin('cc')
        )
    )
) {
    echo $current_catalogue["ncOfflineText"];
    exit;
}

if ($AUTH_USER_ID) {

    if (empty($current_user)) {
        $current_user = $db->get_row("SELECT `Language` FROM `User` WHERE `User_ID` = '".$AUTH_USER_ID."'", ARRAY_A);
    }

    $AUTH_LANG = $current_user['Language'];
}

try {
    require_once ($nc_core->ADMIN_FOLDER."lang/".$nc_core->lang->detect_lang().".php");
} catch (Exception $e) {
    die($e->getMessage());
}

if ($nc_core->modules->get_vars('netshop') && class_exists('NetShop')) {
    $GLOBALS['shop'] = new NetShop();
}
$template_env = $nc_core->template->get_by_id($template);
// set template from current subdivision
if (!$template || !$template_env["Template_ID"]) {
    $template = $nc_core->subdivision->get_current("Template_ID");
}

// set template as default
if ($template) {
    $nc_core->template->set_current_by_id($template);
}

// init log functional in stats module
if (nc_module_check_by_keyword('stats')) {
    Stats_Log();
}

if ($nc_core->inside_admin) {
    if ($nc_core->get_settings('EditDesignTemplateID') && !$nc_core->subdivision->get_current('UseEditDesignTemplate')) {
        $template = $nc_core->get_settings('EditDesignTemplateID');
        // set template as current
        $nc_core->template->set_current_by_id($template);
    }
    require_once ($nc_core->ADMIN_FOLDER."admin.inc.php");
}

$template_settings = array();

// get current template env
$template_env = $nc_core->template->get_current();

$File_Mode = $template_env['File_Mode'];

$nc_parent_template_folder_path = null;

if ($File_Mode) {
    $nc_parent_template_folder_path = nc_standardize_path_to_folder(
        nc_get_http_folder($nc_core->TEMPLATE_FOLDER) .
        nc_get_path_to_main_parent_folder($template_env['File_Path'])
    );
}

if ($nc_core->subdivision->get_current("Subdivision_ID") == $nc_core->catalogue->get_current("Title_Sub_ID")) {
    $f_title = $nc_core->subdivision->get_current("ncH1") ?: $nc_core->catalogue->get_current("Catalogue_Name");
} else {
    $f_title = $nc_core->subdivision->get_current("ncH1") ?: $nc_core->subdivision->get_current("Subdivision_Name");
}

if ($nc_core->page->get_routing_result('resource_type') === 'infoblock' && $nc_core->sub_class->get_current("Sub_Class_ID") != $cc_array[0] && $nc_core->sub_class->get_current("Checked")) {
    $f_title = $nc_core->sub_class->get_current("Sub_Class_Name");
}

$nc_core->page->set_h1($f_title);
$nc_core->page->set_current_metatags($current_sub);

if (!$File_Mode) {

    $template_env = $nc_core->template->convert_subvariables($template_env);
    $template_header = "";
    $template_footer = "";

    if (empty($isNaked) && $nc_core->get_page_type() !== 'rss' && $nc_core->get_page_type() !== 'xml') {

        if ($nc_core->subdivision->get_current('Template_ID') == $template) {
            $template_settings = $nc_core->subdivision->get_template_settings($sub);
        } else if (isset($nc_use_site_template_settings) && $nc_use_site_template_settings) {
            // используется в скриптах modules/auth
            $template_settings = $nc_core->catalogue->get_template_settings($catalogue);
        }
        else {
            $template_settings = $nc_core->template->get_settings_default_values($template);
        }

        $template_header = $template_env['Header'];
        $template_footer = $template_env['Footer'];

        eval(nc_check_eval($nc_core->template->get_current("Settings")));

        // При выводе страницы 404 через соответствующую функцию нужно, чтобы переменные попали в глобальную область видимости
        if ($sub == $e404_sub) {
            foreach (get_defined_vars() as $k => $v) {
                $GLOBALS[$k] = &$$k;
            }
        }

        if ($nc_core->subdivision->get_current("UseMultiSubClass") == 2 && strpos($template_header, 's_browse_cc') === false) {
            $template_header .= s_browse_cc(array(
                    'prefix' => "<div id='s_browse_cc'>",
                    'suffix' => "</div><br />",
                    'active' => "<span>%NAME</span>",
                    'active_link' => "<span>%NAME</span>",
                    'unactive' => "<span> <a href=%URL>%NAME</a></span>",
                    'divider' => " &nbsp; "
            ));
        }

        // metatag noindex
        if ($nc_core->subdivision->get_current("DisallowIndexing") == 1) {
            $template_header = nc_insert_in_head($template_header, "<meta name='robots' content='noindex' />");
        }


        if ($nc_core->get_variable("admin_mode") || $nc_core->get_variable("inside_admin")) {
            // reverse direction!
            $template_header = nc_insert_in_head(
                $template_header,
                "<script type='text/javascript' src='" . nc_add_revision_to_url($nc_core->ADMIN_PATH . 'js/package.js') . "'></script>\r\n"
            );
            $template_header = nc_insert_in_head($template_header, nc_js(true), true);
        }

        if ($nc_core->inside_admin) {
            // output interface settings (add 'em to the footer before </body>)
            $UI_CONFIG = new ui_config_objects($cc);

            $js_code = '".$UI_CONFIG->to_json()."';

            if ($template_footer && nc_preg_match("/(<\/body>)/i", $template_footer, $regs)) {
                $template_footer = str_replace($regs[1], $js_code.$regs[1], $template_footer);
            } else {
                $template_footer .= $js_code;
            }
        }
    }
    elseif($admin_modal) {
        eval(nc_check_eval($nc_core->template->get_current("Settings")));
    }

// openstat
    if (defined('NC_OPENSTAT_COUNTER')) {
        if (!$admin_mode && !$inside_admin) {
            $pos = nc_strpos($template_header, NC_OPENSTAT_COUNTER);
            if ($pos !== FALSE) {
                $template_header = nc_substr($template_header, 0, $pos).nc_openstat_get_code().nc_substr($template_header, $pos + nc_strlen(NC_OPENSTAT_COUNTER));
                $template_header = str_replace(NC_OPENSTAT_COUNTER, "", $template_header);
                $template_footer = str_replace(NC_OPENSTAT_COUNTER, "", $template_footer);
            } else {
                $pos = nc_strpos($template_footer, NC_OPENSTAT_COUNTER);
                if ($pos !== FALSE) {
                    $template_footer = nc_substr($template_footer, 0, $pos).nc_openstat_get_code().nc_substr($template_footer, $pos + nc_strlen(NC_OPENSTAT_COUNTER));
                    $template_footer = str_replace(NC_OPENSTAT_COUNTER, "", $template_footer);
                }
            }
        }
    }

    $template_header = nc_insert_demo_mode_message($template_header);

    if (!$check_auth && !defined('NC_AUTH_IN_PROGRESS')) {
        $nc_result_msg = "";

        if ($AUTH_USER_ID || (!$AUTH_USER_ID && !$nc_core->modules->get_vars('auth') )) {
            if ($nc_core->inside_admin) {
                $nc_result_msg = nc_print_status(NETCAT_MODERATION_ERROR_NORIGHTS, 'error', null, true);
            } else {
                $nc_result_msg = NETCAT_MODERATION_ERROR_NORIGHTS;
            }
        } elseif (!$AUTH_USER_ID && $nc_core->modules->get_vars('auth')) {
            $nc_result_msg = $nc_auth->login_form(true);
        }

        $nc_result_msg .= ob_get_clean();

        nc_evaluate_template($template_header, $template_footer, false);

        $nc_core->output_page($template_header, $nc_result_msg, $template_footer);
        exit;
    }

    if (!$message && $action === "full") {
        exit;
    }

    if ($AUTH_USER_ID) {
        $current_user = $nc_core->user->get_by_id($AUTH_USER_ID);
    }
}