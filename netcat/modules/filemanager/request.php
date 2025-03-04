<?php

$NETCAT_FOLDER = join(strstr(__FILE__, "/") ? "/" : "\\", array_slice(preg_split("/[\/\\\]+/", __FILE__), 0, -4)).( strstr(__FILE__, "/") ? "/" : "\\" );
require_once ($NETCAT_FOLDER."vars.inc.php");

// for IE
if (!isset($NC_CHARSET) || !$NC_CHARSET) $NC_CHARSET = "windows-1251";

// header with correct charset
header("Content-type: text/plain; charset=".$NC_CHARSET);
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);

if ($_POST['action']) {

    // disable auth screen
    define("NC_AUTH_IN_PROGRESS", 1);
    define("NC_ADDED_BY_AJAX", 1);

    // include system
    require_once ($ADMIN_FOLDER."function.inc.php");

    // check permission
    $perm->ExitIfNotAccess(array(NC_PERM_MODULE, 'filemanager'), 'file_property_edit');

    // object
    $nc_filemanager = nc_filemanager::get_object();

    // required variables
    $action = $nc_core->input->fetch_post('action');
    // other variables
    $path = $nc_core->input->fetch_post('path');
    $rename = $nc_core->input->fetch_post('rename');
    $permissions = $nc_core->input->fetch_post('permissions');

    $absolute_path = $nc_filemanager->get_base_folder().$path;

    if (!file_exists($absolute_path)) {
        echo json_encode(array('error' => sprintf(NETCAT_MODULE_FILEMANAGER_ADMIN_ERROR_NOT_EXIST, $absolute_path)), true);
        exit;
    }

    if (!$nc_filemanager->can_interact_with_file($absolute_path)) {
        echo json_encode(array('error' => NETCAT_MODERATION_ERROR_NORIGHTS), true);
        exit;
    }

    switch ($action) {
        // get settings
        case "show":
            // get permissions array
            $perm_arr = $nc_filemanager->get_permission($absolute_path);
            // return permissions
            if (!empty($perm_arr)) {
                echo json_encode(array('permissions' => $perm_arr, 'action' => $action, 'error' => 0), true);
                exit;
            } else {
                echo json_encode(array('error' => sprintf(NETCAT_MODULE_FILEMANAGER_ADMIN_ERROR_DIR_READ_PERMISSION, $absolute_path)), true);
                exit;
            }
            break;
        // save settings
        case "save":
            if (!$nc_core->token->verify()) {
                echo nc_array_json(array('error' => NETCAT_TOKEN_INVALID));
                die;
            }

            $path = trim($path, "/");
            $path_arr = explode("/", $path);

            if (!empty($path_arr)) {
                $current_name = array_pop($path_arr);
                $new_name = !empty($path_arr) ? join("/", $path_arr)."/".$rename : $rename;
            } else {
                echo json_encode(array('error' => sprintf(NETCAT_MODULE_FILEMANAGER_ADMIN_ERROR_PATH, $path)), true);
                exit;
            }

            // rename
            if ($current_name != $rename) {
                try {
                    $renamed = $nc_filemanager->rename($path, $new_name);
                } catch (Exception $e) {
                    // got error
                    echo json_encode(array('error' => $e->getMessage()), true);
                    exit;
                }
            } else {
                $renamed = false;
            }

            // permissions
            if ($permissions) {
                // absolute path
                $absolute_path = $nc_filemanager->get_base_folder().(!$renamed ? $path : $new_name);
                // permission array
                $perm_arr = explode(",", $permissions);
                $perm_binary_arr = array();
                // convert
                if (!empty($perm_arr) && count($perm_arr) == 9) {
                    // binary digits
                    $j = 0;
                    for ($i = 0; $i < 9; $i++) {
                        if (!($i % 3) && $i > 0) $j++;
                        $perm_binary_arr[$j][] = $perm_arr[$i];
                    }
                    // octet digits
                    $mode = "";
                    for ($i = 0; $i < 3; $i++) {
                        $mode.= bindec(join("", $perm_binary_arr[$i]));
                    }
                    // chmod
                    try {
                        if (!$nc_filemanager->chmod($absolute_path, octdec($mode))) {
                            // got error
                            echo json_encode(array('error' => sprintf(NETCAT_MODULE_FILEMANAGER_ADMIN_ERROR_CHMOD, $absolute_path)), true);
                            exit;
                        }
                    } catch (Exception $e) {
                        // got error
                        echo json_encode(array('error' => $e->getMessage()), true);
                        exit;
                    }
                    // format mode
                    $formated_mode = $nc_filemanager->format_file_permission($absolute_path);

                    $id_dir = is_dir($absolute_path) ? 1 : 0;
                    $is_readable = is_readable($absolute_path) ? 1 : 0;
                    $is_writable = is_writable($absolute_path) ? 1 : 0;
                } else {
                    $formated_mode = false;
                }
            }

            $result = array(
                'action' => $action,
                'path' => $path,
                'dir' => $id_dir,
                'readable' => $is_readable,
                'writable' => $is_writable,
                'error' => 0
            );

            if ($formated_mode) {
                $result['mode'] = $formated_mode;
            }

            if ($renamed) {
                $result['renamed'] = $new_name;
            }

            echo json_encode($result, true);
            exit;

            break;
        // default
        default:
            echo json_encode(array('error' => NETCAT_MODULE_FILEMANAGER_ADMIN_ERROR_UNDEFINED_ACTION), true);
            exit;
    }
} else {
    echo json_encode(array('error' => NETCAT_MODULE_FILEMANAGER_ADMIN_ERROR_UNDEFINED_ACTION), true);
    exit;
}
