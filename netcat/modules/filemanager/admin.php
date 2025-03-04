<?php

// get settings
$NETCAT_FOLDER = join(strstr(__FILE__, "/") ? "/" : "\\", array_slice(preg_split("/[\/\\\]+/", __FILE__), 0, -4)).( strstr(__FILE__, "/") ? "/" : "\\" );
require_once $NETCAT_FOLDER . 'vars.inc.php';

$module_keyword = 'filemanager';

require_once $ADMIN_FOLDER . 'function.inc.php';

// load modules env
if (!isset($MODULE_VARS)) {
    $MODULE_VARS = $nc_core->modules->get_module_vars();
}

// UI config
require_once $ADMIN_FOLDER . 'modules/ui.php';
// UI functional
$UI_CONFIG = new ui_config_module($module_keyword);

$Title1 = NETCAT_MODULES;
$Title2 = NETCAT_MODULE_FILEMANAGER;
$current_dir = '';
// default phase
if (!isset($phase)) $phase = 1;

if (in_array($phase, array(21, 22, 31, 41))) {
    if (!$nc_core->token->verify()) {
        if (defined('NC_ADMIN_DISABLE_LOGIN_PAGE') || $nc_core->input->fetch_post('NC_HTTP_REQUEST')) { // AJAX call
            nc_set_http_response_code(401);
            exit;
        }

        BeginHtml($Title2, $Title1, "http://".$DOC_DOMAIN."/settings/modules/".$module_keyword."/");
        nc_print_status(NETCAT_TOKEN_INVALID, 'error');
        EndHtml();
        exit;
    }
}

// object
$nc_filemanager = nc_filemanager::get_object();

switch ($phase) {
    // step 1: show manager
    case 1:
        BeginHtml($Title2, $Title1, "http://".$DOC_DOMAIN."/settings/modules/".$module_keyword."/");
        // check permission
        $perm->ExitIfNotAccess(array(NC_PERM_MODULE, 'filemanager'));
        // if dir setted
        $dir = $nc_core->input->fetch_get_post("dir") ? $nc_core->input->fetch_get_post("dir") : "";
        // show manager
        try {
            echo $nc_filemanager->manager($dir);
        } catch (Exception $e) {
            nc_print_status($e->getMessage(), "error");
        }
        break;

    // file preview
    case 2:
        BeginHtml($Title2, $Title1, "http://".$DOC_DOMAIN."/settings/modules/".$module_keyword."/");
        // check permission
        $perm->ExitIfNotAccess(array(NC_PERM_MODULE, 'filemanager'), 'file_view');

        // file path
        $file_path = isset($file) ? $nc_filemanager->get_base_folder().$file : "";

        // show preview
        try {
            echo $nc_filemanager->preview($file_path);
        } catch (Exception $e) {
            nc_print_status($e->getMessage(), "error");
        }
        break;

    // upload save
    case 21:
        BeginHtml($Title2, $Title1, "http://".$DOC_DOMAIN."/settings/modules/".$module_keyword."/");
        // check permission
        $perm->ExitIfNotAccess(array(NC_PERM_MODULE, 'filemanager'), 'file_edit');

        // current dir path
        if ($nc_core->input->fetch_post("dir")) {
            $current_dir = trim($nc_core->input->fetch_post("dir"), "/");
            $current_dir_path = $nc_filemanager->get_base_folder().$current_dir;
        } else {
            $current_dir_path = rtrim($nc_filemanager->get_base_folder(), "/");
        }

        // show manager or rewrite dialog
        $show_manager = true;

        // save upload
        if (file_exists($current_dir_path)) {
            // check write permissions
            if (is_writable($current_dir_path)) {
                // POST variables
                $new_dir = $nc_core->input->fetch_post("new_dir");
                $new_file = $_FILES['new_file'];

                // create new folder
                if ($new_dir) {
                    // new dir path
                    $new_dir_path = $current_dir_path."/".trim($new_dir, "/");
                    // if no exist
                    if (!file_exists($new_dir_path)) {
                        if (mkdir($new_dir_path, $nc_core->DIRCHMOD) !== false) {
                            // change permission
                            $nc_filemanager->chmod($new_dir_path, $nc_core->DIRCHMOD);
                            nc_print_status(sprintf(NETCAT_MODULE_FILEMANAGER_ADMIN_DIR_CREATED, $new_dir_path), "ok");
                        } else {
                            nc_print_status(sprintf(NETCAT_MODULE_FILEMANAGER_ADMIN_ERROR_DIR_CREATE, $current_dir_path), "error");
                        }
                    } else {
                        nc_print_status(sprintf(NETCAT_MODULE_FILEMANAGER_ADMIN_ERROR_DIR_ALREADY_EXIST, $new_dir_path), "error");
                    }
                }

                // put new file or rewrite exist
                if ($new_file['tmp_name']) {
                    // new file path
                    $new_file_path = $current_dir_path."/".$new_file['name'];
                    $nc_filemanager->check_module_permission_for_file('file_edit', $new_file_path);

                    // check upload
                    if (is_uploaded_file($new_file['tmp_name'])) {
                        // if no exist
                        if (!file_exists($new_file_path)) {
                            // move file to the folder
                            if (move_uploaded_file($new_file['tmp_name'], $new_file_path) !== false) {
                                nc_print_status(sprintf(NETCAT_MODULE_FILEMANAGER_ADMIN_FILE_UPLOADED, $new_file_path), "ok");
                            } else {
                                nc_print_status(sprintf(NETCAT_MODULE_FILEMANAGER_ADMIN_ERROR_FILE_UPLOAD, $new_file_path), "error");
                            }
                        } else {
                            // temp file name
                            $temp_file_name = $nc_core->TMP_FOLDER.md5($new_file['name']);
                            // move file to the temp folder and show dialog
                            if (move_uploaded_file($new_file['tmp_name'], $temp_file_name) !== false) {
                                // show rewrite dialog
                                echo $nc_filemanager->rewrite($temp_file_name, $new_file_path);
                                // do not show manager
                                $show_manager = false;
                            } else {
                                nc_print_status(sprintf(NETCAT_MODULE_FILEMANAGER_ADMIN_ERROR_FILE_UPLOAD, $new_file_path), "error");
                            }
                        }
                    } else {
                        nc_print_status(sprintf(NETCAT_MODULE_FILEMANAGER_ADMIN_ERROR_FILE_UPLOAD, $new_file_path), "error");
                    }
                }
            } else {
                nc_print_status(sprintf(NETCAT_MODULE_FILEMANAGER_ADMIN_ERROR_DIR_WRITE_PERMISSION, $current_dir_path), "error");
            }
        } else {
            nc_print_status(sprintf(NETCAT_MODULE_FILEMANAGER_ADMIN_ERROR_DIR_NOT_EXIST, $current_dir_path), "error");
        }

        // show manager
        if ($show_manager) echo $nc_filemanager->manager($current_dir);
        break;

    // file rewrite
    case 22:
        BeginHtml($Title2, $Title1, "http://".$DOC_DOMAIN."/settings/modules/".$module_keyword."/");

        // file paths
        $temp_file_path = $nc_core->input->fetch_post("temp_file") ? $nc_filemanager->get_base_folder().$nc_core->input->fetch_post("temp_file") : "";
        $new_file_path = $nc_core->input->fetch_post("new_file") ? $nc_filemanager->get_base_folder().$nc_core->input->fetch_post("new_file") : "";

        $temp_file_path = preg_replace('!/\.{2,}/!', '/', $temp_file_path);
        $new_file_path = preg_replace('!/\.{2,}/!', '/', $new_file_path);

        // check permission
        $nc_filemanager->check_module_permission_for_file('file_edit', $new_file_path);

        // delete object
        if (file_exists($temp_file_path)) {
            // check permissions
            if (is_writable(dirname($new_file_path))) {
                // delete file
                unlink($new_file_path);
                // move file
                if (@rename($temp_file_path, $new_file_path)) {
                    nc_print_status(sprintf(NETCAT_MODULE_FILEMANAGER_ADMIN_FILE_REWRITED, $new_file_path), "ok");
                } else {
                    nc_print_status(sprintf(NETCAT_MODULE_FILEMANAGER_ADMIN_ERROR_FILE_WRITE_PERMISSION, $new_file_path), "error");
                }
            } else {
                nc_print_status(sprintf(NETCAT_MODULE_FILEMANAGER_ADMIN_ERROR_FILE_DELETE_PERMISSION, $new_file_path), "error");
            }
        }

        // show manager
        echo $nc_filemanager->manager(dirname($nc_core->input->fetch_post("new_file")));
        break;

    // file edit
    case 3:
        $AJAX_SAVER = true;
        BeginHtml($Title2, $Title1, "http://".$DOC_DOMAIN."/settings/modules/".$module_keyword."/");
        // check permission
        $perm->ExitIfNotAccess(array(NC_PERM_MODULE, 'filemanager'), 'file_edit');

        // file path
        $file_path = isset($file) ? $nc_filemanager->get_base_folder() . stripslashes($file) : "";

        // show edit form
        if (file_exists($file_path)) {
            $nc_core->token->exit_if_invalid();
            echo $nc_filemanager->edit($file_path);
        } else {
            nc_print_status(sprintf(NETCAT_MODULE_FILEMANAGER_ADMIN_ERROR_FILE_NOT_EXIST, $file_path), "error");
        }
        break;

    // file save after edit
    case 31:
        BeginHtml($Title2, $Title1, "http://".$DOC_DOMAIN."/settings/modules/".$module_keyword."/");
        // check permission
        $perm->ExitIfNotAccess(array(NC_PERM_MODULE, 'filemanager'), 'file_edit');

        // file path
        $file_path = $nc_core->input->fetch_post("file") ? $nc_filemanager->get_base_folder().$nc_core->input->fetch_post("file") : "";

        // save file
        if (file_exists($file_path)) {
            if (is_writable($file_path)) {
                $content = $nc_core->input->fetch_post("file_data");
                // save file
                if (file_put_contents($file_path, $content) !== false) {
                    nc_print_status(sprintf(NETCAT_MODULE_FILEMANAGER_ADMIN_FILE_UPDATED, $file_path), "ok");
                } else {
                    nc_print_status(sprintf(NETCAT_MODULE_FILEMANAGER_ADMIN_ERROR_FILE_WRITE, $file_path), "error");
                }
            } else {
                $error_string = sprintf(NETCAT_MODULE_FILEMANAGER_ADMIN_ERROR_FILE_WRITE_PERMISSION, $file_path);
                if ($_POST["NC_HTTP_REQUEST"]) {
                    $GLOBALS["_RESPONSE"]['error'] = $error_string;
                }
                nc_print_status($error_string, "error");
            }
        } else {
            $error_string = sprintf(NETCAT_MODULE_FILEMANAGER_ADMIN_ERROR_FILE_NOT_EXIST, $file_path);
            if ($_POST["NC_HTTP_REQUEST"]) {
                $GLOBALS["_RESPONSE"]['error'] = $error_string;
            }
            nc_print_status($error_string, "error");
        }

        // show manager
        echo $nc_filemanager->manager(dirname($nc_core->input->fetch_post("file")));
        break;

    // delete file/dir dialog
    case 4:
        BeginHtml($Title2, $Title1, "http://".$DOC_DOMAIN."/settings/modules/".$module_keyword."/");
        // check permission
        $perm->ExitIfNotAccess(array(NC_PERM_MODULE, 'filemanager'), 'file_delete');

        // object path
        $obj_path = isset($path) ? $nc_filemanager->get_base_folder() . stripslashes($path) : "";

        // show edit form
        if (file_exists($obj_path)) {
            echo $nc_filemanager->delete($obj_path);
        } else {
            nc_print_status(sprintf(NETCAT_MODULE_FILEMANAGER_ADMIN_ERROR_NOT_EXIST, htmlspecialchars($obj_path)), "error");
        }
        break;

    // delete file/dir
    case 41:
        BeginHtml($Title2, $Title1, "http://".$DOC_DOMAIN."/settings/modules/".$module_keyword."/");
        // check permission
        $perm->ExitIfNotAccess(array(NC_PERM_MODULE, 'filemanager'), 'file_delete');

        // object path
        $obj_path = $nc_core->input->fetch_post("file") ? $nc_filemanager->get_base_folder().$nc_core->input->fetch_post("file") : "";

        // delete object
        if (file_exists($obj_path)) {
            switch (true) {
                case is_file($obj_path):
                    if (is_writable(dirname($obj_path))) {
                        // delete file
                        if (unlink($obj_path)) {
                            nc_print_status(sprintf(NETCAT_MODULE_FILEMANAGER_ADMIN_FILE_DELETED, $obj_path), "ok");
                        }
                    } else {
                        nc_print_status(sprintf(NETCAT_MODULE_FILEMANAGER_ADMIN_ERROR_FILE_DELETE_PERMISSION, $obj_path), "error");
                    }
                    break;
                case is_dir($obj_path):
                    if ($nc_filemanager->delete_dir($obj_path)) {
                        nc_print_status(sprintf(NETCAT_MODULE_FILEMANAGER_ADMIN_DIR_DELETED, $obj_path), "ok");
                    } else {
                        nc_print_status(sprintf(NETCAT_MODULE_FILEMANAGER_ADMIN_ERROR_DIR_DELETE_PERMISSION, $obj_path), "error");
                    }
                    break;
            }
        }

        // show manager
        echo $nc_filemanager->manager(dirname($nc_core->input->fetch_post("file")));
        break;

    // file download
    case 5:
        BeginHtml($Title2, $Title1, "http://".$DOC_DOMAIN."/settings/modules/".$module_keyword."/");
        // check permission
        $perm->ExitIfNotAccess(array(NC_PERM_MODULE, 'filemanager'), 'file_view');

        // file path
        $file = $nc_core->input->fetch_get("file");

        // file path
        $file_path = isset($file) ? $nc_filemanager->get_base_folder().$file : "";

        // download file
        if (file_exists($file_path)) {
            echo $nc_filemanager->download($file_path);
        } else {
            nc_print_status(sprintf(NETCAT_MODULE_FILEMANAGER_ADMIN_ERROR_FILE_NOT_EXIST, $file_path), "error");
        }
        break;
}

EndHtml();
