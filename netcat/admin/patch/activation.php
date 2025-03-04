<?php

/* $Id */
$NETCAT_FOLDER = join(strstr(__FILE__, "/") ? "/" : "\\", array_slice(preg_split("/[\/\\\]+/", __FILE__), 0, -4)).( strstr(__FILE__, "/") ? "/" : "\\" );
include_once ($NETCAT_FOLDER."vars.inc.php");
require ($ADMIN_FOLDER."function.inc.php");
require ($ADMIN_FOLDER."patch/function.inc.php");
require ($ADMIN_FOLDER."install.inc.php");
require ($ADMIN_FOLDER."tar.inc.php");


$Delimeter = " &gt ";
$main_section = "settings";
$item_id = 4;
$Title2 = TOOLS_ACTIVATION;
$Title3 = "<a href=".$ADMIN_PATH."patch/>".TOOLS_ACTIVATION."</a>";
$Title4 = TOOLS_ACTIVATION;
$Title5 = TOOLS_PATCH_INSTRUCTION;

if (!$phase) $phase = 1;

$UI_CONFIG = new ui_config_tool(TOOLS_ACTIVATION, TOOLS_ACTIVATION, "i_netcat_big.gif", "tools.activation".($phase && $phase != 5 ? "(".$phase.")" : ""));

// Система может быть уже активированной
if (!$nc_core->is_trial) {
    BeginHtml($Title2, $Title2, "http://".$DOC_DOMAIN."/settings/activation/");
    $perm->ExitIfNotAccess(NC_PERM_PATCH, 0, 0, 0, 0);
    nc_print_status(TOOLS_ACTIVATION_ALREADY_ACTIVE, 'ok');
    EndHtml();
    exit;
}

switch ($phase) {
    # Форма ввода данных
    case 1:
        BeginHtml($Title2, $Title2, "http://".$DOC_DOMAIN."/settings/activation/");
        $perm->ExitIfNotAccess(NC_PERM_PATCH, 0, 0, 0, 0);

        nc_activation_show_form();
	break;

    # Сообственно, активация
    case 2:
        BeginHtml($Title2, $Title2, "http://".$DOC_DOMAIN."/settings/activation/");
        $perm->ExitIfNotAccess(NC_PERM_PATCH, 0, 0, 0, 1);

        if (!$_POST['activation_code'] || !$_POST['license']) {
            nc_print_status(TOOLS_ACTIVATION_INPUT_KEY_CODE, 'error');
            nc_activation_show_form();
            break;
        }

        switch ($_POST['urphis']) {
            case 'ur':
                if (
                    !$_POST['u_Organization'] ||
                    !$_POST['u_OrgEmail'] ||
                    !$_POST['u_Phone'] ||
                    !$_POST['u_INN'] ||
                    !$_POST['u_Domains']
                ) {
                    nc_print_status(TOOLS_ACTIVATION_FORM_ERR_MANDATORY, 'error');
                    nc_activation_show_form();
                    break 2;
                }

                if (!preg_match('/\S+@\S+\.\S+/', $_POST['u_OrgEmail'])) {
                    nc_print_status(TOOLS_ACTIVATION_FORM_ERR_ORG_EMAIL, 'error');
                    nc_activation_show_form();
                    break 2;
                }

                if (!in_array(strlen($_POST['u_INN']), array(10, 12), true)) {
                    nc_print_status(TOOLS_ACTIVATION_FORM_ERR_INN, 'error');
                    nc_activation_show_form();
                    break 2;
                }

                break;
            case 'phis':
                if (
                    !$_POST['p_OrgEmail'] ||
                    !$_POST['p_Phone'] ||
                    !$_POST['p_Person'] ||
                    !$_POST['p_Domains']
                ) {
                    nc_print_status(TOOLS_ACTIVATION_FORM_ERR_MANDATORY, 'error');
                    nc_activation_show_form();
                    break 2;
                }

                if (!preg_match('/\S+@\S+\.\S+/', $_POST['p_OrgEmail'])) {
                    nc_print_status(TOOLS_ACTIVATION_FORM_ERR_PRIMARY_EMAIL, 'error');
                    nc_activation_show_form();
                    break 2;
                }

                if ($_POST['p_PersonEmail'] && !preg_match('/\S+@\S+\.\S+/', $_POST['p_PersonEmail'])) {
                    nc_print_status(TOOLS_ACTIVATION_FORM_ERR_ADDIT_EMAIL, 'error');
                    nc_activation_show_form();
                    break 2;
                }

                break;
        }

        $patch_file_data = nc_activation_get_files();

        // get gzip file
        $patch_file_tgz = !empty($patch_file_data) ? $patch_file_data['_FILE'] : "";

        if (!$patch_file_tgz) break;
        if (!file_exists($TMP_FOLDER.$patch_file_tgz)) break;

        if (!checkPermissions($HTTP_ROOT_PATH."tmp/", $DOCUMENT_ROOT)) break;

        // this temporary file will be deleted in the end of this function or in InstallationAborted()
        if (!nc_tgz_extract($TMP_FOLDER.$patch_file_tgz, $TMP_FOLDER) && !file_exists($TMP_FOLDER."id.txt")) {
            InstallationAborted(TOOLS_PATCH_ERR_EXTRACT, 'activation');
        }

        // check files in patch
        if (!CheckDeps('patch')) {
            InstallationAborted(TOOLS_PATCH_INFO_NOTINSTALLED.".<br>", 'activation');
        }

        // other version
        $current_minor_version  = preg_replace('/^(\d+\.\d+).*$/', "$1", $VERSION_ID);
        $required_minor_version = preg_replace('/^(\d+\.\d+).*$/', "$1", $VersionID);
        if ($required_minor_version != $current_minor_version) {
            InstallationAborted(str_replace(array("%REQUIRE", "%EXIST"), array($required_minor_version, $current_minor_version), TOOLS_PATCH_INVALIDVERSION), 'activation');
        }

        // other system
        if ($SystemID && $SystemID != $SYSTEM_ID) {
            list($required_sys_name, $required_sys_color) = nc_system_name_by_id($SystemID);
            list($installed_sys_name, $installed_sys_color) = nc_system_name_by_id($SYSTEM_ID);
            InstallationAborted(str_replace(array("%REQUIRE", "%EXIST"), array($required_sys_name, $installed_sys_name), TOOLS_PATCH_INVALIDVERSION)."<br>", 'activation');
        }

        // проверка версий
        if ($LAST_LOCAL_PATCH != $PatchName) {
            InstallationAborted(TOOLS_PATCH_INFO_NOTINSTALLED.".<br>", 'activation');
        }

        // install.inc.php not readable or include them
        $InstallFile = $TMP_FOLDER."install.inc.php";
        if (!is_readable($InstallFile)) {
            InstallationAborted(TOOLS_MODULES_ERR_CANTOPEN." install.inc.php.", 'activation');
        } else {
            require ($InstallFile);
        }
        // files in patch
        if (!is_readable($TMP_FOLDER."id.txt")) {
            InstallationAborted(TOOLS_MODULES_ERR_CANTOPEN." id.txt.<br>", 'activation');
        }
        if (!is_readable($TMP_FOLDER."files.txt")) {
            InstallationAborted(TOOLS_MODULES_ERR_CANTOPEN." files.txt.", 'activation');
        }
        // check files by listing in files.txt
        if (!nc_patch_check_files_by_list($TMP_FOLDER."files.txt")) {
            InstallationAborted(TOOLS_PATCH_ERROR_FILELIST_NOT_WRITABLE."<br/><br/>".TOOLS_PATCH_ERROR_AUTOINSTALL."<br/><br/><a href='".$patch_file_data['LINK']."'>".TOOLS_PATCH_DOWNLOAD_LINK_DESCRIPTION."</a><br/>", 'activation');
        }
        if (!is_readable($TMP_FOLDER."sql.txt")) {
            InstallationAborted(TOOLS_MODULES_ERR_CANTOPEN." sql.txt.", 'activation');
        }
        if (!is_readable($TMP_FOLDER."required.txt")) {
            InstallationAborted(TOOLS_MODULES_ERR_CANTOPEN." required.txt.", 'activation');
        }
        if (!is_readable($TMP_FOLDER."symlinks.txt")) {
            InstallationAborted(TOOLS_MODULES_ERR_CANTOPEN." symlinks.txt.", 'activation');
        }

        $Ability = CheckAbilityOfInstallation();
        if ($Ability["Success"] == 0) {
            InstallationAborted(TOOLS_PATCH_ERROR.": ".$Ability["ErrorMessage"].".", 'activation');
        }

        $Install = InstallPatch();
        if ($Install["Success"] == 0) {
            InstallationAborted(TOOLS_PATCH_ERROR_DURINGINSTALL.": ".$Install["ErrorMessage"].".", 'activation');
        }

        // copy files from files.txt
        $CopyFilesResult = CopyFiles();
        // execute queries from sql.txt, one per string
        $ExecSQLResult = ExecSQL($TMP_FOLDER."sql.txt");
        // symlinks.txt
        $CreatLinksResult = CreatLinks();

        // insert patch info into the database
        $inserted = $db->query("INSERT INTO `Patch`
			(`Patch_Name`, `Created`, `Description`)
			VALUES ('".$db->escape($PatchName)."', NOW(), '".$db->escape($Description)."')");
        // don't show update notice once more

        $PatchResult = array();
        // results
        if ($CopyFilesResult['files']) {
            $PatchResult['files'] = str_replace("%COUNT", $CopyFilesResult['files'], TOOLS_PATCH_INFO_FILES_COPIED);
        }
        if ($ExecSQLResult['sqls']) {
            $PatchResult['sqls'] = str_replace("%COUNT", $ExecSQLResult['sqls'], TOOLS_PATCH_INFO_QUERIES_EXEC);
        }
        if ($CreatLinksResult['links']) {
            $PatchResult['links'] = str_replace("%COUNT", $CreatLinksResult['links'], TOOLS_PATCH_INFO_SYMLINKS_EXEC);
        }
        // result text
        if (!empty($PatchResult))
                $PatchResultText = join("\r\n", $PatchResult)."\r\n";

        // after action in install.inc.php
        if (function_exists("InstallPatchAfterAction")) {
            InstallPatchAfterAction();
        }

        // clear tmp folder
        DeleteFilesInDirectory($TMP_FOLDER);

        //удаление лишних полей
        $db->query("DELETE FROM `Settings` WHERE `Key`='InstallationID' OR `Key`='InstallationDateOut'");
        // информация о лицензии
        $db->query("UPDATE `Settings` SET `Value`='".intval($license)."' WHERE `Key`='ProductNumber'");
        $db->query("UPDATE `Settings` SET `Value`='".$db->escape($activation_code)."' WHERE `Key`='Code'");
        $db->query("REPLACE INTO `Settings` (`Key`, `Value`, `Module`) VALUES ('Owner', '".$db->escape($patch_file_data['NAME'])."', 'system')");
        // patch installed OK
        $infotext = TOOLS_ACTIVATION_OK1 . ($patch_file_data['netcat_user'] ? TOOLS_ACTIVATION_OK2 : TOOLS_ACTIVATION_OK3 ) . str_replace(array("%REGNUM", "%REGCODE", "%SYSID"), array(intval($license), $db->escape($activation_code), $SYSTEM_ID), TOOLS_ACTIVATION_OK4);
        nc_print_status($infotext, 'ok');

        break;

        case 5:
        BeginHtml($Title5, $Title5, "http://" . $DOC_DOMAIN . "/settings/patch/");
        $perm->ExitIfNotAccess(NC_PERM_PATCH, 0, 0, 0, 1);
        $UI_CONFIG->activeTab    = 'path-info';
        $UI_CONFIG->headerText   = TOOLS_ACTIVATION_INSTRUCTION;
        $UI_CONFIG->locationHash = '#tools.activation(5)';
        $for_activation = true;

        $lang   = 'ru';
        $encode = $nc_core->NC_UNICODE ? 'utf8' : 'cp1251';
        require $ADMIN_FOLDER . "patch/information.{$lang}.{$encode}.php";

        break;
}

EndHtml();
?>