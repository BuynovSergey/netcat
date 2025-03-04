<?php

/* $Id */
$NETCAT_FOLDER = join(strstr(__FILE__, "/") ? "/" : "\\", array_slice(preg_split("/[\/\\\]+/", __FILE__), 0, -4)) . (strstr(__FILE__, "/") ? "/" : "\\");
include_once $NETCAT_FOLDER . 'vars.inc.php';
require $ADMIN_FOLDER . 'function.inc.php';
require $ADMIN_FOLDER . 'trash/function.inc.php';

/** @var Permission $perm */
/** @var nc_Core $nc_core */

$Delimeter    = " &gt ";
$main_section = "list";
$Title2       = "<a href=\"" . $ADMIN_PATH . "trash.php\">" . TOOLS_TRASH . "</a>";

if (!isset($phase)) {
    $phase = 1;
}

$UI_CONFIG = new ui_config_trash(TRASH_TAB_LIST, 'list', TRASH_TAB_TITLE);

/* Проверка token-а нужна только для сохранения настроек и восстановлениях */
if (in_array($phase, array(2, 4, 5, 6)) && !$nc_core->token->verify()) {
    BeginHtml(TOOLS_TRASH, $Title2, "http://{$DOC_DOMAIN}/tools/trash/");
    nc_print_status(NETCAT_TOKEN_INVALID, 'error');
    EndHtml();
    exit;
}

if (!$perm->accessToTrash()) {
    die(NETCAT_MODERATION_ERROR_NORIGHTS);
}

switch ($phase) {
    case 1: // список удаленных объектов
        BeginHtml(TOOLS_TRASH, $Title2, "http://{$DOC_DOMAIN}/tools/trash/");
        nc_trash_list(isset($options) ? $options : array());
        break;
    case 2: // Восстановление по номерам в корзине, форма восстановление раздела
        BeginHtml(TOOLS_TRASH, $Title2, "http://{$DOC_DOMAIN}/tools/trash/");
        // форма создания раздела
        if (nc_trash_prerecovery($trash_ids)) {
            break;
        }
    // break не нужен
    case 21: // сообственно восстановление
        $return_url = $nc_core->input->fetch_get('return_url');
        if ($phase != 2) {
            BeginHtml(TOOLS_TRASH, $Title2, "http://{$DOC_DOMAIN}/tools/trash/");
        }
        if ($sub_name) {
            nc_trash_recovery_sub($trash_ids);
            print "<script>top.frames['treeIframe'].window.location.reload(); </script>";
        }
        if ($recovered_objects_count = $nc_core->trash->recovery($trash_ids)) {
            if ($return_url) {
                header('Location:' . html_entity_decode($return_url));
                exit();
            }
            $recovered_objects_state_text = $nc_core->lang->get_numerical_inclination($recovered_objects_count, array(
                NETCAT_TRASH_RECOVERED_SK1,
                NETCAT_TRASH_RECOVERED_SK2,
                NETCAT_TRASH_RECOVERED_SK3
            ));
            $recovered_objects_count_text = $nc_core->lang->get_numerical_inclination($recovered_objects_count, array(
                NETCAT_TRASH_MESSAGES_SK1,
                NETCAT_TRASH_MESSAGES_SK2,
                NETCAT_TRASH_MESSAGES_SK3
            ));
            nc_print_status($recovered_objects_state_text . ' ' . $recovered_objects_count . ' ' . $recovered_objects_count_text, 'ok');
        }
        nc_trash_list();
        break;
    case 3: // Удаление из корзины объектов
        BeginHtml(TOOLS_TRASH, $Title2, "http://{$DOC_DOMAIN}/tools/trash/");
        if ($removed_objects_count = $nc_core->trash->delete($trash_ids)) {
            $removed_objects_count_text = $nc_core->lang->get_numerical_inclination($removed_objects_count, array(
                NETCAT_ADMIN_TRASH_OBJECT_HAS_BEEN_REMOVED,
                NETCAT_ADMIN_TRASH_OBJECTS_REMOVED,
                NETCAT_ADMIN_TRASH_OBJECT_IS_REMOVED
            ));
            nc_print_status($removed_objects_count . ' ' . $removed_objects_count_text, 'info');
        }
        nc_trash_list();
        break;
    case 4: // очистка корзины
        # Очистка корзины
        $UI_CONFIG = new ui_config_trash(TRASH_TAB_LIST, 'list', TRASH_TAB_TITLE, TRASH_TAB_SETTINGS);
        BeginHtml(TOOLS_TRASH, $Title2, "http://{$DOC_DOMAIN}/tools/trash/");
        $nc_core->trash->clean();
        nc_print_status(NETCAT_ADMIN_TRASH_TRASH_HAS_BEEN_SUCCESSFULLY_CLEARNED, 'ok');
        nc_trash_list();
        break;
}

EndHtml();
?>