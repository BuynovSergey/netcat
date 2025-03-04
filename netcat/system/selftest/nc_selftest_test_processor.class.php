<?php

class nc_selftest_test_processor {

    /**
     * @var array
     */
    private $sites;

    /**
     * @param array $sites
     */
    public function __construct(array $sites) {
        $this->sites = $sites;
    }

    /**
     * @return bool
     */
    public function is_https_enabled() {
        return (!empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] !== "off") ||
            $_SERVER["SERVER_PORT"] == 443 ||
            !empty($_SERVER["HTTP_X_FORWARDED_PROTO"]) && $_SERVER["HTTP_X_FORWARDED_PROTO"] == "https";
    }

    /**
     * @param int $site_id
     *
     * @return bool
     */
    public function is_https_enabled_on_site($site_id) {
        return $this->sites[$site_id]["ncHTTPS"] == 1;
    }

    /**
     * @param int $site_id
     *
     * @return bool
     */
    public function is_demo_mode_enabled($site_id) {
        return $this->sites[$site_id]["ncMode"] == "demo";
    }

    /**
     * @param int $site_id
     *
     * @return array
     */
    public function get_demo_content($site_id) {
        global $MYSQL_DB_NAME;

        $tables = nc_db()->get_col(
            "SELECT `table_name` FROM information_schema.COLUMNS
             WHERE `table_schema` = '" . $MYSQL_DB_NAME . "'
             AND `table_name` REGEXP 'Message[0-9]+'
             AND `column_name` = 'ncDemoContent'"
        );

        $demo_content_subdivisions = array();

        foreach ($tables as $table) {
            $row = nc_db()->get_results(
                "SELECT s.`Subdivision_ID`, s.`Subdivision_Name`, s.`Catalogue_ID`, m.`Message_ID` 
                 FROM `" . $table . "` m 
                 JOIN `Subdivision` s ON m.`Subdivision_ID` = s.`Subdivision_ID`
                 WHERE m.`Subdivision_ID` > 0 
                 AND m.`ncDemoContent` = 1
                 AND s.`Catalogue_ID` =" . (int)$site_id,
                ARRAY_A
            );

            if ($row) {
                $demo_content_subdivisions[] = $row;
            }
        }

        return $demo_content_subdivisions;
    }

    /**
     * @return bool
     */
    public function is_install_directory_exists() {
        global $SUB_FOLDER, $DOCUMENT_ROOT;

        return file_exists($DOCUMENT_ROOT . $SUB_FOLDER . "/install");
    }

    /**
     * @return array
     */
    public function get_directories_with_wrong_permissions() {
        return $this->get_windows_directory_permissions();
    }

    /**
     * @return array
     */
    private function get_windows_directory_permissions() {
        $core = nc_core::get_object();
        $description = null;

        $path = $core->DOCUMENT_ROOT . $core->SUB_FOLDER . $core->HTTP_FILES_PATH;

        if (!is_dir($path) || !is_writable($path)) {
            $description[] = $core->HTTP_FILES_PATH;
        }

        if (!is_dir($core->TMP_FOLDER) || !is_writable($core->TMP_FOLDER)) {
            $description[] = $core->HTTP_ROOT_PATH . "tmp/";
        }

        $path = $core->DOCUMENT_ROOT . $core->SUB_FOLDER . $core->HTTP_CACHE_PATH;

        if (!is_dir($path) || !is_writable($path)) {
            $description[] = $core->HTTP_CACHE_PATH;
        }

        $path = $core->DOCUMENT_ROOT . $core->SUB_FOLDER . $core->HTTP_DUMP_PATH;

        if (!is_dir($path) || !is_writable($path)) {
            $description[] = $core->HTTP_DUMP_PATH;
        }

        return $description;
    }

    /**
     * @param int $site_id
     *
     * @return string|null
     */
    public function get_last_indexed_site_time($site_id) {
        $site_id = (int)$site_id;

        return nc_db()->get_var(
            "SELECT `LastStartTime` FROM `Search_Rule` WHERE `Catalogue_ID` = $site_id ORDER BY `Rule_ID` DESC LIMIT 1"
        );
    }

    /**
     * @return int|null
     */
    public function get_actual_patch_id() {
        $admin_notice = new nc_AdminNotice();

        return $admin_notice->update();
    }

    /**
     * @return string|null
     */
    public function get_crontab_last_launch() {
        return nc_db()->get_var(
            "SELECT FROM_UNIXTIME(`Cron_Launch`)
             FROM `CronTasks`
             WHERE FROM_UNIXTIME(`Cron_Launch`) > DATE(DATE_ADD(NOW(), INTERVAL -1 DAY))
             LIMIT 1"
        );
    }

    /**
     * @param int $site_id
     *
     * @return int
     */
    public function get_unprocessed_orders_count($site_id) {
        $order_data = nc_netshop::get_instance($site_id)->get_order_infoblock_data();
        $order_component_id = (int)$order_data["order_component_id"];

        return (int)nc_db()->get_var(
            "SELECT count(m.`Message_ID`) AS cnt FROM `Message$order_component_id` m 
             JOIN `Subdivision` s ON m.`Subdivision_ID` = s.`Subdivision_ID` 
             WHERE s.`Catalogue_ID` = $site_id AND m.`Status` = 0"
        );
    }

    /**
     * @param $site_id
     *
     * @return int
     * @throws nc_selftest_exception
     */
    public function get_unprocessed_requests_count($site_id) {
        try {
            $requests = nc_requests::get_instance($site_id);
            $request_component_id = $requests->get_request_component_id();
        } catch (nc_Exception_Class_Doesnt_Exist $e) {
            throw new nc_selftest_exception($e->getMessage(), $e->getCode(), $e);
        }

        return (int)nc_db()->get_var(
            "SELECT COUNT(m.`Message_ID`) AS cnt FROM `Message$request_component_id` m 
             JOIN `Subdivision` s ON m.`Subdivision_ID` = s.`Subdivision_ID`
             WHERE s.`Catalogue_ID` = $site_id AND m.Status IS NULL"
        );
    }

    /**
     * @param int $site_id
     *
     * @return bool
     */
    public function is_content_versioning_enabled($site_id) {
        return nc_core::get_object()->get_settings("StoreVersions", "system", false, $site_id) == "1";
    }

    /**
     * @param int $site_id
     * @param string $directive
     *
     * @return bool
     */
    public function has_robots_disallow_directive($site_id, $directive) {
        $robots_contents = $this->sites[$site_id]["Robots"];
        $line = strtok($robots_contents, "\r\n");
        $has_match = false;

        while ($line !== false) {
            if (!$line = trim($line)) {
                continue;
            }

            if (preg_match("/^\s*Disallow:(.*)/i", $line, $regexp_matches)) {
                if (isset($regexp_matches[1]) && trim($regexp_matches[1]) == $directive) {
                    $has_match = true;
                    break;
                }
            }

            $line = strtok("\r\n");
        }

        return $has_match;
    }

    /**
     * @return bool
     */
    public function has_robots_file() {
        global $DOCUMENT_ROOT;

        return file_exists($DOCUMENT_ROOT . "/robots.txt");
    }

}
