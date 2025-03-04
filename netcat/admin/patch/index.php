<?php

$NETCAT_FOLDER = join(strstr(__FILE__, "/") ? "/" : "\\", array_slice(preg_split("/[\/\\\]+/", __FILE__), 0, -4)) . ( strstr(__FILE__, "/") ? "/" : "\\" );
require_once ($NETCAT_FOLDER . "vars.inc.php");
require ($ADMIN_FOLDER . "function.inc.php");
require ($ADMIN_FOLDER . "patch/function.inc.php");
require ($ADMIN_FOLDER . "install.inc.php");
require ($ADMIN_FOLDER . "tar.inc.php");

global $db;

$Delimeter = " &gt ";
$main_section = "settings";
$item_id = 4;
$Title2 = TOOLS_PATCH;
$Title3 = "<a href=" . $ADMIN_PATH . "patch/>" . TOOLS_PATCH . "</a>";
$Title4 = TOOLS_PATCH_CHEKING;
$Title5 = TOOLS_PATCH_INSTRUCTION;

$phase = isset($phase) ? (int)$phase : 1;

if (!$phase) {
    $phase = 1;
}

$UI_CONFIG = new ui_config_tool(TOOLS_PATCH, TOOLS_PATCH, "i_tool_patch_big.gif", "tools.patch" . ($phase && $phase != 5 ? "(" . $phase . ")" : ""));
$UI_CONFIG->tabs[] = array(
    "id"       => "path-info",
    "caption"  => TOOLS_PATCH_INSTRUCTION_TAB,
    "location" => 'tools.patch(5)'
);


switch ($phase) {
    # список установленных обновлений
    case 1:
        BeginHtml($Title2, $Title2, "http://" . $DOC_DOMAIN . "/settings/patch/");
        $perm->ExitIfNotAccess(NC_PERM_PATCH, 0, 0, 0, 0);
        break;

    # процедура установки обновления
    case 2: // закачанный файл
    case 4: // установка с сервера обновлений
        if (!$nc_core->token->verify()) {
            header('Location: ?phase=1');
            exit;
        }

        $result = array();

        if (!$perm->isAccess(NC_PERM_PATCH, 0, 0, 1)) {
            $result['error_message'] = NETCAT_MODERATION_ERROR_NORIGHTS;
        } else {
            try {
                $installer = new nc_patch_installer();
                $result = $phase === 4
                    ? $installer->install_from_server()
                    : $installer->install_from_uploaded_file($_FILES['FilePatch']['tmp_name']);
            } catch (Exception $e) {
                $result['error_message'] = $e->getMessage();
            }
        }

        // Стартовать вывод нужно после установки патча, чтобы не нужно было отслеживать использование
        // минифицированных файлов, которые при установке патча удаляются
        BeginHtml($Title4, $Title3 . $Delimeter . $Title4, "http://" . $DOC_DOMAIN . "/settings/patch/");
        if (!empty($result['error_message'])) {
            nc_print_status($result['error_message'], 'error');
        }
        if (!empty($result['messages'])) {
            echo $result['messages'];
        }

        break;

    case 3:
        BeginHtml($Title4, $Title3 . $Delimeter . $Title4, "http://" . $DOC_DOMAIN . "/settings/patch/");
        $perm->ExitIfNotAccess(NC_PERM_PATCH, 0, 0, 0, 1);
        CheckForNewPatch();
        break;

    case 5:
        BeginHtml($Title5, $Title5, "http://" . $DOC_DOMAIN . "/settings/patch/");
        $perm->ExitIfNotAccess(NC_PERM_PATCH, 0, 0, 0, 1);
        $UI_CONFIG->activeTab    = 'path-info';
        $UI_CONFIG->headerText   = TOOLS_PATCH_INSTRUCTION;
        $UI_CONFIG->locationHash = '#tools.patch(5)';

        $lang   = 'ru';
        $encode = $nc_core->NC_UNICODE ? 'utf8' : 'cp1251';
        require $ADMIN_FOLDER . "patch/information.{$lang}.{$encode}.php";

        EndHtml();
        exit;
}

PatchForm();
PatchList();
EndHtml();
