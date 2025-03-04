<?php

# работа со списками

require("function.inc.php");
require("classificator.inc.php");

if (!isset($ClassificatorID)) {
    $ClassificatorID = $nc_core->input->fetch_get_post('ClassificatorID');
}
$ClassificatorID = (int)$ClassificatorID;
$KnownClassificatorName = GetClassificatorNameByID($ClassificatorID);
$Delimeter = " &gt ";
$main_section = "control";
$item_id = 4;
$Title1 = "";
$Title2 = CONTENT_CLASSIFICATORS;
$Title3 = "<a href=" . $ADMIN_PATH . "classificator.php>" . CONTENT_CLASSIFICATORS . "</a>";
$Title4 = CONTROL_CONTENT_CATALOUGE_ADD;
$Title5 = $KnownClassificatorName;
$Title6 = $KnownClassificatorName . " (" . CONTENT_CLASSIFICATORS_ELEMENTS . ")";
$Title7 = "<a href=\"" . $ADMIN_PATH . "classificator.php?phase=6&ClassificatorID=" . $ClassificatorID . "\">" . $KnownClassificatorName . " (" . CONTENT_CLASSIFICATORS_ELEMENTS . ")</a>";
if ($ClassificatorID && isset($IdInClassificator)) {
    $Title8 = GetOneClassificatorName($ClassificatorID, $IdInClassificator);
}
$Title9 = CONTENT_CLASSIFICATORS_LIST_ADD;
$Title10 = CONTENT_CLASSIFICATORS_LIST_EDIT;
$Title11 = CONTENT_CLASSIFICATORS_ELEMENTS_ADD;
$Title12 = CONTENT_CLASSIFICATORS_ELEMENTS_EDIT;
$Title13 = ucfirst(CONTENT_CLASSIFICATORS_ELEMENTS);
$Title14 = "<a href=" . $ADMIN_PATH . "classificator.php>" . CLASSIFICATORS_IMPORT_HEADER . "</a>";
$Title15 = CLASSIFICATORS_IMPORT_HEADER;

if (!isset($phase))
{
    $phase = null;
}
if (in_array($phase, array(2, 31, 5, 9, 11, 13))) {
    if (!$nc_core->token->verify()) {
        BeginHtml($Title2, $Title2, "http://" . $DOC_DOMAIN . "/management/lists/");
        nc_print_status(NETCAT_TOKEN_INVALID, 'error');
        EndHtml();
        exit;
    }
}

if (isset($phase)) {

    switch ($phase) {
        case 1:
            # форма для добавления списка
            BeginHtml($Title9, $Title3 . $Delimeter . $Title9, "http://" . $DOC_DOMAIN . "/management/lists/add/");
            $perm->ExitIfNotAccess(NC_PERM_CLASSIFICATOR, NC_PERM_ACTION_ADD, 0, 0, 0);
            if (+$_REQUEST['isNaked']) {
                ob_clean();
                AddClassificator_modal();
                exit;
            }
            AddClassificator("", "", 0);
            break;

        case 2:
            # собственно добавление
            BeginHtml($Title2, $Title2, "http://" . $DOC_DOMAIN . "/management/lists/");
            $perm->ExitIfNotAccess(NC_PERM_CLASSIFICATOR, NC_PERM_ACTION_ADD, 0, 0, 1);
            $System = 0;
            $NewID = AddClassificatorCompleted($ClassificatorName, $ClassificatorTable, $System, $SortType, $SortDirection);
            if ($NewID) {
                header("Location: classificator.php?phase=4&ClassificatorID={$NewID}&isNew=1");
                exit;
                nc_print_status(CLASSIFICATORS_SUCCESS_ADD, 'ok');
                OneClassificatorList($NewID, GetSortTypeByID($NewID), GetSortDirectionByID($NewID));
                echo "<script>parent.window.frames[0].location.reload();</script>";
            } else {
                nc_print_status(CLASSIFICATORS_ERROR_ADD, 'error');
            }
            break;

        case 3:
            # подтверждение удаления списка
            BeginHtml($Title2, $Title2, "http://" . $DOC_DOMAIN . "/management/lists/");
            $perm->ExitIfNotAccess(NC_PERM_CLASSIFICATOR, NC_PERM_ACTION_DEL, 0, 0, 1);
            $UI_CONFIG = new ui_config_classificators('classificator.list');
            $arr_classificator_to_del = array();
            $input = $nc_core->input->fetch_get_post();
            foreach ($input as $key => $val) {
                if (strcmp(substr($key, 0, 6), "Delete") == 0) {
                    $arr_classificator_to_del[] = $val;
                }
            }

            if (!empty($arr_classificator_to_del)) {
                ClassificatorConfirmDelete($arr_classificator_to_del);
            } else {
                ClassificatorList(0);
            }


            break;
        case 31:
            # удаление списка
            BeginHtml($Title2, $Title2, "http://" . $DOC_DOMAIN . "/management/lists/");
            $perm->ExitIfNotAccess(NC_PERM_CLASSIFICATOR, NC_PERM_ACTION_DEL, 0, 0, 1);

            $arr_classificator_to_del = array();

            foreach ($_POST as $key => $val) {
                if (strcmp(substr($key, 0, 6), "Delete") == 0) {
                    if (DeleteClassificator($val)) {
                        $arr_classificator_to_del[] = $val;
                    }
                }
            }

            $count = count($arr_classificator_to_del);
            if ($count == 1) {
                nc_print_status(CLASSIFICATORS_SUCCESS_DELETEONE, 'ok');
            } else if ($count > 1) {
                nc_print_status(CLASSIFICATORS_SUCCESS_DELETE, 'ok');
            }
            ClassificatorList(0);

            global $UI_CONFIG;

            if (!empty($arr_classificator_to_del)) {
                foreach ($arr_classificator_to_del as $int_classificator_id) {
                    $UI_CONFIG->treeChanges['deleteNode'][] = "classificator-{$int_classificator_id}";
                }
            }
            break;
        case 4:
            # форма изменения настроек списка
            BeginHtml($Title10, $Title3 . $Delimeter . $Title5, "http://" . $DOC_DOMAIN . "/management/lists/");
            $perm->ExitIfNotAccess(NC_PERM_CLASSIFICATOR, NC_PERM_ACTION_VIEW, $ClassificatorID, 0, 0);

            nc_naked_action_header();
            if (!empty($isNew)) {
                print "<script>top.frames['treeIframe'].window.location.reload(); </script>";
                nc_print_status(CLASSIFICATORS_SUCCESS_ADD, 'ok');
            }
            OneClassificatorList($ClassificatorID, GetSortTypeByID($ClassificatorID), GetSortDirectionByID($ClassificatorID));
            nc_naked_action_footer();

            break;

        case 5:
            # собственно изменение настроек списка
            BeginHtml($Title10, $Title3 . $Delimeter . $Title5, "http://" . $DOC_DOMAIN . "/management/lists/elements/");
            $perm->ExitIfNotAccess(NC_PERM_CLASSIFICATOR, NC_PERM_ACTION_ADMIN, $ClassificatorID, 0, 1);

            foreach ($_POST as $key => $val) {
                if (strcmp(substr($key, 0, 6), "Delete") == 0)
                    DeleteFromOneClassificator($ClassificatorID, $val);
                if (strcmp(substr($key, 0, 8), "Priority") == 0)
                    UpdatePriorityForOneClassificator($ClassificatorID, substr($key, 8), $val);
            }
            UpdateCheckedForOneClassificator($ClassificatorID);

            UpdateClassificatorCompleted($ClassificatorID, $ClassificatorName, isset($System) ? $System : 0, $SortType, $SortDirection);
            nc_print_status(CLASSIFICATORS_SUCCESS_EDIT, 'ok');
            OneClassificatorList($ClassificatorID, GetSortTypeByID($ClassificatorID), GetSortDirectionByID($ClassificatorID));
            global $UI_CONFIG;
            $UI_CONFIG->treeChanges['updateNode'][] = array("nodeId" => "classificator-{$ClassificatorID}",
                "name" => "$ClassificatorID. $ClassificatorName");
            break;


        case 8:
            # форма добавления записи в конкретный список
            BeginHtml($Title11, $Title3 . $Delimeter . $Title7 . $Delimeter . $Title11, "http://" . $DOC_DOMAIN . "/management/lists/elements/add/");
            $perm->ExitIfNotAccess(NC_PERM_CLASSIFICATOR, NC_PERM_ACTION_ADDELEMENT, $ClassificatorID, 0, 0);
            if (IsSystemClassificator($ClassificatorID) && !$perm->isDirectAccessClassificator(NC_PERM_ACTION_ADDELEMENT, $ClassificatorID)) {
                nc_print_status(CONTENT_CLASSIFICATORS_ERR_EDITI_GUESTRIGHTS, 'error');
                EndHtml();
                exit();
            }
            if (+$_REQUEST['isNaked']) {
                ob_clean();
                echo include_cd_files();
                InsertInOneClassificator_modal($ClassificatorID);
                exit;
            }
            InsertInOneClassificator($ClassificatorID);
            break;

        case 9:
            # окончательное добавление записи в конкретный список
            BeginHtml($Title10, $Title3 . $Delimeter . $Title5, "http://" . $DOC_DOMAIN . "/management/lists/elements/");
            $perm->ExitIfNotAccess(NC_PERM_CLASSIFICATOR, NC_PERM_ACTION_ADDELEMENT, $ClassificatorID, 0, 1);
            if (IsSystemClassificator($ClassificatorID) && !$perm->isDirectAccessClassificator(NC_PERM_ACTION_ADDELEMENT, $ClassificatorID)) {
                nc_print_status(CONTENT_CLASSIFICATORS_ERR_EDITI_GUESTRIGHTS, 'error');
                EndHtml();
                exit();
            }
            if (InsertInOneClassificatorCompleted($ClassificatorID, $NameInClassificator, $Priority, $ValueInClassificator)) {
                nc_print_status(CONTENT_CLASSIFICATORS_ELEMENTS_ADD_SUCCESS, 'ok');
                OneClassificatorList($ClassificatorID, GetSortTypeByID($ClassificatorID), GetSortDirectionByID($ClassificatorID));
                EndHtml();
                exit;
            }
            break;

        case 10:
            # форма обновления записи в конкретном списке
            BeginHtml($Title12, $Title3 . $Delimeter . $Title7 . $Delimeter . $Title8, "http://" . $DOC_DOMAIN . "/management/lists/elements/settings/");
            // Доступ view, ф-ция сама определит, просто показать или показать в режиме редакторования.
            $perm->ExitIfNotAccess(NC_PERM_CLASSIFICATOR, NC_PERM_ACTION_VIEW, $ClassificatorID, 0, 0);
            $UI_CONFIG = new ui_config_classificator_item('item.edit', $ClassificatorID, $IdInClassificator);
            if (+$_REQUEST['isNaked']) {
                ob_clean();
                echo include_cd_files();
                UpdateOneClassificator_modal($ClassificatorID, $IdInClassificator);
                exit;
            }
            UpdateOneClassificator($ClassificatorID, $IdInClassificator);

            break;

        case 11:
            # окончательное обновление записи в конкретном списке
            BeginHtml($Title2, $Title3 . $Delimeter . $Title6, "http://" . $DOC_DOMAIN . "/management/lists/elements/");
            $perm->ExitIfNotAccess(NC_PERM_CLASSIFICATOR, NC_PERM_ACTION_EDIT, $ClassificatorID, 0, 1);
            UpdateOneClassificatorCompleted($ClassificatorID, $IdInClassificator, $NameInClassificator, $ValueInClassificator);
            OneClassificatorList($ClassificatorID, GetSortTypeByID($ClassificatorID), GetSortDirectionByID($ClassificatorID));
            break;

        case 12:
            # форма для импортирования списка
            BeginHtml($Title15, $Title3 . $Delimeter . $Title15, "http://" . $DOC_DOMAIN . "/management/lists/import/");
            $perm->ExitIfNotAccess(NC_PERM_CLASSIFICATOR, NC_PERM_ACTION_ADD, 0, 0);

            if (+$_REQUEST['isNaked']) {
                ob_clean();
                ImportClassificator_modal();
                exit;
            }

            ImportClassificator("", "", 0);
            break;


        case 13:
            # импортирование списка
            BeginHtml($Title2, $Title2, "http://" . $DOC_DOMAIN . "/management/lists/");
            $perm->ExitIfNotAccess(NC_PERM_CLASSIFICATOR, NC_PERM_ACTION_ADD, 0, 1);
            ImportClassificatorCompleted($ClassificatorName, $ClassificatorTable, $System, $SortType, $SortDirection, $FileCSV);

            nc_naked_action_header();
            ClassificatorList(0);
            nc_naked_action_footer();

            break;
    }
} else {
    $UI_CONFIG = new ui_config_classificators('classificator.list');
    BeginHtml($Title2, $Title2, "http://" . $DOC_DOMAIN . "/management/lists/");
    $perm->ExitIfNotAccess(NC_PERM_CLASSIFICATOR, NC_PERM_ACTION_LIST, 0, 0, 0);

    nc_naked_action_header();
    ClassificatorList(0);
    nc_naked_action_footer();
}
print("<script>\n"
        ."\$nc(document).ready(function() {\n"
        . "if (!(top.\$nc('#tree_add_link').length))"
        . "top.\$nc('#tree_mode_name').append('<a class=\"button icons nc-icon nc--dev-components-add nc--hovered\" id=\"tree_add_link\" href=\"#classificator.add\"></a>');"
        . "});"
        . "</script>");
EndHtml();
