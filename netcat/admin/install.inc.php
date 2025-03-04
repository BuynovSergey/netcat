<?php

function InstallationAborted($msg='', $action='') {
    global $TMP_FOLDER;

    switch ($action) {
        case 'patch':
            nc_print_status(($msg ? $msg."<br>" : "").TOOLS_PATCH_ERR_CANTINSTALL, "error");
            PatchForm();
            PatchList();
            break;
        case 'module':
            nc_print_status(($msg ? $msg."<br>" : "").TOOLS_MODULES_ERR_INSTALL, "error");
            break;
        case 'activation':
            nc_print_status(($msg ? $msg : TOOLS_PATCH_ERROR), "error");
            nc_activation_show_form();
            break;
        default:
            nc_print_status(($msg ? $msg : TOOLS_PATCH_ERROR), "error");
            break;
    }

    DeleteFilesInDirectory($TMP_FOLDER);
    EndHtml();
    exit;
}

function CopyFiles($action = '') {
    global $TMP_FOLDER, $MODULE_FOLDER, $ROOT_FOLDER, $DOCUMENT_ROOT, $SUB_FOLDER;
    global $Keyword;

    if ($action === 'module') {
        @mkdir(nc_module_folder($Keyword), 0775);

        copy($TMP_FOLDER . 'index.php', nc_module_folder($Keyword) . 'index.php');
        copy($TMP_FOLDER . 'function.inc.php', nc_module_folder($Keyword) . 'function.inc.php');
        copy($TMP_FOLDER . 'en.lang.php', nc_module_folder($Keyword) . 'en.lang.php');
        copy($TMP_FOLDER . 'ru.lang.php', nc_module_folder($Keyword) . 'ru.lang.php');

        if (is_readable($TMP_FOLDER . 'admin.php')) {
            copy($TMP_FOLDER . 'admin.php', nc_module_folder($Keyword) . 'admin.php');
        }
        if (is_readable($TMP_FOLDER. 'admin.inc.php')) {
            copy($TMP_FOLDER . 'admin.inc.php', nc_module_folder($Keyword) . 'admin.inc.php');
        }
        if (is_readable($TMP_FOLDER. 'setup.php')) {
            copy($TMP_FOLDER . 'setup.php', nc_module_folder($Keyword) . 'setup.php');
        }
    }

    $FileWithFileList = 'files.txt';
    $COPY_FOLDER = $DOCUMENT_ROOT . $SUB_FOLDER;

    # сколько файлов скопировано, сколько всего файлов
    $result = array("files" => 0, "total" => 0);

    $fpAny = fopen($TMP_FOLDER.$FileWithFileList, "r");

    while (!feof($fpAny)) {
        $file_name = chop(fgets($fpAny, 4096));
        if (strlen($file_name) == 0) break;

        $directory = dirname($file_name);

        $tmpDirectory = $COPY_FOLDER;
        $tok = strtok($directory, "/");
        while ($tok) {
            $tmpDirectory.= "/".$tok;
            @mkdir($tmpDirectory, 0775);
            $tok = strtok("/");
        }
        $file_copied = @copy($TMP_FOLDER.$file_name, $COPY_FOLDER."/".$file_name);
        if ($file_copied) $result["files"]++;

        $result["total"]++;
    }
    fclose($fpAny);

    return $result;
}

function LoadID($action = '') {
    global $TMP_FOLDER, $db, $nc_core;
    global $PatchName, $SystemID, $VersionID, $Description;
    global $Keyword, $SystemVersion, $Patch, $Name;
    global $ExampleURL, $HelpURL, $Parameters, $SysMessage;

    $Patch = array();

    if (!file_exists($TMP_FOLDER . 'id.txt')) {
        return false;
    }

    if ($action === 'patch') {
        $fp = fopen($TMP_FOLDER . 'id.txt', 'r');
        $PatchName = rtrim(fgets($fp, 4096));
        $SystemID = rtrim(fgets($fp, 4096));
        $VersionID = rtrim(fgets($fp, 4096));
        $Description = rtrim(fgets($fp, 4096));
        fclose($fp);
        if (!$nc_core->NC_UNICODE) {
            $Description = $nc_core->utf8->utf2win($Description);
        }
    } elseif ($action === 'module') {
        $fp = fopen($TMP_FOLDER . 'id.txt', 'r');
        $Keyword = rtrim(fgets($fp, 4096));
        $SystemID = rtrim(fgets($fp, 4096));
        $SystemVersion = rtrim(fgets($fp, 4096));
        $Patches = rtrim(fgets($fp, 4096));

        $tok = strtok($Patches, ' ');
        while ($tok) {
            $Patch [] = $tok;
            $tok = strtok(' ');
        }

        $Name = rtrim(fgets($fp, 4096));
        $ExampleURL = rtrim(fgets($fp, 4096));
        $HelpURL = rtrim(fgets($fp, 4096));
        $Description = rtrim(fgets($fp, 4096));

        fclose($fp);

        $fp = fopen($TMP_FOLDER . 'parameters.txt', 'r');
        while (!feof($fp)) {
            $Parameters .= fgets($fp, 4096);
        }
        fclose($fp);
        if (MAIN_LANG !== 'ru') {
            $SysMessage = @file_get_contents($TMP_FOLDER . 'message_int.txt');
        } else {
            $SysMessage = @file_get_contents($TMP_FOLDER . 'message.txt');
        }
    }
}

function CheckDeps($action) {
    global $TMP_FOLDER, $db, $Patch;
    global $PatchName, $SystemID, $VersionID, $Description, $Required;

    $ReturnValue = 1;
    LoadID($action);

    if ($action === 'module' && !count($Patch)) {
        return $ReturnValue;
    }

    if ($action === 'patch') {
        LoadRequired();
    }

    $patches = (array)$db->get_col("SELECT `Patch_Name` FROM `Patch`");
    $listed = $action === 'patch' ? $Required : $Patch;

    if (!is_array($listed)) {
        return $ReturnValue;
    }

    foreach ($listed as $val) {
        $cmp = 0;
        foreach ($patches as $patch_name) {
            if (strcmp((int) sprintf("%0-3s", $patch_name), (int) sprintf("%0-3s", $val)) == 0) {
                $cmp++;
                break;
            }
        }
        if ($cmp == 0) {
            nc_print_status(TOOLS_MODULES_ERR_PATCH." ".$val.".<br>\r\n", 'error');
            $ReturnValue = 0;
        }
    }

    return $ReturnValue;
}

function LoadRequired() {
    global $TMP_FOLDER, $db, $Required;

    $FileRequired = "required.txt";
    $fp = fopen($TMP_FOLDER.$FileRequired, "r");
    while (!feof($fp)) {
        $buffer = chop(fgets($fp, 4096));
        if (strlen($buffer) > 0) {
            $Required[] = $buffer;
        } else {
            break;
        }
    }

    fclose($fp);
}

function IsAlreadyInstalled($action='') {
    global $db, $PatchName, $TMP_FOLDER, $Keyword;
    if ($action == 'patch') {
        if ($db->get_var("SELECT `Patch_Name` FROM `Patch` WHERE `Patch_Name` = '".$db->escape($PatchName)."'"))
                return 1;
    }
    if ($action == 'module') {

        $select = "SELECT `Keyword` FROM `Module` WHERE `Keyword`='".$db->escape($Keyword)."'";
        $Result = $db->query($select);
        if ($db->num_rows > 0) {
            return 1;
        }
    }

    return 0;
}