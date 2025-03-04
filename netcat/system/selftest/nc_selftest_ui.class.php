<?php

class nc_selftest_ui extends ui_config {


    /**
     * @inheritDoc
     */
    public function __construct() {
        parent::__construct();
        $this->treeMode = "sitemap";
        $this->headerText = TOOLS_SELFTEST_HEADING;
    }

}
