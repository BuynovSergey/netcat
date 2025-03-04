<?php

function nc_quickbar_in_template_header($buffer, $File_Mode = false, $return_shortpage_update_array = false) {
    global $AUTH_USER_ID, $ADMIN_TEMPLATE, $HTTP_ROOT_PATH, $ADMIN_PATH, $perm;
    global $SUB_FOLDER, $REQUEST_METHOD;
    global $current_catalogue, $current_sub, $current_cc;
    global $inside_admin, $admin_mode, $user_table_mode, $action, $message, $classID, $date, $curPos, $cur_cc;
    /** @var int $message */

    $nc_core = nc_Core::get_object();

    if (($inside_admin || !nc_quickbar_permission())) {
        return $return_shortpage_update_array ? null : $buffer;
    }

    // не добавляем quickbar, если шаблон не назначен
    if ($nc_core->template->get_current() == false) {
        return "";
    }
    if (!$return_shortpage_update_array) {
        // reversive direction!
        $buffer = nc_insert_in_head($buffer, nc_js(), $nc_core->get_variable("admin_mode"));
    }

    $get_param = array();
    if ($curPos) {
        $get_param['curPos'] = $curPos;
    }
    if ($cur_cc) {
        $get_param['cur_cc'] = $cur_cc;
    }

    $routing_result = $nc_core->page->get_routing_result(); // присутствует только в режиме просмотра
    // параметр cc для ссылки на режим редактирования (может отсутствовать или не совпадать с $current_cc['Sub_Class_ID'])
    $cc = $nc_core->input->fetch_get_post('cc_only')
        ?: $nc_core->input->fetch_get_post('cc')
        // $routing_result['infoblock_id'] присутствует и для раздела (равен ID первого инфоблока в разделе) ↓
        ?: (nc_array_value($routing_result, 'resource_type') !== 'folder' ? nc_array_value($routing_result, 'infoblock_id') : 0);

    $cc = (int)$cc;
    $cc_data = array();

    if ($cc === (int)nc_array_value($current_cc, 'Sub_Class_ID', 0)) {
        $cc_data = $current_cc;
    } elseif ($cc) {
        $cc_data = $nc_core->sub_class->get_by_id($cc);
    }

    if (nc_module_check_by_keyword('routing')) {
        if ($message) {
            $routing_object_parameters =
                array(
                    'site_id' => $current_catalogue['Catalogue_ID'],
                    'folder' => $current_sub['Hidden_URL'],
                    'folder_id' => $current_sub['Subdivision_ID'],
                    'infoblock_id' => nc_array_value($cc_data, 'Sub_Class_ID'),
                    'infoblock_keyword' => nc_array_value($cc_data, 'EnglishName'),
                    'object_id' => $message,
                    'action' => 'full',
                    'format' => 'html',
                    'variables' => $get_param,
                    'date' => $date
                );
            $view_link = nc_routing::get_object_path($classID, $routing_object_parameters, 'full', 'html', false, null, true);
        } else {
            $view_link = (string)nc_routing::get_folder_path($current_sub['Subdivision_ID'], $date, $get_param);
            if (!$view_link) {
                if ($cc_data) {
                    $view_link = nc_routing::get_infoblock_path($cc_data['Sub_Class_ID'], $cc_data['DefaultAction'], 'html', $date, $get_param);
                } else {
                    $view_link = '#';
                }
            }
        }
    } else {
        $view_link = $SUB_FOLDER .
            ($current_sub['Subdivision_ID'] != $current_catalogue['Title_Sub_ID']
                ? $current_sub['Hidden_URL']
                : "/") .
            ($date ? implode('/', explode('-', $date)) . "/" : "") .
            ($message && !empty($cc_data['EnglishName'])
                ? $cc_data['EnglishName'] . "_" . $message . ".html"
                : "") . nc_array_to_url_query($get_param, '&amp;');
    }

    if (!$user_table_mode) {
        $edit_link = $SUB_FOLDER . $HTTP_ROOT_PATH .
            ($action == "change" ? "message" : $action) . ".php?catalogue=" . $current_catalogue['Catalogue_ID'] .
            ($current_sub['Subdivision_ID'] ? "&amp;sub=" . $current_sub['Subdivision_ID'] : "") .
            ($cc ? "&amp;cc=" . $cc : "") .
            ($message ? "&amp;message=" . $message : "") .
            ($date ? "&amp;date=" . $date : "") .
            ($curPos ? "&amp;curPos=" . $curPos : "") .
            ($cur_cc ? "&amp;cur_cc=" . $cur_cc : "");
    } else {
        $edit_link = $SUB_FOLDER . $HTTP_ROOT_PATH . "?catalogue=" . $current_catalogue['Catalogue_ID'] . ($current_sub['Subdivision_ID'] ? "&amp;sub=" . $current_sub['Subdivision_ID'] : "") . (!empty($cc_data['Sub_Class_ID']) ? "&amp;cc=" . $cc_data['Sub_Class_ID'] : "") . ($message ? "&amp;message=" . $message : "");
    }

    $admin_link = "";

    switch (true) {
        case nc_array_value($cc_data, 'System_Table_ID') == 3 && $message:
            $admin_link = "#user.edit(" . $message . ")";
            break;
        case nc_array_value($cc_data, 'Sub_Class_ID') && $message:
            $admin_link = "#object.view(" . nc_array_value($cc_data, 'Sub_Class_ID') . "," . $message . ")";
            break;
        case nc_array_value($cc_data, 'Sub_Class_ID'):
            $admin_link = "#object.list(" . nc_array_value($cc_data, 'Sub_Class_ID') . ")";
            break;
        case nc_array_value($current_sub, 'Subdivision_ID'):
            $admin_link = "#subclass.list(" . nc_array_value($current_sub, 'Subdivision_ID') . ")";
            break;
        case nc_array_value($current_catalogue, 'Catalogue_ID'):
            $admin_link = "#site.map(" . nc_array_value($current_catalogue, 'Catalogue_ID') . ")";
    }

    $admin_link = $ADMIN_PATH . $admin_link;
    $sub_admin_link = $ADMIN_PATH . "subdivision/index.php?phase=5&SubdivisionID={$current_sub['Subdivision_ID']}&view=all";
    $page_version_link = $SUB_FOLDER . $HTTP_ROOT_PATH . 'action.php?ctrl=admin.version&action=show_page_versions&subdivision_id=' . nc_array_value($current_sub, 'Subdivision_ID');
    $template_admin_link = $ADMIN_PATH . 'template/index.php?phase=4&TemplateID=' . $nc_core->template->get_current('Template_ID');

    if ($return_shortpage_update_array) {
        return array(
            'view_link' => $view_link,
            'edit_link' => $edit_link,
            'sub_admin_link' => $sub_admin_link,
            'template_admin_link' => $template_admin_link,
            'admin_link' => $admin_link,
        );
    }

    $ANY_SYSTEM_MESSAGE = $nc_core->db->get_var("SELECT COUNT(*) FROM `SystemMessage` WHERE `Checked` = 0");
    $lang = $nc_core->lang->detect_lang(1);

    if ($lang == 'ru') {
        $lang = $nc_core->NC_UNICODE ? "ru_utf8" : "ru_cp1251";
    }

    //--------------------------------------------------------------------------
    // Генерация панели инструментов (navbar) для front-end
    //--------------------------------------------------------------------------

    $navbar = $nc_core->ui->navbar();
    $lsDisplayType = $nc_core->get_display_type();
    $store_versions = $nc_core->get_settings('StoreVersions', 'system', false, $current_catalogue['Catalogue_ID']);

    // Просмотр
    $navbar->quickmenu->add_btn($SUB_FOLDER . $view_link, NETCAT_QUICKBAR_BUTTON_VIEWMODE)->active(!$admin_mode);

    if ($lsDisplayType != 'longpage_vertical') {
        // Редактирование
        $navbar->quickmenu->add_btn($edit_link, NETCAT_QUICKBAR_BUTTON_EDITMODE)->active($admin_mode);

        // Пункт меню "Ещё"
        $navbar->more = $navbar->menu->add_btn('#', NETCAT_QUICKBAR_BUTTON_MORE)->submenu();

        if (!empty($current_sub['Subdivision_ID']) && $perm->isAccess(NC_PERM_ITEM_SUB, NC_PERM_ACTION_EDIT, $current_sub['Subdivision_ID'], 0)) {
            $navbar->more->add_btn($sub_admin_link, NETCAT_QUICKBAR_BUTTON_SUBDIVISION_SETTINGS)->icon('settings')->click('nc.load_dialog(this.href); return false');
            if ($action === 'full') {
                $open_object_link = $SUB_FOLDER . $HTTP_ROOT_PATH . "message.php?" .
                    "catalogue=$current_catalogue[Catalogue_ID]" .
                    "&amp;sub=$current_sub[Subdivision_ID]" .
                    "&amp;cc=$cc" .
                    "&amp;message=$message";
                $component_id = $nc_core->sub_class->get_by_id($cc, 'Class_ID');
                $object_type = $nc_core->component->get_by_id($component_id, 'ObjectNameSingularGenitive') ?: NETCAT_VERSION_OF_OBJECT;

                $open_object_text = nc_ucfirst(sprintf(NETCAT_QUICKBAR_BUTTON_OBJECT_SETTINGS, $object_type));
                $navbar->more->add_btn($open_object_link, $open_object_text)->icon('edit')->click('nc.load_dialog(this.href); return false');
            }
            if ($store_versions) {
                $navbar->more->add_btn($page_version_link, NETCAT_QUICKBAR_BUTTON_SUBDIVISION_VERSIONS)->icon('dev-classificator')->click('nc.load_dialog(this.href); return false');
            }
        }
        if ($perm->isAccess(NC_PERM_TEMPLATE, 0, 0, 0)) {
            $navbar->more->add_btn($template_admin_link, NETCAT_QUICKBAR_BUTTON_TEMPLATE_SETTINGS)->icon('dev-templates')->click('nc_form(this.href); return false');
        }

        $navbar->more->add_btn($admin_link, NETCAT_QUICKBAR_BUTTON_ADMIN)->icon('mod-default');

        if (nc_module_check_by_keyword('landing')) {
            nc_landing::get_instance()->add_save_landing_page_settings_menu_item($navbar->more, $current_sub['Subdivision_ID']);
        }

    } else {
        $navbar->quickmenu->add_btn('#', NETCAT_QUICKBAR_BUTTON_EDITMODE)
                          ->disabled()
                          ->title(NETCAT_QUICKBAR_BUTTON_EDITMODE_UNAVAILABLE_FOR_LONGPAGE)
                          ->click('return false')
                          ->modificator('default-cursor');
    }

    // Design toggle
    if ($admin_mode) {
        $design_mode = $nc_core->input->fetch_cookie('nc_design_mode');
        $checked_attribute = $design_mode == "1" ? "checked" : "";
        $navbar->menu->add_text(
            "<label class='nc-design-toggle'>
                    <input type='checkbox' name='design' value='$design_mode' $checked_attribute>
                    <span class='nc-design-toggle-switch'></span>
                    <span class='nc-design-toggle-title'>" . NETCAT_QUICKBAR_BUTTON_DESIGN_MODE . "</span>
                </label>"
        )->class_name("nc-design-toggle-wrapper");
    }

    // Undo / Redo
    if ($admin_mode && $store_versions) {
        $version_button = function ($type) {
            return
                "<li class='nc-version-button nc--disabled' data-type='$type'>" .
                "<a href='#'><i class='nc-icon-l nc--version-$type'></i></a>" .
                "<div class='nc-version-title'></div>" .
                "</li>";
        };
        $navbar->toolbar->text(
            $version_button('undo') . $version_button('redo')
        );
    }

    // AJAX Loader
    $navbar->tray->add_btn('#')->compact()->icon_large('navbar-loader')->id('nc-navbar-loader')->style('display:none');

    // Иконка с сообщениями
    if ($perm->isAccess(NC_PERM_REPORT)) {
        $navbar->tray->add_btn($ADMIN_PATH . '#tools.systemmessages')->compact()
            ->title($ANY_SYSTEM_MESSAGE ? BEGINHTML_ALARMON : BEGINHTML_ALARMOFF, true)
            ->icon_large('system-message')
            ->id('trayMessagesIcon')
            ->disabled(!$ANY_SYSTEM_MESSAGE);
    }

    // Меню пользователя
    if (nc_module_check_by_keyword('auth')) {
        $logout_link = nc_module_path('auth')
            . '?logoff=1'
            . '&REQUESTED_FROM=' . urlencode($nc_core->REQUEST_URI)
            . '&REQUESTED_BY=' . $REQUEST_METHOD;
    } else {
        $logout_link = $ADMIN_PATH . 'unauth.php';
    }

    if (defined('NC_SAAS_SITE')) {
        $navbar_left_button = $nc_core->ui->btn(
            defined('NC_SAAS_SITES_LIST_URL') ? NC_SAAS_SITES_LIST_URL : '#',
            CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_AUTH_PROFILE
        )->attr('target', '_blank')->light()->text_darken()->render();
    } else {
        $navbar_left_button = $nc_core->ui->btn('#', NETCAT_ADMIN_AUTH_CHANGE_PASS)->click('nc_password_change(); return false')
                                                 ->light()->text_darken()->render();
    }

    $navbar_logout_button = $nc_core->ui->btn($logout_link, NETCAT_ADMIN_AUTH_LOGOUT)->red()->render();

    // Меню профиля пользователя
    $login = $perm->getLogin();
    $user_menu_content = NETCAT_ADMIN_AUTH_PERM . " <span class='nc-text-grey'>" .
        str_replace('"', '\"', implode(', ', Permission::get_all_permission_names_by_id($AUTH_USER_ID))) .
        "</span><hr class='nc-hr'>"
        . "<div class='nc--nowrap'>"
        . $navbar_left_button
        . $navbar_logout_button
        . '</div>';


    // Для ширины экрана > 960
    $navbar->tray->add_btn('#', $login)
                 ->click('return false')
                 ->title(BEGINHTML_USER . ': ' . $login)
                 ->htext(BEGINHTML_USER)
                 ->class_name('nc-user-profile nc--desktop')
                 ->dropdown()
                 ->div($user_menu_content)->class_name('nc-padding-10');

    // Для ширины экрана < 960
    $navbar->tray->add_btn('#')
                 ->click('return false')
                 ->icon_large('user-profile')
                 ->class_name('nc-user-profile nc--mobile nc--hide')
                 ->dropdown()
                 ->div($user_menu_content)->class_name('nc-padding-10');

    // $navbar
    $navbar_html = (string)$navbar->fixed();
    $navbar_html .= "
<script type='text/javascript'>
(function() {
    var padding = Math.round(parseInt(jQuery('body').css('padding-top')) + parseInt(jQuery('body').css('margin-top')) + jQuery('div.nc-navbar').height()),
        navbar_menu_timeout;
    
    jQuery('body').css({marginTop:padding+'px', position:'relative'});
    
    jQuery('div.nc-navbar li.nc--dropdown > a').click(function(){
        jQuery(this.parentNode).addClass('nc--clicked').closest('.nc-navbar').addClass('nc--clicked');
        return false;
    }).parent().mouseleave(function(){
        var ob = this
        navbar_menu_timeout = setTimeout(function(){
           jQuery(ob).removeClass('nc--clicked').closest('.nc-navbar').removeClass('nc--clicked');
        }, 500);
    }).mouseover(function(){
        if (jQuery(this).hasClass('nc--clicked')) {
            clearTimeout(navbar_menu_timeout);
        }
    }).find('li a').click(function(){
        jQuery('div.nc-navbar, div.nc-navbar li.nc--dropdown, div.nc-navbar li.nc--clicked').removeClass('nc--clicked');
    });
    
    jQuery('body').click(function(){
        jQuery('div.nc-navbar, div.nc-navbar > ul > li.nc--clicked').removeClass('nc--clicked');
    });
    
    jQuery('div.nc-navbar .nc-design-toggle input').change(function() {
        nc_cookies.set('nc_design_mode', this.checked ? 1 : 0); 
        jQuery('body').toggleClass('nc--mode-edit-design', this.checked);
    }).change();
    })();
</script>";


    // Содержание модального окна быстрого изменения пароля
    //TODO: Сделать загрузку содержимого окна через ajax
    $navbar_html .= "
<div id='nc_password_change' class='nc-shadow-large nc--hide nc-admin'>
    <form style='width:350px; line-height:20px' class='nc-form' method='post' action='" . $ADMIN_PATH . "user/index.php'>
        <div class='nc-padding-15'>
            <h2 class='nc-h2'>" . NETCAT_ADMIN_AUTH_CHANGE_PASS . "</h2>
            <hr class='nc-hr' style='margin:5px -15px 15px'>
            <div>
                <label>" . CONTROL_USER_NEWPASSWORD . "</label><br>
                <input class='nc--wide' type='password' name='Password1' maxlength='32' placeholder='" . CONTROL_USER_NEWPASSWORD . "' />
            </div>
            <div>
                <label>" . CONTROL_USER_NEWPASSWORDAGAIN . "</label><br>
                <input class='nc--wide' type='password' name='Password2' maxlength='32' placeholder='" . CONTROL_USER_NEWPASSWORDAGAIN . "' />
            </div>
            <input type='hidden' name='UserID' value='" . $AUTH_USER_ID . "' />
            <input type='hidden' name='phase' value='7' />
            " . $nc_core->token->get_input() . "
        </div>
    </form>
    <div class='nc-form-actions'>
        <script type='text/javascript'>function nc_password_change_submit(){\$nc('#nc_password_change form').submit();}</script>
        <button class='nc-btn nc--bordered nc--red nc--right' onclick='\$nc.modal.close()' type='button'>" . CONTROL_BUTTON_CANCEL . "</button>
        <button class='nc_admin_metro_button nc-btn nc--blue nc--right' onclick='nc_password_change_submit()'>" . NETCAT_REMIND_SAVE_SAVE . "</button>
    </div>
</div>
<!-- /#nc_password_change -->";

    $addon =
        "<!-- Netcat QuickBar -->
        <script type='text/javascript' src='" . nc_add_revision_to_url($ADMIN_PATH . "js/lang/$lang.js") .
        "' charset='" . $nc_core->NC_CHARSET . "'></script>
        <link rel='stylesheet' href='" . nc_add_revision_to_url($ADMIN_PATH . '/js/codemirror/lib/codemirror.css') . "'>
        <script type='text/javascript'>
            var nc_token = '" . $nc_core->token->get(+$AUTH_USER_ID) . "';
        </script>\n";

    $js_files = array(
        $ADMIN_PATH . 'js/classes/nc_cookies.class.js',
        $ADMIN_PATH . 'js/classes/nc_drag.class.js',
        $ADMIN_PATH . 'js/codemirror/lib/codemirror.js',
        $ADMIN_PATH . 'js/codemirror/mode/xml.js',
        $ADMIN_PATH . 'js/codemirror/mode/mysql.js',
        $ADMIN_PATH . 'js/codemirror/mode/javascript.js',
        $ADMIN_PATH . 'js/codemirror/mode/css.js',
        $ADMIN_PATH . 'js/codemirror/mode/clike.js',
        $ADMIN_PATH . 'js/codemirror/mode/php.js',
    );
    foreach (nc_minify_file($js_files, 'js') as $file) {
        $addon .= "<script src='" . $file . "'></script>\n";
    }

    $addon .=
        "<script type='text/javascript'>
                    jQuery(function () {

                        function getEditorTypeById(id) {
                            if(id == 'Query') {
                                return 'text/x-mysql';
                            }
                            return 'text/x-php';
                        }

                        if(true){

                            window.CMEditors = [];

                            function createCMEditor(ind, el) {
                                var init = true;
                                return function () {
                                    if(init) {
                                        var h = jQuery(el).height();
                                        window.CMEditors[ind] = CodeMirror.fromTextArea(el,{
                                            lineNumbers: true,
                                            mode: getEditorTypeById(jQuery(el).attr('id')),
                                            indentUnit: 4
                                        });
                                        window.CMEditors[ind].id = jQuery(el).attr('id');
                                        var scrollEl = jQuery(window.CMEditors[ind].getScrollerElement());
                                        scrollEl.height(h);
                                    }
                                    else {
                                        var h = jQuery(window.CMEditors[ind].getScrollerElement()).height();
                                        window.CMEditors[ind].toTextArea();
                                        jQuery(el).height(h);
                                    }
                                    init = !init;
                                }
                            }

                            jQuery('textarea').each(function (ind, el) {
                                return null;
                                var prev0 =  jQuery(el).prev(), prev = prev0.prev(), prevPrev = prev.prev(),
                                prev0F = prev0.filter('div.resize_block').children(), prevF = prev.filter('div.resize_block').children(), prevPrevF = prevPrev.filter('div.resize_block').children();
                                prevF.add(prev0F).add(prevPrevF).each(function (i, e) {
                                    jQuery(e).bind('click', function () {
                                        var idd = jQuery(this).attr('href').substr(1);
                                        for(var k in window.CMEditors) {
                                            if(window.CMEditors[k].id == idd) {
                                                var scrollEl = jQuery(window.CMEditors[k].getScrollerElement());
                                                if(jQuery(this).hasClass('textarea_shrink')) {
                                                    scrollEl.height(scrollEl.height() + 20);
                                                }
                                                else if(scrollEl.height() > 120) {
                                                    scrollEl.height(scrollEl.height() - 20);
                                                }
                                                break;
                                            }
                                        }
                                    });
                                });
                                jQuery(el).after(jQuery('<input>').attr({type: 'checkbox', id: 'cmtext'+ind})
                                .click(createCMEditor(ind, el))
                                .after(jQuery('<label>').attr('for', 'cmtext'+ind).html(' " . NETCAT_SETTINGS_CODEMIRROR_SWITCH . "')));
                            });
                        }
                    });
                    jQuery('body').attr('style', 'overflow-y: auto;');
                </script>
                <!-- для диалога генерации альтернативных форм -->
                <script type='text/javascript'>
                    var SUB_FOLDER = '" . $SUB_FOLDER . "';
                    var NETCAT_PATH = '" . $SUB_FOLDER . $HTTP_ROOT_PATH . "';
                    var ADMIN_PATH = '" . $ADMIN_PATH . "';
                    var ADMIN_LANG = '" . MAIN_LANG . "';
                    var NC_CHARSET = '" . $nc_core->NC_CHARSET . "';
                    var ICON_PATH = '" . $ADMIN_TEMPLATE . "img/';
                    var NETCAT_REMIND_SAVE_TEXT = '" . NETCAT_REMIND_SAVE_TEXT . "';
                </script>" .
        "<script>
                    function showhide(val, val2) {
                        var obj=document.getElementById(val)
                        var obj2=document.getElementById(val2)
                        obj.className=(obj.className=='show_add')? 'hide_add': 'show_add'
                        obj2.className=(obj2.className=='blue')? 'white': 'blue'
                }
                </script>";

    $addon .= $navbar_html;

    if ($File_Mode) {
        $addon = str_replace("\\\"", "\"", $addon);
    }

    switch (true) {
        case preg_match("/\<\s*?frameset.*?\>/is", $buffer):
            break;
        case preg_match("/\<\s*?body.*?\>/im", $buffer):
            $preg_pattern = "/(\<\s*?body.*?\>){1}/im";
            $preg_replacement = "\$1\n" . $addon;
            break;
        case preg_match("/\<\s*?html\s*?\>/is", $buffer):
            $preg_pattern = "/(\<\s*?html\s*?\>){1}/is";
            $preg_replacement = "\$1\n<body>" . $addon . "</body>";
            break;
    }

    if (!empty($preg_pattern) && !empty($preg_replacement)) {
        $buffer = preg_replace($preg_pattern, $preg_replacement, $buffer);
        if (!headers_sent()) {
            header('X-Accel-Expires: 0'); // для исключения кэширования средствами веб-сервера
        }
    }

    return $buffer;
}
