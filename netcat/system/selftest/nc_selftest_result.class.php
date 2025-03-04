<?php

final class nc_selftest_result {

    /**
     * @var array
     */
    private $test_meta;

    /**
     * @var bool
     */
    private $is_passed;

    /**
     * @var array
     */
    private $result;

    /**
     * @param array|string $test_meta_or_test_name
     * @param bool $assertion
     */
    public function __construct($test_meta_or_test_name, $assertion) {
        $this->test_meta = is_string($test_meta_or_test_name) ?
            nc_selftest_meta::get_tests_meta($test_meta_or_test_name) :
            $test_meta_or_test_name;

        $this->is_passed = $assertion;
        $this->fill_result();
    }

    /**
     * @param string $test_name
     *
     * @return nc_selftest_result
     */
    public static function create_error_result($test_name) {
        $result = new nc_selftest_result($test_name, false);
        $result->set_test_crashed();

        return $result;
    }

    /**
     * @return array
     */
    public function get_result() {
        return $this->result;
    }

    /**
     * @return void
     */
    public function set_test_crashed() {
        $this->result["error"] = true;
        $this->set_status(nc_selftest_meta::TEST_STATUS_ERROR);
    }

    /**
     * @param string $site_name
     */
    public function set_site_name($site_name) {
        $this->result["site_name"] = $site_name;
    }

    public function set_description($description) {
        $this->result["description"] = $description;
    }

    /**
     * @param string $status
     *
     * @return void
     */
    public function set_status($status) {
        $this->result["status"] = $status;
    }

    /**
     * @return void
     */
    private function fill_result() {
        $status = $this->is_passed ? nc_selftest_meta::TEST_STATUS_SUCCESS : $this->test_meta["failed_status"];

        $this->result["shared"] = nc_array_value($this->test_meta, "shared", false);
        $this->result["passed"] = $this->is_passed;
        $this->result["status"] = $status;
        $this->result["status_i18n"] = nc_selftest_meta::$STATUS_LOCALES_MAPPING[$status];
        $this->result["label"] = $this->test_meta["label"];

        $this->fill_description();
    }

    /**
     * @return void
     */
    private function fill_description() {
        $this->result["short_description"] = $this->result["status"] == nc_selftest_meta::TEST_STATUS_SUCCESS ?
            nc_array_value($this->test_meta, "success_description", TOOLS_SELFTEST_DONE_SUCCESSFULLY) :
            $this->test_meta["failed_short_description"];

        $this->result["description"] = $this->result["status"] == nc_selftest_meta::TEST_STATUS_SUCCESS ?
            nc_array_value($this->test_meta, "success_description", TOOLS_SELFTEST_DONE_SUCCESSFULLY) :
            $this->test_meta["failed_description"];
    }


}
