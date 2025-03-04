<?php

class nc_content_changer_site_instruction extends nc_content_changer_instruction {

    public static $entity_type = "site";


    /**
     * @param string $action
     * @param int|string|null $site_keyword
     * @param array $dataset
     */
    public function __construct($action = "update", $site_keyword = null, $dataset = array()) {
        parent::__construct($action, "", $dataset, array());
        $this->site_keyword = $site_keyword;
    }


}
