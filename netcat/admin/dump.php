<?php

require_once("../require/s_common.inc.php");
require("function.inc.php");
require("dump.inc.php");
require("tar.inc.php");


global $HTTP_HOST;
$Delimeter = " &gt; ";
$main_section = "settings";
$item_id = 8;
$Title1 = "";
$Title2 = TOOLS_DUMP;
$Title3 = "<a href='" . $ADMIN_PATH . "dump.php'>" . TOOLS_DUMP . "</a>";
$Title4 = TOOLS_DUMP_CREATE;
$Title5 = TOOLS_DUMP;
$Title6 = TOOLS_DUMP_RESTORE;
$Title7 = TOOLS_DUMP_CREATE;

$UI_CONFIG = new ui_config_tool(TOOLS_DUMP, TOOLS_DUMP, 'i_tool_backup_big.gif', 'tools.backup');

$nc_core = nc_core::get_object();

$desired_memory_limit = 512;
$current_memory_limit = $nc_core->get_memory_limit();
if ($current_memory_limit > 0 && $current_memory_limit < $desired_memory_limit) {
    ini_set('memory_limit', $desired_memory_limit . 'M');
}

//if win
if (substr(php_uname(), 0, 7) == "Windows") {
    $isWin = 1;
}
else {
    $isWin = 0;
}

if (!$perm->isSupervisor()) {
    BeginHtml($Title2, $Title2, "http://" . $DOC_DOMAIN . "/settings/dump/");
    nc_print_status(NETCAT_MODERATION_ERROR_NORIGHTS, "error");
    EndHtml();
    exit;
}


if ($phase) {
    switch ($phase) {
        case 1:
            //Само архивирование
            if ($AUTHORIZATION_TYPE == "session") {
                header("Location:" . $ADMIN_PATH . "dump.php?" . session_name() . "=" . session_id());
            }
            BeginHtml($Title5, $Title2, "http://" . $DOC_DOMAIN . "/settings/dump/");

            $backup_options = array('mode' => 'full', 'standalone' => true); // Default settings

            if (isset($_POST['backup_mode'])) {
                $backup_mode = $_POST['backup_mode'];
                switch ($backup_mode) {
                    case 'full':
                        $backup_options['mode'] = 'full';
                        $backup_options['no_standalone'] = false;
                        break;
                    case 'simple':
                        $backup_options['mode'] = 'default';
                        $backup_options['no_standalone'] = true;
                        break;
                    case 'sql':
                        $backup_options['mode'] = 'sql';
                        $backup_options['no_standalone'] = true;
                        break;
                }
            }

            nc_dump_flush_buffer(TOOLS_DUMP_CREATE_WAIT);
            mkDump(false, $backup_options);
            nc_dump_remove_wait_message();

            showUploadForm();
            print "<br>";
            ShowBackUps();
            break;

        case 2:
            //Удаление файла
            BeginHtml($Title5, $Title2, "http://" . $DOC_DOMAIN . "/settings/dump/");
            DeleteDump($del);
            showUploadForm();
            print "<br>";
            ShowBackUps();
            break;

        case 3:
            BeginHtml($Title6, $Title3 . " > " . $Title6, "http://" . $DOC_DOMAIN . "/settings/dump/");
            DumpQuery($file);
            break;

        case 4:
            BeginHtml($Title2, $Title5, "http://" . $DOC_DOMAIN . "/settings/dump/");
            mkDump();
            break;

        case 5:
            BeginHtml($Title2, $Title5, "http://" . $DOC_DOMAIN . "/settings/dump/");
            AskDump();
            break;

        case 6:
            BeginHtml($Title2, $Title5, "http://" . $DOC_DOMAIN . "/settings/dump/");
            $database = 0;
            $netcat_template = 0;
            $netcat_files = 0;
            $images = 0;
            $modules = 0;
            if (checkBox($what, "database")) {
                $database = 1;
            }
            if (checkBox($what, "netcat_template")) {
                $netcat_template = 1;
            }
            if (checkBox($what, "netcat_files")) {
                $netcat_files = 1;
            }
            if (checkBox($what, "images")) {
                $images = 1;
            }
            if (checkBox($what, "modules")) {
                $modules = 1;
            }

            nc_dump_flush_buffer(TOOLS_DUMP_RESTORE_WAIT);
            $err = ReadBackUP($file, $images, $netcat_files, $database, $modules, 0, $netcat_template);
            nc_dump_remove_wait_message();

            if (!$err) {
                nc_print_status(TOOLS_DUMP_MSG_RESTORED, 'ok');
            }
            else {
                nc_print_status($err, 'error');
            }
            break;


        case 7:
            BeginHtml($Title2, $Title5, "http://" . $DOC_DOMAIN . "/settings/dump/");
            if (!$_FILES['filename']['tmp_name']) {
                nc_print_status(TOOLS_MODULES_ERR_NOTUPLOADED, "error");
                showUploadForm();
                print "<br>";
                ShowBackUps();
                break;
            }

            $file = $_FILES['filename']['name'];
            move_uploaded_file($_FILES['filename']['tmp_name'], $nc_core->TMP_FOLDER . $file);

            $database = 0;
            $netcat_template = 0;
            $netcat_files = 0;
            $images = 0;
            $modules = 0;

            if (checkBox($what, "database")) {
                $database = 1;
            }
            if (checkBox($what, "netcat_template")) {
                $netcat_template = 1;
            }
            if (checkBox($what, "netcat_files")) {
                $netcat_files = 1;
            }
            if (checkBox($what, "images")) {
                $images = 1;
            }
            if (checkBox($what, "modules")) {
                $modules = 1;
            }

            nc_dump_flush_buffer(TOOLS_DUMP_RESTORE_WAIT);
            $err = ReadBackUP($file, $images, $netcat_files, $database, $modules, 1, $netcat_template);
            nc_dump_remove_wait_message();

            if (!$err) {
                nc_print_status(TOOLS_DUMP_MSG_RESTORED, "ok");
                showUploadForm();
                print "<br>";
                ShowBackUps();
            }
            else {
                nc_print_status($err, "error");
                showUploadForm();
                print "<br>";
                ShowBackUps();
            }
            break;
    }
}
else {
    BeginHtml($Title5, $Title2, "http://" . $DOC_DOMAIN . "/settings/dump/");
    showUploadForm();
    print "<br>";
    ShowBackUps();
}

EndHtml();

