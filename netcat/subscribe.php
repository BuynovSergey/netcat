<?php

$action = "subscribe";

$NETCAT_FOLDER = join(strstr(__FILE__, "/") ? "/" : "\\", array_slice(preg_split("/[\/\\\]+/", __FILE__), 0, -2)).( strstr(__FILE__, "/") ? "/" : "\\" );
@include_once ($NETCAT_FOLDER."vars.inc.php");
require_once $ROOT_FOLDER . 'connect_io.php';

$nc_core = nc_Core::get_object();
$nc_core->action = 'subscribe';

require ($INCLUDE_FOLDER."index.php");

if (!isset($use_multi_sub_class)) {
    // subdivision multisubclass option
    $use_multi_sub_class = $nc_core->subdivision->get_current("UseMultiSubClass");
}

ob_start();

$nc_core = nc_Core::get_object();
// если ли модуль?
if (!$nc_core->modules->get_by_keyword('subscriber', 0)) exit();

$MODULE_VARS = $nc_core->modules->get_module_vars();

$ver = $nc_core->modules->get_vars('subscriber', 'VERSION');
// первая версия модуля
if (!$MODULE_VARS['subscriber']['VERSION'] || $MODULE_VARS['subscriber']['VERSION'] == 1) {
    $posting = 1;
    if ($subscribeCond && $posting) {
        eval(nc_check_eval($subscribeCond));
    } elseif (!$subscribeCond && $posting) {
        echo NETCAT_MODULE_SUBSCRIBE_SUCCESS;
    }

    if ($posting) {
        switch ($subscribeAction) {
            case "delete":
                if (!$id) break;
                subscribe_deleteItem($id);
                break;
            case "toggle":
                if (!$id) break;
                subscribe_toggleItem($id);
                break;
            default:
                subscribe_addItem($cc);
        }

        if ($sub != $MODULE_VARS['subscriber']['SUBSCRIBER_LIST_SUB']) {
            echo s_browse_subscribes();
        }
    }
} else { // новая версия модуля
    try {
        // объект для управления рассылками
        $nc_subscriber = nc_subscriber::get_object();
        // рассылка по этому компоненту в разделе
        $mailer = $nc_subscriber->get_mailer_by_cc($cc);
        // если пользователь авторизирован - то его сразу нужно попробовать подписать
        if ($AUTH_USER_ID) $posting = 1;
        // условие подписки
        $cond = $mailer['SubscribeCond'];
        if ($cond && $posting) eval(nc_check_eval($cond));

        if (!$posting) {
            if (!$AUTH_USER_ID) { // неавторизированный пользователь - выводим форму подписки
                eval(nc_check_eval("\$result= \"".$nc_subscriber->tools->get_settings('FormSubscribe')."\";"));
                echo $result;
            } else { // автризированный пользвоатель не смог подписаться - выводим причину
                eval(nc_check_eval("echo \"".$warnText."\";"));
            }
        } else { // подписка
            if ($nc_status = $nc_subscriber->subscription_add($mailer['Mailer_ID'], 0, 0, 0, $_POST['fields'], $message)) {
                // действие после подписки
                $act = $nc_subscriber->get($mailer['Mailer_ID'], 'SubscribeAction');
                if ($act) {
                    eval(nc_check_eval("echo \"".$act."\";"));
                }
            }
        }
    }
    // повторное письмо
    catch (nc_Exception_Subscriber_AlreadySubscribe $e) {
        if (!$AUTH_USER_ID) {
            $nc_subscriber->send_confirm_mail($mailer['Mailer_ID'], $e->get_user());
            echo NETCAT_MODULE_SUBSCRIBER_CONFIRM_SENT_AGAIN;
        }
    }
    catch (ExceptionEmail $e) {
        echo NETCAT_MODULE_SUBSCRIBER_WRONG_EMAIL;
    }
    catch (Exception $e) {
        echo $e->getMessage();
    }
}

$cc_subscribe = $cc;

if ($cc_array && $use_multi_sub_class && !$inside_admin) {
    foreach ($cc_array as $cc) {
        if (( $cc && $cc_subscribe != $cc) || $user_table_mode) {
            // поскольку компонентов несколько, то current_cc нужно переопределить
            $current_cc = $nc_core->sub_class->set_current_by_id($cc);
            echo s_list_class($sub, $cc, $nc_core->url->get_parsed_url('query').($date ? "&date=".$date : "")."&isMainContent=1&isSubClassArray=1");
        }
    }
    // current_cc нужно вернуть в первоначальное состояние, чтобы использовать в футере макета
    $current_cc = $nc_core->sub_class->set_current_by_id($cc_subscribe);
}

$nc_result_msg = ob_get_clean();

if ($File_Mode) {
    require_once($INCLUDE_FOLDER . 'index_fs.inc.php');
}

if (!$File_Mode || $templatePreview) {
    nc_evaluate_template($template_header, $template_footer, $File_Mode);
}

// выполнить необходимую обработку кода страницы и отдать результат пользователю:
$nc_core->output_page($template_header, $nc_result_msg, $template_footer);
