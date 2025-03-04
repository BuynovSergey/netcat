<?php
/* $id$ */

class nc_auth_admin {

    protected $core, $db;

    public function __construct() {
        $this->core = nc_Core::get_object();
        // global variables to internal
        $this->db = & $this->core->db;
    }

    public function get_mainsettings_url() {
        return "#module.auth.general";
    }

    /**
     * Вывод информации о модуле
     */
    public function info_show() {
        $all_user_count = $this->db->get_var("SELECT COUNT(`User_ID`) FROM `User`");
        $all_user_unckecked = $this->db->get_var("SELECT COUNT(`User_ID`) FROM `User` WHERE `Checked` = 0");
        $all_user_nonconfirmed = $this->db->get_var("SELECT COUNT(`User_ID`) FROM `User` WHERE `Checked` = 0 AND `Confirmed` = 0 AND `RegistrationCode` <> '' ");

        echo "<br /><div style='margin-bottom: 20px;'>" . NETCAT_MODULE_AUTH_DESCRIPTION . "</div>";
        echo "<div style='margin-bottom: 4px;'>" . NETCAT_MODULE_AUTH_ADMIN_INFO_USER_COUNT . ": " . ($all_user_count ? "<a href='" . $this->core->ADMIN_PATH . "user/'>" . $all_user_count . "</a>" : NETCAT_MODULE_AUTH_ADMIN_INFO_NONE) . "</div>";
        echo "<div style='margin-bottom: 4px;'>" . NETCAT_MODULE_AUTH_ADMIN_INFO_USER_COUNT_UNCHECKED . ": " . ($all_user_unckecked ? "<a href='" . $this->core->ADMIN_PATH . "user/?Checked=2'>" . $all_user_unckecked . "</a>" : NETCAT_MODULE_AUTH_ADMIN_INFO_NONE) . "</div>";
        echo "<div>" . NETCAT_MODULE_AUTH_ADMIN_INFO_USER_COUNT_UNCONFIRMED . ": " . ($all_user_nonconfirmed ? "<a href='" . $this->core->ADMIN_PATH . "user/?nonConfirmed=1'>" . $all_user_nonconfirmed . "</a>" : NETCAT_MODULE_AUTH_ADMIN_INFO_NONE) . "</div>";
    }

    /**
     * dummy
     */
    public function info_save() {
        return;
    }

    /**
     * Настройки регистрации по логину и паролю
     */
    public function classic_show() {
        global $UI_CONFIG;
        $UI_CONFIG->add_reg_toolbar();
        // настройки
        $settings = $this->core->get_settings('', 'auth', false, $this->core->catalogue->id());

        // поля из системной таблицы
        $st = new nc_Component(0, 3);
        $fields = array();
        foreach ($st->get_fields() as $v) {
            if ($v['edit_type'] != 1)
                continue;
            if ($v['name'] == $this->core->AUTHORIZE_BY)
                continue;
            $fields[$v['name']] = $v['description'];
        }

        // группы пользователя
        $groups = array();
        $res = $this->db->get_results("SELECT `PermissionGroup_ID` as `id`, `PermissionGroup_Name` as `name` FROM `PermissionGroup` ORDER BY `PermissionGroup_ID`", ARRAY_A);
        if (!empty($res))
            foreach ($res as $v)
                $groups[$v['id']] = $v['name'];

        // основные настройки
        echo "<form method='post' action='admin.php' class='nc-form' id='adminForm' style='padding:0; margin:0;'>\n";
        echo $this->catalogue_select_field('classic');
        echo "<fieldset>\n" .
            "<legend>\n" .
            "" . NETCAT_MODULE_AUTH_ADMIN_MAIN_SETTINGS_TITLE . "\n" .
            "</legend>\n";
        echo nc_admin_checkbox(NETCAT_MODULE_AUTH_ADMIN_CLASSIC_DENY_REG, 'deny_reg', $settings);
        echo nc_admin_checkbox(NETCAT_MODULE_AUTH_ADMIN_CLASSIC_DENY_RECOVERY, 'deny_recovery', $settings);
        echo nc_admin_checkbox(NETCAT_MODULE_AUTH_ADMIN_CLASSIC_ALLOW_CYRILLIC, 'allow_cyrillic', $settings);
        echo nc_admin_checkbox(NETCAT_MODULE_AUTH_ADMIN_CLASSIC_ALLOW_SPECIALCHARS, 'allow_specialchars', $settings);
        echo nc_admin_checkbox(NETCAT_MODULE_AUTH_ADMIN_CLASSIC_ALLOW_CHANGE_LOGIN, 'allow_change_login', $settings);
        echo nc_admin_checkbox(NETCAT_MODULE_AUTH_ADMIN_CLASSIC_BING_TO_CATALOGUE, 'bind_to_catalogue', $settings);
        echo nc_admin_checkbox(NETCAT_MODULE_AUTH_ADMIN_CLASSIC_WITH_SUBDOMAIN, 'with_subdomain', $settings);
        echo nc_admin_input_in_text(NETCAT_MODULE_AUTH_ADMIN_CLASSIC_PASS_MIN, 'pass_min', $settings, 1);

        $captcha_settings_link = nc_core::get_object()->ADMIN_PATH . '#security.auth.captcha';
        echo '<div><a href="' . $captcha_settings_link . '" target="_top">' . NETCAT_MODULE_AUTH_ADMIN_CLASSIC_AUTH_CAPTCHA . '</a></div>';

        echo "</fieldset>\n";

        // форма регистрации
        echo
            "<fieldset>\n" .
            "<legend>\n" .
            "" . NETCAT_MODULE_AUTH_ADMIN_CLASSIC_REGISTRATION_FORM . "\n" .
            "</legend>\n";
        echo nc_admin_checkbox(NETCAT_MODULE_AUTH_ADMIN_CLASSIC_CHECK_LOGIN, 'check_login', $settings);
        echo nc_admin_checkbox(NETCAT_MODULE_AUTH_ADMIN_CLASSIC_CHECK_PASS, 'check_pass', $settings);
        echo nc_admin_checkbox(NETCAT_MODULE_AUTH_ADMIN_CLASSIC_CHECK_PASS2, 'check_pass2', $settings);
        echo nc_admin_checkbox(NETCAT_MODULE_AUTH_ADMIN_CLASSIC_CHECK_AGREED, 'agreed', $settings);

        echo NETCAT_MODULE_AUTH_ADMIN_CLASSIC_FIELDS_IN_REG_FORM . ":<br/>";
        echo "<input type='radio' name='field_all' id='field_all' value='1' " . ($settings['field_all'] ? "checked='checked'" : "") . "><label for='field_all'>" . NETCAT_MODULE_AUTH_ADMIN_CLASSIC_FIELDS_IN_REG_FORM_ALL . "</label><br/>";
        echo "<input type='radio' name='field_all' id='field_custom' value='0' " . (!$settings['field_all'] ? "checked='checked'" : "") . "><label for='field_custom'>" . NETCAT_MODULE_AUTH_ADMIN_CLASSIC_FIELDS_IN_REG_FORM_CUSTOM . "</label>";
        echo "<div style='padding-left: 15px;'>";
        $f = explode(',', $settings['field_custom']);
        foreach ($fields as $k => $v) {
            echo nc_admin_checkbox($v, 'field_custom_' . $k, in_array($k, $f));
        }
        echo "</div>";
        echo "</fieldset>\n";


        // активация
        echo
            "<fieldset>\n" .
            "<legend>\n" .
            "" . NETCAT_MODULE_AUTH_ADMIN_CLASSIC_ACTIVATION . "\n" .
            "</legend>\n";
        echo nc_admin_checkbox(NETCAT_MODULE_AUTH_ADMIN_CLASSIC_CONFIRM, 'confirm', $settings);
        echo nc_admin_checkbox(NETCAT_MODULE_AUTH_ADMIN_CLASSIC_CONFIRM_AFTER_MAIL, 'confirm_after_mail', $settings);
        echo nc_admin_checkbox(NETCAT_MODULE_AUTH_ADMIN_CLASSIC_PREMODARATION, 'premoderation', $settings);
        echo nc_admin_checkbox(NETCAT_MODULE_AUTH_ADMIN_CLASSIC_NOTIFY_ADMIN, 'notify_admin', $settings);
        echo nc_admin_checkbox(NETCAT_MODULE_AUTH_ADMIN_CLASSIC_AUTHAUTORIZE, 'autoauthorize', $settings);
        echo nc_admin_input_in_text(NETCAT_MODULE_AUTH_ADMIN_CLASSIC_CONFIRM_TIME, 'confirm_time', $settings, 4);
        echo "</fieldset>\n";

        // группы пользователей
        echo
            "<fieldset>\n" .
            "<legend>\n" .
            "" . NETCAT_MODULE_AUTH_GROUPS_WHERE_USER_WILL_BE . "\n" .
            "</legend>\n";
        $f = explode(',', $settings['group']);
        if (empty($f[0])) {
                echo nc_print_status(NETCAT_MODULE_AUTH_GROUPS_WHERE_USER_WILL_BE_EMPTY, 'error', null, 1);
            }
        foreach ($groups as $g_id => $g_name) {
            echo nc_admin_checkbox($g_name, 'group_' . $g_id, in_array($g_id, $f));
        }
        echo "</fieldset>\n";

        if (!isset($Catalogue_ID)) {
            $Catalogue_ID = null;
        }
        echo "<input type='hidden' name='Catalogue_ID' value='" . $Catalogue_ID . "' />\n";
        echo $this->core->token->get_input() . "\n";
        echo "<input type='hidden' name='view' value='classic' />\n";
        echo "<input type='hidden' name='act' value='save' />\n";
        echo "</form>\n";


        $UI_CONFIG->actionButtons[] =
            array("id" => "submit",
                "caption" => NETCAT_MODERATION_BUTTON_CHANGE,
                "action" => "mainView.submitIframeForm('adminForm')");
    }

    public function classic_save() {
        $catalogue_id = $this->core->catalogue->id();

        $params = array(
            'deny_reg',
            'deny_recovery',
            'allow_cyrillic',
            'allow_specialchars',
            'allow_change_login',
            'pass_min',
            'bind_to_catalogue',
            'with_subdomain',
            'check_login',
            'check_pass',
            'check_pass2',
            'agreed',
            'confirm_time',
            'field_all',
            'confirm',
            'confirm_after_mail',
            'premoderation',
            'notify_admin',
            'autoauthorize'
        );

        // настройки
        foreach ($params as $v) {
            $this->core->set_settings($v, (int)$this->core->input->fetch_get_post($v), 'auth', $catalogue_id);
        }

        // поля, выводимые при регистрации
        $f = array();
        foreach ($this->core->input->fetch_get_post() as $k => $v) {
            if (nc_preg_match('/field_custom_([a-z_]\w*)/i', $k, $match)) {
                $f[] = $match[1];
            }
        }
        $this->core->set_settings('field_custom', implode(',', $f), 'auth', $catalogue_id);

        // группа
        $f = array();
        foreach ($this->core->input->fetch_get_post() as $k => $v) {
            if (nc_preg_match('/group_([0-9]+)/i', $k, $match)) {
                $f[] = $match[1];
            }
        }
        $this->core->set_settings('group', implode(',', $f), 'auth', $catalogue_id);
    }

    /**
     * Показ формы настроек шаблонов вывода
     */
    public function templates_show() {
        global $UI_CONFIG;
        $UI_CONFIG->add_settings_toolbar();

        /** @var Permission $perm */
        global $perm;
        $perm->ExitIfNotSupervisor();

        $nc_auth = nc_auth::get_object();

        $auth_editor = new nc_tpl_module_subtypes_editor(true);
        $auth_editor->load('auth')->fill();

        ?>

        <script type="text/javascript">
            body = {
                user_login_form: "<?php echo nc_text_for_js(str_replace('"', '\\"', $nc_auth->tpl->get_user_login_form_default_fs())); ?>",
                change_password_form: "<?php echo nc_text_for_js(str_replace('"', '\\"', $nc_auth->tpl->get_change_password_form_default_fs())); ?>",
                recovery_password_form: "<?php echo nc_text_for_js(str_replace('"', '\\"', $nc_auth->tpl->get_recovery_password_form_default_fs())); ?>"
            };

            function recovery(type, name, confirmed){
                if (!confirmed) {
                    if (confirm(ncLang.WarnAuthMail)) {
                        recovery(type, name, 1);
                    }
                    return false;
                }

                selectedTextarea = jQuery("#" + type + "_" + name);
                selectedTextarea.val(body[name]);
                if (typeof selectedTextarea.codemirror == 'function') {
                    selectedTextarea.codemirror('setValue');
                }
            }
        </script>
        <br/>
        <?php
        $auth_settings = $auth_editor->get_all_fields();
        $auth_settings['old'] = $this->core->get_settings('', 'auth', false, $this->core->catalogue->id());
        echo "<form action='admin.php' name='AuthSettings' id='AuthSettings' method='post'>";
        echo $this->catalogue_select_field('templates');
        echo NETCAT_MODULE_AUTH_ADMIN_INFO;

        foreach ($auth_settings as $type => $settings) {
            $pref = ($type == 'old' ? '' : $type . '_');
            echo "
    <legend style='padding-bottom: 0px;'>" . constant("TITLE_" . strtoupper($type)) . "</legend>
    <fieldset>
        <div style='float:right; margin-right: 20px;'><a href='#' onclick=\"recovery('$type', 'user_login_form'); return false;\" >
        " . NETCAT_MODULE_AUTH_RESTORE_DEF . "</a></div><br clear='all'>
        " . nc_admin_textarea(NETCAT_MODULE_AUTH_FORM_AUTH, $pref . 'user_login_form', nc_array_value($settings,'user_login_form'), 1, 0, "height:15em;") . "
    </fieldset>

    <fieldset>
        <div style='float:right; margin-right: 20px;'><a href='#' onclick=\"recovery('$type', 'change_password_form'); return false;\" >" . NETCAT_MODULE_AUTH_RESTORE_DEF . "</a></div><br clear='all'>" .
                nc_admin_textarea(NETCAT_MODULE_AUTH_FORM_CHG_PASS, $pref . 'change_password_form', nc_array_value($settings,'change_password_form'), 1, 0, "height:15em;") .
                nc_admin_textarea(NETCAT_MODULE_AUTH_FORM_CHG_PASS_AFTER, $pref . 'change_password_after', nc_array_value($settings,'change_password_after'), 1) .
                nc_admin_textarea(NETCAT_MODULE_AUTH_FORM_CHG_PASS_WARNBLOCK, $pref . 'change_password_warn', nc_array_value($settings,'change_password_warn'), 1) . "
    </fieldset>

    <fieldset>
        <div style='float:right; margin-right: 20px;'>
        <a href='#' onclick=\"recovery('$type', 'recovery_password_form'); return false;\" >" . NETCAT_MODULE_AUTH_RESTORE_DEF . "</a></div><br clear='all'>" .
                nc_admin_textarea(NETCAT_MODULE_AUTH_FORM_REC_PASS, $pref . 'recovery_password_form', nc_array_value($settings,'recovery_password_form'), 1, 0, "height:15em;") .
                nc_admin_textarea(NETCAT_MODULE_AUTH_FORM_REC_PASS_AFTER, $pref . 'recovery_password_after', nc_array_value($settings,'recovery_password_after'), 1) .
                nc_admin_textarea(NETCAT_MODULE_AUTH_FORM_CHG_PASS_WARNBLOCK, $pref . 'recovery_password_warn', nc_array_value($settings,'recovery_password_warn'), 1) .
                nc_admin_textarea(NETCAT_MODULE_AUTH_FORM_CHG_PASS_DENY, $pref . 'recovery_password_deny', nc_array_value($settings,'recovery_password_deny'), 1) . "
    </fieldset>

    <fieldset>" .
                nc_admin_textarea(NETCAT_MODULE_AUTH_FORM_CONFIRM_AFTER, $pref . 'confirm_after', nc_array_value($settings,'confirm_after'), 1) .
                nc_admin_textarea(NETCAT_MODULE_AUTH_FORM_CONFIRM_AFTER_WARNBLOCK, $pref . 'confirm_after_warn', nc_array_value($settings,'confirm_after_warn'), 1) . "
    </fieldset>
        ";
        }
        echo "<br />";

        echo $this->core->token->get_input() . "
    <input type='hidden' name='view' value='templates' />
    <input type='hidden' name='act' value='save' /></form>";

        global $UI_CONFIG;
        $UI_CONFIG->add_settings_toolbar();
        $UI_CONFIG->actionButtons[] =
            array("id" => "submit",
                "caption" => NETCAT_MODERATION_BUTTON_CHANGE,
                "action" => "mainView.submitIframeForm('adminForm')");
        return 0;
    }

    public function templates_save() {
        /** @var Permission $perm */
        global $perm;
        $perm->ExitIfNotSupervisor();

        $catalogue_id = $this->core->catalogue->id();

        $params = array(
            'user_login_form',
            'user_login_form_disable',
            'change_password_form',
            'change_password_after',
            'change_password_warn',
            'recovery_password_form',
            'recovery_password_after',
            'recovery_password_warn',
            'recovery_password_deny',
            'confirm_after',
            'confirm_after_warn',
        );

        // настройки
        foreach ($params as $v) {
            $this->core->set_settings($v, $this->core->input->fetch_get_post($v), 'auth', $catalogue_id);
        }

        $module_editor = new nc_tpl_module_subtypes_editor();
        $module_editor->load('auth')->save($_POST, true);
    }

    /**
     * Показ формы настроек авторизации через внешние сервисы
     */
    public function ex_show() {
        global $UI_CONFIG;
        $UI_CONFIG->add_reg_toolbar();

        /** @var Permission $perm */
        global $perm;
        $perm->ExitIfNotSupervisor();

        // настройки
        $settings = $this->core->get_settings('', 'auth', false, $this->core->catalogue->id());

        $settings['ex_enabled'] = unserialize($settings['ex_enabled']);
        $settings['ex_apps'] = unserialize($settings['ex_apps']);
        $settings['ex_group'] = unserialize($settings['ex_group']);
        $settings['ex_addaction_prep'] = unserialize($settings['ex_addaction_prep']);
        $settings['ex_addaction'] = unserialize($settings['ex_addaction']);
        $settings['ex_fields'] = unserialize($settings['ex_fields']);
        $settings['ex_openid_providers'] = unserialize($settings['ex_openid_providers']);
	    $settings['ex_oauth_providers'] = unserialize((nc_array_value($settings,'ex_oauth_providers')));
        if (!$settings['ex_group']) {
            $settings['ex_group'] = array();
        }

        // группы пользователя
        $groups = array();
        $res = $this->db->get_results("SELECT `PermissionGroup_ID` as `id`, `PermissionGroup_Name` as `name` FROM `PermissionGroup` ORDER BY `PermissionGroup_ID`", ARRAY_A);
        if (!empty($res)) {
            foreach ($res as $v) {
                $groups[$v['id']] = $v['name'];
            }
        }

        // поля из системной таблицы
        $utable = new nc_Component(0, 3);
        $field_user = $utable->get_fields();
        $js_field_user = array();
        if (!empty($field_user)) {
            foreach ($field_user as $v) {
                $js_field_user[] = "" . $v['name'] . ": '" . $v['description'] . "'";
            }
        }

        if (!$this->core->php_ext("curl")) {
            nc_print_status(NETCAT_MODULE_AUTH_ADMIN_EX_CURL_REQUIRED, 'info');
        }
        if (!$this->core->php_ext("json")) {
            nc_print_status(NETCAT_MODULE_AUTH_ADMIN_EX_JSON_REQUIRED, 'info');
        }

        echo "<form action='admin.php' name='adminForm' class='nc-form' id='adminForm' method='post'>";
        echo $this->catalogue_select_field('ex');
        echo $this->core->token->get_input();
        echo "<input type='hidden' name='view' value='ex' /><input type='hidden' name='act' value='save' />";

        // настройки каждого сервиса
        $types = array('vk', 'fb', 'twitter', 'openid', 'oauth');
        $result = '';
        foreach ($types as $v) {
            $field = new nc_admin_fieldset(constant("NETCAT_MODULE_AUTH_ADMIN_EX_" . strtoupper($v)));
            $field->add(nc_admin_checkbox(constant("NETCAT_MODULE_AUTH_ADMIN_EX_" . strtoupper($v) . "_ENABLED"), $v . '_enabled', $settings['ex_enabled'][$v]));
            if ($v !== 'openid' && $v !== 'oauth') {
                $field->add(
                    nc_admin_input(constant("NETCAT_MODULE_AUTH_APPLICATION_ID_" . strtoupper($v)), $v . '_app_id', $settings['ex_apps'][$v]['app_id'], 0, 'width:30%; margin-bottom: 5px;') .
                    nc_admin_input(constant("NETCAT_MODULE_AUTH_SECRET_KEY_" . strtoupper($v)), $v . '_app_key', $settings['ex_apps'][$v]['app_key'], 0, 'width:30%')
                );
            }

            // группы
            $html = "<div style='margin-bottom: 5px;'>" . NETCAT_MODULE_AUTH_GROUPS_WHERE_USER_WILL_BE . ":</div>";

            if (empty($settings['ex_group'][$v])) {
                $html .= nc_print_status(NETCAT_MODULE_AUTH_GROUPS_WHERE_USER_WILL_BE_EMPTY, 'error', null, 1);
            }
            foreach ($groups as $g_id => $g_name) {
                $html .= "<input id='" . $v . "_group_" . $g_id . "' type='checkbox' name='" . $v . "_group[]' value='" . $g_id . "' " . (@in_array($g_id, $settings['ex_group'][$v]) ? " checked='checked' " : "") . " />
            <label for='" . $v . "_group_" . $g_id . "'>" . $g_name . "</label><br/>";
            }
            $field->add($html);

            // действия перед добавлением
            $field->add(nc_admin_textarea(NETCAT_MODULE_AUTH_ACTION_BEFORE_FIRST_AUTHORIZATION, $v . '_addaction_prep', $settings['ex_addaction_prep'][$v], 1));
            // действие после добавления
            $field->add(nc_admin_textarea(NETCAT_MODULE_AUTH_ACTION_AFTER_FIRST_AUTHORIZATION, $v . '_addaction', $settings['ex_addaction'][$v], 1));

            // соответствие полей
            $html = "<div style='font-weight: bold; margin-top: 10px;'>" . NETCAT_MODULE_AUTH_ACTION_FIELDS_MAPPING . "</div>";
            $html .= "<div id='" . $v . "_mapping'></div>
        <a href='#' onclick='nc_mf_" . $v . ".add(); return false;' class='nc-btn nc--light'>
          <i class='nc-icon nc--file-add'></i> " . NETCAT_MODULE_AUTH_ACTION_FIELDS_MAPPING_ADD . "
        </a>";
            $field->add($html);

            if ($v === 'openid') {
                $html = "<div id='openid_providers'></div>
        <a href='#' onclick='op.add(); return false' class='nc-btn nc--light'>
          <i class='nc-icon nc--file-add'></i> " . NETCAT_MODULE_AUTH_PROVIDER_ADD . "
        </a>";
                $field->add($html);
            }

	        if ($v === 'oauth') {
		        $html = "<div id='oauth_providers'>

		                    <div class='oauth_header'>
						        <div class='img'><img src='" . nc_module_path('auth') . "images/icons/oauth.png'></div>
								<div class='name'>".NETCAT_MODULE_AUTH_PROVIDER."</div>
								<div class='provider'>ID</div>
								<div class='imglink'>".NETCAT_MODULE_AUTH_PROVIDER_ICON."</div>
								<div class='appid'>".NETCAT_MODULE_AUTH_APPLICATION_ID."</div>
								<div class='pubkey'>".NETCAT_MODULE_AUTH_PUBLIC_KEY."</div>
								<div class='seckey'>".NETCAT_MODULE_AUTH_SECRET_KEY."</div>
								<div class='drop'></div>
								<div style='clear:both;'></div>
							</div>

		                </div>
        <a href='#' onclick='oap.add(); return false' class='nc-btn nc--light'>
          <i class='nc-icon nc--file-add'></i> " . NETCAT_MODULE_AUTH_PROVIDER_ADD . "
        </a>";
		        $field->add($html);
	        }

            $result .= $field->result();
            unset($field);
        }

        echo $result;

        // js для полей и openid провайдеров
        echo "<script type='text/javascript'>
                  MODULE_AUTH_OPENID_ICON_PATH = '" . nc_module_path('auth') . "images/icons/openid.png';
                  MODULE_AUTH_OAUTH_ICON_PATH = '" . nc_module_path('auth') . "images/icons/oauth.png';
                  nc_mf_vk = new nc_mapping_fields({ " . implode(',', $js_field_user) . " }, { 'uid' : 'ID', 'first_name': '" . NETCAT_MODULE_AUTH_FIRST_NAME . "', 'last_name': '" . NETCAT_MODULE_AUTH_LAST_NAME . "', 'nickname':'" . NETCAT_MODULE_AUTH_NICKNAME . "', 'photo_big' : '" . NETCAT_MODULE_AUTH_PHOTO . "', 'email':'Email'}, 'vk_mapping', 'nc_mf_vk', '" . NETCAT_MODULE_AUTH_ADMIN_EX_DATA_VK . "' );
                  nc_mf_fb = new nc_mapping_fields({ " . implode(',', $js_field_user) . " }, { 'id' : 'ID', 'name': '" . NETCAT_MODULE_AUTH_FIRST_NAME . "', 'email':'Email', 'picture' : '" . NETCAT_MODULE_AUTH_PHOTO . "'}, 'fb_mapping', 'nc_mf_fb', '" . NETCAT_MODULE_AUTH_ADMIN_EX_DATA_FB . "' );
                  nc_mf_twitter = new nc_mapping_fields({ " . implode(',', $js_field_user) . " }, { 'id' : 'ID', 'name': '" . NETCAT_MODULE_AUTH_FIRST_NAME . "', 'profile_image_url' : '" . NETCAT_MODULE_AUTH_PHOTO . "', 'screen_name':'" . NETCAT_MODULE_AUTH_LOGIN . "', 'email':'Email'}, 'twitter_mapping', 'nc_mf_twitter', '" . NETCAT_MODULE_AUTH_ADMIN_EX_DATA_TWITTER . "' );
                  nc_mf_openid = new nc_mapping_fields({ " . implode(',', $js_field_user) . " }, { 'nickname' : '" . NETCAT_MODULE_AUTH_NICKNAME . "', 'fullname': '" . NETCAT_MODULE_AUTH_FIRST_NAME . "', 'email':'Email'}, 'openid_mapping', 'nc_mf_openid', '" . NETCAT_MODULE_AUTH_ADMIN_EX_DATA_OPENID . "' );
                  nc_mf_oauth = new nc_mapping_fields({ " . implode(',', $js_field_user) . " }, { 'uid' : 'ID', 'name': '" . NETCAT_MODULE_AUTH_FIRST_NAME . "', 'nick': '" . NETCAT_MODULE_AUTH_NICKNAME . "', 'photo' : '" . NETCAT_MODULE_AUTH_PHOTO . "', 'email':'Email'}, 'oauth_mapping', 'nc_mf_oauth', '" . NETCAT_MODULE_AUTH_ADMIN_EX_DATA_OAUTH . "' );
                  op = new nc_openidproviders();
                  oap = new nc_oauthproviders();";

        // поля
        foreach ($types as $v) {
            if (!empty($settings['ex_fields'][$v])) {
                foreach ($settings['ex_fields'][$v] as $f1 => $f2) {
                    echo "nc_mf_" . $v . ".add('" . $f1 . "','" . $f2 . "');";
                }
            }
        }
        // провайдеры
        if (!empty($settings['ex_openid_providers'])) {
            foreach ($settings['ex_openid_providers'] as $provider) {
                echo "op.add('" . $provider['name'] . "','" . $provider['url'] . "', '" . $provider['imglink'] . "');";
            }
        }
	    // провайдеры
	    if (!empty($settings['ex_oauth_providers'])) {
            foreach ($settings['ex_oauth_providers'] as $provider) {
                echo "oap.add('" . $provider['imglink'] . "','" . $provider['name'] . "','" . $provider['provider'] . "', '" . $provider['appid'] . "', '" . $provider['pubkey'] . "', '" . $provider['seckey'] . "');";
            }
        }
        echo "</script>";
        echo nc_admin_js_resize();
        echo "</form>";

        $UI_CONFIG->actionButtons[] =
            array("id" => "submit",
                "caption" => NETCAT_MODERATION_BUTTON_CHANGE,
                "action" => "mainView.submitIframeForm('adminForm')");
        return true;
    }

    /**
     * Сохранение настроек "Авторизация через внешние сервисы"
     */
    public function ex_save() {
        /** @var Permission $perm */
        global $perm;
        $perm->ExitIfNotSupervisor();

        $nc_auth = nc_auth::get_object();
        $catalogue_id = $this->core->catalogue->id();

        $input = $this->core->input->fetch_get_post();

        $types = array('vk', 'fb', 'twitter', 'openid', 'oauth');

        // основные параметры
        foreach ($types as $v) {
            // возможность авторизации
            $ex_enabled[$v] = (int)nc_array_value($input,$v . '_enabled');
            // id приложения
            if (isset($input[$v . '_app_id'])) {
                $ex_apps[$v]['app_id'] = $input[$v . '_app_id'];
            }
            // секретный ключ
            if (isset($input[$v . '_app_key'])) {
                $ex_apps[$v]['app_key'] = $input[$v . '_app_key'];
            }
            // действие перед добавлением
            $ex_addaction_prep[$v] = $input[$v . '_addaction_prep'];
            // действие после добавления
            $ex_addaction[$v] = $input[$v . '_addaction'];
            // группы
            $ex_group[$v] = $input[$v . '_group'];
        }
        // соответствие полей и openid-провайдеры
        foreach ($input as $k => $v) {
            if (preg_match('/([a-z]+)_mapping_field1_value_(\d+)/i', $k, $match)) {
                $name = $match[1];
                $id = $match[2];
                $f1 = $input[$name . '_mapping_field1_value_' . $id];
                $f2 = $input[$name . '_mapping_field2_value_' . $id];
                $ex_fields[$name][$f1] = $f2;
            }

            if (preg_match('/openid_providers_name_(\d+)/i', $k, $match)) {
                $id = $match[1];
                if (!$input['openid_providers_name_' . $id]) {
                    continue;
                }
                $ex_openid_providers[$id] = array(
                    'name' => $input['openid_providers_name_' . $id],
                    'url' => $input['openid_providers_url_' . $id],
                    'imglink' => $input['openid_providers_imglink_' . $id]);
            }
	        if (preg_match('/oauth_providers_name_(\d+)/i', $k, $match)) {
		        $id = $match[1];
		        if (!$input['oauth_providers_name_' . $id]) {
                    continue;
                }
		        $ex_oauth_providers[$id] = array(
			        'provider' => $input['oauth_providers_provider_' . $id],
			        'name' => $input['oauth_providers_name_' . $id],
			        'appid' => $input['oauth_providers_appid_' . $id],
			        'pubkey' => $input['oauth_providers_pubkey_' . $id],
			        'seckey' => $input['oauth_providers_seckey_' . $id],
			        'imglink' => $input['oauth_providers_imglink_' . $id]);
	        }
        }

        $params = array(
            'ex_enabled',
            'ex_apps',
            'ex_addaction_prep',
            'ex_addaction',
            'ex_group',
            'ex_fields',
            'ex_openid_providers',
	        'ex_oauth_providers',
        );

        foreach ($params as $param) {
            $this->core->set_settings($param, isset($$param) ? serialize($$param) : null, 'auth', $catalogue_id);
        }

        return true;
    }

    /**
     * Форма общих настроек
     */
    public function general_show() {
        global $UI_CONFIG;
        $UI_CONFIG->add_settings_toolbar();

        // настройки
        $settings = $this->core->get_settings('', 'auth', false, $this->core->catalogue->id());

        echo "<form method='post' action='admin.php' id='adminForm' class='nc-form' style='padding:0; margin:0;'>\n";
        echo $this->catalogue_select_field('general');

        // Способы авторизации на сайте
        echo "<fieldset>\n" .
            "<legend>\n" .
            "" . NETCAT_MODULE_AUTH_ADMIN_GENERAL_AUTH_SITE . "\n" .
            "</legend>\n";
        echo nc_admin_checkbox(NETCAT_MODULE_AUTH_ADMIN_GENERAL_METHOD_LOGIN, 'authtype_site_login', $settings['authtype_site'] & NC_AUTHTYPE_LOGIN);
        echo nc_admin_checkbox(NETCAT_MODULE_AUTH_ADMIN_GENERAL_METHOD_HASH, 'authtype_site_hash', $settings['authtype_site'] & NC_AUTHTYPE_HASH);
        echo nc_admin_checkbox(NETCAT_MODULE_AUTH_ADMIN_GENERAL_METHOD_EX, 'authtype_site_ex', $settings['authtype_site'] & NC_AUTHTYPE_EX);
        echo nc_admin_checkbox(NETCAT_MODULE_AUTH_ADMIN_GENERAL_METHOD_TOKEN, 'authtype_site_token', $settings['authtype_site'] & NC_AUTHTYPE_TOKEN);
        echo "</fieldset>\n";

        // Способы авторизации в систему администрирования
        echo
            "<fieldset>\n" .
            "<legend>\n" .
            "" . NETCAT_MODULE_AUTH_ADMIN_GENERAL_AUTH_ADMIN . "\n" .
            "</legend>\n";
        echo nc_admin_checkbox(NETCAT_MODULE_AUTH_ADMIN_GENERAL_METHOD_LOGIN, 'authtype_admin_login', $settings['authtype_admin'] & NC_AUTHTYPE_LOGIN);
        echo nc_admin_checkbox(NETCAT_MODULE_AUTH_ADMIN_GENERAL_METHOD_HASH, 'authtype_admin_hash', $settings['authtype_admin'] & NC_AUTHTYPE_HASH);
        echo nc_admin_checkbox(NETCAT_MODULE_AUTH_ADMIN_GENERAL_METHOD_TOKEN, 'authtype_admin_token', $settings['authtype_admin'] & NC_AUTHTYPE_TOKEN);
        echo "<div style='height: 3px;'>&nbsp;</div>";
        //echo nc_admin_checkbox('Разрешить вход только по https-протоколу', 'admin_https', $settings['admin_https']);
        echo "</fieldset>\n";

        // основные настройки
        echo
            "<fieldset>\n" .
            "<legend>\n" .
            "" . NETCAT_MODULE_AUTH_ADMIN_GENERAL_PM . "\n" .
            "</legend>\n";
        echo nc_admin_checkbox(NETCAT_MODULE_AUTH_ADMIN_GENERAL_PM_ALLOW, 'pm_allow', $settings);
        echo nc_admin_checkbox(NETCAT_MODULE_AUTH_ADMIN_GENERAL_PM_NOTIFY, 'pm_notify', $settings);
        echo "</fieldset>\n";

        // друзья
        echo
            "<fieldset>\n" .
            "<legend>\n" .
            "" . NETCAT_MODULE_AUTH_ADMIN_GENERAL_FRIEND_BANNED . "\n" .
            "</legend>\n";
        echo nc_admin_checkbox(NETCAT_MODULE_AUTH_ADMIN_GENERAL_FRIEND_ALLOW, 'friend_allow', $settings);
        echo nc_admin_checkbox(NETCAT_MODULE_AUTH_ADMIN_GENERAL_BANNED_ALLOW, 'banned_allow', $settings);
        echo "</fieldset>\n";

        // личный счет
        echo
            "<fieldset>\n" .
            "<legend>\n" .
            "" . NETCAT_MODULE_AUTH_ADMIN_GENERAL_PA . "\n" .
            "</legend>\n";
        echo nc_admin_checkbox(NETCAT_MODULE_AUTH_ADMIN_GENERAL_PA_ALLOW, 'pa_allow', $settings);
        echo nc_admin_input_in_text(NETCAT_MODULE_AUTH_ADMIN_GENERAL_PA_CURRENCY, 'pa_currency', $settings['pa_currency'], 4) . '<br/>';
        echo nc_admin_input_in_text(NETCAT_MODULE_AUTH_ADMIN_GENERAL_PA_START, 'pa_start', $settings['pa_start'], 4);
        echo "</fieldset>\n";


        echo $this->core->token->get_input() . "\n";
        echo "<input type='hidden' value='general' name='view' />";
        echo "<input type='hidden' value='save' name='act' />";
        echo "</form>\n";


        $UI_CONFIG->actionButtons[] =
            array("id" => "submit",
                "caption" => NETCAT_MODERATION_BUTTON_CHANGE,
                "action" => "mainView.submitIframeForm('adminForm')");
    }

    /**
     * @throws Exception
     */
    public function general_save() {
        $catalogue_id = $this->core->catalogue->id();

        // включение/выключение Личного счета надо обрабатывать отдельно
        $cur_pa_allow = $this->core->get_settings('pa_allow', 'auth');
        if ($cur_pa_allow != $this->core->input->fetch_get_post('pa_allow')) {
            // компонент "Личный счет"
            if (($class_id = $this->core->get_settings('pa_class_id', 'auth'))) {
                $subs = $this->core->db->get_results("SELECT s.`Subdivision_ID` as `id`, s.`Subdivision_Name` as `name` FROM `Subdivision` as `s`, `Sub_Class` AS `sc` WHERE sc.Subdivision_ID = s.Subdivision_ID AND sc.Class_ID = '" . $class_id . "' ", ARRAY_A);
                $subs_id = $this->core->db->get_col(null, 0);
                $subs_name = $this->core->db->get_col(null, 1);
            }

            $field = $this->core->get_settings('pa_field', 'auth');
            $method = $cur_pa_allow ? 'uncheck_field' : 'check_field';

            // включение/выключение поля
            $this->core->$method('User', $field);

            // включение/выключение разделов
            if ($subs_id) {
                $this->core->db->query("UPDATE Subdivision SET Checked = '" . ($cur_pa_allow ? 0 : 1) . "' WHERE Subdivision_ID IN (" . join(',', $subs_id) . ") ");
            }
        }

        // способы авторизации на сайте
        $r = $this->core->input->fetch_get_post('authtype_site_login') ? NC_AUTHTYPE_LOGIN : 0;
        $r += $this->core->input->fetch_get_post('authtype_site_hash') ? NC_AUTHTYPE_HASH : 0;
        $r += $this->core->input->fetch_get_post('authtype_site_ex') ? NC_AUTHTYPE_EX : 0;
        $r += $this->core->input->fetch_get_post('authtype_site_token') ? NC_AUTHTYPE_TOKEN : 0;

        if ($r === 0) {
            throw new Exception(NETCAT_MODULE_AUTH_ADMIN_GENERAL_AUTH_SITE_NO_METHODS_PROVIDED_ERROR);
        }

        $this->core->set_settings('authtype_site', $r, 'auth', $catalogue_id);

        // способы авторизации в админку
        $r = $this->core->input->fetch_get_post('authtype_admin_login') ? NC_AUTHTYPE_LOGIN : 0;
        $r += $this->core->input->fetch_get_post('authtype_admin_hash') ? NC_AUTHTYPE_HASH : 0;
        $r += $this->core->input->fetch_get_post('authtype_admin_token') ? NC_AUTHTYPE_TOKEN : 0;

        if ($r === 0) {
            throw new Exception(NETCAT_MODULE_AUTH_ADMIN_GENERAL_NO_METHODS_PROVIDED_ERROR);
        }

        $this->core->set_settings('authtype_admin', $r, 'auth', $catalogue_id);

        $params = array('pm_allow', 'pm_notify', 'friend_allow', 'banned_allow', 'pa_allow', 'pa_start', 'pa_currency', 'admin_https');
        // настройки
        foreach ($params as $v) {
            $this->core->set_settings($v, $this->core->input->fetch_get_post($v), 'auth', $catalogue_id);
        }
    }

    public function mail_show() {
        global $UI_CONFIG;
        $UI_CONFIG->add_settings_toolbar();

        /** @var Permission $perm */
        global $perm;
        $perm->ExitIfNotSupervisor();

        $nc_core = nc_Core::get_object();
        $settings = $this->core->get_settings('', 'auth', false, $this->core->catalogue->id());
        ?>
        <script type="text/javascript">
            body = {mail_confirm: "<?php echo nc_text_for_js(NETCAT_MODULE_AUTH_ADMIN_DEF_MAIL_CONFIRM_BODY) ?>",
                mail_confirm_after: "<?php echo nc_text_for_js(NETCAT_MODULE_AUTH_ADMIN_DEF_MAIL_CONFIRM_AFTER_BODY) ?>",
                mail_recovery: "<?php echo nc_text_for_js(NETCAT_MODULE_AUTH_ADMIN_DEF_MAIL_PASSWORDRECOVERY_BODY) ?>",
                mail_notify_admin: "<?php echo nc_text_for_js(NETCAT_MODULE_AUTH_ADMIN_DEF_MAIL_ADMIN_NOTIFY_BODY) ?>"};
            subject = {mail_confirm: "<?php echo nc_text_for_js(NETCAT_MODULE_AUTH_ADMIN_DEF_MAIL_CONFIRM_SUBJECT) ?>",
                mail_confirm_after: "<?php echo nc_text_for_js(NETCAT_MODULE_AUTH_ADMIN_DEF_MAIL_CONFIRM_AFTER_SUBJECT) ?>",
                mail_recovery: "<?php echo nc_text_for_js(NETCAT_MODULE_AUTH_ADMIN_DEF_MAIL_PASSWORDRECOVERY_SUBJECT) ?>",
                mail_notify_admin: "<?php echo nc_text_for_js(NETCAT_MODULE_AUTH_ADMIN_DEF_MAIL_ADMIN_NOTIFY_SUBJECT) ?>"};

            function recovery(name, confirmed){
                if (!confirmed) {
                    if (confirm(ncLang.WarnAuthMail)) {
                        recovery(name, 1);
                    }
                    return false;
                }

                jQuery("#" + name + "_subject").val(subject[name]);
                bodyTextArea = jQuery("#" + name + "_body");
                bodyTextArea.val(body[name]);
                if (typeof bodyTextArea.codemirror === 'function') {
                    bodyTextArea.codemirror('setValue');
                }
                //jQuery("#" + name + "_is_html").attr('checked', 'checked');
            }
        </script>
        <?php
        $catalogue_id = $this->core->catalogue->id();
        echo "<form action='admin.php' method='post' enctype='multipart/form-data'>";
        echo $this->catalogue_select_field('mail');
        echo "<fieldset>
                <legend>" . NETCAT_MODULE_AUTH_REG_CONFIRM . "</legend>
                <div style='float:left'>" . NETCAT_MODULE_AUTH_ADMIN_MAIL_SUBJECT . ":</div>
                <div style='float:right'><a href='#' onclick='recovery(\"mail_confirm\", 0); return false;'>" . NETCAT_MODULE_AUTH_RESTORE_DEF . "</a></div><br clear='all'>
                <input id='mail_confirm_subject' name='mail_confirm_subject'  type='text' style='width:100%; margin-top:5px;' value='" . htmlspecialchars(nc_array_value($settings,'mail_confirm_subject'), ENT_QUOTES) . "'>
                " . nc_admin_textarea(NETCAT_MODULE_AUTH_ADMIN_MAIL_BODY, 'mail_confirm_body', $settings, 1, 0, 'height:10em;') . "
                " . nc_admin_checkbox(NETCAT_MODULE_AUTH_ADMIN_MAIL_HTML, 'mail_confirm_is_html', $settings);
        ?>
        <?= nc_mail_attachment_form('auth_confirm_' . $catalogue_id); ?>
        <?php
        echo "<fieldset>
                <legend>" . NETCAT_MODULE_AUTH_REG_CONFIRM_AFTER . "</legend>
                <div style='float:left'>" . NETCAT_MODULE_AUTH_ADMIN_MAIL_SUBJECT . ":</div>
                <div style='float:right'><a href='#' onclick='recovery(\"mail_confirm_after\", 0); return false;'>" . NETCAT_MODULE_AUTH_RESTORE_DEF . "</a></div><br clear='all'>
                <input id='mail_confirm_after_subject' name='mail_confirm_after_subject'  type='text' style='width:100%; margin-top:5px;' value='" . htmlspecialchars(nc_array_value($settings,'mail_confirm_after_subject'), ENT_QUOTES) . "'>
                " . nc_admin_textarea(NETCAT_MODULE_AUTH_ADMIN_MAIL_BODY, 'mail_confirm_after_body', $settings, 1, 0, 'height:10em;') . "
                " . nc_admin_checkbox(NETCAT_MODULE_AUTH_ADMIN_MAIL_HTML, 'mail_confirm_after_is_html', $settings);
        ?>
        <?= nc_mail_attachment_form('auth_confirm_after_' . $catalogue_id); ?>
        <?php
        echo "</fieldset>
            <fieldset>
                <legend>" . NETCAT_MODULE_AUTH_RECOVERY . "</legend>
                <div style='float:left'>" . NETCAT_MODULE_AUTH_ADMIN_MAIL_SUBJECT . ":</div>
                <div style='float:right'><a href='#' onclick='recovery(\"mail_recovery\", 0); return false;'>" . NETCAT_MODULE_AUTH_RESTORE_DEF . "</a></div><br clear='all'>
                <input id='mail_recovery_subject' name='mail_recovery_subject'  type='text' style='width:100%; margin-top:5px;' value='" . htmlspecialchars(nc_array_value($settings,'mail_recovery_subject'), ENT_QUOTES) . "'>
                " . nc_admin_textarea(NETCAT_MODULE_AUTH_ADMIN_MAIL_BODY, 'mail_recovery_body', $settings, 1, 0, 'height:10em;') . "
                " . nc_admin_checkbox(NETCAT_MODULE_AUTH_ADMIN_MAIL_HTML, 'mail_recovery_is_html', $settings) . "
            </fieldset>";
        echo nc_mail_attachment_form('auth_recovery_' . $catalogue_id);
        echo "<fieldset>
                <legend>" . NETCAT_MODULE_AUTH_ADMIN_MAIL_NOTIFY . "</legend>
                <div style='float:left'>" . NETCAT_MODULE_AUTH_ADMIN_MAIL_SUBJECT . ":</div>
                <div style='float:right'><a href='#' onclick='recovery(\"mail_notify_admin\", 0); return false;'>" . NETCAT_MODULE_AUTH_RESTORE_DEF . "</a></div><br clear='all'>
                <input id='mail_notify_admin_subject' name='mail_notify_admin_subject'  type='text' style='width:100%; margin-top:5px;' value='" . htmlspecialchars(nc_array_value($settings,'mail_notify_admin_subject'), ENT_QUOTES) . "'>
                " . nc_admin_textarea(NETCAT_MODULE_AUTH_ADMIN_MAIL_BODY, 'mail_notify_admin_body', $settings, 1, 0, 'height:10em;') . "
                " . nc_admin_checkbox(NETCAT_MODULE_AUTH_ADMIN_MAIL_HTML, 'mail_notify_admin_is_html', $settings) . "
            </fieldset>";
        echo nc_mail_attachment_form('auth_notify_' . $catalogue_id);

        echo $this->core->token->get_input() . "

            <input type='hidden' name='view' value='mail' />
            <input type='hidden' name='act' value='save' />";
        echo "</form>";
        echo nc_admin_js_resize();

        $UI_CONFIG->actionButtons[] =
            array("id" => "submit",
                "caption" => NETCAT_MODERATION_BUTTON_CHANGE,
                "action" => "mainView.submitIframeForm('adminForm')");
    }

    public function mail_save() {
        /** @var Permission $perm */
        global $perm;
        $perm->ExitIfNotSupervisor();

        $catalogue_id = $this->core->catalogue->id();

        $params = array(
            'mail_confirm_subject',
            'mail_confirm_body',
            'mail_confirm_is_html',
            'mail_confirm_after_subject',
            'mail_confirm_after_body',
            'mail_confirm_after_is_html',
            'mail_recovery_subject',
            'mail_recovery_body',
            'mail_recovery_is_html',
            'mail_notify_admin_subject',
            'mail_notify_admin_body',
            'mail_notify_admin_is_html'
        );

        foreach ($params as $v) {
            $this->core->set_settings($v, $this->core->input->fetch_get_post($v), 'auth', $catalogue_id);
        }

        nc_mail_attachment_form_save('auth_confirm_' . $catalogue_id);
        nc_mail_attachment_form_save('auth_confirm_after_' . $catalogue_id);
        nc_mail_attachment_form_save('auth_recovery_' . $catalogue_id);
        nc_mail_attachment_form_save('auth_notify_' . $catalogue_id);
    }

    public function system_show() {
        global $UI_CONFIG;
        $UI_CONFIG->add_settings_toolbar();
        $settings = $this->core->get_settings('', 'auth', false, $this->core->catalogue->id());
        echo "
            <style>
              select {width: 98%; margin-left: 10px; }
              .nc_t {width:70%}
              .nc_t .f {width:30%}
              .nc_t .l {width:70%}
            </style>";

        echo "<form action='admin.php' method='post'>";
        echo $this->catalogue_select_field('system');
        echo "<fieldset><legend>" . NETCAT_MODULE_AUTH_ADMIN_SYSTEM_COMPONENTS_SUBS . "</legend>
            <table class='nc_t'>
            <tr>" . nc_admin_select_component('<td class="f">' . NETCAT_MODULE_AUTH_ADMIN_SYSTEM_COMPONENT_FRIENDS . '</td><td class="l">', 'friend_class_id', $settings) . "</td></tr>
            <tr>" . nc_admin_select_component('<td>' . NETCAT_MODULE_AUTH_ADMIN_SYSTEM_COMPONENT_PM . '</td><td>', 'pm_class_id', $settings) . "</td></tr>
            <tr>" . nc_admin_select_component('<td>' . NETCAT_MODULE_AUTH_ADMIN_SYSTEM_COMPONENT_PA . '</td><td>', 'pa_class_id', $settings) . "</td></tr>
            <tr>" . nc_admin_select_field('User', '<td>' . NETCAT_MODULE_AUTH_ADMIN_SYSTEM_FIELD_PA . '</td><td>', 'pa_field', $settings) . "</td></tr>
            <tr>" . nc_admin_select_subdivision('<td>' . NETCAT_MODULE_AUTH_ADMIN_SYSTEM_SUB_MATERIALS . '</td><td>', 'materials_sub_id', $settings) . "</td></tr>
            <tr>" . nc_admin_input_in_text('<td>' . NETCAT_MODULE_AUTH_ADMIN_SYSTEM_SUB_MODIFY . '</td><td style="padding-left: 10px;">%input</td>', 'modify_sub', $settings, 0, 'width: 100%;') . "</tr>
            <tr>" . nc_admin_input_in_text('<td>' . NETCAT_MODULE_AUTH_ADMIN_SYSTEM_CC_USER_LIST . '</td><td style="padding-left: 10px;">%input</td>', 'user_list_cc', $settings, 0, 'width: 100%;') . "</tr>
            </table>
            </fieldset>

            <fieldset><legend>" . NETCAT_MODULE_AUTH_ADMIN_SYSTEM_PSEUDO . "</legend>
            " . nc_admin_checkbox(NETCAT_MODULE_AUTH_ADMIN_SYSTEM_PSEUDO_ALLOW, 'pseudo_enabled', $settings) . "
            " . nc_admin_checkbox(NETCAT_MODULE_AUTH_ADMIN_SYSTEM_PSEUDO_CHECK_IP, 'pseudo_check_ip', $settings) . "
            <table>
            <tr>" . nc_admin_select_usergroup('<td>' . NETCAT_MODULE_AUTH_ADMIN_SYSTEM_PSEUDO_GROUP . '</td><td>', 'pseudo_group', $settings['pseudo_group']) . "</td></tr>
            <tr>" . nc_admin_select_field('User', '<td>' . NETCAT_MODULE_AUTH_ADMIN_SYSTEM_PSEUDO_FIELD . '</td><td>', 'pseudo_field', $settings) . "</td></tr>
            </table>
            </fieldset>

            <fieldset><legend>" . NETCAT_MODULE_AUTH_ADMIN_SYSTEM_HASH . "</legend>
            " . nc_admin_checkbox(NETCAT_MODULE_AUTH_ADMIN_SYSTEM_HASH_DELETE, 'hash_delete', $settings) . "
            " . nc_admin_input_in_text(NETCAT_MODULE_AUTH_ADMIN_SYSTEM_HASH_EXPIRE, 'hash_expire', $settings, 4) . "<br/>
            " . nc_admin_input_in_text(NETCAT_MODULE_AUTH_ADMIN_SYSTEM_HASH_DISABLED_SUBS, 'hash_disabled_subs', $settings, 12) . "
            </fieldset>

            <fieldset><legend>" . NETCAT_MODULE_AUTH_ADMIN_SYSTEM_OTHER . "</legend>
            " . nc_admin_checkbox(NETCAT_MODULE_AUTH_FORM_DISABLED, 'user_login_form_disable', $settings) . "
            " . nc_admin_input_in_text(NETCAT_MODULE_AUTH_ADMIN_SYSTEM_OTHER_ONLINE, 'online_timeleft', $settings, 4) . "<br/>
            " . nc_admin_input_in_text(NETCAT_MODULE_AUTH_ADMIN_SYSTEM_OTHER_IP, 'ip_check_level', $settings, 4) . "<br/>
            " . nc_admin_input_in_text(NETCAT_MODULE_AUTH_ADMIN_NOTIFICATION_EMAIL, 'admin_notification_email', $settings, 15) . "
            </fieldset>

            " . $this->core->token->get_input() . "
            <input type='hidden' name='view' value='system' />
            <input type='hidden' name='act' value='save' />
            </form>";

        $UI_CONFIG->actionButtons[] =
            array("id" => "submit",
                "caption" => NETCAT_MODERATION_BUTTON_CHANGE,
                "action" => "mainView.submitIframeForm('adminForm')");
    }

    public function system_save() {
        $catalogue_id = $this->core->catalogue->id();

        $params = array(
            'friend_class_id',
            'pm_class_id',
            'pa_class_id',
            'pa_field',
            'materials_sub_id',
            'modify_sub',
            'user_list_cc',
            'pseudo_enabled',
            'pseudo_check_ip',
            'pseudo_group',
            'pseudo_field',
            'hash_delete',
            'hash_expire',
            'hash_disabled_subs',
            'online_timeleft',
            'ip_check_level',
            'user_login_form_disable',
            'admin_notification_email'
        );

        foreach ($params as $v) {
            $this->core->set_settings($v, $this->core->input->fetch_get_post($v), 'auth', $catalogue_id);
        }

        $materials_sub_id = $this->core->input->fetch_get_post('materials_sub_id');
        $sub_folder_length = strlen($this->core->SUB_FOLDER);
        $url = substr(nc_folder_path($materials_sub_id), $sub_folder_length);

        $this->core->set_settings('materials_url', $url, 'auth', $catalogue_id);
    }

    //FIXME: После ввода глобального переключателя сайтов метод и его вызовы (выше) можно удалять
    /**
     * Поле выбора редактируемого сайта
     *
     * @param string $view текущее представление настроек
     *
     * @return string
     */
    protected function catalogue_select_field($view) {
        static $options;

        if (is_null($options)) {
            $options = array('' => CONTROL_USER_SELECTSITEALL);
            $catalogues = $this->core->catalogue->get_all();

            foreach ($catalogues as $id => $row) {
                $options[$id] = $id . '. ' . $row['Catalogue_Name'];
            }
        }

        $url = "admin.php?view={$view}&current_catalogue_id=";
        $field = $this->core->ui->html->select('current_catalogue_id', $options, $this->core->catalogue->id())
            ->attr('onchange', 'window.location.href="' . $url . '"+this.value');

        return "<div>{$field}</div>";
    }
}
