<?php

require_once "../../require/cron_api.inc.php";

try {
    $s = nc_subscriber_send::get_object();

    if ($s->is_blocked()) {
        die("Blocked");
    }

    $s->block();
    $nc_subscriber = nc_subscriber::get_object();

    $s->formation_service();

    $s->send();
    $s->send_periodical();
    $s->send_serial();
    $s->send_prepared();


    $s->update_stats();

    if (rand(0, 20) == 5) {
        $nc_subscriber->delete_expire();
    }
} catch (Exception $e) {
    echo $e->getMessage();
}

$s->unlock();
