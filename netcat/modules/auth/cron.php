<?php

require_once "../../require/cron_api.inc.php";


// Удаление пользователей, с неподтвержденной регистрацией
if (($confirm_time = intval($nc_core->get_settings('confirm_time', 'auth')))) {
    $users_to_drop = $db->get_col(
        "SELECT `User_ID` FROM `User`
           WHERE `Confirmed` = 0
             AND `Checked` = 0
             AND `RegistrationCode` <> ''
             AND (UNIX_TIMESTAMP(`Created`) + " . $confirm_time . "*3600) < UNIX_TIMESTAMP(NOW()) "
    );

    if ($users_to_drop) {
        $nc_core->user->delete_by_id($users_to_drop);
    }
}
