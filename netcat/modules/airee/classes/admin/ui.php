<?php

class nc_airee_admin_ui extends ui_config {

    protected $site_id;

    public function __construct() {
        $this->headerText = NETCAT_MODULE_AIREE_DESCRIPTION;
        $this->subheaderText = '';

        $this->locationHash = 'module.airee';

        $this->treeMode = 'modules';
        $this->treeSelectedNode = 'module-' . nc_module_check_by_keyword('airee');

        $this->actionButtons[] = array(
            'id' => 'submit_form',
            'caption' => NETCAT_MODULE_AIREE_SETTINGS_SAVE,
            'action' => 'mainView.submitIframeForm()'
        );
    }

    /**
     * @param int $site_id
     */
    public function set_site_id($site_id) {
        $this->site_id = $site_id;
        $this->locationHash .= "($site_id)";
    }

}