<?php

class nc_landing_admin_ui extends ui_config {

    /**
     */
    public function __construct() {
        $this->headerText = NETCAT_MODULE_LANDING;
        $this->subheaderText = '';

        $this->locationHash = "module.landing";

        $this->treeMode = "modules";
        $this->treeSelectedNode = "module-" . nc_module_check_by_keyword('landing');
    }

}