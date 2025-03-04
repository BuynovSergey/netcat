<?php

class nc_requests_list_admin_ui extends nc_requests_admin_ui {

    /**
     * @param $catalogue_id
     * @param string $current_action
     */
    public function __construct($catalogue_id, $current_action = "index") {
        parent::__construct('list', '');

        $this->catalogue_id = $catalogue_id;
        $this->activeTab = $current_action;
        $this->locationHash .= "({$catalogue_id})";
    }

}