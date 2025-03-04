<?php

/* $Id$ */
if (!isset($templatePreview)) $templatePreview = 0;
$templatePreview+= 0;
if (!($templatePreview > 0)) {
    die();
}
$template = $templatePreview;
$NETCAT_FOLDER = join(strstr(__FILE__, "/") ? "/" : "\\", array_slice(preg_split("/[\/\\\]+/", __FILE__), 0, -4)).( strstr(__FILE__, "/") ? "/" : "\\" );
@include_once ($NETCAT_FOLDER."vars.inc.php");

require_once ($ADMIN_FOLDER.'CheckUserFunctions.inc.php');
require_once ($ADMIN_FOLDER.'template.inc.php');
require_once ($ADMIN_FOLDER.'admin.inc.php');

# Загрузка файла локализации
$lang = $nc_core->lang->detect_lang();
require_once($ADMIN_FOLDER."lang/".$lang.".php");
$nc_core->modules->load_env();

$admin_mode = 1; // для авторизации.
if (!Authorize()) {
    Refuse();
}

// Если нет Директорских прав отлуп.
if (!$perm->isSupervisor()) {
    BeginHtml(NETCAT_MODERATION_ERROR_NORIGHTS);
    nc_print_status(NETCAT_MODERATION_ERROR_NORIGHTS, "error");
    EndHtml();
    die();
}

$postTemplate_ID = $_POST["TemplateID"] + 0;

if ($postTemplate_ID > 0 && ($postTemplate_ID == $templatePreview)) {
    // Если в POST есть $TemplateID - то это попытка передать нам редактируемый макет для Preview.
    // Занесем его в сессию
    $_SESSION["PreviewTemplate"][$templatePreview] = array(
            "Settings" => $_POST["Settings"],
            "Header" => $_POST["Header"],
            "Footer" => $_POST["Footer"],
            "CustomSettings" => $_POST["CustomSettings"]);

    // Также определим дополнительные поля макета и также занесем их в сессию
    require_once ($ROOT_FOLDER."connect_io.php");
    $template_fields_sql = "SELECT b.`System_Table_Name` AS system_table_name,
  a.`Field_ID` as id,
  a.`Field_Name` as name,
  a.`TypeOfData_ID` as type,
  a.`Inheritance` as inheritance,
  a.`Format` as format
  FROM `Field` AS a, `System_Table` AS b
  WHERE a.`System_Table_ID` = b.`System_Table_ID`
  ORDER BY a.`System_Table_ID`, a.`Priority`";

    $res = $db->get_results($template_fields_sql, ARRAY_A);

    if (!empty($res)) {
        foreach ($res AS $row) {
            $mysystem_table_fields[$row['system_table_name']][] = $row;
        }
    }
    if (!empty($mysystem_table_fields["Template"])) {
        foreach ($mysystem_table_fields["Template"] as $template_field) {
            $_SESSION["PreviewTemplate"][$templatePreview][$template_field["name"]] = $_POST["f_".$template_field["name"]];
        }
    }
}

if ((!isset($_SESSION["PreviewTemplate"][$templatePreview]) ) || (!$_SESSION["PreviewTemplate"][$templatePreview])) {
    // Это попытка вызвать preview для с несуществующим данными.
    BeginHtml(NETCAT_PREVIEW_ERROR_NODATA);
    nc_print_status(NETCAT_PREVIEW_ERROR_NODATA, "error");
    EndHtml();
    die();
} elseif ($postTemplate_ID > 0 && ($postTemplate_ID != $templatePreview)) {
    // Это ошибочная ситуация POST c одним классом , а предросмотр с другим.
    BeginHtml(NETCAT_PREVIEW_ERROR_WRONGDATA);
    nc_print_status(NETCAT_PREVIEW_ERROR_WRONGDATA, "error");
    EndHtml();
    die();
}
// В предпросмотре admin_mode=0;
$admin_mode = 0;
// Первый вызов предпросмотра идет без $sub - мы рисуем карту сайта чтобы пользователь выбрал раздел.
if ((!isset($sub)) || (!$sub)) {
    require_once ($INCLUDE_FOLDER."s_common.inc.php");
    require_once ($INCLUDE_FOLDER."s_browse.inc.php");

    $sbrowse_mapsub['prefix'] = "<ul style='list-style: disc inside'>";
    $sbrowse_mapsub['suffix'] = "</ul>";

    $sbrowse_mapsub['unactive'] = "<li><a \".(\$data[\$i][Checked]==0?\"style='color:gray'\":\"\").\"href='%URL?template=$template&templatePreview=$templatePreview'>\".\$data[\$i][Subdivision_ID].\". %NAME</a>\".s_browse_sub(\$data[\$i][Subdivision_ID],\$GLOBALS[sbrowse_mapsub]).\"</li>";
    $sbrowse_mapsub['active'] = $sbrowse_mapsub['unactive'];

    if (!isset($catalogue) || (!$catalogue)) {
        $catalogue = $nc_core->catalogue->get_by_host_name($HTTP_HOST);
        $catalogue = $catalogue["Catalogue_ID"];
    } else {
        $catalogue+= 0;
    }

    BeginHtml(NETCAT_PREVIEW_INFO_CHOOSESUB);
    nc_print_status(NETCAT_PREVIEW_INFO_CHOOSESUB, "info");
    $current_sub['Subdivision_ID'] = 100000;
    $current_sub['Catalogue_ID'] = $catalogue;
    $admin_mode = 0;
    echo s_browse_sub(0, $sbrowse_mapsub, 1);

    EndHtml();
    die();
}