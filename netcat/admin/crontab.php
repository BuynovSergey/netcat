#!/usr/local/bin/php

<?php

$DOCUMENT_ROOT = join("/", array_slice(explode(DIRECTORY_SEPARATOR, __FILE__), 0, -3));
putenv("DOCUMENT_ROOT=$DOCUMENT_ROOT");
$_SERVER["DOCUMENT_ROOT"] = $DOCUMENT_ROOT;

require_once $DOCUMENT_ROOT . "/netcat/connect_io.php";

$db = nc_db();
$tasks = $db->get_results("SELECT * FROM `CronTasks`", ARRAY_A);

if (empty($tasks)) {
    exit();
}

$nc_core = nc_core::get_object();
$_SERVER["HTTP_HOST"] = $nc_core->catalogue->get_current("Domain") ?: "localhost";
putenv("HTTP_HOST=$_SERVER[HTTP_HOST]");

$secret_key = $nc_core->get_settings("SecretKey");

@set_time_limit(0);
ignore_user_abort(true);
nc_crontab_run_tasks();

/**
 * @return void
 */
function nc_crontab_run_tasks() {
    global $nc_core, $tasks, $db;

    $scheme = $nc_core->catalogue->get_scheme_by_host_name($_SERVER["HTTP_HOST"]);

    foreach ($tasks as $task) {
        $time = $task["Cron_Launch"];

        if ($task["Cron_Minutes"] > 0) {
            $time += $task["Cron_Minutes"] * 60;
        }

        if ($task["Cron_Hours"] > 0) {
            $time += $task["Cron_Hours"] * 3600;
        }

        if ($task["Cron_Days"] > 0) {
            $time += $task["Cron_Days"] * 86400;
        }

        if ($task["Cron_Minutes"] || $task["Cron_Hours"] || $task["Cron_Days"]) {
            if ($time <= time()) {
                if (getenv("debug")) {
                    echo "Fetching URL " . nc_crontab_add_cron_key($task["Cron_Script_URL"]) . "\n";
                }

                if (substr($task["Cron_Script_URL"], 0, 1) === "/") {
                    $no_err = nc_crontab_fetch_url(
                        $scheme . "://" . $_SERVER["HTTP_HOST"] . nc_crontab_add_cron_key($task["Cron_Script_URL"])
                    );
                } else {
                    $no_err = nc_crontab_fetch_url(nc_crontab_add_cron_key($task["Cron_Script_URL"]));
                }

                if ($no_err) {
                    $db->query("UPDATE `CronTasks` SET `Cron_Launch` = '" . time() . "' WHERE `Cron_ID`='$task[Cron_ID]' LIMIT 1");
                }
            }
        }
    }
}

/**
 * @param string $url
 *
 * @return bool
 */
function nc_crontab_fetch_url($url) {
    if (function_exists("curl_version")) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_NOBODY, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_exec($ch);

        if (curl_errno($ch) > 0) {
            curl_close($ch);

            return false;
        }

        curl_close($ch);

        return true;
    }

    if (function_exists("stream_get_contents")) {
        $context = stream_context_create(
            array(
                "http" => array(
                    "method" => "GET",
                ),
            )
        );

        $fp = fopen($url, "r", false, $context);

        if ($fp === false) {
            return false;
        }

        $result = stream_get_contents($fp);
        fclose($fp);

        return $result !== false;
    }

    if (strpos(ini_get("disable_functions"), "passthru") === false) {
        passthru('wget -O - -q  "$url"', $ret_var);

        return $ret_var == 0;
    }

    return false;
}

/**
 * @param string $url
 *
 * @return string
 */
function nc_crontab_add_cron_key($url) {
    global $secret_key;

    $delimiter = "?";

    if (strpos($url, "?") !== false) {
        $delimiter = "&";
    }

    return $url . $delimiter . "cron_key=" . $secret_key;
}
