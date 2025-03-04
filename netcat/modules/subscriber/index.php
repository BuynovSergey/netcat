<?php

$NETCAT_FOLDER = join(strstr(__FILE__, "/") ? "/" : "\\", array_slice(preg_split("/[\/\\\]+/", __FILE__), 0, -4)) . (strstr(__FILE__, "/") ? "/" : "\\");
include_once($NETCAT_FOLDER . "vars.inc.php");
require_once($ROOT_FOLDER . "connect_io.php");

$MODULE_VARS = $nc_core->modules->load_env();

$nc_subscriber = nc_subscriber::get_object();
$res = $nc_subscriber->tools->get_subscribe_sub();
$catalogue = $res['Catalogue_ID'];
$sub = isset($_GET['sub']) ? $_GET['sub'] : $res['Subdivision_ID'];

unset($res);

require($INCLUDE_FOLDER . "index.php");


// авторизация
//if ( !$AUTH_USER_ID && ! Authorize() ) exit();
Authorize();

$input = array_merge((array)$_GET, (array)$_POST);

krsort($input);

if (!$AUTH_USER_ID && $current_cc['UseCaptcha'] && $MODULE_VARS['captcha']) {
    if (!nc_captcha_verify_code($nc_captcha_code)) {
        $status = 4;
    }
}


$nc_subscriber = nc_subscriber::get_object();

if (!$status) {
    $status = 0;

    try {
        if (!empty($input)) {
            foreach ($input as $k => $v) {
                if (substr($k, 0, 9) == 'subscribe') {
                    $mailer_id = intval(substr($k, 10));

                    if (!$mailer_id) {
                        continue;
                    }

                    if ($v == 1) {
                        if (!$nc_subscriber->check_rights($mailer_id)) {
                            continue;
                        }

                        $cond = $nc_subscriber->get($mailer_id, 'SubscribeCond');
                        $posting = 1;
                        if ($cond) {
                            eval(nc_check_eval($cond));
                        }
                        if (!$posting) {
                            continue;
                        }

                        if ($nc_subscriber->subscription_add($mailer_id, 0, 0, 0, $input['fields'])) {
                            $status = 1;
                            $act = $nc_subscriber->get($mailer_id, 'SubscribeAction');
                            if ($act) {
                                eval(nc_check_eval("echo \"" . $act . "\";"));
                            }
                        }
                    } else if ($v == -1) {
                        $nc_subscriber->subscription_delete_by_mailer($mailer_id);
                    }
                }

                if (substr($k, 0, 6) == 'period') {
                    $mailer_id = intval(substr($k, 7));
                    $v = intval($v);
                    if (!$mailer_id || !$v) {
                        continue;
                    }
                    $sbs_id = $db->get_var("SELECT `ID` FROM `Subscriber_Subscription` WHERE `Mailer_ID` = '" . $mailer_id . "' AND `User_ID` = '" . $AUTH_USER_ID . "'");
                    $nc_subscriber->subscription_change_period($sbs_id, $v);
                }
            }
        }
    } catch (ExceptionEmail $e) {
        $status = 2; // неправильный email
    } catch (nc_Exception_Subscriber_AlreadySubscribe $e) {
        $status = 5; // уже подписан
        if (!$AUTH_USER_ID) {
            $nc_subscriber->send_confirm_mail($mailer_id, $e->get_user());
        }
    } catch (Exception $e) {
        die($e->getMessage());
    }
}
if (!$status) {
    $status = 3;
}

// уберем nc_status из адреса
$input['redirect_url'] = nc_preg_replace('/nc_status=(\d+)/i', '', $input['redirect_url']);

if (!$AUTH_USER_ID) {
    $input['redirect_url'] .= (strpos($input['redirect_url'], '?') !== false) ? "&nc_status=" . $status : "?nc_status=" . $status;
}
$redirect_url = $input['redirect_url'] ? $input['redirect_url'] : $_SERVER['HTTP_REFERER'];

if ($redirect_url) {
    ob_clean();
    nc_check_redirect($input['redirect_url']);
}

exit();
