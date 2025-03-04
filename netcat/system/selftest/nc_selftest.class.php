<?php

final class nc_selftest {

    /**
     * @var int
     */
    private $current_step;

    /**
     * @var string
     */
    private $current_test;

    /**
     * @var array
     */
    private $result;

    /**
     * @var array[]
     */
    private $sites;

    /**
     * @var int
     */
    private $pointer;

    /**
     * @var nc_selftest_test_processor
     */
    private $test_processor;

    /**
     * @var array[]
     */
    private $tests_meta;


    /**
     * @param int $current_step
     *
     */
    public function __construct($current_step = 1, nc_selftest_test_processor $test_processor = null) {
        $this->current_step = (int)$current_step ?: 1;
        $this->pointer = $this->current_step - 1;
        $this->collect_sites_data();
        $this->test_processor = $test_processor ?: new nc_selftest_test_processor($this->sites);
        $this->tests_meta = $this->has_sites() ? nc_selftest_meta::get_tests_meta() :
            array_filter(
                nc_selftest_meta::get_tests_meta(),
                function ($meta) {
                    return isset($meta["shared"]);
                }
            );
    }


    /**
     * @return void
     */
    private function collect_sites_data() {
        $sites = nc_core::get_object()->catalogue->get_enabled_sites();

        if (!$sites) {
            $this->sites = array();

            return;
        }

        foreach ($sites as $site) {
            $this->sites[(int)$site["Catalogue_ID"]] = $site;
        }
    }

    /**
     * @return array
     * @throws nc_selftest_exception
     */
    public function start() {
        session_write_close();

        $indexes = array_keys($this->tests_meta);

        if (!isset($indexes[$this->pointer])) {
            throw new nc_selftest_exception("Index out of bounds error");
        }

        $method_name = $indexes[$this->pointer];

        if (!method_exists($this, $method_name)) {
            throw new nc_selftest_exception("Method does not exists");
        }

        $this->current_test = $method_name;
        $test_parameters = $this->tests_meta[$method_name];

        if (isset($test_parameters["required_module"]) && nc_module_check_by_keyword($test_parameters["required_module"]) === false) {
            return $this->get_crashed_test_result()->get_result();
        }

        if (isset($test_parameters["shared"])) {
            try {
                $result = call_user_func(array($this, $this->current_test));
                $this->result["results"] = $result->get_result();
            } catch (Exception $e) {
                $this->result["results"] = $this->get_crashed_test_result()->get_result();
            }
        } else {
            if ($this->has_sites()) {
                foreach ($this->sites as $site_id => $site) {
                    try {
                        /** @var nc_selftest_result $result */
                        $result = call_user_func(array($this, $this->current_test), $site_id);
                        $result->set_site_name($site["Catalogue_Name"]);
                        $this->result["results"][$site_id] = $result->get_result();
                    } catch (Exception $e) {
                        $result = $this->get_crashed_test_result()->get_result();
                        $result->set_site_name($site["Catalogue_Name"]);
                        $this->result["results"][$site_id] = $result;
                    }
                }
            }
        }

        $has_tests = $this->has_tests();
        $this->result["test_ends"] = $this->has_tests();

        if (!$has_tests) {
            $this->result["next_step"] = ++$this->current_step;
            $this->pointer++;
        }

        return $this->result;
    }

    /**
     * @return bool
     */
    public function has_sites() {
        return !empty($this->sites);
    }

    /**
     * @return string
     */
    public function get_current_test() {
        if ($this->current_test) {
            return $this->current_test;
        }

        $indexes = array_values(nc_selftest_meta::get_tests_meta());

        return $indexes[0]["label"];
    }

    /**
     * @return int
     */
    public function get_total_tests_count() {
        return count(nc_selftest_meta::get_tests_meta());
    }

    /**
     * @return nc_selftest_result
     */
    private function get_crashed_test_result() {
        return nc_selftest_result::create_error_result($this->current_test);
    }

    /**
     * Подставляет текущий id сайта в описание, где имеется плейсхолдер %s
     *
     * @param int $site_id
     *
     * @return array
     */
    private function get_formatted_failed_description($site_id) {
        $test_meta = nc_selftest_meta::get_tests_meta($this->current_test);
        $test_meta["failed_description"] = str_replace("%s", $site_id, $test_meta["failed_description"]);

        return $test_meta;
    }

    /**
     * @return bool
     */
    private function has_tests() {
        return $this->current_step == count($this->tests_meta);
    }

    /**
     * @param int $site_id
     *
     * @return nc_selftest_result
     */
    public function check_https($site_id) {
        $this->current_test = "check_https";

        $has_server_https = $this->test_processor->is_https_enabled();
        $is_db_https_enabled = $this->test_processor->is_https_enabled_on_site($site_id);
        $test_meta = nc_selftest_meta::get_tests_meta($this->current_test);

        if (!$has_server_https && !$is_db_https_enabled) {
            $test_meta["failed_description"] = TOOLS_SELFTEST_HTTPS_HAS_NO_SSL .
                sprintf(TOOLS_SELFTEST_HTTPS_HAS_NO_HTTPS_DB_PARAMETER, $site_id);
        } elseif ($has_server_https && !$is_db_https_enabled) {
            $test_meta["failed_description"] = sprintf(TOOLS_SELFTEST_HTTPS_HAS_NO_HTTPS_DB_PARAMETER, $site_id);
        } elseif ($is_db_https_enabled && !$has_server_https) {
            $test_meta["failed_description"] = TOOLS_SELFTEST_HTTPS_HAS_NO_SSL;
        }

        return new nc_selftest_result($test_meta, $has_server_https && $is_db_https_enabled);
    }

    /**
     * @param int $site_id
     *
     * @return nc_selftest_result
     */
    public function check_demo_site($site_id) {
        $this->current_test = "check_demo_site";

        return new nc_selftest_result(
            $this->get_formatted_failed_description($site_id),
            !$this->test_processor->is_demo_mode_enabled($site_id)
        );
    }

    /**
     * @return nc_selftest_result
     */
    public function check_demo_content($site_id) {
        $this->current_test = "check_demo_content";

        return new nc_selftest_result(
            $this->get_formatted_failed_description($site_id),
            !$this->test_processor->get_demo_content($site_id)
        );
    }

    /**
     * @return nc_selftest_result
     */
    public function check_install_directory() {
        $this->current_test = "check_install_directory";

        return new nc_selftest_result($this->current_test, !$this->test_processor->is_install_directory_exists());
    }

    /**
     * @return nc_selftest_result
     */
    public function check_directory_permissions() {
        $this->current_test = "check_directory_permissions";

        $directories = $this->test_processor->get_directories_with_wrong_permissions();
        $test_meta = nc_selftest_meta::get_tests_meta($this->current_test);
        $result = new nc_selftest_result($this->current_test, empty($directories));

        if ($directories) {
            $result->set_description(
                str_replace(
                    "%s",
                    implode("<br>", $directories),
                    $test_meta["failed_description"]
                )
            );
        }

        return $result;
    }

    /**
     * @param int $site_id
     *
     * @return nc_selftest_result
     * @throws Exception
     */
    public function check_indexes($site_id) {
        $this->current_test = "check_indexes";

        $last_start_time = $this->test_processor->get_last_indexed_site_time($site_id);
        $result = new nc_selftest_result($this->current_test, $last_start_time !== null);

        if (!$last_start_time) {
            return $result;
        }

        $now = new DateTime();
        $interval = $now->diff(new DateTime(date('Y-m-d H:i:s', $last_start_time)));

        if ($interval->days >= 30) {
            $result->set_description(
                sprintf(
                    TOOLS_SELFTEST_INDEXES_PERF_LONG_TIME_AGO,
                    date("d.m.Y H:i:s", $last_start_time)
                )
            );

            $result->set_status(nc_selftest_meta::TEST_STATUS_RECOMMENDED);
        }

        return $result;
    }

    /**
     * @return nc_selftest_result
     */
    public function check_updates() {
        $this->current_test = "check_updates";

        return new nc_selftest_result($this->current_test, $this->test_processor->get_actual_patch_id() == null);
    }

    /**
     * @return nc_selftest_result
     */
    public function check_crontab() {
        $this->current_test = "check_crontab";

        return new nc_selftest_result($this->current_test, $this->test_processor->get_crontab_last_launch() !== null);
    }

    /**
     * @param int $site_id
     *
     * @return nc_selftest_result
     */
    public function check_orders($site_id) {
        $this->current_test = "check_orders";

        $unprocessed_orders_count = $this->test_processor->get_unprocessed_orders_count($site_id);
        $test_meta = nc_selftest_meta::get_tests_meta($this->current_test);

        if ($unprocessed_orders_count) {
            $test_meta["failed_description"] = sprintf(
                $test_meta["failed_description"],
                $unprocessed_orders_count,
                $site_id
            );
        }

        return new nc_selftest_result($test_meta, $unprocessed_orders_count == 0);
    }

    /**
     * @param int $site_id
     *
     * @return nc_selftest_result
     * @throws nc_selftest_exception
     */
    public function check_requests($site_id) {
        $this->current_test = "check_requests";
        $test_meta = nc_selftest_meta::get_tests_meta($this->current_test);
        $unprocessed_requests_count = $this->test_processor->get_unprocessed_requests_count($site_id);

        if ($unprocessed_requests_count) {
            $test_meta["failed_description"] = sprintf(
                $test_meta["failed_description"],
                $unprocessed_requests_count,
                $site_id
            );
        }

        return new nc_selftest_result($test_meta, $unprocessed_requests_count == 0);
    }

    /**
     * @param int $site_id
     *
     * @return nc_selftest_result
     */
    public function check_content_versioning($site_id) {
        $this->current_test = "check_content_versioning";

        return new nc_selftest_result(
            $this->get_formatted_failed_description($site_id),
            $this->test_processor->is_content_versioning_enabled($site_id) == true
        );
    }

    /**
     * @param int $site_id
     *
     * @return nc_selftest_result
     */
    public function check_seo_robots($site_id) {
        $this->current_test = "check_seo_robots";

        if ($this->test_processor->has_robots_file()) {
            $test_meta = nc_selftest_meta::get_tests_meta($this->current_test);
            $test_meta["failed_description"] = str_replace("%s", $site_id, TOOLS_SELFTEST_SEO_HAS_ROBOTS_FILE);

            return new nc_selftest_result($test_meta, false);
        }

        return new nc_selftest_result(
            $this->get_formatted_failed_description($site_id),
            !$this->test_processor->has_robots_disallow_directive($site_id, "/")
        );
    }

}
