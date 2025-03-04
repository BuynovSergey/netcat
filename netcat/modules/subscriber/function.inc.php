<?php

if (!class_exists("nc_System")) die("Unable to load file.");
/* $Id: function.inc.php 6210 2012-02-10 10:30:32Z denis $ */

function s_browse_subscribes() {
    $nc_core = nc_Core::get_object();
    $MODULE_VARS = $nc_core->modules->get_module_vars();

    // функция не досутпна в новых версиях
    if ($MODULE_VARS['subscriber']['VERSION'] != 1) return false;
    global $db, $admin_mode;
    global $AUTH_USER_ID, $HTTP_ROOT_PATH, $SUB_FOLDER;
    global $browse_subscribes;
    global $current_sub, $current_cc;
    global $catalogue, $sub, $cc;

    $browse_template = $browse_subscribes;

    if (!$AUTH_USER_ID) return;

    $routing_module_enabled = nc_module_check_by_keyword('routing');

    $data = $db->get_results(
        "SELECT a.`Subscriber_ID`,
                a.`Sub_Class_ID`,
                c.`Sub_Class_Name`,
                CONCAT(b.`Hidden_URL`, c.`EnglishName`, '.html') AS Subscription_URL,
                b.`Subdivision_ID`,
                b.`Subdivision_Name`,
                b.`Hidden_URL`,
                a.`Status`
          FROM `Subscriber` AS a,
               `Subdivision` AS b,
               `Sub_Class` AS c
         WHERE a.`User_ID` = '$AUTH_USER_ID'
           AND a.`Sub_Class_ID` = c.`Sub_Class_ID`
           AND c.`Subdivision_ID` = b.`Subdivision_ID` " .
        $browse_template['sortby'],
        ARRAY_A);

    $data_count = $db->num_rows;

    $result = "";
    eval(nc_check_eval("\$result = \"".$browse_template['prefix']."\";"));

    for ($i = 0; $i < $data_count; $i++) {

        if ($data[$i]['Status']) {
            eval(nc_check_eval("\$result.= \"".$browse_template['active']."\";"));
        } else {
            eval(nc_check_eval("\$result.= \"".$browse_template['unactive']."\";"));
        }

        $prefix = $SUB_FOLDER . $HTTP_ROOT_PATH."subscribe.php?catalogue=".$catalogue."&sub=".$sub.($cc ? "&cc=".$cc : "")."&id=".$data[$i]['Subscriber_ID'];

        $toggleLink = $prefix."&subscribeAction=toggle";
        $unsubscribeLink = $prefix."&subscribeAction=delete";

        if ($routing_module_enabled) {
            $infoblock_path = nc_routing::get_infoblock_path($data[$i]['Sub_Class_ID']);
            $folder_path = nc_routing::get_folder_path($data[$i]['Subdivision_ID']);
        }
        else {
            $infoblock_path = $SUB_FOLDER . $data[$i]['Subscription_URL'];
            $folder_path = $SUB_FOLDER . $data[$i]['Hidden_URL'];
        }

        $result = str_replace("%ID", $data[$i]['Subscriber_ID'], $result);
        $result = str_replace("%CC_ID", $data[$i]['Sub_Class_ID'], $result);
        $result = str_replace("%CC_NAME", $data[$i]['Sub_Class_Name'], $result);
        $result = str_replace("%CC_URL", $infoblock_path, $result);
        $result = str_replace("%SUB_ID", $data[$i]['Subdivision_ID'], $result);
        $result = str_replace("%SUB_NAME", $data[$i]['Subdivision_Name'], $result);
        $result = str_replace("%SUB_URL", $folder_path, $result);

        $result = str_replace("%TOGGLE_LINK", $toggleLink, $result);
        $result = str_replace("%UNSUBSCRIBE_LINK", $unsubscribeLink, $result);

        if ($i <> ($data_count - 1))
                eval(nc_check_eval("\$result.= \"".$browse_template['divider']."\";"));
    }

    eval(nc_check_eval("\$result.= \"".$browse_template['suffix']."\";"));

    return $result;
}

function subscribe_addItem($cc) {
    $nc_core = nc_Core::get_object();
    $MODULE_VARS = $nc_core->modules->get_module_vars();

    // для совместимости
    if ($MODULE_VARS['subscriber']['VERSION'] == 1) {
        global $AUTH_USER_ID, $db;

        if (!$AUTH_USER_ID) return false;

        // validate
        $cc = intval($cc);

        $count = $db->get_var("SELECT COUNT(*) FROM `Subscriber`
      WHERE `Sub_Class_ID` = '".intval($cc)."' AND `User_ID` = '".$AUTH_USER_ID."'");

        if (!$count)
                $res = $db->query("INSERT INTO `Subscriber`
      (`User_ID`, `Status`, `Sub_Class_ID`)
      VALUES
      ('".$AUTH_USER_ID."', 1, '".$cc."')");

        return ($res);
    }

    try {
        $nc_subscriber = nc_subscriber::get_object();
        $nc_subscriber->subscription_add_by_cc($cc);
    } catch (Exception $e) {
        ;
    }
}

function subscribe_deleteItem($id) {
    $nc_core = nc_Core::get_object();
    $MODULE_VARS = $nc_core->modules->get_module_vars();

    // для совместимости
    if ($MODULE_VARS['subscriber']['VERSION'] == 1) {
        global $AUTH_USER_ID, $db;
        if (!$AUTH_USER_ID) return false;

        $res = $db->query("DELETE FROM `Subscriber`
      WHERE `Subscriber_ID` = '".intval($id)."' AND `User_ID` = '".$AUTH_USER_ID."'");

        return ($res);
    }

    try {
        $nc_subscriber = nc_subscriber::get_object();
        $nc_subscriber->subscription_delete($id);
    } catch (Exception $e) {
        ;
    }
}

function subscribe_toggleItem($id) {
    $nc_core = nc_Core::get_object();
    $MODULE_VARS = $nc_core->modules->get_module_vars();

    // функция не досутпна в новых версиях
    if ($MODULE_VARS['subscriber']['VERSION'] != 1) return false;

    global $AUTH_USER_ID;
    global $db;

    if (!$AUTH_USER_ID) return false;

    // validate
    $id = intval($id);

    $res = $db->query("UPDATE `Subscriber` SET `Status` = (1 - `Status`)
    WHERE `Subscriber_ID` = '".$id."' AND `User_ID` = '".$AUTH_USER_ID."'");

    return ($res);
}

function subscribe_sendmail($cc, $mailbody) {
    $nc_core = nc_Core::get_object();
    $MODULE_VARS = $nc_core->modules->get_module_vars();

    // функция не досутпна в новых версиях
    if ($MODULE_VARS['subscriber']['VERSION'] != 1) return false;

    $db = $nc_core->db;

    if (!$SPAM_FIELD = $MODULE_VARS['subscriber']['USER_EMAIL_FIELD'])
            return false;

    // validate
    $cc = intval($cc);

    $SPAM_FROM_NAME = $MODULE_VARS['subscriber']['SEND_FROM_NAME'];
    $SPAM_FROM = $MODULE_VARS['subscriber']['SEND_FROM_EMAIL'];

    $res = $db->get_col("SELECT b.`".$SPAM_FIELD."` FROM `Subscriber` AS a, `User` AS b
    WHERE a.`Sub_Class_ID` = '".$cc."' AND a.`User_ID` = b.`User_ID` AND a.`Status` = 1");

    if (!empty($res)) {
        foreach ($res as $usr_email) {
            $nc_core->mail->mailbody(strip_tags($mailbody), $mailbody);
            $nc_core->mail->init(true);
            $nc_core->mail->send($usr_email, $SPAM_FROM_NAME, $SPAM_FROM, $MODULE_VARS['subscriber']['SUBJECT_CONTENT'], $SPAM_FROM_NAME);
        }
    }
}

function subscribe_checkItem($cc, $user_id = 0) {
    $nc_core = nc_Core::get_object();
    $MODULE_VARS = $nc_core->modules->get_module_vars();

    // функция не досутпна в новых версиях
    if ($MODULE_VARS['subscriber']['VERSION'] == 1) {

        global $AUTH_USER_ID;
        global $db;

        // static storage
        static $storage = array();

        // validate
        $cc = intval($cc);
        $user_id = intval($user_id);

        // if no user set current user
        if (!$user_id) $user_id = $AUTH_USER_ID;
        // unknown user detected
        if (!$user_id) return false;

        if (!isset($storage[$cc][$user_id])) {
            $storage[$cc][$user_id] = $db->get_var("SELECT `Subscriber_ID` FROM `Subscriber`
        WHERE `Sub_Class_ID` = '".$cc."' AND `User_ID` = '".$user_id."'");
        }

        // return result
        return $storage[$cc][$user_id];
    }

    try {
        $nc_subscriber = nc_subscriber::get_object();
        return $nc_subscriber->is_subscribe_to_cc($cc, $user_id);
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Создает письмо в очереди отправки, прикрепит файлы отправленные через файловые поля file1 - file3
 *
 * @param string $email - адресат
 * @param string $subject - заголовок письма
 * @param string $message - тело письма
 * @param int $html - указывает что письмо с html тегами
 *
 */
function subscriber_mail2queue($email, $subject, $message, $html = 1) {
    require_once nc_module_folder('subscriber') . 'nc_subscriber_admin.class.v2.php';
    $nc_subs_admin = new nc_subscriber_admin();
    $nc_subs_admin->mail2queue($email, $subject, $message, $html);
}

global $MODULE_FOLDER;

// загрузка файлов с классами
if (!$MODULE_VARS['subscriber']['VERSION'] || $MODULE_VARS['subscriber']['VERSION'] != 1) {
    include_once nc_module_folder('subscriber') . 'nc_subscriber_tools.class.php';
    include_once nc_module_folder('subscriber') . 'nc_subscriber.class.php';
    include_once nc_module_folder('subscriber') . 'nc_subscriber_send.class.php';

    $nc_subscriber = nc_subscriber::get_object();
}
?>