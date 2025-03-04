<?php

class nc_routing_admin_ui extends ui_config {

    protected $_site_id;

    /**
     * @param $tree_node
     * @param $sub_header_text
     */
    public function __construct($tree_node, $sub_header_text) {
        $this->headerText = NETCAT_MODULE_ROUTING;
        $this->subheaderText = $sub_header_text;

        $this->locationHash = "module.routing.$tree_node";

        $this->treeMode = "modules";
        $this->treeSelectedNode = "routing-$tree_node";
    }

    /**
     * @param $site_id
     */
    public function set_site_id($site_id) {
        $this->_site_id = $site_id;
    }

    /**
     *
     */
    public function add_submit_button($caption = NETCAT_MODULE_ROUTING_BUTTON_SAVE) {
        $this->actionButtons[] = array(
            "id" => "submit_form",
            "caption" => $caption,
            "action" => "mainView.submitIframeForm()"
        );
    }

    public function add_create_button($location, $module_keyword = null) {
        $this->actionButtons[] = array(
            "id" => "add",
            "caption" => NETCAT_MODULE_ROUTING_BUTTON_ADD,
            "location" => "#module.routing.$location",
            "align" => "left");
    }

    /**
     * Для форм редактирования
     */
    public function add_save_and_cancel_buttons($save_button_caption = NETCAT_MODULE_ROUTING_BUTTON_SAVE) {
        $this->actionButtons[] = array(
            "id" => "history_back",
            "caption" => NETCAT_MODULE_ROUTING_BUTTON_BACK,
            "action" => "history.back(1)",
            "align" => "left"
        );
        $this->add_submit_button($save_button_caption);
    }

    public function set_location_hash($hash, $module_keyword = null) {
        $this->locationHash = "module.routing.$hash";
    }

}
