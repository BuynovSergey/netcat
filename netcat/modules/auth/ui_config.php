<?php

/* $Id: ui_config.php 4368 2011-03-29 20:28:32Z denis $ */
if (!class_exists("nc_System")) die("Unable to load file.");

/**
 * Класс для облегчения формирования UI в модулях
 */
class ui_config_module_auth extends ui_config_module {

    public $headerText = NETCAT_MODULE_AUTH;

    public function __construct($view, $params) {

        $this->tabs[] = array(
                'id' => "info",
                'caption' => NETCAT_MODULE_AUTH_ADMIN_TAB_INFO,
                'location' => "module.auth.info"
        );
        $this->tabs[] = array(
                'id' => "reg",
                'caption' => NETCAT_MODULE_AUTH_ADMIN_TAB_REGANDAUTH,
                'location' => "module.auth.classic"
        );
        $this->tabs[] = array(
                'id' => "settings",
                'caption' => NETCAT_MODULE_AUTH_ADMIN_TAB_SETTINGS,
                'location' => "module.auth.general"
        );

        $this->activeTab = $view;
        $this->locationHash = "module.auth.".$view.($params ? "(".$params.")" : "");
        $this->treeMode = "modules";

        $module_settings = nc_Core::get_object()->modules->get_by_keyword('auth');
        $this->treeSelectedNode = "module-".$module_settings['Module_ID'];
    }

    public function add_reg_toolbar() {
        $this->toolbar[] = array(
                'id' => "classic",
                'caption' => NETCAT_MODULE_AUTH_ADMIN_TAB_CLASSIC,
                'location' => "module.auth.classic",
                'group' => "admin"
        );
        $this->toolbar[] = array(
                'id' => "ex",
                'caption' => NETCAT_MODULE_AUTH_ADMIN_TAB_EXAUTH,
                'location' => "module.auth.ex",
                'group' => "admin"
        );
        list($active_button) = explode("_", $this->activeTab);
        $this->activeToolbarButtons[] = $active_button;
        $this->activeTab = 'reg';
    }

    public function add_settings_toolbar() {
        $this->toolbar[] = array(
                'id' => "general",
                'caption' => NETCAT_MODULE_AUTH_ADMIN_TAB_GENERAL,
                'location' => "module.auth.general",
                'group' => "admin"
        );
        $this->toolbar[] = array(
                'id' => "templates",
                'caption' => NETCAT_MODULE_AUTH_ADMIN_TAB_TEMPLATES,
                'location' => "module.auth.templates",
                'group' => "admin"
        );
        $this->toolbar[] = array(
                'id' => "mail",
                'caption' => NETCAT_MODULE_AUTH_ADMIN_TAB_MAIL,
                'location' => "module.auth.mail",
                'group' => "admin"
        );
        $this->toolbar[] = array(
                'id' => "system",
                'caption' => NETCAT_MODULE_AUTH_ADMIN_TAB_SYSTEM,
                'location' => "module.auth.system",
                'group' => "admin"
        );
        list($active_button) = explode("_", $this->activeTab);
        $this->activeToolbarButtons[] = $active_button;
        $this->activeTab = 'settings';
    }

}
?>