<?php

$NETCAT_FOLDER = join(strstr(__FILE__, "/") ? "/" : "\\", array_slice(preg_split("/[\/\\\]+/", __FILE__), 0, -4)).( strstr(__FILE__, "/") ? "/" : "\\" );
include_once ($NETCAT_FOLDER."vars.inc.php");
require ($ADMIN_FOLDER."function.inc.php");
require ($ADMIN_FOLDER."subdivision/function.inc.php");
require ($ADMIN_FOLDER."catalogue/function.inc.php");
require_once ($INCLUDE_FOLDER."s_common.inc.php");
require ($ADMIN_FOLDER."wizard/wizard.inc.php");

$Title1 = SECTION_INDEX_WIZARD_SUBMENU_SITE;
$Title2 = '';

if (!isset($phase)) $phase = 1;

if (in_array($phase, array(2))) {
    if (!$nc_core->token->verify()) {
        BeginHtml($Title2, $Title2, "http://".$DOC_DOMAIN."/settings/sql/");
        nc_print_status(NETCAT_TOKEN_INVALID, 'error');
        EndHtml();
        exit;
    }
}

switch ($phase) {

    // Создание нового сайта, ввод основных параметров
    case 1:
        if (!isset($Title10)) {
            $Title10 = '';
        }
        if (!isset($Title3)) {
            $Title3 = '';
        }
        if (!isset($Delimeter)) {
            $Delimeter = '';
        }       
        BeginHtml($Title10, $Title3.$Delimeter.$Title10, "http://".$DOC_DOMAIN."/management/sites/add/");
        $perm->ExitIfNotAccess(NC_PERM_ITEM_SITE, NC_PERM_ACTION_ADD, 0, 0, 1);
        $UI_CONFIG = new ui_config_wizard_site($phase, 0);
        CatalogueForm(0, 2, "wizard_site.php", 1, 'wizard');
        break;
    // Создали сайт, настраиваем его основные разделы и добавляем разделы, относящиеся к модулям
    case 2:
        BeginHtml($Title10, $Title3.$Delimeter.$Title10, "http://".$DOC_DOMAIN."/management/sites/add/");
        $perm->ExitIfNotAccess(NC_PERM_ITEM_SITE, NC_PERM_ACTION_ADD, 0, 0, 1);
        // Добавляем сайт


        if ($posting == 1) {
            if ($Catalogue_Name == "") {
                nc_print_status(CONTROL_CONTENT_CATALOUGE_ERROR_CASETREE_ONE, 'error');
                $UI_CONFIG = new ui_config_wizard_site(1, $CatalogueID);
                CatalogueForm($CatalogueID, 2, "wizard_site.php", 1, $action);
                break;
            }

            if (!IsAllowedDomain($Domain, 0)) {
                nc_print_status(CONTROL_CONTENT_CATALOUGE_ERROR_DUPLICATE_DOMAIN, 'error');
                $UI_CONFIG = new ui_config_wizard_site(1, $CatalogueID);
                CatalogueForm($CatalogueID, 2, "wizard_site.php", 1, $action);
                break;
            }

            if (strspn(strtolower($Domain), "abcdefghijklmnopqrstuvwxyz0123456789-.:") != strlen($Domain)) {
                nc_print_status(CONTROL_CONTENT_CATALOUGE_ERROR_CASETREE_THREE, 'error');
                $UI_CONFIG = new ui_config_wizard_site(1, $CatalogueID);
                CatalogueForm($CatalogueID, 2, "wizard_site.php", 1, $action);
                break;
            }

            if (ActionCatalogueCompleted($CatalogueID, 1)) {
                $UI_CONFIG = new ui_config_wizard_site($phase, $CatalogueID);

                $site = $db->get_row("SELECT Catalogue_ID, Catalogue_Name, Domain, Mirrors, Checked
                                FROM Catalogue
                               WHERE Catalogue_ID = '".$CatalogueID."'
                            ORDER BY Priority", ARRAY_A);

                if ($site['Checked']) {
                    $image = 'i_site.gif';
                } else {
                    $image = 'i_site_disabled.gif';
                }

                if ($type == 1) {
                    $scheme = nc_Core::get_object()->catalogue->get_scheme_by_id($CatalogueID);
                    $buttons = array();
                    $buttons[] = array("image" => "i_preview.gif",
                            "label" => CONTROL_CONTENT_CATALOUGE_FUNCS_SHOWMENU_A_VIEW,
                            "action" => "window.open('{$scheme}://".(($site['Domain']) ? $site['Domain'] : $HTTP_HOST)."');"
                    );

                    $buttons[] = array("image" => "i_folder_add.gif",
                            "label" => CONTROL_CONTENT_SUBDIVISION_FUNCS_MAINDATA_A_ADDSUBSECTION,
                            "href" => "subdivision.add(0,$site[Catalogue_ID])");
                    $UI_CONFIG->treeChanges['addNode'][] = array("nodeId" => "site-$site[Catalogue_ID]",
                            "name" => $site['Catalogue_ID'] . '. ' . strip_tags($site['Catalogue_Name']),
                            "href" => "#site.map($site[Catalogue_ID])",
                            "image" => $image,
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
                }
            }
            nc_site_wizard_map();
            //nc_site_wizard_main_sub_form(3, $CatalogueID);
        } else {
            // Настраиваем основные разделы
            $UI_CONFIG = new ui_config_wizard_site($phase, $CatalogueID);
            nc_site_wizard_map();
            //nc_site_wizard_main_sub_form(3, $CatalogueID);
        }
        break;
}
EndHtml();
?>
