<?php

require_once "../require/cron_api.inc.php";
require_once $INCLUDE_FOLDER . 'lib/Mail/Queue.php';

$number = filter_input(INPUT_GET, 'number', FILTER_SANITIZE_NUMBER_INT) ?: 20;

$db_options = array('type' => 'ezsql', 'mail_table' => 'Mail_Queue');
$mail_options = array('driver' => 'mail');

$mail_queue = new Mail_Queue($db_options, $mail_options);
$mail_queue->sendMailsInQueue($number);
