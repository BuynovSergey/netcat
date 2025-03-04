<?php

$NETCAT_FOLDER = join(strstr(__FILE__, "/") ? "/" : "\\", array_slice(preg_split("/[\/\\\]+/", __FILE__), 0, -4)) . (strstr(__FILE__, "/") ? "/" : "\\");
include_once($NETCAT_FOLDER . "vars.inc.php");
require_once($ADMIN_FOLDER . "function.inc.php");
require_once($ADMIN_FOLDER . "wysiwyg/function.inc.php");

$nc_core = nc_Core::get_object();

if (!isset($phase)) {
    $phase = 1;
}

if (!$perm->isSupervisor()) {
    BeginHtml();
    nc_print_status(NETCAT_MODERATION_ERROR_NORIGHTS, 'error');
    EndHtml();
    exit;
}


if (in_array($phase, array(2, 6, 8)) && !$nc_core->token->verify()) {
    BeginHtml();
    nc_print_status(NETCAT_TOKEN_INVALID, 'error');
    EndHtml();
    exit;
}


try {
    switch ($phase) {
        case 1:
            //Настройки редактора
            BeginHtml();
            if (!isset($editor) || $editor != 'fckeditor') {
                WysiwygCkeditorSettingsForm();
            } else {
                WysiwygFckeditorSettingsForm();
            }
            break;

        case 2:
            //Сохранение настроек редактора
            BeginHtml();

            if (!isset($editor) || $editor != 'fckeditor') {
                if (WysiwygCkeditorSettingsCompleted()) {
                    nc_print_status(NETCAT_WYSIWYG_SETTINGS_MESSAGE_SETTINGS_SAVED, 'ok');
                } else {
                    nc_print_status(NETCAT_WYSIWYG_SETTINGS_MESSAGE_SETTINGS_SAVE_ERROR, 'error');
                }
                WysiwygCkeditorSettingsForm();
            } else {
                if (WysiwygFckeditorSettingsCompleted()) {
                    nc_print_status(NETCAT_WYSIWYG_SETTINGS_MESSAGE_SETTINGS_SAVED, 'ok');
                } else {
                    nc_print_status(NETCAT_WYSIWYG_SETTINGS_MESSAGE_SETTINGS_SAVE_ERROR, 'error');
                }
                WysiwygFckeditorSettingsForm();
            }


            break;

        case 3:
            //Вывод списка панелей
            BeginHtml();
            WysiwygCkeditorPanels();
            break;

        case 4:
            //Форма добавления панели
            BeginHtml();
            PanelForm();
            break;

        case 5:
            //Форма изменения панели
            BeginHtml();
            $Wysiwyg_Panel_ID = $Wysiwyg_Panel_ID ? (int)$Wysiwyg_Panel_ID : 0;
            if (!PanelForm($Wysiwyg_Panel_ID)) {
                nc_print_status(NETCAT_WYSIWYG_CKEDITOR_SETTINGS_MESSAGE_PANEL_NOT_EXISTS, 'error');
            }
            break;

        case 6:
            //Добавление/изменение панели
            BeginHtml();
            if (!PanelFormCompleted()) {
                nc_print_status($errorString, 'error');
                PanelForm(isset($Wysiwyg_Panel_ID) ? $Wysiwyg_Panel_ID : null);
            } else {
                nc_print_status(isset($Wysiwyg_Panel_ID) ? NETCAT_WYSIWYG_CKEDITOR_SETTINGS_MESSAGE_PANEL_EDIT_SUCCESSFUL : NETCAT_WYSIWYG_CKEDITOR_SETTINGS_MESSAGE_PANEL_ADD_SUCCESSFUL, 'ok');
                WysiwygCkeditorPanels();
            }
            break;

        case 7:
            //Подтверждение удаления панелей
            BeginHtml();
            if (!DeleteConfirmationForm()) {
                nc_print_status(NETCAT_WYSIWYG_CKEDITOR_SETTINGS_MESSAGE_PANELS_NOT_SELECTED, 'info');
            }
            break;

        case 8:
            //Удаление панелей
            BeginHtml();
            if (DeletePanels()) {
                nc_print_status(NETCAT_WYSIWYG_CKEDITOR_SETTINGS_MESSAGE_PANELS_DELETED, 'ok');
            } else {
                nc_print_status(NETCAT_WYSIWYG_CKEDITOR_SETTINGS_MESSAGE_PANELS_DELETE_ERROR, 'error');
            }
            WysiwygCkeditorPanels();
            break;
        case 9:
            //Активирование редактора
            BeginHtml();
            ActivateEditor();
            nc_print_status(NETCAT_WYSIWYG_SETTINGS_MESSAGE_EDITOR_ACTIVATED, 'ok');
            if ($editor_type == 2) {
                WysiwygFckeditorSettingsForm();
            } else {
                WysiwygCkeditorSettingsForm();
            }
            break;
    }
} catch (nc_Exception_DB_Error $e) {
    nc_print_status(sprintf(NETCAT_ERROR_SQL, $e->query(), $e->error()), 'error');
} catch (Exception $e) {
    nc_print_status($e->getMessage(), 'error');
}

EndHtml();