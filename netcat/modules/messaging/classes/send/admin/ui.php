<?php

class nc_messaging_send_admin_ui extends nc_messaging_admin_ui {
    public function __construct($sub_header_text = NETCAT_MODULE_MESSAGING_SERVICE_MANUAL_SEND, $location_hash = "send",
        $tree_node = "send") {
        parent::__construct($sub_header_text, $location_hash, $tree_node);
    }


    /**
     * @param string $save_button_caption
     *
     * @return void
     */
    public function add_save_and_cancel_buttons($save_button_caption = NETCAT_CUSTOM_ONCE_SAVE) {
        $this->actionButtons[] = array(
            "id" => "submit_form",
            "caption" => NETCAT_SETTINGS_EDITOR_SEND,
            "action" => "mainViewIframe.submitForm()",
        );

        $this->actionButtons[] = array(
            "id" => "submit_form",
            "align" => "left",
            "caption" => NETCAT_MODERATION_CLEAR_FORM,
            "action" => "mainView.resetIframeForm()",
        );
    }


}
