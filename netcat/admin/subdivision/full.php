<?php

$NETCAT_FOLDER = join(strstr(__FILE__, "/") ? "/" : "\\", array_slice(preg_split("/[\/\\\]+/", __FILE__), 0, -4)).( strstr(__FILE__, "/") ? "/" : "\\" );
include_once ($NETCAT_FOLDER."vars.inc.php");

require_once ($ADMIN_FOLDER."function.inc.php");
require_once ($ADMIN_FOLDER."catalogue/function.inc.php");
require_once ($ADMIN_FOLDER."subdivision/function.inc.php");

$main_section = "control";
$item_id = 2;
$Delimeter = " &gt ";
$Title2 = CONTROL_CONTENT_SUBDIVISION_FULL_TITLE;

if ($CatalogueID) {
    nc_core::get_object()->cookie->set("NetCat_Sitemap_ID", $CatalogueID, time() + 2592000);
}

$UI_CONFIG = new ui_config_catalogue('map', $CatalogueID);

BeginHtml($Title2, $Title2, "http://".$DOC_DOMAIN."/management/full/");

ShowFullSubdivisionList();

EndHtml();
?>