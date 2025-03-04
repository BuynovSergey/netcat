<?php

$module_keyword = "tagscloud";
$main_section = "settings";
$item_id = 3;

$NETCAT_FOLDER = join(strstr(__FILE__, "/") ? "/" : "\\", array_slice(preg_split("/[\/\\\]+/", __FILE__), 0, -4)).( strstr(__FILE__, "/") ? "/" : "\\" );
include_once ($NETCAT_FOLDER."vars.inc.php");
require ($ADMIN_FOLDER."function.inc.php");
require_once ($ADMIN_FOLDER."modules/ui.php");

$Title1 = NETCAT_MODULES_TUNING;
$Title2 = NETCAT_MODULES;

$UI_CONFIG = new ui_config_tool(TOOLS_MODULES_LIST, TOOLS_MODULES_LIST, 'i_modules_big.gif', 'module.list');

// вывод сообщения о невозможности установки - нет прав
if (!($perm->isSupervisor() || $perm->isGuest())) {
    BeginHtml($Title2, $Title1, "http://".$DOC_DOMAIN."/settings/modules/");
    nc_print_status(NETCAT_MODERATION_ERROR_NORIGHTS, 'error');
    EndHtml();
    exit;
}

// проверка, установлен этот модуль или нет
$res = $db->get_row("SELECT * FROM `Module` WHERE `Keyword` = '".$db->escape($module_keyword)."' AND `Installed` = 0", ARRAY_A);
// вывод сообщения об успешном окончании установки
if (!$db->num_rows) {
    BeginHtml($Title2, $Title1, "http://".$DOC_DOMAIN."/settings/modules/");
    nc_print_status(NETCAT_MODULE_INSTALLCOMPLIED, 'ok');
    EndHtml();
    exit;
} else {
    $module_data = $res;
}

// load modules env
$lang = $nc_core->lang->detect_lang(1);
$MODULE_VARS = $nc_core->modules->load_env($lang);

// определяем компонент с тегами
$ClassID = intval($MODULE_VARS['tagscloud']['TAGS_CLASS_ID']);

if (!isset($phase)) $phase = 2;

switch ($phase) {
    case 1:
        BeginHtml($Title2, $Title1, "http://".$DOC_DOMAIN."/settings/modules/");
        break;

    case 2:
        BeginHtml($Title2, $Title1, "http://".$DOC_DOMAIN."/settings/modules/");
        // выбор раздела для установки
        SelectParentSub();
        break;

    case 3:
        BeginHtml($Title2, $Title1, "http://".$DOC_DOMAIN."/settings/modules/");
        // создадим раздел для тегов
        $TagsSub = InsertSub(NETCAT_MODULE_TAGSCLOUD_TAGS, "tags", "", 0, 0, 0, 0, 0, $ClassID, $SubdivisionID, $CatalogueID, "index", 0);


        //обновим информацию в настройках модуля
        if ($TagsSub) {
            // добавляем Hidden_URL в раздел с тегами
            $parent_Hidden_URL = $db->get_var("SELECT `Hidden_URL` FROM `Subdivision` WHERE `Subdivision_ID` = '".intval($SubdivisionID)."'");
            $db->query("UPDATE `Subdivision` SET `Hidden_URL` = '".($parent_Hidden_URL ? $parent_Hidden_URL."tags/" : "/tags/")."' WHERE `Subdivision_ID` = '".intval($TagsSub)."'");
            // обновляем параметры модуля
            $module_data["Parameters"] = UpdateParameters($module_data["Parameters"], "TAGS_SUB_ID", $TagsSub);
            $db->query("UPDATE `Module` SET `Parameters` = '".$module_data["Parameters"]."' WHERE `Module_ID` = '".intval($module_data["Module_ID"])."'");
        } else {
            echo "Раздел \"Теги\" (ключевое слово \"tags\") уже существует.<br>Обновите информацию для \"TAGS_SUB_ID\" в настройках модуля.";
        }

        // пометим как установленный
        $db->query("UPDATE `Module` SET `Installed` = 1 WHERE `Module_ID` = '".intval($module_data["Module_ID"])."'");
        echo "<br><br>";
        nc_print_status(NETCAT_MODULE_INSTALLCOMPLIED, 'ok');
        break;
}

EndHtml();
