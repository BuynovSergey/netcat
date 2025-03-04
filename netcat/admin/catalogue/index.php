<?php

$NETCAT_FOLDER = join(strstr(__FILE__, "/") ? "/" : "\\", array_slice(preg_split("/[\/\\\]+/", __FILE__), 0, -4)).( strstr(__FILE__, "/") ? "/" : "\\" );

include_once ($NETCAT_FOLDER."vars.inc.php");
require_once ($ADMIN_FOLDER."function.inc.php");
require_once ($ADMIN_FOLDER."subdivision/function.inc.php");
require_once ($ADMIN_FOLDER."catalogue/function.inc.php");
require_once ($INCLUDE_FOLDER."s_common.inc.php");

/**
 * @var $perm Permission
 */
if (isset($CatalogueID)) {
    $CatalogueID = intval($CatalogueID);
} else {
    $CatalogueID = null;
}

$Catalogue_Name = !empty($Catalogue_Name) ? $Catalogue_Name : '';

if ($CatalogueID) {
    $CatalogueName = $nc_core->catalogue->get_by_id($CatalogueID, "Catalogue_Name");
    $message = $CatalogueID;
}

$Delimeter = " &gt ";
$main_section = "control";
$item_id = 1;
$Title2 = CONTROL_CONTENT_CATALOUGE_SITE;
$Title3 = "<a href=\"".$ADMIN_PATH."catalogue/\">".CONTROL_CONTENT_SUBDIVISION_INDEX_SITES."</a>";

if (!isset($CatalogueName)) {
    $CatalogueName = '';
}

$Title5 = $CatalogueName;
$Title6 = "<a href=\"".$ADMIN_PATH."catalogue/?phase=6&CatalogueID=".$CatalogueID."\">".$CatalogueName."</a>";
$Title7 = CONTROL_CONTENT_CATALOUGE_SITEDELCONFIRM;
$Title8 = $CatalogueName;
$Title9 = CONTROL_CONTENT_CATALOUGE_ADDSECTION;
$Title10 = CONTROL_CONTENT_CATALOUGE_ADDSITE;
$Title11 = $CatalogueName;
$Title12 = CONTROL_CONTENT_CATALOUGE_SITEDELCONFIRM;
$Title13 = CONTROL_CONTENT_CATALOUGE_SITEOPTIONS;


if (!isset($phase)) $phase = 1;

if (in_array($phase, array(3, 4, 5))) {
    if (!$nc_core->token->verify()) {
        BeginHtml($Title13, $Title3.$Delimeter.$Title6.$Delimeter.$Title13, "http://".$DOC_DOMAIN."/management/sites/settings/");
        nc_print_status(NETCAT_TOKEN_INVALID, 'error');
        EndHtml();
        exit;
    }
}

try {
    switch ($phase) {
        // Список сайтов
        case 1:
            BeginHtml(
                $Title13,
                $Title3 . $Delimeter . $Title6 . $Delimeter . $Title13,
                "http://" . $DOC_DOMAIN . "/management/sites/settings/"
            );
            $perm->ExitIfNotAccess(NC_PERM_ITEM_SITE, 'viewall', 0, 0, 0);
            $UI_CONFIG = new ui_list_catalogue();
            ShowCatalogueList();
            break;

        // Отображение формы редактирования/добавления
        case 2:
            if (empty($type)) {
                $type = 2;
            }

            if ($type == 1) { // добавление
                BeginHtml($Title10, $Title3 . $Delimeter . $Title10, "http://" . $DOC_DOMAIN . "/management/sites/add/");
                $perm->ExitIfNotAccess(NC_PERM_ITEM_SITE, NC_PERM_ACTION_ADD, 0, 0, 0);
                $UI_CONFIG = new ui_config_catalogue('add', 0);
                $action = 'all';
            }

            if ($type == 2) { // редактирование
                if (!isset($action)) {
                    $action = '';
                }
                BeginHtml(
                    $Title13,
                    $Title3 . $Delimeter . $Title6 . $Delimeter . $Title13,
                    "http://" . $DOC_DOMAIN . "/management/sites/settings/"
                );
                $perm->ExitIfNotAccess(NC_PERM_ITEM_SITE, NC_PERM_ACTION_EDIT, $CatalogueID, 0, 0);
                $UI_CONFIG = new ui_config_catalogue('edit', $CatalogueID, $action);
            }

            CatalogueForm($CatalogueID, 3, "index.php", $type, $action);
            break;

        case 3:
            // Обновление сайта с последующим отображением списка сайтов
            if ($type == 1) { // Добавление
                BeginHtml($Title2, $Title2, "http://" . $DOC_DOMAIN . "/management/sites/");
                $perm->ExitIfNotAccess(NC_PERM_ITEM_SITE, NC_PERM_ACTION_ADD, 0, 0, 1);
            }
            if ($type == 2) { // Изменение
                BeginHtml($Title11, $Title3 . $Delimeter . $Title5, "http://" . $DOC_DOMAIN . "/management/sites/info/");
                $perm->ExitIfNotAccess(NC_PERM_ITEM_SITE, NC_PERM_ACTION_EDIT, $CatalogueID, 0, 1);
            }

            if (!defined("NC_SAAS_SITE") && $Catalogue_Name == "") {
                nc_print_status(CONTROL_CONTENT_CATALOUGE_ERROR_CASETREE_ONE, 'error');
                CatalogueForm($CatalogueID, 3, "index.php", $type, $action);
                break;
            }

            if (isset($Domain) && !IsAllowedDomain($Domain, $CatalogueID)) {
                nc_print_status(CONTROL_CONTENT_CATALOUGE_ERROR_DUPLICATE_DOMAIN, 'error');
                CatalogueForm($CatalogueID, 3, "index.php", $type, $action);
                break;
            }

            // Домен сайта должен содержать только  буквы, цифры, подчеркивание, дефис и точку и порт (например, :8080), либо быть пустым
            if (isset($Domain) && !nc_preg_match($nc_core->NC_UNICODE ? "/^[-0-9a-zа-яё._]*(:\d+)*$/i" : "/^[-0-9a-z._]*(:\d+)*$/i", $Domain)) {
                nc_print_status(CONTROL_CONTENT_CATALOUGE_ERROR_CASETREE_THREE, 'error');
                CatalogueForm($CatalogueID, 3, "index.php", $type, $action);
                break;
            }

            $infoMessage = checkDomain(isset($Domain) ? $Domain : "" , $CatalogueID);

            if (!empty($infoMessage)) {
                nc_print_status(CONTROL_CONTENT_CATALOUGE_ERROR_INCORRECT_DOMAIN_FULLTEXT, 'info');
            }

            if (ActionCatalogueCompleted($CatalogueID, $type)) {
                $UI_CONFIG = new ui_config_catalogue('map', $CatalogueID);
                $site = $nc_core->catalogue->get_by_id($CatalogueID);
                $scheme = nc_Core::get_object()->catalogue->get_scheme_by_id($CatalogueID);

                $image = $site['Checked'] ? 'site' : 'site nc--disabled';
                
                if ($type == 1) {
                    $buttons = array();
                    $buttons[] = array(
                        "image" => "i_preview.gif",
                        "label" => CONTROL_CONTENT_CATALOUGE_FUNCS_SHOWMENU_A_VIEW,
                        "action" => "window.open('{$scheme}://" . ($site['Domain'] ?: $HTTP_HOST) . "');",
                        "icon" => "arrow-right",
                        "sprite" => true,
                    );

                    $buttons[] = array(
                        "image" => "i_folder_add.gif",
                        "label" => CONTROL_CONTENT_SUBDIVISION_FUNCS_MAINDATA_A_ADDSUBSECTION,
                        "action" => "parent.location.hash = 'subdivision.add(0,$site[Catalogue_ID])'",
                        "icon" => "folder-add",
                        "sprite" => true,
                    );

                    $UI_CONFIG->treeChanges['addNode'][] = array(
                        "nodeId" => "site-$site[Catalogue_ID]",
                        "name" => $site['Catalogue_ID'] . '. ' . strip_tags($site['Catalogue_Name']),
                        "href" => "#site.map($site[Catalogue_ID])",
                        "sprite" => $image,
                        "hasChildren" => true,
                        "acceptDropFn" => "treeSitemapAcceptDrop",
                        "onDropFn" => "treeSitemapOnDrop",
                        "dragEnabled" => true,
                        "buttons" => $buttons
                    );

                    $UI_CONFIG->addNavBarCatalogue = array(
                        'name' => $site["Catalogue_Name"],
                        'href' => "#site.map($site[Catalogue_ID])",
                    );

                    nc_print_status(CONTROL_CONTENT_CATALOUGE_SUCCESS_ADD, 'ok');
                    ShowFullSubdivisionList();
                } else if ($type == 2) {
                    $UI_CONFIG = new ui_config_catalogue('edit', $CatalogueID, $action);
                    $UI_CONFIG->treeChanges['updateNode'][] = array(
                        "nodeId" => "site-$site[Catalogue_ID]",
                        "name" => $site['Catalogue_ID'] . '. ' . strip_tags($site['Catalogue_Name']),
                        "sprite" => $image,
                        "preview_action" => "window.open('{$scheme}://" . ($site['Domain'] ?: $HTTP_HOST) . "');"
                    );

                    nc_print_status(CONTROL_CONTENT_CATALOUGE_SUCCESS_EDIT, 'ok');
                    CatalogueForm($CatalogueID, 3, "index.php", 2, $action);
                }
            }

            break;

        case 4:
            // Запрос подтверждения удаления сайта
            if (CheckIfDelete ()) {
                BeginHtml($Title12, $Title3.$Delimeter.$Title7, "http://".$DOC_DOMAIN."/management/sites/");
                $perm->ExitIfNotAccess(NC_PERM_ITEM_SITE, NC_PERM_ACTION_DEL, 0, 0, 1);
                UpdateCataloguePriority ();
                $UI_CONFIG = new ui_config_catalogue('delete', $CatalogueID);
                AscIfDelete(5, "index.php");
            } else {
                BeginHtml($Title2, $Title2, "http://".$DOC_DOMAIN."/management/sites/");
                $perm->ExitIfNotAccess(NC_PERM_ITEM_SITE, NC_PERM_ACTION_DEL, 0, 0, 1);
                UpdateCataloguePriority ();
                $UI_CONFIG = new ui_list_catalogue();
                ShowCatalogueList ();
            }

            break;

        case 5:
            // Удаление сайта
            BeginHtml(
                $Title13,
                $Title3 . $Delimeter . $Title6 . $Delimeter . $Title13,
                "http://" . $DOC_DOMAIN . "/management/sites/settings/"
            );

            $perm->ExitIfNotAccess(NC_PERM_ITEM_SITE, NC_PERM_ACTION_DEL, 0, 0, 1);
            $UI_CONFIG = new ui_list_catalogue();

            DeleteCatalogue();
            ShowCatalogueList();

            break;

        case 6:
            // Отображение информации о сайте
            BeginHtml($Title11, $Title3 . $Delimeter . $Title5, "http://" . $DOC_DOMAIN . "/management/sites/info/");
            $perm->ExitIfNotAccess(NC_PERM_ITEM_SITE, NC_PERM_ACTION_INFO, $CatalogueID, 0, 0);
            $UI_CONFIG = new ui_config_catalogue('info', $CatalogueID, 'info');
            ShowMenu($CatalogueID, 2, "index.php", 11, "index.php");

            break;
    }
} catch (nc_Exception_DB_Error $e) {
    nc_print_status(sprintf(NETCAT_ERROR_SQL, $e->query(), $e->error()), 'error');
} catch (Exception $e) {
    nc_print_status($e->getMessage(), 'error');
}


EndHtml ();
