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
// для авторизации
$admin_mode = 1;
if (!Authorize()) Refuse();

if (!$perm->isSupervisor()) {
    BeginHtml(NETCAT_MODERATION_ERROR_NORIGHTS);
    nc_print_status(NETCAT_MODERATION_ERROR_NORIGHTS, "error");
    EndHtml();
    die();
}


$postClass_ID = nc_array_value($_POST, "ClassID") + 0;

$ClassTemplate = $db->get_var("SELECT `ClassTemplate` FROM `Class` WHERE `Class_ID` = '".$classPreview."'");

if ($postClass_ID > 0 && ($postClass_ID == $classPreview)) {
// Если в POST есть $ClassID - то это попытка передать нам редактируемый компонент для Preview.
// Занесем его в сессию
    $_SESSION["PreviewClass"][$classPreview] = array(
            "FormPrefix" => $_POST["FormPrefix"],
            "RecordTemplate" => $_POST["RecordTemplate"],
            "FormSuffix" => $_POST["FormSuffix"],
            "RecordsPerPage" => $_POST["RecordsPerPage"],
            "SortBy" => $_POST["SortBy"],
            "TitleTemplate" => $_POST["TitleTemplate"],
            "RecordTemplateFull" => $_POST["RecordTemplateFull"],
            "NL2BR" => $_POST["NL2BR"],
            "Settings" => $_POST["Settings"],
            "CustomSettingsTemplate" => nc_array_value($_POST,"CustomSettingsTemplate"),
            "DaysToHold" => $_POST["DaysToHold"]
    );
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

    // Выберем те разделы данного сайта , в которых компонет с действием по умолчанию - Просмотр.
    $res_arr = $db->get_results("SELECT a.`Sub_Class_ID`, a.`Sub_Class_Name`, a.`EnglishName`, a.`Subdivision_ID`, b.`Subdivision_Name`, b.`Hidden_URL`
    FROM `Sub_Class` a
    LEFT JOIN `Subdivision` AS b ON a.`Subdivision_ID` = b.`Subdivision_ID`
    WHERE a.`Class_ID` = '".($ClassTemplate ? $ClassTemplate : $classPreview)."'
      AND b.`Catalogue_ID` = '".$catalogue."'
      AND a.`DefaultAction` = 'index'
      ".($ClassTemplate ? "AND a.`Class_Template_ID` = '".$classPreview."'" : "")."
    ORDER BY a.`Subdivision_ID`", ARRAY_A);

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
            $Location = $nc_core->SUB_FOLDER.$res_arr[0]["Hidden_URL"].$res_arr[0]["EnglishName"].".html?classPreview=".$classPreview;
            header("Location: ".$Location);
            die();
            break;
        // Разделов с таким компонентом больше одного предоставим возможность пользователю выбрать.
        case ($sub_count > 1):
            BeginHtml(NETCAT_PREVIEW_INFO_MORESUB);
            nc_print_status(NETCAT_PREVIEW_INFO_MORESUB, "info");
            echo "<div>";
            foreach ($res_arr as $tmp_arr) {
                if (empty($sort_title_id) || $sort_title_id != $tmp_arr['Subdivision_ID']) {
                    echo (empty($sort_title_id) ? "</ul>" : "");
                    $sort_title_id = $tmp_arr['Subdivision_ID'];
                    echo "<b>".$tmp_arr["Subdivision_ID"]." . ".$tmp_arr["Subdivision_Name"]."</b><ul>";
                }
                echo "<li><a href='".$nc_core->SUB_FOLDER.$tmp_arr["Hidden_URL"].$tmp_arr["EnglishName"].".html?classPreview=".$classPreview."'>".$tmp_arr["Sub_Class_ID"]." . ".$tmp_arr["Sub_Class_Name"]."</a></li>";
            }
            echo "</ul></div>";
            EndHtml();
            die();
            break;
    }
}

// В режиме предпросмотра - admin_mode=0;
$admin_mode = 0;
?>