<?php

if (!class_exists('nc_core')) {
    die;
}

$catalogue = isset($catalogue) ? (int)$catalogue : 0;
$sub = isset($sub) ? (int)$sub : 0;
$cc = isset($cc) ? (int)$cc : 0;
$template = isset($template) ? (int)$template : 0;

if (empty($message)) {
    $message = 0;
} elseif (is_array($message)) {
    $message = array_map('intval', $message);
} else {
    $message = (int)$message;
}

if (empty($isNaked) || !empty($admin_modal)) {

    if (!$templatePreview) {
        $template_view = $nc_core->template->get_file_template($template);
    } else {
        $template_view = new nc_tpl_template_view($nc_core->TEMPLATE_FOLDER, $nc_core->db);
        $template_view->load_template($templatePreview, $template_env['File_Path'], $is_preview = true);
    }

    if (empty($template_settings)) {
        if ($nc_core->subdivision->get_current('Template_ID') == $template) {
            $template_settings = $nc_core->subdivision->get_template_settings($sub);
        } else if (isset($nc_use_site_template_settings) && $nc_use_site_template_settings) {
            // используется в скриптах modules/auth
            $template_settings = $nc_core->catalogue->get_template_settings($catalogue);
        }
        else {
            $template_settings = $nc_core->template->get_settings_default_values($template);
        }
    }

    if (!$templatePreview) {
        if ($action !== 'index' && $action !== 'full') {
            // для action = index и full всё уже подключено в /netcat/index.php
            $template_view->include_all_required_assets();
        }
        $array_settings_path = $template_view->get_all_settings_path_in_array();
        foreach ($array_settings_path as $path) {
            include_once $path;
        }
    }

    // При выводе страницы 404 через соответствующую функцию нужно, чтобы переменные попали в глобальную область видимости
    if (isset($e404_sub) && $sub == $e404_sub) {
        foreach (get_defined_vars() as $k => $v) {
            $GLOBALS[$k] = &$$k;
        }
    }

    $template_view->fill_fields();

    if ($templatePreview) {
        eval('?>' . $template_view->get_settings());
    }

    $template_env['Header'] = $template_view->get_header();
    $template_env['Footer'] = $template_view->get_footer();

    // %FIELD replace with inherited template field value
    $template_env = $nc_core->template->convert_subvariables($template_env);
}

$template_header = "";
$template_footer = "";

if (empty($isNaked) && $nc_core->get_page_type() !== 'rss' && $nc_core->get_page_type() !== 'xml') {
    $template_header = $template_env['Header'];
    $template_footer = $template_env['Footer'];

    if ($nc_core->subdivision->get_current("UseMultiSubClass") == 2 && !$nc_core->get_variable("inside_admin")) {
        $template_header_code = '';
        $array_headers_path = $template_view->get_all_header_path_in_array();
        foreach ($array_headers_path as $path) {
            $template_header_code .= file_get_contents($path);
        }
        if (strpos($template_header_code, '_browse_cc') === false) {
            $template_header .= s_browse_cc(array(
                    'prefix' => "<div id='browse_cc'>",
                    'suffix' => "</div><br />",
                    'active' => "<span>%NAME</span>",
                    'active_link' => "<span>%NAME</span>",
                    'unactive' => "<span> <a href=%URL>%NAME</a></span>",
                    'divider' => " &nbsp; "
            ));
        }
    }

    if (!$templatePreview) {
        eval(nc_check_eval($nc_core->template->get_current("Settings")));
    }
    // add system CSS styles in admin mode

    if ($nc_core->get_variable("admin_mode") || $nc_core->get_variable("inside_admin")) {
        // reversive direction!
        $template_header = nc_insert_in_head(
            $template_header,
            "<script type='text/javascript' src='" . nc_add_revision_to_url($nc_core->ADMIN_PATH . 'js/package.js') . "'></script>\r\n"
        );
        $template_header = nc_insert_in_head($template_header, nc_js(), true);
    }

    if (!$nc_core->catalogue->get_current('ncMobile') && $nc_core->catalogue->get_current('ncMobileSrc')) {
        $template_mobile_js = "<script type='text/javascript' src='" . nc_add_revision_to_url($nc_core->ADMIN_PATH . 'js/mobile.js') . "'></script>\r\n";
        $template_header = nc_insert_in_head($template_header, $template_mobile_js, true);
        $mobile_data = $nc_core->catalogue->get_mobile(0, true);
        //$_SESSION['no_mobile_redirect'] = 0;
        if ($_SERVER['HTTP_REFERER'] && @strpos($_SERVER['HTTP_REFERER'], $mobile_data['Domain']) !== false) {
            $_SESSION['no_mobile_redirect'] = 1;
        }

        if ($mobile_data['Catalogue_ID'] && $mobile_data['ncMobileRedirect'] && !$_SESSION['no_mobile_redirect']) {
            $_SESSION['no_mobile_redirect'] = 1;
            $device = $nc_core->return_device();
            $mobile_href = nc_get_scheme() . '://' . $nc_core->subdivision->get_alternative_link();

            if ($mobile_data['ncMobileIdentity'] == 1) {
                if ($device !== 'desktop') {
                    header('Location: ' . $mobile_href);
                    exit;
                }
            } elseif ($mobile_data['ncMobileIdentity'] == 2) {
                if ($nc_core->mobile_screen()) {
                    header('Location: ' . $mobile_href);
                    exit;
                }
            } else {
                if ($device !== 'desktop' && $nc_core->mobile_screen()) {
                    header('Location: ' . $mobile_href);
                    exit;
                }
            }
        }
    }

    // metatag noindex
    if ($nc_core->subdivision->get_current("DisallowIndexing") == 1) {
        $template_header = nc_insert_in_head($template_header, "<meta name='robots' content='noindex' />");
    }
}

// openstat
if (defined('NC_OPENSTAT_COUNTER')) {
    if (!$admin_mode && !$inside_admin) {
        $pos = nc_strpos($template_header, NC_OPENSTAT_COUNTER);
        if ($pos !== FALSE) {
            $template_header = nc_substr($template_header, 0, $pos) . nc_openstat_get_code(1) . nc_substr($template_header, $pos + nc_strlen(NC_OPENSTAT_COUNTER));
            $template_header = str_replace(NC_OPENSTAT_COUNTER, "", $template_header);
            $template_footer = str_replace(NC_OPENSTAT_COUNTER, "", $template_footer);
        } else {
            $pos = nc_strpos($template_footer, NC_OPENSTAT_COUNTER);
            if ($pos !== FALSE) {
                $template_footer = nc_substr($template_footer, 0, $pos) . nc_openstat_get_code(1) . nc_substr($template_footer, $pos + nc_strlen(NC_OPENSTAT_COUNTER));
                $template_footer = str_replace(NC_OPENSTAT_COUNTER, "", $template_footer);
            }
        }
    }
}

$template_header = nc_insert_demo_mode_message($template_header);

/** @var Permission $perm */
$nc_no_rights_error =
    !$check_auth && // нет прав на текущий/первый инфоблок в разделе
    !defined('NC_AUTH_IN_PROGRESS') && // константа «не запрашивать логин-пароль»
    !($perm && $perm->isAnySubClassAdmin()); // у пользователя нет прав на администрирование хотя бы одного инфоблока в системе

if ($nc_no_rights_error) {
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

    $nc_core->output_page($template_header, $nc_result_msg, $template_footer);
    exit;
}

if (!$message && $action === "full") {
    exit;
}

if ($AUTH_USER_ID) {
    $current_user = $nc_core->user->get_by_id($AUTH_USER_ID);
}
