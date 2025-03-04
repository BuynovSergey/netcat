<?php

/* $Id: index.php 7691 2012-07-17 05:46:42Z alive $ */

$NETCAT_FOLDER = join(strstr(__FILE__, "/") ? "/" : "\\", array_slice(preg_split("/[\/\\\]+/", __FILE__), 0, -4)) . (strstr(__FILE__, "/") ? "/" : "\\");
include_once $NETCAT_FOLDER . 'vars.inc.php';
require $ADMIN_FOLDER . 'function.inc.php';
require $ADMIN_FOLDER . 'catalogue/function.inc.php';
require $ADMIN_FOLDER . 'subdivision/subclass.inc.php';
require_once $INCLUDE_FOLDER . 's_common.inc.php';
require $ADMIN_FOLDER . 'subdivision/function.inc.php';

/** @var Permission $perm */
/** @var nc_Core $nc_core */

$SubdivisionID += 0;

if ($SubdivisionID) {
    try {
        $nc_core->subdivision->get_by_id($SubdivisionID);
    } catch (Exception $e) {
        BeginHtml();
        nc_print_status(CONTROL_CONTENT_CATALOUGE_FUNCS_ERROR_NOSUBDIVISION, 'error');
        EndHtml();
        exit();
    }
}

$Delimeter = " &gt ";
$main_section = "control";
$item_id = 1;
$Title1 = "<a href=\"" . $ADMIN_PATH . "catalogue/\">" . CONTROL_CONTENT_SUBDIVISION_INDEX_SITES . "</a>";
$Title2 = CONTROL_CONTENT_SUBDIVISION_INDEX_SECTIONS;

$Title3 = "<a href=\"" . $ADMIN_PATH . "subdivision/?";
if (!isset($ParentSubID)) {
    $ParentSubID = null;
}
if (isset($CatalogueID) && $CatalogueID) {
    $Title3 .= "CatalogueID=" . $CatalogueID;
} else {
    $Title3 .= "ParentSubID=" . $ParentSubID;
}
$Title3 .= "\">" . CONTROL_CONTENT_SUBDIVISION_INDEX_SITES . "</a>";

$Title4 = CONTROL_CONTENT_SUBDIVISION_INDEX_ADDSECTION;
if ($SubdivisionID) {
    $nc_core->subdivision->get_by_id($SubdivisionID, "Subdivision_Name");
}
if (!isset($SubdivisionName)) {
    $SubdivisionName = '';
}
$Title5 = $SubdivisionName;
$Title8 = CONTROL_CONTENT_SUBDIVISION_INDEX_OPTIONSECTION;
$Title9 = CONTROL_CONTENT_SUBDIVISION_INDEX_DELETECONFIRMATION;
$Title10 = CONTROL_CONTENT_SUBDIVISION_INDEX_MOVESECTION;
$Title11 = CONTROL_CONTENT_SUBDIVISION_INDEX_SECTIONS;

$CatalogueURL = $ADMIN_PATH . "catalogue/?phase=6&CatalogueID=";
$SubdivisionURL = $ADMIN_PATH . "subdivision/?phase=4&SubdivisionID=";
if (!isset($CatalogueID)) {
    $CatalogueID = null;
}
$loc = new SubdivisionLocation($CatalogueID, $ParentSubID, $SubdivisionID);
if ($phase != 14) {
    $sh = new SubdivisionHierarchy($Delimeter, $CatalogueURL, $SubdivisionURL);
}

// default phase
if (!isset($phase)) {
    $phase = 1;
}

if (in_array($phase, array(3, 6, 11)) && !$nc_core->token->verify()) {
    BeginHtml($Title2, $Title2, "http://" . $DOC_DOMAIN . "/management/favorites/");
    nc_print_status(NETCAT_TOKEN_INVALID, 'error');
    EndHtml();
    exit;
}

switch ($phase) {
    // покажем список рубрик
    case 1:
        BeginHtml($Title2, $Title1 . $Delimeter . $sh->Link . $Delimeter . $Title11, "http://" . $DOC_DOMAIN . "/management/sites/sections/");
        if ($CatalogueID) {
            $perm->ExitIfNotAccess(NC_PERM_ITEM_SITE, NC_PERM_ACTION_INFO, $CatalogueID, 0, 0);
            $UI_CONFIG = new ui_config_catalogue('info', $CatalogueID, 'sublist', $loc->ParentSubID);
        } else {
            $perm->ExitIfNotAccess(NC_PERM_ITEM_SUB, NC_PERM_ACTION_LIST, $ParentSubID, 0, 0);
            $UI_CONFIG = new ui_config_subdivision_info($ParentSubID, 'sublist');
        }
        ShowSubdivisionList();
        break;

    // форма добавления раздела
    case 2:
        BeginHtml($Title4, $Title1 . $Delimeter . $sh->Link . $Delimeter . $Title4, "http://" . $DOC_DOMAIN . "/management/sites/sections/add/");
        if ($ParentSubID) {
            $perm->ExitIfNotAccess(NC_PERM_ITEM_SUB, NC_PERM_ACTION_ADD, $ParentSubID, 0, 0);
        } else {
            $perm->ExitIfNotAccess(NC_PERM_ITEM_SITE, NC_PERM_ACTION_ADDSUB, $CatalogueID, 0, 0);
        }
        $UI_CONFIG = new ui_config_subdivision_add($ParentSubID, $CatalogueID);

        nc_subdivision_show_add_form($CatalogueID, $ParentSubID);
        break;

    // добавление раздела
    case 3:
        BeginHtml($Title2, $Title1 . $Delimeter . $sh->Link . $Delimeter . $Title11, "http://" . $DOC_DOMAIN . "/management/sites/sections/");
        if ($ParentSubID) {
            $perm->ExitIfNotAccess(NC_PERM_ITEM_SUB, NC_PERM_ACTION_ADD, $ParentSubID, 0, 1);
        } else {
            $perm->ExitIfNotAccess(NC_PERM_ITEM_SITE, NC_PERM_ACTION_ADDSUB, $CatalogueID, 0, 1);
        }

        try {
            $SubdivisionID = nc_subdivision_add();
            ob_end_clean();
            header("Location: " . $ADMIN_PATH . "subdivision/SubClass.php?" . ($Class_ID ? "" : "phase=1&") . "SubdivisionID=" . $SubdivisionID . "&subdivisionTreeChange=1");
            exit();
        } catch (Exception $e) {
            nc_print_status($e->getMessage(), 'error');
            nc_subdivision_show_add_form($CatalogueID, $ParentSubID);
        }
        break;


    // покажем меню операций для рубрики
    case 4:
        BeginHtml($Title5, $Title1 . $Delimeter . $sh->Link, "http://" . $DOC_DOMAIN . "/management/sites/sections/info/");
        $perm->ExitIfNotAccess(NC_PERM_ITEM_SUB, NC_PERM_ACTION_INFO, $SubdivisionID, 0, 0);
        $UI_CONFIG = new ui_config_subdivision_info($SubdivisionID, 'info');
        ShowSubdivisionMenu($SubdivisionID, 9, "index.php", 5, "index.php", 12, "index.php");
        break;

    // форма изменения раздела
    case 5:
        if (!$view) {
            $view = 'edit';
        }

        if ($view !== 'all') {
            BeginHtml($Title8, $Title1 . $Delimeter . $sh->Link . $Delimeter . $Title8, "http://" . $DOC_DOMAIN . "/management/sites/sections/settings/");
        }

        $perm->ExitIfNotAccess(NC_PERM_ITEM_SUB, NC_PERM_ACTION_EDIT, $SubdivisionID, 0, 0);

        if ($view === 'all') {
            nc_subdivision_print_modal_prefix($SubdivisionID);
        } else {
            $UI_CONFIG = new ui_config_subdivision_settings($SubdivisionID, $view);
        }

        nc_subdivision_show_edit_form($SubdivisionID, $view);

        if ($view === 'all') {
            nc_subdivision_print_modal_suffix();
            exit;
        }

        break;

    case 6:
        $UI_CONFIG = new ui_config_subdivision_settings($SubdivisionID, $view);
        BeginHtml($Title8, $Title1 . $Delimeter . $sh->Link . $Delimeter . $Title8, "http://" . $DOC_DOMAIN . "/management/sites/sections/settings/");
        $perm->ExitIfNotAccess(NC_PERM_ITEM_SUB, NC_PERM_ACTION_EDIT, $SubdivisionID, 0, 1);

        if ($view === 'all') {
            $oldHiddenURL = GetHiddenURL($SubdivisionID);
            nc_subdivision_save($view);
            $buffer = ob_get_clean();
            $errorMessage = strpos($buffer, "<div id='statusMessage' class='nc_print_status status_error'>");
            if ($errorMessage !== false) {
                $errorStart = strpos($buffer, "<div class='nc_print_status_text'>");
                $errorEnd = strpos($buffer, "</div>", $errorStart);
                $error = substr($buffer, $errorStart, $errorEnd - $errorStart);

                $isNaked = 1;
                nc_print_status($error, 'error');
            } else {
                echo 'OK';
                // перезагрузить страницу, так как изменение почти всех настроек
                // раздела (вкл/выкл, приоритет, название, ключевое слово, макет
                // дизайна и егонастройки) приводят к обширным и сложно предсказуемым
                // изменениям различных частей страницы (title, стили, содержимое
                // меню, заголовки и т.п.)
                echo "\nReloadPage=1";
                if ($oldHiddenURL != GetHiddenURL($SubdivisionID)) {
                    echo "\nNewHiddenURL=" . GetHiddenURL($SubdivisionID);
                }
            }
            exit;
        }

        try {
            if (nc_subdivision_save($view)) {
                $UI_CONFIG = new ui_config_subdivision_settings($SubdivisionID, $view);
                nc_print_status(CONTROL_CONTENT_SUBDIVISION_SUCCESS_EDIT, 'ok');
            }
        } catch (Exception $e) {
            nc_print_status($e->getMessage(), 'error');
        }
        nc_subdivision_show_edit_form($SubdivisionID, $view);
        break;

    // изменение раздела
    case 6000:
        BeginHtml($Title2, $Title1 . $Delimeter . $sh->Link, "http://" . $DOC_DOMAIN . "/management/sites/sections/info/");
        $perm->ExitIfNotAccess(NC_PERM_ITEM_SUB, NC_PERM_ACTION_EDIT, $SubdivisionID, 0, 1);

        if ($posting == 1) {
            // визуальные настройки
            $settings_array = $nc_core->db->get_var(
                "SELECT `CustomSettingsTemplate` FROM `Class`
                 WHERE `Class_ID` = '" . (int)$custom_class_id . "'"
            );

            // проверка названия раздела
            if (!$Subdivision_Name) {
                $posting = 0;
                nc_print_status(CONTROL_CONTENT_SUBDIVISION_INDEX_ERROR_THREE_NAME, 'error');
                SubdivisionForm(6, "index.php", 2, $full);
                break;
            }

            // проверка уникальности ключевого слова для текущего раздела
            if (!IsAllowedSubdivisionEnglishName($EnglishName, $loc->ParentSubID, $loc->SubdivisionID, $loc->CatalogueID)) {
                $posting = 0;
                nc_print_status(CONTROL_CONTENT_SUBDIVISION_INDEX_ERROR_THREE_KEYWORD, 'error');
                SubdivisionForm(6, "index.php", 2, $full);
                break;
            }

            // проверка символов для ключевого слова
            if (!$nc_core->subdivision->validate_english_name($EnglishName)) {
                $posting = 0;
                nc_print_status(CONTROL_CONTENT_SUBDIVISION_SUBCLASS_ERROR_KEYWORD_INVALID, 'error');
                SubdivisionForm(6, "index.php", 2, $full);
                break;
            }

            // если раздел изменен переходим к информации по разделу или к дереву разделов
            if (ActionSubdivisionCompleted($type)) {
                nc_print_status(CONTROL_CONTENT_SUBDIVISION_SUCCESS_EDIT, 'ok');
                SubdivisionForm(6, "index.php", 2, $full);
                break;
            } // какая-то ошибка
            else {
                if ($nc_core->db->last_error) {
                    nc_print_status(sprintf(NETCAT_ERROR_SQL, $nc_core->db->last_query, $nc_core->db->last_error), 'error');
                }
            }
        }
        break;

    // спросить, действительно ли надо удалить рубрику
    case 7:
        foreach ($nc_core->input->fetch_get_post() as $key => $val) {
            if (strpos($key, 'Delete') === 0) {
                $sub_id = (int)substr($key, 6);
                $sub_ids[] = (int)substr($key, 6);
            }
        }

        if (!$ParentSubID && !$CatalogueID) {
            list($CatalogueID, $ParentSubID) = $nc_core->db->get_row("SELECT Catalogue_ID, Parent_Sub_ID FROM Subdivision WHERE Subdivision_ID = '" . $sub_id . "'", ARRAY_N);
        }

        if (CheckIfDelete()) {
            BeginHtml($Title9, $Title1 . $Delimeter . $sh->Link . $Delimeter . $Title9, "http://" . $DOC_DOMAIN . "/management/sites/sections/");
            if ($ParentSubID) {
                $perm->ExitIfNotAccess(NC_PERM_ITEM_SUB, NC_PERM_ACTION_DEL, $ParentSubID, 0, 1);
            } else {
                $perm->ExitIfNotAccess(NC_PERM_ITEM_SITE, NC_PERM_ACTION_DELSUB, $CatalogueID, 0, 1);
            }

            UpdateSubdivisionPriority();
            foreach ($sub_ids as $ksi => $vsi) {
                $UI_CONFIG = new ui_config_subdivision_delete($vsi);
            }
            AscIfDeleteSubdivision(11, "index.php");

        } else {
            BeginHtml($Title2, $Title1 . $Delimeter . $sh->Link . $Delimeter . $Title11, "http://" . $DOC_DOMAIN . "/management/sites/sections/");
            if ($ParentSubID) {
                $perm->ExitIfNotAccess(NC_PERM_ITEM_SUB, NC_PERM_ACTION_DEL, $ParentSubID, 0, 1);
                $UI_CONFIG = new ui_config_subdivision_info($ParentSubID, 'sublist');
            } else {
                $perm->ExitIfNotAccess(NC_PERM_ITEM_SITE, NC_PERM_ACTION_DELSUB, $CatalogueID, 0, 1);
                $UI_CONFIG = new ui_config_catalogue('info', $CatalogueID, 'sublist', $loc->ParentSubID);
            }

            UpdateSubdivisionPriority();
            ShowSubdivisionList();
        }
        break;

    case 8:
        BeginHtml();
        ?>
        <div class="nc-message-restored nc--hide">
            <? nc_print_status($nc_core->lang->get_numerical_inclination(1, array(NETCAT_TRASH_RECOVERED_SK1, NETCAT_TRASH_RECOVERED_SK2, NETCAT_TRASH_RECOVERED_SK3)) . " 1 " . $nc_core->lang->get_numerical_inclination(1, array(NETCAT_TRASH_MESSAGES_SK1, NETCAT_TRASH_MESSAGES_SK2, NETCAT_TRASH_MESSAGES_SK3)), 'ok'); ?>
        </div>
        <div class="nc-message-removed nc--hide">
            <? nc_print_status('1 ' . $nc_core->lang->get_numerical_inclination(1, array(NETCAT_ADMIN_TRASH_OBJECT_HAS_BEEN_REMOVED, NETCAT_ADMIN_TRASH_OBJECTS_REMOVED, NETCAT_ADMIN_TRASH_OBJECT_IS_REMOVED)), 'info'); ?>
        </div>
        <?
        $empty = true;
        $sql = "SELECT `Sub_Class_ID` FROM `Sub_Class` WHERE `Subdivision_ID` = {$SubdivisionID} ORDER BY `Priority`";
        $removed_cc_list = (array)$nc_core->db->get_col($sql);
        foreach ($removed_cc_list as $removed_cc) {
            $sql = "SELECT * FROM `Trash_Data` WHERE `Sub_Class_ID` = {$removed_cc} LIMIT 1";
            if ($nc_core->db->get_row($sql)) {
                $empty = false;
                include_once $nc_core->ADMIN_FOLDER . 'trash/get_trash.php';
            }
        }

        if ($empty) {
            nc_print_status(NETCAT_TRASH_NOMESSAGES, 'info');
        }
        ?>
        <script>
            $nc(function () {
                $nc('.nc-button-restore, .nc-button-remove').on('click', function () {
                    var $this = $nc(this);
                    $nc('.nc-message-restored, .nc-message-removed').hide();
                    nc.process_start('trash_action');
                    $nc.get($this.attr('href'), function () {
                        if ($this.hasClass('nc-button-restore')) {
                            $nc('.nc-message-restored').show();
                        } else {
                            $nc('.nc-message-removed').show();
                        }
                        $this.closest('.nc_idtab').parent().remove();
                        nc.process_stop('trash_action');
                    });
                    return false;
                });
            });
        </script>
        <?
        $UI_CONFIG = new ui_config_subdivision_trashed_objects($SubdivisionID);
        break;

    // удалим [один или несколько] разделов
    case 11:
        BeginHtml($Title2, $Title1 . $Delimeter . $sh->Link . $Delimeter . $Title11, "http://" . $DOC_DOMAIN . "/admin/catalogue/sections/");
        if ($ParentSubID) {
            $perm->ExitIfNotAccess(NC_PERM_ITEM_SUB, NC_PERM_ACTION_DEL, $ParentSubID, 0, 1);
            $UI_CONFIG = new ui_config_subdivision_info($ParentSubID, 'sublist');
        } else {
            $perm->ExitIfNotAccess(NC_PERM_ITEM_SITE, NC_PERM_ACTION_DELSUB, $CatalogueID, 0, 1);
            $UI_CONFIG = new ui_config_catalogue('map', $CatalogueID);
        }

        DeleteSubdivision();
        ShowSubdivisionList();

        break;

    case 13:
        // 2.4 - собственно перенесем рубрику в новую родительскую рубрику
        break;

    case 14: //Просмотр
        if ($SubClassID || $SubdivisionID) {
            $href = '';
            if ($SubdivisionID) {
                $href = nc_folder_url($SubdivisionID);
                $SubClassID = 0;
            }
            if ($SubClassID) {
                $href = nc_infoblock_url($SubClassID);
                $SubdivisionID = 0;
            }
            $UI_CONFIG = new ui_config_subdivision_preview($SubdivisionID, $SubClassID);
            $UI_CONFIG->actionButtons[] = array(
                "id" => "preview",
                "caption" => SUBDIVISION_TAB_PREVIEW_BUTTON_PREVIEW,
                "action" => "urlDispatcher.load('$href', '1')",

            );
            print "<script>window.onload = function(){ window.location.href='$href'; }</script>";
        }
        break;

    case 15: // покажем права для раздела
        BeginHtml($Title5, $Title1 . $Delimeter . $sh->Link, "http://" . $DOC_DOMAIN . "/management/sites/sections/info/");
        $perm->ExitIfNotAccess(NC_PERM_ITEM_SUB, NC_PERM_ACTION_INFO, $SubdivisionID, 0, 0);
        $UI_CONFIG = new ui_config_subdivision_info($SubdivisionID, 'userlist');
        nc_show_subdivision_rights($SubdivisionID);
        break;

    case 16: //покажем диалог со списком разделов для выбора корня добавления
        $perm->ExitIfNotAccess(NC_PERM_ITEM_SITE, NC_PERM_ACTION_ADDSUB, $CatalogueID, 0, 0);
        ?>
        <div class="nc-modal-dialog nc-modal-dialog-without-tabs nc-modal-dialog-without-header" data-confirm-close="no">
            <div class="nc-modal-dialog-header">
                <h2><?php echo CONTROL_CONTENT_SUBDIVISION_FUNCS_ADDSECTION; ?></h2>
            </div>
            <div class="nc-modal-dialog-body">
                <h4><?= CONTROL_CONTENT_SUBDIVISION_FUNCS_SELECT_ROOT_SECTION; ?></h4>
                <input type="hidden" name="CatalogueID" value="<?= (int)$CatalogueID; ?>">
                <select name="ParentSubID" style="width: 270px;">
                    <option value="0"><?php echo CONTROL_CONTENT_SUBDIVISION_FUNCS_LINEADD_ROOT; ?></option>
                    <?php echo nc_print_root_subdivisions($CatalogueID); ?>
                </select>
            </div>
            <div class="nc-modal-dialog-footer">
                <button class='nc-btn nc--blue' onclick='nc_load_add_subdivision_form();'><?php echo CONTROL_CONTENT_SUBDIVISION_FUNCS_CONTINUE; ?></button>
                <button data-action="close"><?php echo CONTROL_BUTTON_CANCEL; ?></button>
            </div>
        </div>
        <script type='text/javascript'>
            function nc_load_add_subdivision_form() {
                var CatalogueID = $nc("INPUT[name=CatalogueID]").val();
                var ParentSubID = $nc("SELECT[name=ParentSubID]").val();
                mainView.loadIframe('<?php echo $ADMIN_PATH; ?>subdivision/index.php?phase=2&CatalogueID=' + CatalogueID + '&ParentSubID=' + ParentSubID);
                nc.ui.modal_dialog.get_current_dialog().close();
            }
        </script>
        <?php
        exit;
}

EndHtml();
