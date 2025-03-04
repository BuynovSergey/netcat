<?php

class nc_requests_admin_ui extends ui_config {

    /**
     * @param $tree_node
     * @param $sub_header_text
     */
    public function __construct($tree_node, $sub_header_text) {
        $this->headerText = NETCAT_MODULE_REQUESTS;
        $this->subheaderText = $sub_header_text;

        $this->locationHash = "module.requests.$tree_node";

        $this->treeMode = "modules";
        $this->treeSelectedNode = "requests-$tree_node";
    }

    public function set_location_hash($hash, $module_keyword = null) {
        $this->locationHash = "module.requests.$hash";
    }

}
