<?php


final class nc_selftest_meta {

    const TEST_STATUS_SUCCESS = "success";
    const TEST_STATUS_CRITICAL = "critical";
    const TEST_STATUS_RECOMMENDED = "recommended";
    const TEST_STATUS_ERROR = "error";

    /**
     * @var string[]
     */
    public static $STATUS_LOCALES_MAPPING = array(
        nc_selftest_meta::TEST_STATUS_SUCCESS => TOOLS_SELFTEST_HAS_NO_PROBLEMS,
        nc_selftest_meta::TEST_STATUS_CRITICAL => TOOLS_SELFTEST_STATUS_CRITICAL,
        nc_selftest_meta::TEST_STATUS_RECOMMENDED => TOOLS_SELFTEST_STATUS_RECOMMENDED,
        nc_selftest_meta::TEST_STATUS_ERROR => TOOLS_SELFTEST_STATUS_ERROR,
    );

    /**
     * @var array[]
     */
    private static $TESTS_MAPPING = array(
        "check_https" => array(
            "label" => TOOLS_SELFTEST_HTTPS_CAPTION,
            "failed_short_description" => TOOLS_SELFTEST_HTTPS_FAIL_DESCRIPTION,
            "failed_description" => TOOLS_SELFTEST_HTTPS_FAIL_DESCRIPTION,
            "failed_status" => self::TEST_STATUS_CRITICAL,
            "success_description" => TOOLS_SELFTEST_HTTPS_TEST_SUCCESS,
        ),

        "check_demo_site" => array(
            "label" => TOOLS_SELFTEST_DEMO_MODE_CAPTION,
            "failed_short_description" => TOOLS_SELFTEST_DEMO_MODE_FAIL_DESCRIPTION_SHORT,
            "failed_description" => TOOLS_SELFTEST_DEMO_MODE_FAIL_DESCRIPTION,
            "failed_status" => self::TEST_STATUS_RECOMMENDED,
            "success_description" => TOOLS_SELFTEST_DEMO_MODE_TEST_SUCCESS,
        ),

        "check_demo_content" => array(
            "label" => TOOLS_SELFTEST_DEMO_CONTENT_CAPTION,
            "failed_short_description" => TOOLS_SELFTEST_DEMO_CONTENT_FAIL_DESCRIPTION_SHORT,
            "failed_description" => TOOLS_SELFTEST_DEMO_CONTENT_FAIL_DESCRITION,
            "failed_status" => self::TEST_STATUS_RECOMMENDED,
            "success_description" => TOOLS_SELFTEST_DEMO_CONTENT_TEST_SUCCESS,
        ),

        "check_install_directory" => array(
            "shared" => true,
            "label" => TOOLS_SELFTEST_INSTALL_DIRECTORY_CAPTION,
            "failed_short_description" => TOOLS_SELFTEST_INSTALL_DIRECTORY_FAIL_DESCRIPTION_SHORT,
            "failed_description" => TOOLS_SELFTEST_INSTALL_DIRECTORY_FAIL_DESCRIPTION,
            "failed_status" => self::TEST_STATUS_CRITICAL,
            "success_description" => TOOLS_SELFTEST_INSTALL_DIRECTORY_TEST_SUCCESS,
        ),

        "check_directory_permissions" => array(
            "shared" => true,
            "label" => TOOLS_SELFTEST_PERMISSIONS_CAPTION,
            "failed_short_description" => TOOLS_SELFTEST_PERMISSIONS_FAIL_DESCRIPTION_SHORT,
            "failed_description" => TOOLS_SELFTEST_PERMISSIONS_FAIL_DESCRIPTION,
            "failed_status" => self::TEST_STATUS_CRITICAL,
            "success_description" => TOOLS_SELFTEST_PERMISSIONS_TEST_SUCCESS,
        ),

        "check_indexes" => array(
            "label" => TOOLS_SELFTEST_INDEXES_CAPTION,
            "failed_short_description" => TOOLS_SELFTEST_INDEXES_FAIL_DESCRIPTION_SHORT,
            "failed_description" => TOOLS_SELFTEST_INDEXES_FAIL_DESCRIPTION,
            "failed_status" => self::TEST_STATUS_RECOMMENDED,
            "success_description" => TOOLS_SELFTEST_INDEXES_TEST_SUCCESS,
        ),

        "check_updates" => array(
            "shared" => true,
            "label" => TOOLS_SELFTEST_UPDATES_CAPTION,
            "failed_short_description" => TOOLS_SELFTEST_UPDATES_FAIL_DESCRIPTION_SHORT,
            "failed_description" => TOOLS_SELFTEST_UPDATES_FAIL_DESCRIPTION,
            "failed_status" => self::TEST_STATUS_CRITICAL,
            "success_description" => TOOLS_SELFTEST_UPDATES_TEST_SUCCESS,
        ),

        "check_crontab" => array(
            "shared" => true,
            "label" => TOOLS_SELFTEST_CRONTAB_CAPTION,
            "failed_short_description" => TOOLS_SELFTEST_CRONTAB_FAIL_DESCRIPTION_SHORT,
            "failed_description" => TOOLS_SELFTEST_CRONTAB_FAIL_DESCRIPTION,
            "failed_status" => self::TEST_STATUS_RECOMMENDED,
            "success_description" => TOOLS_SELFTEST_CRONTAB_TEST_SUCCESS,
        ),

        "check_requests" => array(
            "label" => TOOLS_SELFTEST_REQUESTS_CAPTION,
            "failed_short_description" => TOOLS_SELFTEST_REQUESTS_FAIL_DESCRIPTION_SHORT,
            "failed_description" => TOOLS_SELFTEST_REQUESTS_FAIL_DESCRIPTION,
            "failed_status" => self::TEST_STATUS_CRITICAL,
            "success_description" => TOOLS_SELFTEST_REQUESTS_TEST_SUCCESS,
            "required_module" => "requests",
        ),

        "check_orders" => array(
            "label" => TOOLS_SELFTEST_NETSHOP_ORDERS_CAPTION,
            "failed_short_description" => TOOLS_SELFTEST_NETSHOP_ORDERS_FAIL_DESCRIPTION_SHORT,
            "failed_description" => TOOLS_SELFTEST_NETSHOP_ORDERS_FAIL_DESCRIPTION,
            "failed_status" => self::TEST_STATUS_CRITICAL,
            "success_description" => TOOLS_SELFTEST_NETSHOP_ORDERS_TEST_SUCCESS,
            "required_module" => "netshop",
        ),

        "check_content_versioning" => array(
            "label" => TOOLS_SELFTEST_CONTENT_VERSIONING_CAPTION,
            "failed_short_description" => TOOLS_SELFTEST_CONTENT_VERSIONING_FAIL_DESCRIPTION_SHORT,
            "failed_description" => TOOLS_SELFTEST_CONTENT_VERSIONING_FAIL_DESCRIPTION,
            "failed_status" => self::TEST_STATUS_RECOMMENDED,
            "success_description" => TOOLS_SELFTEST_CONTENT_VERSIONING_TEST_SUCCESS,
        ),

        "check_seo_robots" => array(
            "label" => TOOLS_SEFLTEST_SEO_ROBOTS_CAPTION,
            "failed_short_description" => TOOLS_SEFLTEST_SEO_ROBOTS_FAIL_DESCRIPTION_SHORT,
            "failed_description" => TOOLS_SEFLTEST_SEO_ROBOTS_DESCRIPTION,
            "failed_status" => self::TEST_STATUS_RECOMMENDED,
            "success_description" => TOOLS_SELFTEST_SEO_ROBOTS_TEST_SUCCESS,
        ),
    );

    /**
     * @param string|null $key
     *
     * @return array[]
     */
    public static function get_tests_meta($key = null) {
        $map = self::$TESTS_MAPPING;

        return $key ? nc_array_value($map, $key) : $map;
    }
}
