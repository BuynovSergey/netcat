<?php

class nc_captcha_admin_ui extends ui_config {

    protected $site_id;

    /**
     */
    public function __construct() {
        $this->headerText = NETCAT_MODULE_CAPTCHA;
        $this->subheaderText = '';

        $this->locationHash = "module.captcha";

        $this->treeMode = "modules";
        $this->treeSelectedNode = "module-" . nc_module_check_by_keyword('captcha');

        $this->actionButtons[] = array(
            "id" => "submit_form",
            "caption" => NETCAT_MODULE_CAPTCHA_SETTINGS_SAVE,
            "action" => "mainView.submitIframeForm()"
        );
    }

    /**
     *
     */
    public function set_site_id($site_id) {
        $this->site_id = $site_id;
        $this->locationHash .= "($site_id)";
    }

}