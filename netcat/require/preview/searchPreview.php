<?php

/* $Id$ */
if (!isset($classPreview)) $classPreview = 0;
$classPreview+= 0;
if (!($classPreview > 0)) die();

$NETCAT_FOLDER = join(strstr(__FILE__, "/") ? "/" : "\\", array_slice(preg_split("/[\/\\\]+/", __FILE__), 0, -4)).( strstr(__FILE__, "/") ? "/" : "\\" );
@include_once ($NETCAT_FOLDER."vars.inc.php");

require_once ($ADMIN_FOLDER.'CheckUserFunctions.inc.php');
require_once ($ADMIN_FOLDER.'template.inc.php');
require_once ($ADMIN_FOLDER.'admin.inc.php');

# Загрузка файла локализации
$lang = $nc_core->lang->detect_lang();
require_once($ADMIN_FOLDER."lang/".$lang.".php");
$nc_core->modules->load_env();

$classPreview+= 0;
$admin_mode = 1; // для авторизации.
if (!Authorize()) Refuse();

if (!$perm->isSupervisor()) {
    BeginHtml(NETCAT_MODERATION_ERROR_NORIGHTS);
    nc_print_status(NETCAT_MODERATION_ERROR_NORIGHTS, "error");
    EndHtml();
    die();
}

$postClass_ID = $_POST["ClassID"] + 0;

$ClassTemplate = $db->get_var("SELECT `ClassTemplate` FROM `Class` WHERE `Class_ID` = '".$classPreview."'");

if ($postClass_ID > 0 && ($postClass_ID == $classPreview)) {
// Если в POST есть $ClassID - то это попытка передать нам редактируемую форму поиска для Preview.
// Занесем его в сессию
    $_SESSION["PreviewClass"][$classPreview] = array("SearchTemplate" => $_POST["SearchTemplate"]);
}

if (!isset($_SESSION["PreviewClass"][$classPreview]) || !$_SESSION["PreviewClass"][$classPreview]) {
    // Это попытка вызвать preview для с несуществующим данными.
    BeginHtml(NETCAT_PREVIEW_ERROR_NODATA);
    nc_print_status(NETCAT_PREVIEW_ERROR_NODATA, "error");
    EndHtml();
    die();
} elseif ($postClass_ID > 0 && ($postClass_ID != $classPreview)) {
    // Это ошибочная ситуация POST c одним классом , а предросмотр с другим.
    BeginHtml(NETCAT_PREVIEW_ERROR_WRONGDATA);
    nc_print_status(NETCAT_PREVIEW_ERROR_WRONGDATA, "error");
    EndHtml();
    die();
}

// Если нам для preview не передан $sub номер раздела то, нам нужно разобраться чтоже показывать.
if (!isset($sub) || !$sub) {
    // если вдруг передавали $cc уберем его потому что мы возьмем его из Sub_Class
    unset($cc);

    require_once ($ROOT_FOLDER."connect_io.php");

    // если нет $catalogue - то определим по имени хоста.
    if (!isset($catalogue) || !$catalogue) {
        $catalogue = $nc_core->catalogue->get_by_host_name($HTTP_HOST);
        $catalogue = $catalogue["Catalogue_ID"];
    } else {
        $catalogue+= 0;
    }

    $res_arr = $db->get_results("SELECT a.`Subdivision_ID`, a.`Sub_Class_ID`, a.`Sub_Class_Name`, b.`Subdivision_Name`
    FROM `Sub_Class` a
    LEFT JOIN `Subdivision` AS b ON a.`Subdivision_ID` = b.`Subdivision_ID`
    WHERE a.`Class_ID` = '".($ClassTemplate ? $ClassTemplate : $classPreview)."'
      AND b.`Catalogue_ID` = '".$catalogue."'
      ".($ClassTemplate ? "AND a.`Class_Template_ID` = '".$classPreview."'" : ""), ARRAY_A);

    $sub_count = count($res_arr);

    switch (true) {
        // Нет разделов с таким компонентом.
        case ($sub_count == 0):
            BeginHtml(NETCAT_PREVIEW_ERROR_NOSUB);
            nc_print_status(NETCAT_PREVIEW_ERROR_NOSUB, "error");
            EndHtml();
            die();
            break;
        // Раздел всего один - сразу перенаправляем на него.
        case ($sub_count == 1):
            $Location = $SUB_FOLDER.$HTTP_ROOT_PATH."search.php?catalogue=".$catalogue."&sub=".$res_arr[0]['Subdivision_ID']."&cc=".$res_arr[0]['Sub_Class_ID']."&classPreview=".$classPreview;
            header("Location: ".$Location);
            die();
            break;
        //  Предоставим возможность пользователю выбрать раздел
        case ($sub_count > 1):
            BeginHtml(NETCAT_PREVIEW_INFO_MORESUB);
            nc_print_status(NETCAT_PREVIEW_INFO_MORESUB, "info");
            echo "<div>";
            foreach ($res_arr as $tmp_arr) {
                $sub = $tmp_arr["Subdivision_ID"] + 0;
                $cc = $tmp_arr["Sub_Class_ID"] + 0;
                $sub_name = $tmp_arr["Subdivision_Name"];
                $cc_name = $tmp_arr["Sub_Class_Name"];

                if (!$sort_title_id || $sort_title_id != $sub) {
                    echo ($sort_title_id ? "</ul>" : "");
                    $sort_title_id = $sub;
                    echo "<b>".$sub." . ".$sub_name."</b><ul>";
                }

                echo "<li><a href='".$SUB_FOLDER.$HTTP_ROOT_PATH."search.php?catalogue=".$catalogue."&amp;sub=".$sub."&amp;cc=".$cc."&amp;classPreview=".$classPreview."'>".$cc." . ".$cc_name."</a></li>";
            }
            echo "</ul></div>";
            EndHtml();
            die();
            break;
    }
}