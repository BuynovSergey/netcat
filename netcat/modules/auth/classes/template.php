<?php

class nc_auth_template {

    /** @var nc_Core $core */
    protected $core;

    public function __construct() {
        $this->core = nc_Core::get_object();
    }

    public function get_vk() {
        global $nc_auth_vk, $REQUEST_URI;
        static $button_number = 0;


        $button_id = 'vk_login_btn_' . ++$button_number;
        $result = $js = '';

        $result .= '<style>';
        $result .=  '.nc-social-auth-container { width: 20px; height: 20px; position: relative; overflow: hidden }';
        $result .=  '.nc-social-auth-icon { width: 100%; height: auto; }';
        $result .=  '.nc-social-auth-trigger { position: absolute; left: 0; top: 0; opacity: 0.01; width: 100%; height: 100% }';
        $result .= '</style>';
        $result .= '<div class="nc-social-auth-container">';
        $result .= nc_get_svg_as_icon(
            $this->core->DOCUMENT_ROOT . $this->core->HTTP_TEMPLATE_PATH . 'icon/font_awesome/vk.svg',
            array('class' => 'nc-social-auth-icon')
        );
        if ($button_number === 1) {
            $result .= "<script src='//vkontakte.ru/js/api/openapi.js'></script>\n";
            $redirect_path = nc_module_path('auth') . '?nc_vk=1&REQUESTED_FROM=' . urlencode($REQUEST_URI);
            $js = "\nfunction nc_vk_login(is_member) { is_member ? location.href = '$redirect_path' : VK.Auth.login(nc_vk_login); }" .
                  "\nVK.init({ apiId: " . $nc_auth_vk->get_app_id() . " })";
        }

        $result .= "<div id='$button_id' class='vk_login_button nc-social-auth-trigger' onclick='nc_vk_login()'></div>\n";
        $result .= "<script>$js\nVK.UI.button('$button_id');\n</script>\n";
        $result .= '</div>';
        return $result;
    }

    public function get_fb() {
        global $nc_auth, $nc_auth_fb, $REQUEST_URI;
        $icon = nc_get_svg_as_icon(
            $this->core->DOCUMENT_ROOT . $this->core->HTTP_TEMPLATE_PATH . 'icon/font_awesome/facebook.svg',
            array('class' => 'nc-social-auth-icon')
        );
        return "
<style>
.nc-social-auth-container { width: 20px; height: 20px; position: relative; overflow: hidden }
.nc-social-auth-icon { width: 100%; height: auto; }
.nc-social-auth-trigger { position: absolute; left: 0; top: 0; opacity: 0.01; width: 100%; height: 100% }
</style>
<div class='nc-social-auth-container'>
$icon
<div id='fb-root'></div>
<fb:login-button class='nc-social-auth-trigger'>Войти</fb:login-button>
<script type='text/javascript'>
    window.fbAsyncInit = function() {
        FB.init({ appId: ".$nc_auth_fb->get_app_id().", status: false, cookie: false, xfbml: true, oauth: true});
        function updateButton(response) {
            if (response.authResponse) {
                location.href = '" . nc_module_path('auth') . "?nc_fb=1&token='+response.authResponse.accessToken+'&REQUESTED_FROM=".htmlspecialchars($REQUEST_URI, ENT_QUOTES)."';
            } else {
                var button = document.getElementById('fb-auth');
                button.onclick = function() {
                    FB.login(function(response) {
                        if (response.authResponse) {
                            location.href = '" . nc_module_path('auth') . "?nc_fb=1&token='+response.authResponse.accessToken+'&REQUESTED_FROM=".htmlspecialchars($REQUEST_URI, ENT_QUOTES)."';
                        } else {
                            //error
                        }
                    }, {scope:'email'});
                }
            }
        }
        FB.Event.subscribe('auth.statusChange', updateButton);	
    };    
    (function(d){
         var js, id = 'facebook-jssdk'; if (d.getElementById(id)) {return;}
         js = d.createElement('script'); js.id = id; js.async = true;
         js.src = \"//connect.facebook.net/ru_RU/all.js\";
         d.getElementsByTagName('head')[0].appendChild(js);
       }(document));
</script>
</div>";
    }

    public function get_twitter() {
        $icon = nc_get_svg_as_icon(
            $this->core->DOCUMENT_ROOT . $this->core->HTTP_TEMPLATE_PATH . 'icon/font_awesome/twitter.svg',
            array('class' => 'nc-social-auth-icon')
        );
        $result = '<style>';
        $result .=  '.nc-social-auth-container { width: 20px; height: 20px; position: relative; overflow: hidden }';
        $result .=  '.nc-social-auth-icon { width: 100%; height: auto; }';
        $result .=  '.nc-social-auth-trigger { position: absolute; left: 0; top: 0; opacity: 0.01; width: 100%; height: 100% }';
        $result .= '</style>';
        $result .= '<div class="nc-social-auth-container">';
        $result .= $icon;
        $result .= "<div class='twitter'><a class='nc-social-auth-trigger' href='" . nc_module_path('auth') . "?nc_twitter=1'><img src='" . nc_module_path('auth') . "images/icons/twitter.png' alt='twitter'></a></div>";
        $result .= '</div>';

        return $result;
    }

    public function get_openid() {
        $providers = unserialize($this->core->get_settings('ex_openid_providers', 'auth'));
        $result = "<form action='" . nc_module_path('auth') . "'><div class='nc_auth_openid'><div class='auth_header'>OpenID</div>";
        if (!empty($providers)) {
            foreach ($providers as $pr) {
                $result .= "<img onclick='nc_auth_openid_select(\"$pr[url]\");' class='nc_auth_openid_icon' src='$pr[imglink]' alt='$pr[name]' title='$pr[name]'>";
            }
        }
        $result .= "<input class='auth_text' type='text' name='openid_url' id='openid_url'><input class='auth_submit' type='submit' title='".NETCAT_MODULE_AUTH_ENTER."' value='".NETCAT_MODULE_AUTH_ENTER."'></div></form>";

        return $result;
    }

	public function get_oauth() {
		global $nc_auth_oauth;
		$providers = unserialize($this->core->get_settings('ex_oauth_providers', 'auth'));
		$result = "<form action='" . nc_module_path('auth') . "'><div class='nc_auth_oauth'><div class='auth_header'>OAuth</div>";
		if (!empty($providers)) {
            foreach ($providers as $pr) {
                $result .= "<a href='{$nc_auth_oauth->make_auth_url($pr['provider'], $pr['appid'])}'><img class='nc_auth_oauth_icon' src='$pr[imglink]' alt='$pr[name]' title='$pr[name]'></a>";
            }
        }
		$result .= '</div></form>';

		return $result;
	}

    /**
     * Не рекомендовано к использованию
     * @deprecated
     * @return string
     */
    public function get_token() {
        $nc_auth_token = new nc_auth_token();
        $nc_token_rand = $nc_auth_token->get_random_256();
        $_SESSION['nc_token_rand'] = $nc_token_rand;
        $r = "<div id='nc_auth_token_form' style='display: none;' class='nc_auth_token_form'>";
        $r .= "<div id='nc_auth_token_info'></div>";
        $r .= "<form id='nc_token_form'><div><select name='nc_token_login' id='nc_token_login'></select></div>";
        $r .= "<input type='hidden' value='' id='nc_token_signature'  name='nc_token_signature'/>";
        $r .= "<input class='auth_submit' onclick='nc_token_sign(); return false;' type='button' title='".NETCAT_MODULE_AUTH_BY_TOKEN."' value='".NETCAT_MODULE_AUTH_BY_TOKEN."' />";
        $r .= "</form></div>";
        $r .= "<script type='text/javascript'>

     nc_token_obj = new nc_auth_token( {'randnum' : '".$nc_token_rand."'});
     
     if ( nc_token_obj.load() ) jQuery('#nc_auth_token_form').show();
     
     function nc_token_sign ( ) {
        jQuery('#nc_auth_token_info').hide();
        err_text = { 1: '".CONTROL_AUTH_TOKEN_PLUGIN_DONT_INSTALL."', 2: '".CONTROL_AUTH_USB_TOKEN_NOT_INSERTED."',
                   3: '".CONTROL_AUTH_PIN_INCORRECT."', 4: '".CONTROL_AUTH_KEYPAIR_INCORRECT."'};

      if ( (err_num = nc_token_obj.sign()) ) {
        jQuery('#nc_auth_token_info').html(err_text[err_num]);
        jQuery('#nc_auth_token_info').show();
      }
    }
     </script>";

        return $r;
    }

    public function get_messages() {
        return NETCAT_MODULE_AUTH_NOT_NEW_MESSAGE . '<br>';
    }

    public function get_messages_new() {
        return NETCAT_MODULE_AUTH_NEW_MESSAGE . ": <a href='%msg_url'>%msg_new</a><br />";
    }

    public function get_authorized() {
        $r = "<div class='auth_block'>\n";
        $r .= "\t<div class='nc_autorized'>".NETCAT_MODULE_AUTH_HELLO.", <a class='nc_auth_profile_link' href='%profile_link'>%login</a>!<br/>\n";
        $r .= "\t%messages\n";
        $r .= "\t<a class='nc_auth_logout' href='%exit_link'>".NETCAT_MODULE_AUTH_LOGOUT."</a>\n";
        $r .= "\t</div>\n</div>\n";

        return $r;
    }

    public function get_login_save_hidden() {
        return "<input type='hidden' name='loginsave' value='1' />";
    }

    public function get_login_save($checked = 0) {
        $checked_attribute = $checked ? "checked='checked'" : '';
        $r = "<div class='auth_label'>";
        $r .= "<input type='checkbox' {$checked_attribute} class='auth_checkbox' id='loginsave' name='loginsave' value='1' />";
        $r .= "<label for='loginsave'>&nbsp;" . NETCAT_MODULE_AUTH_REMEMBER_ME . '</label>';
        $r .= '</div>';

        return $r;
    }

    public function get_auth_form_v($params) {
        $r = "<div class='auth_block'><form id='%form_id' action='%action' method='post'>";
        $r .= "<input type='hidden' name='AuthPhase' value='1'/>";
        $r .= "<input type='hidden' value='".$this->core->catalogue->get_current('Catalogue_ID')."' name='catalogue' />";
        $r .= "<input type='hidden' value='".$this->core->subdivision->get_current('Subdivision_ID')."' name='sub' />";
        $r .= "<input type='hidden' value='".$this->core->sub_class->get_current('Sub_Class_ID')."' name='cc' />";
        $r .= "<input type='hidden' name='REQUESTED_FROM' value='%requested_from'/>";
        $r .= "<input type='hidden' name='REQUESTED_BY' value='GET' />";
        $r .= "<div class='auth_header'>".$params['auth_text']."</div>";
        if ($params['invalid_captcha']) {
            $r .= "<div class='auth_error'>".$params['captcha_wrong']."</div>";
        }
        if ($params['ajax']) {
            $r .= "<div class='auth_error' id='nc_auth_captcha_error' style='display:none;'>" . $params['captcha_wrong'] . "</div>";
        }
        if ($params['ajax']) {
            $r .= "<div class='auth_error' id='nc_auth_error' style='display:none;'>" . $params['login_wrong'] . "</div> ";
        }
        $r .= "<div class='auth_label'><b>".$params['login_text']."</b>".( $params['hide_register_link'] ? "" : " (<a href='%register_link'>".$params['reg_text']."</a>)")."</div>";
        $r .= "<input class='auth_text' type='text' name='AUTH_USER' />";
        $r .= "<div class='auth_label'><b>".$params['pass_text']."</b> ".( $params['hide_recovery_pass'] ? "" : " (<a href='%recovery_link'>".$params['recovery_text']."</a>)")."</div>";
        $r .= "<input class='auth_text' type='password' name='AUTH_PW' />";
        $r .= "%login_save";
        if ($params['need_captcha']) {
            $r .= "%captcha";
            $r .= "<div class='auth_label'>".NETCAT_MODERATION_CAPTCHA.":</div>";
            $r .= "<input class='auth_text' type='text' name='nc_captcha_code' size='10' />";
        }
        $r .= "<div><input type='submit' title='".htmlspecialchars($params['submit_name'])."' value='".htmlspecialchars($params['submit_name'])."' class='auth_submit'></div>";
        $r .= "</form>";
        $r .= "%vk_form %fb_form %twitter_form %openid_form %oauth_form %token_form";
        $r .= "<div style='clear: both;'></div></div>";

        return $r;
    }

    public function get_auth_form_t($params) {
        $r = "<div class='auth_block'><form id='%form_id' action='%action' method='post'>";
        $r .= "<input type='hidden' name='AuthPhase' value='1'/>";
        $r .= "<input type='hidden' value='".$this->core->catalogue->get_current('Catalogue_ID')."' name='catalogue' />";
        $r .= "<input type='hidden' value='".$this->core->subdivision->get_current('Subdivision_ID')."' name='sub' />";
        $r .= "<input type='hidden' value='".$this->core->sub_class->get_current('Sub_Class_ID')."' name='cc' />";
        $r .= "<input type='hidden' name='REQUESTED_FROM' value='%requested_from' />";
        $r .= "<table>";
        if ($params['ajax']) {
            $r .= "<tr><td colspan='2'><div class='auth_error' id='nc_auth_captcha_error' style='display:none;'>" . $params['captcha_wrong'] . "</div>";
        }
        if ($params['ajax']) {
            $r .= "<div class='auth_error' id='nc_auth_error' style='display:none;'>" . $params['login_wrong'] . "</div></td></tr>";
        }

        $r .=" <tr><td>".$params['login_text'].":</td><td><input type='text' name='AUTH_USER' /></td></tr>";
        $r .= "<tr><td>".$params['pass_text'].":</td><td><input type='password' name='AUTH_PW' /></td></tr>";


        if ($params['need_captcha']) {
            $r .= "<tr><td rowspan='2' style='vertical-align: bottom;'>".NETCAT_MODERATION_CAPTCHA_SMALL."</td>";
            $r .= "<td>%captcha</td></tr><tr><td><input class='auth_text' type='text' name='nc_captcha_code' size='10' /></td></tr>";
        }

        $r .= "<tr><td /><td>%login_save</td></tr>";

        $r .= "<tr><td /><td><input class='auth_submit' type='submit' title='".htmlspecialchars($params['submit_name'])."' value='".htmlspecialchars($params['submit_name'])."'></td></tr>";

        if (!$params['hide_register_link'] || !$params['hide_recovery_pass']) {
            $r .= "<tr>";
            if (!$params['hide_register_link']) {
                $r .= "<td><a href='%register_link'>" . $params['reg_text'] . "</a></td>";
            }
            if (!$params['hide_recovery_pass']) {
                $r .= "<td><a href='%recovery_link'>" . $params['recovery_text'] . "</a></td>";
            }
            $r .= "</tr>";
        }

        if ($params['vk_enabled'] || $params['fb_enabled'] || $params['twitter_enabled']) {
            $r .= "<tr><td colspan='2'>%vk_form %fb_form %twitter_form <div style='clear: both;'></div></td></tr>";
        }

        if ($params['openid_enabled']) {
            $r .= "<tr><td colspan='2'>%openid_form <div style='clear: both;'></div></td></tr>";
        }

	    if ($params['oauth_enabled']) {
		    $r .= "<tr><td colspan='2'>%oauth_form <div style='clear: both;'></div></td></tr>";
	    }

        $r .= "</table></form></div>";

        return $r;
    }

    public function get_auth_form_h($params) {
        $r = "<div class='auth_block'><form id='%form_id' action='%action' method='post'>";
        $r .= "<input type='hidden' name='AuthPhase' value='1'/>";
        $r .= "<input type='hidden' value='".$this->core->catalogue->get_current('Catalogue_ID')."' name='catalogue' />";
        $r .= "<input type='hidden' value='".$this->core->subdivision->get_current('Subdivision_ID')."' name='sub' />";
        $r .= "<input type='hidden' value='".$this->core->sub_class->get_current('Sub_Class_ID')."' name='cc' />";
        $r .= "<input type='hidden' name='REQUESTED_FROM' value='%requested_from'/>";
        $r .= "<span class='auth_header'>".$params['auth_text']."</span>";
        if ($params['invalid_captcha']) {
            $r .= "<div class='auth_error'>".$params['captcha_wrong']."</div>";
        }
        $r .= "<div class='block_width'>";
        $r .= "<div class='column_left'><b>".$params['login_text']."</b>".( $params['hide_register_link'] ? "" : " (<a href='%register_link'>".$params['reg_text']."</a>)")."</div>";
        $r .= "<div class='column_center'><b>".$params['pass_text']."</b> ".( $params['hide_recovery_pass'] ? "" : " (<a href='%recovery_link'>".$params['recovery_text']."</a>)")."</div>";
        $r .= "<div class='column_right'></div>";

        $r .= "<div class='block_width no_margin'>";
        $r .= "<div class='column_left'><input class='form_text' type='text' name='AUTH_USER' />";
        $r .= "<div>%login_save</div>";
        $r .= "</div>";

        $r .= "<div class='column_center'><input class='form_text' type='password' name='AUTH_PW' /></div>";
        $r .= "<div class='column_right'><input type='submit' class='form_submit' title='".htmlspecialchars($params['submit_name'])."' value='".htmlspecialchars($params['submit_name'])."' /></div>";
        $r .= "</div>";

        $r .= "</form></div>";

        return $r;
    }

    public function get_user_login_form_default() {
        $res = <<<NETCAT_FORM
".(\$nc_core->catalogue->get_current('Title_Sub_ID') == \$sub ?" <div class='type_block'>
<h2>".NETCAT_MODULE_AUTH_AUTHORIZATION."</h2>
" : "")."
<form method='post' action='" . nc_module_path('auth') . "'>
  <input type='hidden' name='AuthPhase' value='1' />
  <input type='hidden' name='REQUESTED_FROM' value='".htmlspecialchars(\$REQUESTED_FROM, ENT_QUOTES)."' />
  <input type='hidden' name='REQUESTED_BY' value='".htmlspecialchars(\$REQUESTED_BY, ENT_QUOTES)."' />
  <input type='hidden' name='catalogue' value='".\$catalogue."' />
  <input type='hidden' name='sub' value='".\$sub."' />
  <input type='hidden' name='cc' value='".\$cc."' />
  <table cellpadding='4' cellspacing='0' border='0'>
    <tr>
      <td>".NETCAT_MODULE_AUTH_LOGIN.":</td>
      <td><input type='text' name='AUTH_USER' size='32' maxlength='32' value='".htmlspecialchars(\$AUTH_USER, ENT_QUOTES)."' /></td>
    </tr>
    <tr>
      <td>".NETCAT_MODULE_AUTH_PASSWORD.":</td>
      <td><input type='password' name='AUTH_PW' size='32' maxlength='32' /></td>
    </tr>
    ".(\$ADMIN_AUTHTYPE == "manual" && \$AUTHORIZATION_TYPE == "cookie" ? "<tr><td>&nbsp;</td><td><input type='checkbox' name='loginsave' />".NETCAT_MODULE_AUTH_SAVE."</td></tr>" : "")."
    <tr>
      <td>&nbsp;</td>
      <td>
        <input type='submit' name='submit' title='".NETCAT_MODULE_AUTH_BUT_AUTORIZE."' value='".NETCAT_MODULE_AUTH_BUT_AUTORIZE."' />
        ".(\$AuthPhase && \$REQUEST_URI != \$REQUESTED_FROM ? "<br/><br/><a href='".\$REQUESTED_FROM."'>".NETCAT_MODULE_AUTH_BUT_BACK."</a>" : "")."
      </td>
    </tr>
  </table>
</form>
".(\$nc_core->catalogue->get_current('Title_Sub_ID') == \$sub ?" </div>" : "")."
NETCAT_FORM;
        return $res;
    }

    public function get_user_login_form_default_fs() {
        $res = <<<NETCAT_FORM
<?= (\$nc_core->catalogue->get_current('Title_Sub_ID') == \$sub ? "<div class='type_block'>
<h2>".NETCAT_MODULE_AUTH_AUTHORIZATION."</h2>
" : "") ?>
<form method='post' action='<?= nc_module_path('auth'); ?>'>
  <input type='hidden' name='AuthPhase' value='1' />
  <input type='hidden' name='REQUESTED_FROM' value='<?= htmlspecialchars(\$REQUESTED_FROM, ENT_QUOTES) ?>' />
  <input type='hidden' name='REQUESTED_BY' value='<?= htmlspecialchars(\$REQUESTED_BY, ENT_QUOTES) ?>' />
  <input type='hidden' name='catalogue' value='<?= \$catalogue ?>' />
  <input type='hidden' name='sub' value='<?= \$sub ?>' />
  <input type='hidden' name='cc' value='<?= \$cc ?>' />
  <table cellpadding='4' cellspacing='0' border='0'>
    <tr>
      <td><?= NETCAT_MODULE_AUTH_LOGIN ?>:</td>
      <td><input type='text' name='AUTH_USER' size='32' maxlength='32' value='<?= htmlspecialchars(\$AUTH_USER, ENT_QUOTES) ?>' /></td>
    </tr>
    <tr>
      <td><?= NETCAT_MODULE_AUTH_PASSWORD ?>:</td>
      <td><input type='password' name='AUTH_PW' size='32' maxlength='32' /></td>
    </tr>
    <?= (\$ADMIN_AUTHTYPE == "manual" && \$AUTHORIZATION_TYPE == "cookie" ? "<tr><td>&nbsp;</td><td><input type='checkbox' name='loginsave' />".NETCAT_MODULE_AUTH_SAVE."</td></tr>" : "") ?>
    <tr>
      <td>&nbsp;</td>
      <td>
        <input type='submit' name='submit' title='<?= NETCAT_MODULE_AUTH_BUT_AUTORIZE ?>' value='<?= NETCAT_MODULE_AUTH_BUT_AUTORIZE ?>' />
        <?= (\$AuthPhase && \$REQUEST_URI != \$REQUESTED_FROM ? "<br/><br/><a href='".\$REQUESTED_FROM."'>".NETCAT_MODULE_AUTH_BUT_BACK."</a>" : "") ?>
      </td>
    </tr>
  </table>
</form>
<?= (\$nc_core->catalogue->get_current('Title_Sub_ID') == \$sub ? " </div>" : "") ?>
NETCAT_FORM;
        return $res;
    }

    public function get_change_password_form_default() {
        $res = <<<NETCAT_FORM
<form method='post' action='" . nc_module_path('auth') . "password_change.php'>
  <input type='hidden' name='catalogue' value='".\$catalogue."' />
  <input type='hidden' name='sub' value='".\$sub."' />
  <input type='hidden' name='cc' value='".\$cc."' />
  <input type='hidden' name='post' value='1' />
  <input type='hidden' name='uid' value='".(int)\$uid."' />
  <input type='hidden' name='ucc' value='".htmlspecialchars(\$ucc, ENT_QUOTES)."' />
  <input type='hidden' name='REQUESTED_FROM' value='".htmlspecialchars(\$REQUESTED_FROM, ENT_QUOTES)."' />
  <input type='hidden' name='REQUESTED_BY' value='".htmlspecialchars(\$REQUESTED_BY, ENT_QUOTES)."' />
  ".NETCAT_MODULE_AUTH_CP_NEWPASS.":<br/><input type='password' name='Password1' size='32' maxlength='32' />
  <br/><br/>
  ".NETCAT_MODULE_AUTH_CP_CONFIRM.":<br/><input type='password' name='Password2' size='32' maxlength='32' />
  <br/><br/>
  <input type='submit' name='submit' title='".NETCAT_MODULE_AUTH_CP_DOBUTT."' value='".NETCAT_MODULE_AUTH_CP_DOBUTT."' />
</form>
NETCAT_FORM;
        return $res;
    }

    public function get_change_password_form_default_fs() {
        $res = <<<NETCAT_FORM
<form method='post' action='<?= nc_module_path('auth'); ?>password_change.php'>
  <input type='hidden' name='catalogue' value='<?= \$catalogue ?>' />
  <input type='hidden' name='sub' value='<?= \$sub ?>' />
  <input type='hidden' name='cc' value='<?= \$cc ?>' />
  <input type='hidden' name='post' value='1' />
  <input type='hidden' name='uid' value='<?= (int)\$uid ?>' />
  <input type='hidden' name='ucc' value='<?= htmlspecialchars(\$ucc, ENT_QUOTES) ?>' />
  <input type='hidden' name='REQUESTED_FROM' value='<?= htmlspecialchars(\$REQUESTED_FROM, ENT_QUOTES) ?>' />
  <input type='hidden' name='REQUESTED_BY' value='<?= htmlspecialchars(\$REQUESTED_BY, ENT_QUOTES) ?>' />
  <?= NETCAT_MODULE_AUTH_CP_NEWPASS ?>:<br/><input type='password' name='Password1' size='32' maxlength='32' />
  <br/><br/>
  <?= NETCAT_MODULE_AUTH_CP_CONFIRM ?>:<br/><input type='password' name='Password2' size='32' maxlength='32' />
  <br/><br/>
  <input type='submit' name='submit' title='<?= NETCAT_MODULE_AUTH_CP_DOBUTT ?>' value='<?= NETCAT_MODULE_AUTH_CP_DOBUTT ?>' />
</form>
NETCAT_FORM;
        return $res;
    }

    public function get_recovery_password_form_default() {
        $res = <<<NETCAT_FORM
<form method='post'>
  <input type='hidden' name='catalogue' value='".\$catalogue."' />
  <input type='hidden' name='sub' value='".\$sub."' />
  <input type='hidden' name='cc' value='".\$cc."' />
  <input type='hidden' name='post' value='1' />
  ".NETCAT_MODULE_AUTH_PRF_LOGIN.":<br/>
  <input type='text' name='Login' size='32' maxlength='32' value='".htmlspecialchars(\$Login, ENT_QUOTES)."' />
  <br/><br/>
  ".NETCAT_MODULE_AUTH_PRF_EMAIL.":<br/>
  <input type='text' name='Email' size='32' maxlength='32' value='".htmlspecialchars(\$Email, ENT_QUOTES)."' />
  <br/><br/>
  <input type=submit value='".NETCAT_MODULE_AUTH_PRF_DOBUTT."' />
</form>
NETCAT_FORM;
        return $res;
    }

    public function get_recovery_password_form_default_fs() {
        $res = <<<NETCAT_FORM
<form method='post'>
  <input type='hidden' name='catalogue' value='<?= \$catalogue ?>' />
  <input type='hidden' name='sub' value='<?= \$sub ?>' />
  <input type='hidden' name='cc' value='<?= \$cc ?>' />
  <input type='hidden' name='post' value='1' />
  <?= NETCAT_MODULE_AUTH_PRF_LOGIN ?>:<br/>
  <input type='text' name='Login' size='32' maxlength='32' value='<?= htmlspecialchars(\$Login, ENT_QUOTES) ?>' />
  <br/><br/>
  <?= NETCAT_MODULE_AUTH_PRF_EMAIL ?>:<br/>
  <input type='text' name='Email' size='32' maxlength='32' value='<?= htmlspecialchars(\$Email, ENT_QUOTES) ?>' />
  <br/><br/>
  <input type=submit value='<?= NETCAT_MODULE_AUTH_PRF_DOBUTT ?>' />
</form>
NETCAT_FORM;
        return $res;
    }
}
