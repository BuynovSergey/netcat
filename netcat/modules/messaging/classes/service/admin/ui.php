<?php

class nc_messaging_service_admin_ui extends nc_messaging_admin_ui {

    public function __construct() {
        parent::__construct(NETCAT_MODULE_MESSAGING_PROVIDERS, "service", "service");
    }

    /**
     * @inheritDoc
     */
    public function add_submit_button($caption = NETCAT_CUSTOM_ONCE_SAVE) {
        $this->actionButtons[] = array(
            "id" => "submit_form",
            "caption" => $caption,
            "action" => "mainViewIframe.submitForm()",
        );
    }

}
