<?php

$NETCAT_FOLDER = join(strstr(__FILE__, "/") ? "/" : "\\", array_slice(preg_split("/[\/\\\]+/", __FILE__), 0, -4)) . (strstr(__FILE__, "/") ? "/" : "\\");
include_once $NETCAT_FOLDER . 'vars.inc.php';
require $ADMIN_FOLDER . 'function.inc.php';
require_once $INCLUDE_FOLDER . 's_common.inc.php';
require $ADMIN_FOLDER . 'template/function.inc.php';

$nc_core = nc_core::get_object();
/** @var Permission $perm */

$main_section = "control";
$item_id = 10;
$Delimeter = " &gt ";
$Title2 = CONTROL_TEMPLATE;
$Title3 = "<a href=\"" . $ADMIN_PATH . "template/\">" . CONTROL_TEMPLATE . "</a>";
$Title5 = GetTemplateDescription($TemplateID);
$Title6 = CONTROL_TEMPLATE_ADD;
$Title7 = CONTROL_TEMPLATE_EDIT;


$Title15 = "<a href=\"index.php?phase=4&TemplateID=$TemplateID\">" . GetTemplateDescription($TemplateID) . "</a>";
$Title16 = CONTROL_TEMPLATE_OPT_ADD;
$Title17 = CONTROL_TEMPLATE_OPT_EDIT;


if (!isset($phase)) {
    $phase = 1;
}

if (!isset($_REQUEST['fs'])) {
    $_REQUEST['fs'] = 0;
}
$File_Mode = +$_REQUEST['fs'];

if (in_array($phase, array(3, 5, 7)) && !$nc_core->token->verify()) {
    if (defined('NC_ADMIN_DISABLE_LOGIN_PAGE') || $nc_core->input->fetch_post('NC_HTTP_REQUEST')) { // AJAX call
        nc_set_http_response_code(401);
        exit;
    }

    BeginHtml($Title2, $Title2, "");
    nc_print_status(NETCAT_TOKEN_INVALID, 'error');
    EndHtml();
    exit;
}

$File_Mode = nc_get_file_mode('Template', $phase == 3 ? $_POST['ParentTemplateID'] : $TemplateID);

switch ($phase) {

    case 1:
        # покажем список всех темплейтов
        BeginHtml($Title2, $Title2, "http://" . $DOC_DOMAIN . "/management/design/");
        $perm->ExitIfNotAccess(NC_PERM_TEMPLATE, 0, 0, 0, 0);
        $UI_CONFIG = new ui_config_template('list', $TemplateID);
        FullTemplateList();
        break;

    case 20:
        # форма выбора базового шаблона (первый шаг создания)
        BeginHtml($Title6, $Title3 . $Delimeter . $Title6, "http://" . $DOC_DOMAIN . "/management/design/form/");
        $perm->ExitIfNotAccess(NC_PERM_TEMPLATE, 0, 0, 0, 0);
        BaseTemplateForm($ParentTemplateID, $File_Mode);
        break;

    case 2:
        # покажем форму добавления темплейта
        BeginHtml($Title6, $Title3 . $Delimeter . $Title6, "http://" . $DOC_DOMAIN . "/management/design/form/");
        $perm->ExitIfNotAccess(NC_PERM_TEMPLATE, 0, 0, 0, 0);
        TemplateForm(0, 3, 1, $File_Mode, false, $BaseTemplateID);
        break;

    case 3:
        # собственно добавление темплейта
        $AJAX_SAVER = true;
        if ($perm->isGuest()) {
            $AJAX_SAVER = false;
        }
        
        BeginHtml($Title2, $Title2, "http://" . $DOC_DOMAIN . "/management/design/");
        $perm->ExitIfNotAccess(NC_PERM_TEMPLATE, 0, 0, 0, 1);

        if (!empty($Keyword)) {
            $keyword_validation_result = $nc_core->template->validate_keyword($Keyword, 0, $ParentTemplateID);
        } else {
            $keyword_validation_result = true;
        }

        if (!$posting) {
            break;
        }

        if ($Description && $keyword_validation_result === true) {
            $systemMessageID = ActionTemplateCompleted($type, $File_Mode);
            if ($systemMessageID) {
                TemplateForm($systemMessageID, 5, 2, $File_Mode, true);
            }
        } elseif (!$Description || $keyword_validation_result !== true) {
            $error = ($keyword_validation_result !== true) ? $keyword_validation_result : CONTROL_TEMPLATE_ERR_NAME;
            nc_print_status($error, 'error');
            TemplateForm(0, 3, 1, $File_Mode, true, $BaseTemplateID);
        }
        break;

    case 4:
        # покажем форму редактирования темплейта
        $AJAX_SAVER = true;
        if ($perm->isGuest()) {
            $AJAX_SAVER = false;
        }

        BeginHtml($Title7, $Title3 . $Delimeter . $Title5, "http://" . $DOC_DOMAIN . "/management/design/form/");
        $perm->ExitIfNotAccess(NC_PERM_TEMPLATE, 0, 0, 0, 0);

        if (+$_REQUEST['isNaked']) {
            ob_clean();
            TemplateForm_for_modal($TemplateID, $File_Mode);
            exit;
        } else {
            TemplateForm($TemplateID, 5, 2, $File_Mode);
        }

        break;

    case 5:
        # собственно проапдейтим темплейт
        $AJAX_SAVER = true;
        if ($perm->isGuest()) {
            $AJAX_SAVER = false;
        }

        BeginHtml($Title2, $Title2, "http://" . $DOC_DOMAIN . "/management/design/");
        $perm->ExitIfNotAccess(NC_PERM_TEMPLATE, 0, 0, 0, 1);

        $parent_template_id = $nc_core->template->get_by_id($TemplateID, 'Parent_Template_ID');
        $keyword_validation_result = $nc_core->template->validate_keyword($Keyword, $TemplateID, $parent_template_id);

        if (!$posting) {
            break;
        }

        if ($Description && $keyword_validation_result === true) {
            if (ActionTemplateCompleted($type, $File_Mode)) {
                TemplateForm($TemplateID, 5, 2, $File_Mode);
                global $UI_CONFIG;
                $updateNode = array(
                    "nodeId" => "template-{$TemplateID}",
                    "name" => $TemplateID . ". " . $Description
                );
                $UI_CONFIG->treeChanges['updateNode'][] = $updateNode;
                $GLOBALS["_RESPONSE"]["ui_config"] = (array)$UI_CONFIG;
            }
            else {
                $error = NETCAT_HTTP_REQUEST_ERROR;
                if ($_POST["NC_HTTP_REQUEST"]) {
                    $GLOBALS["_RESPONSE"]["error"] = $error;
                }
                nc_print_status($error, 'error');
            }
        } elseif (!$Description || $keyword_validation_result !== true) {
            $error = ($keyword_validation_result !== true) ? $keyword_validation_result : CONTROL_TEMPLATE_ERR_NAME;
            if ($_POST["NC_HTTP_REQUEST"]) {
                $GLOBALS["_RESPONSE"]["error"] = $error;
            }

            nc_print_status($error, 'error');
            TemplateForm($TemplateID, 5, 2, $File_Mode);
        }
        break;

    case 6:
        # удаление темплейтов
        BeginHtml($Title2, $Title2, "http://" . $DOC_DOMAIN . "/management/design/");
        $perm->ExitIfNotAccess(NC_PERM_TEMPLATE, 0, 0, 0, 1);
        AscIfDeleteTemplate();
        break;

    case 7:
        # удаление темплейтов
        BeginHtml($Title2, $Title2, "http://" . $DOC_DOMAIN . "/management/design/");
        $perm->ExitIfNotAccess(NC_PERM_TEMPLATE, 0, 0, 0, 1);
        DeleteTemplates();
        $treeChanges = $UI_CONFIG->treeChanges;
        $UI_CONFIG = new ui_config_template('list', $TemplateID);
        $UI_CONFIG->treeChanges = $treeChanges;
        nc_print_status(CONTROL_TEMPLATE_DELETED, 'ok');
        FullTemplateList();
        break;

    case 8:
        # список пользовательских настроек
        BeginHtml($Title2, $Title2, "http://" . $DOC_DOMAIN . "/management/design/");
        $UI_CONFIG = new ui_config_template('custom', $TemplateID);
        $perm->ExitIfNotAccess(NC_PERM_TEMPLATE, 0, 0, 0, 1);
        $custom_settings = $nc_core->template->get_by_id($TemplateID, 'CustomSettings');
        nc_customsettings_show(0, $TemplateID, $custom_settings);
        break;
    case 81:
        # массовое удаление пользовательских настроек
        BeginHtml($Title2, $Title2, "http://" . $DOC_DOMAIN . "/management/design/");
        $UI_CONFIG = new ui_config_template('custom', $TemplateID);
        $perm->ExitIfNotAccess(NC_PERM_TEMPLATE, 0, 0, 0, 1);
        $custom_settings = $nc_core->template->get_by_id($TemplateID, 'CustomSettings');
        $custom_settings = nc_customsettings_drop(0, $TemplateID, $custom_settings);
        nc_print_status(NETCAT_CUSTOM_PARAMETR_UPDATED, 'ok');
        nc_customsettings_show(0, $TemplateID, $custom_settings);
        break;
    case 9:
        # форма редактирования одного параметра
        BeginHtml($Title2, $Title2, "http://" . $DOC_DOMAIN . "/management/design/");
        $perm->ExitIfNotAccess(NC_PERM_TEMPLATE, 0, 0, 0, 1);
        $UI_CONFIG = new ui_config_template('custom', $TemplateID);
        $UI_CONFIG->locationHash = !empty($param) ? '#template.custom.edit(' . $TemplateID . ', ' . $param . ')' : '#template.custom.new(' . $TemplateID . ')';
        nc_customsettings_show_once(0, $TemplateID, isset($param) ? $param : null);
        break;

    case 91:
        # добавление/изменение одного параметра
        BeginHtml($Title2, $Title2, "http://" . $DOC_DOMAIN . "/management/design/");
        $perm->ExitIfNotAccess(NC_PERM_TEMPLATE, 0, 0, 0, 1);
        $UI_CONFIG = new ui_config_template('custom', $TemplateID);

        try {
            nc_customsettings_save_once();
        } catch (Exception $e) {
            nc_print_status($e->getMessage(), 'error');
            nc_customsettings_show_once(0, $TemplateID, $param);
            break;
        }

        $custom_settings = $nc_core->template->get_by_id($TemplateID, 'CustomSettings');
        nc_print_status($param ? NETCAT_CUSTOM_PARAMETR_UPDATED : NETCAT_CUSTOM_PARAMETR_ADDED, 'ok');
        nc_customsettings_show(0, $TemplateID, $custom_settings);
        break;

    case 10:
        # ручное редактирование
        BeginHtml($Title2, $Title2, "http://" . $DOC_DOMAIN . "/management/design/");
        $perm->ExitIfNotAccess(NC_PERM_TEMPLATE, 0, 0, 0, 1);
        $UI_CONFIG = new ui_config_template('custom', $TemplateID);
        $UI_CONFIG->locationHash = '#template.custom.manual(' . $TemplateID . ')';
        nc_customsettings_show_manual(0, $TemplateID);
        break;

    case 101:
        # ручное редактирование
        BeginHtml($Title2, $Title2, "http://" . $DOC_DOMAIN . "/management/design/");
        $perm->ExitIfNotAccess(NC_PERM_TEMPLATE, 0, 0, 0, 1);
        $UI_CONFIG = new ui_config_template('custom', $TemplateID);
        $UI_CONFIG->locationHash = '#template.custom.manual(' . $TemplateID . ')';
        $nc_core->template->update($TemplateID, array('CustomSettings' => $nc_core->input->fetch_get_post('CustomSettings')));
        nc_print_status(NETCAT_CUSTOM_PARAMETR_UPDATED, 'ok');
        nc_customsettings_show_manual(0, $TemplateID);
        break;


    case 15:
        # вывод переменных и функций макета
        $BBCODE = true;
        BeginHtml($Title8, $Title8, "http://" . $DOC_DOMAIN . "/management/class/groupofclass/");
        $perm->ExitIfNotAccess(NC_PERM_TEMPLATE, 0, 0, 0, 0);
        nc_form_data_insert($formtype, $window, $form, $textarea);
        break;
}

EndHtml();
?>