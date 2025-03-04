<?php

class nc_messaging_admin_ui extends ui_config {

    public function __construct($sub_header_text = NETCAT_MODULE_MESSAGING, $location_hash = "messaging",
        $tree_node = "messaging") {
        parent::__construct();

        $this->headerText = NETCAT_MODULE_MESSAGING;
        $this->subheaderText = $sub_header_text;
        $this->locationHash = "module.messaging." . $location_hash;
        $this->treeMode = "modules";
        $this->set_tree_node($tree_node);
    }

    /**
     * @param string $node_id
     *
     * @return void
     */
    public function set_tree_node($node_id) {
        $this->treeSelectedNode = "messaging-" . $node_id;
    }

    /**
     * @inheritDoc
     */
    public function set_location_hash($hash, $module_keyword = "messaging") {
        parent::set_location_hash($hash, $module_keyword);
    }

    /**
     * @inheritDoc
     */
    public function add_create_button($location, $module_keyword = "messaging") {
        parent::add_create_button($location, $module_keyword);
    }


}
