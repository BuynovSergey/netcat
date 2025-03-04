<?php

/**
 * class nc_filemanager
 * @package nc_filemanager
 * @category nc_filemanager
 */
class nc_filemanager {

    protected $db;
    protected $module_vars;
    protected $base_folder;
    protected $MODULE_PATH;
    protected $PHP_TYPE;
    protected $url_prefix;
    protected $UI_CONFIG;
    protected $self_folder;

    protected function __construct() {
        global $UI_CONFIG;

        // system superior object
        $nc_core = nc_Core::get_object();

        $this->db = &$nc_core->db;
        $this->module_vars = &$nc_core->modules->get_vars("filemanager");
        $this->UI_CONFIG = $UI_CONFIG;

        $this->base_folder = rtrim($nc_core->DOCUMENT_ROOT . $nc_core->SUB_FOLDER, "/") . "/";

        $this->PHP_TYPE = $nc_core->PHP_TYPE ? $nc_core->PHP_TYPE : "module";

        $this->url_prefix = "admin.php?page=manager";

        $this->self_path   = nc_module_folder('filemanager');
        $this->self_folder = nc_module_path('filemanager');
    }

    public function get_base_folder() {
        // return base_folder
        return $this->base_folder;
    }

    /**
     * Get or instance self object
     *
     * @return self object
     */
    public static function get_object() {
        // call as static
        static $storage;
        // check inited object
        if (!isset($storage)) {
            // init object
            $storage = new self();
        }
        // return object
        return is_object($storage) ? $storage : false;
    }

    /*
     * Show filemanager
     *
     * @param string relative folder path
     *
     * @return text HTML code
     */

    public function manager($dir = "") {

        if (strpos($dir, "..") !== false) {
            throw new Exception(sprintf(NETCAT_MODULE_FILEMANAGER_ADMIN_ERROR_PATH, htmlspecialchars($dir)));
        }

        $dir = trim($dir, "/");

        if ($dir == ".") {
            $dir = "";
        }

        if ($dir) {
            $dir .= "/";
        }

        try {
            $files_array = $this->get_files($dir);
        } catch (Exception $e) {
            nc_print_status($e->getMessage(), "error");
            return false;
        }

        // directory not root
        if ($dir) {
            array_unshift($files_array, array(
                'name' => '..',
                'path' => $this->base_folder . nc_preg_replace("/([^\/]*?[\/]?)$/", "", $dir),
                'dir'  => true,
            ));
        }

        $icons = array(
            'nc--file-archive' => array('zip','rar','tar','gz','7z'),
            'nc--file-image'   => array('jpg','jpeg','gif','png','bmp','tiff', 'ico'),
            'nc--file-source'  => array('html','php','css','js'),
            'nc--file-text'    => array('txt','rtf','doc','docx','odf'),
        );
        $binary_ext = $icons['nc--file-archive'] + $icons['nc--file-image'];

        $token_input = nc_core::get_object()->token->get_input();
            
        if ( ! empty($files_array)) {
            
            $parent_is_writable = is_writable($this->base_folder . $dir);
            
            $files      = array();
            $total_size = 0;
            $dir_count  = 0;
            $file_count = 0;

            foreach ($files_array as $file) {
                $is_writable   = is_writable($file['path']);
                $is_readable   = is_readable($file['path']);
                $is_executable = is_executable($file['path']);

                $path   = trim(str_replace($this->base_folder, "", $file['path']), "/");
                $perm   = $is_readable ? $this->format_file_permission($file['path']) : false;
                $is_dir = (bool)$file['dir'];
                $size   = 0;
                
                $icon = '';
                if ( ! empty($file['ext'])) {
                    foreach ($icons as $ext_icon => $extensions) {
                        if (in_array($file['ext'], $extensions)) {
                            $icon = $ext_icon;
                            break;
                        }
                    }
                }
                if ( ! $icon) {
                    $icon   = 'nc--' . ($is_dir ? "folder-dark" : "file");
                }
                $icon .= (!$is_readable ? ' nc--disabled' : '' );

                if ( ! $is_dir) {
                    if ($is_readable) {
                        $size        = filesize($file['path']);
                        $total_size += $size;
                    }
                    $file_count++;
                } else {
                    if ($file['name'] != '..') {
                        $dir_count++;
                    }
                }

                // Actions
                $actions = array();
                if ($file['name'] != '..') {

                    // Edit
                    if ( ! $file['dir'] && ! in_array($file['ext'], $binary_ext)) {
                        $actions['edit'] = array(
                            'icon'  => 'nc--edit',
                            'title' => NETCAT_MODULE_FILEMANAGER_ADMIN_EDIT,
                            'click'  => '$nc(this).closest("form").submit()',
                            'prefix' =>
                                "<form method='post' action='$this->url_prefix'>" .
                                "<input type='hidden' name='phase' value='3'>" .
                                "<input type='hidden' name='file' value='" . htmlspecialchars($path) . "'>" .
                                $token_input,
                            'suffix' => '</form>',
                        );
                    }

                    // Download
                    if ( ! $file['dir']) {
                        $actions['download'] = array(
                            'icon'  => 'nc--download',
                            'title' => NETCAT_MODULE_FILEMANAGER_ADMIN_DOWNLOAD,
                            'link'  => $this->url_prefix . "&phase=5&file=" . $path,
                        );
                    }

                    // Remove
                    if ($parent_is_writable) {
                        $actions['delete'] = array(
                            'icon'  => 'nc--remove',
                            'title' => NETCAT_MODULE_FILEMANAGER_ADMIN_DELETE,
                            'link'  => $this->url_prefix . "&phase=4&path=" . $path,
                        );
                    }

                    // Settings
                    $actions['settings'] = array(
                        'icon'  => 'nc--settings',
                        'title' => NETCAT_MODULE_FILEMANAGER_ADMIN_SETTINGS,
                        'click' => "nc_filemanagerObj.show_panel('" . rawurlencode($path) . "'); return false",
                    );

                    // Copy link
                    $actions['copy_link'] = array(
                        'icon'  => 'nc--mod-linkmanager',
                        'title' => NETCAT_MODULE_FILEMANAGER_ADMIN_COPY_LINK_BUTTON,
                        'click' => "nc_filemanagerObj.show_link_panel('" . rawurlencode($path) . "', " . (int)$is_dir . "); return false",
                    );
                }

                $files[] = array(
                    'name'          => $file['name'],
                    'icon'          => $icon,
                    'path'          => $path,
                    'link'          => $is_readable ? ($file['dir'] ? $this->url_prefix . "&dir=" . $path : $this->url_prefix . "&phase=2&file=" . $path) : '',
                    'dir'           => $file['dir'],
                    'perm'          => $perm,
                    'size'          => $size,
                    'is_dir'        => (bool)$is_dir,
                    'is_readable'   => (bool)$is_readable,
                    'is_writable'   => (bool)$is_writable,
                    'is_executable' => (bool)$is_executable,
                    'actions'       => $actions,

                );
            }
        }


        $this->UI_CONFIG->actionButtons[] = array(
                "id"      => "refresh",
                "caption" => NETCAT_MODULE_FILEMANAGER_ADMIN_REFRESH_BUTTON,
                "align"   => "left",
                "action"  => "mainView.refreshIframe()"
        );
        $this->UI_CONFIG->actionButtons[] = array(
                "id"      => "submit",
                "caption" => NETCAT_MODULE_FILEMANAGER_ADMIN_SAVE_BUTTON,
                "action"  => "mainView.submitIframeForm('FileManagerUpload')"
        );

        $nc_core = nc_Core::get_object();
        $view = $nc_core->ui->view($this->self_path . 'views/filemanager');

        $view->with('fm',                 $this);
        $view->with('nc_core',            $nc_core);
        $view->with('dir',                $dir);
        $view->with('self_folder',        $this->self_folder);
        $view->with('total_size',         $total_size);
        $view->with('dir_count',          $dir_count);
        $view->with('file_count',         $file_count);
        $view->with('parent_is_writable', $parent_is_writable);
        $view->with('breadcrumbs',        $this->breadcrumbs( $this->base_folder . $dir ));
        $view->with('files',              $files);

        $view->with('module_permissions', array(
            'download' => true, // если есть доступ к файлу, то можно смотреть файл
            'copy_link' => true,
            'edit' => $this->has_module_permission('file_edit'),
            'settings' => $this->has_module_permission('file_property_edit'),
            'delete' => $this->has_module_permission('file_delete'),
        ));

        return $view->make();
    }

    /*
     * Preview file
     *
     * @param string absolute file path
     *
     * @return text HTML code
     */

    public function preview($file) {

        $this->check_module_permission_for_file('file_view', $file);

        if (strpos($file, "..") !== false)
            throw new Exception(sprintf(NETCAT_MODULE_FILEMANAGER_ADMIN_ERROR_PATH, htmlspecialchars($file)));

        // check rights
        if (!file_exists($file)) {
            throw new Exception(sprintf(NETCAT_MODULE_FILEMANAGER_ADMIN_ERROR_FILE_NOT_EXIST, htmlspecialchars($file)));
        }

        if (!is_readable($file)) {
            throw new Exception(sprintf(NETCAT_MODULE_FILEMANAGER_ADMIN_ERROR_FILE_READ_PERMISSION, htmlspecialchars($file)));
        }

        $path   = str_replace($this->base_folder, "", $file);
        $image  = getimagesize($file);
        $source = $image ? false : highlight_string(file_get_contents($file), 1);

        if ($image) {
            $image['path'] = '/' . str_replace($this->base_folder, "", $file);
        }

        $nc_core = nc_Core::get_object();
        $view = $nc_core->ui->view($this->self_path . 'views/preview');
        
        $view->with('breadcrumbs', $this->breadcrumbs( $file ));
        $view->with('image',       $image);
        $view->with('source',      $source);
        $view->with('file',        $file);
        $view->with('dir',         $path);
        
        return $view->make();        
    }

    /*
     * Download file from server
     * Return file data into the browser stream
     *
     * @param string absolute file path
     */

    public function download($file) {

        $this->check_module_permission_for_file('file_view', $file);

        if (strpos($file, "..") !== false)
            throw new Exception(sprintf(NETCAT_MODULE_FILEMANAGER_ADMIN_ERROR_PATH, htmlspecialchars($file)));

        // check rights
        if (!file_exists($file)) {
            throw new Exception(sprintf(NETCAT_MODULE_FILEMANAGER_ADMIN_ERROR_FILE_NOT_EXIST, htmlspecialchars($file)));
        }

        if (!is_readable($file)) {
            throw new Exception(sprintf(NETCAT_MODULE_FILEMANAGER_ADMIN_ERROR_FILE_READ_PERMISSION, htmlspecialchars($file)));
        }

        $file_name = basename($file);
        $file_size = filesize($file);

        while (ob_get_level() && @ob_end_clean())
            continue;


        nc_set_http_response_code(200);

        header("Content-type: application/octet-stream");
        header("Content-Disposition: attachment; filename=\"" . urldecode($file_name) . "\"");
        header('Content-Transfer-Encoding: binary');

        if ($file_size) {
            header("Content-Length: " . $file_size);
            header("Connection: close");
        }

        echo file_get_contents($file);

        exit;
    }

    /*
     * Edit file form
     *
     * @param string absolute file path
     *
     * @return text HTML code
     */

    public function edit($file) {

        $this->check_module_permission_for_file('file_edit', $file);

        //check
        if (strpos($file, "..") !== false)
            throw new Exception(sprintf(NETCAT_MODULE_FILEMANAGER_ADMIN_ERROR_PATH, htmlspecialchars($file)));

        // get files from this dir
        if (!file_exists($file)) {
            throw new Exception(sprintf(NETCAT_MODULE_FILEMANAGER_ADMIN_ERROR_FILE_NOT_EXIST, htmlspecialchars($file)));
        }

        $content = file_get_contents($file);

        $path   = str_replace($this->base_folder, "", $file);
        $image  = getimagesize($file);

        if ($image) {
            $image['path'] = '/' . str_replace($this->base_folder, "", $file);
        }

        // admin buttons
        $this->UI_CONFIG->actionButtons[] = array(
                "id" => "submit",
                "caption" => NETCAT_MODULE_FILEMANAGER_ADMIN_SAVE_BUTTON,
                "action" => "mainView.submitIframeForm('FileManagerEditFile')"
        );

        $nc_core = nc_Core::get_object();
        $view = $nc_core->ui->view($this->self_path . 'views/edit');
        
        $view->with('breadcrumbs', $this->breadcrumbs( $file ));
        $view->with('content',     $content);
        $view->with('nc_core',     $nc_core);
        $view->with('file',        $file);
        $view->with('dir',         $path);
        $view->with('path',        $path);
        
        return $view->make();
    }

    /*
     * Delete file dialog
     *
     * @param string absolute file path
     *
     * @return text HTML code
     */

    public function delete($file) {

        $this->check_module_permission_for_file('file_delete', $file);

        $nc_core = nc_Core::get_object();
        //check
        if (strpos($file, "..") !== false)
            throw new Exception(sprintf(NETCAT_MODULE_FILEMANAGER_ADMIN_ERROR_PATH, htmlspecialchars($file)));


        // check existance
        if (!file_exists($file)) {
            throw new Exception(sprintf(NETCAT_MODULE_FILEMANAGER_ADMIN_ERROR_NOT_EXIST, htmlspecialchars($file)));
        }

        // dialog message
        if (is_dir($file)) {
            nc_print_status(sprintf(NETCAT_MODULE_FILEMANAGER_ADMIN_DIALOG_DIR_DELETE, htmlspecialchars($file)), "info");
        }
        if (is_file($file)) {
            nc_print_status(sprintf(NETCAT_MODULE_FILEMANAGER_ADMIN_DIALOG_FILE_DELETE, htmlspecialchars($file)), "info");
        }

        $result = "<div class='block_delete'>" .
                "<form method='post' action='admin.php' id='FileManagerDelete'>";

        $result.= "<input type='hidden' name='file' value='" . str_replace($this->base_folder, "", $file) . "'>" .
                "<input type='hidden' name='phase' value='41'>" .
                $nc_core->token->get_input() .
                "</form>" .
                "<form method='post' action='admin.php' enctype='multipart/form-data' id='FileManagerCancel'>" .
                "<input type='hidden' name='dir' value='" . dirname(str_replace($this->base_folder, "", $file)) . "'>" .
                "<input type='hidden' name='phase' value='1'>" .
                $nc_core->token->get_input() .
                "</form>" .
                "</div>";

        // admin buttons
        $this->UI_CONFIG->actionButtons[] = array(
            "id" => "cancel",
            "align" => "left",
            "caption" => NETCAT_MODULE_FILEMANAGER_ADMIN_CANCEL_BUTTON,
            "action" => "mainView.submitIframeForm('FileManagerCancel')",

        );
        $this->UI_CONFIG->actionButtons[] = array(
            "id" => "submit",
            "caption" => NETCAT_MODULE_FILEMANAGER_ADMIN_DELETE_BUTTON,
            "action" => "mainView.submitIframeForm('FileManagerDelete')",
            "red_border" => true,
        );

        return $result;
    }

    /*
     * Rewrite file dialog
     *
     * @param string absolute file path, temp
     * @param string absolute file path, new
     *
     * @return text HTML code
     */

    public function rewrite($temp_file_path, $new_file_path) {

        $this->check_module_permission_for_file('file_edit', $new_file_path);

        // check existance
        if (!file_exists($temp_file_path)) {
            throw new Exception(sprintf(NETCAT_MODULE_FILEMANAGER_ADMIN_ERROR_FILE_NOT_EXIST, $temp_file_path));
        }

        // dialog message
        nc_print_status(sprintf(NETCAT_MODULE_FILEMANAGER_ADMIN_DIALOG_FILE_REWRITE, $new_file_path), "info");

        $result = "<div class='block_rewrite'>" .
                "<form method='post' action='admin.php' id='FileManagerRewrite'>" .
                nc_core::get_object()->token->get_input() .
                "<input type='hidden' name='temp_file' value='" . str_replace($this->base_folder, "", $temp_file_path) . "'>" .
                "<input type='hidden' name='new_file' value='" . str_replace($this->base_folder, "", $new_file_path) . "'>" .
                "<input type='hidden' name='phase' value='22'>" .
                "</form>" .
                "<form method='post' action='admin.php' enctype='multipart/form-data' id='FileManagerCancel'>" .
                "<input type='hidden' name='dir' value='" . dirname(str_replace($this->base_folder, "", $new_file_path)) . "'>" .
                "<input type='hidden' name='phase' value='1'>" .
                "</form>" .
                "</div>";

        // admin buttons
        $this->UI_CONFIG->actionButtons[] = array(
                "id" => "cancel",
                "align" => "left",
                "caption" => NETCAT_MODULE_FILEMANAGER_ADMIN_CANCEL_BUTTON,
                "action" => "mainView.submitIframeForm('FileManagerCancel')"
        );
        $this->UI_CONFIG->actionButtons[] = array(
            "id" => "submit",
            "caption" => NETCAT_MODULE_FILEMANAGER_ADMIN_REWRITE_BUTTON,
            "action" => "mainView.submitIframeForm('FileManagerRewrite')",
            "red_border" => true,
        );

        return $result;
    }

    /*
     * Content header path
     *
     * @param string absolute file path
     *
     * @return text HTML code
     */

    public function current($path) {

        if (file_exists($path)) {
            $path = str_replace($this->base_folder, "", $path);
        } else {
            $path = "/";
        }

        $path_str = "";
        $path_arr = array();
        $path_arr_linked = array();

        $path = trim($path, "/");
        $path_arr = explode("/", $path);

        if (!empty($path_arr)) {
            $link = "";
            $path_arr_count = count($path_arr);
            for ($i = 0; $i < $path_arr_count; $i++) {
                if (trim($path_arr[$i]) == "")
                    continue;

                $link.= $path_arr[$i] . "/";
                if (($i + 1) != $path_arr_count) {
                    $path_arr_linked[] = "<a href='" . $this->url_prefix . "&dir=" . $link . "'>" . $path_arr[$i] . "</a>";
                } else {
                    $path_arr_linked[] = $path_arr[$i];
                }
            }
        }

        $begin_link = "<a href='" . $this->url_prefix . "&dir=/'>" . NETCAT_MODULE_FILEMANAGER_ADMIN_ROOT_LINK . "</a>";

        $result = "<link type='text/css' rel='stylesheet' href='" . nc_add_revision_to_url($this->self_folder . 'filemanager.css') . "'>" . // not valid HTML string
                "<div class='block_current'>" .
                "<table cellpadding='5' cellspacing='1' class='current'>" .
                "<tr>" .
                "<td class='title'>" . NETCAT_MODULE_FILEMANAGER_ADMIN_CURRENT . "</td>" .
                "</tr>" .
                "<tr>" .
                "<td class='value'>" . (!empty($path_arr_linked) ? $begin_link . " / " . join(" / ", $path_arr_linked) : "/" ) . "</td>" .
                "</tr>" .
                "</table>" .
                "</div>";

        return $result;
    }

    /*
     * Content path
     *
     * @param string absolute file path
     *
     * @return array
     */

    public function breadcrumbs($path) {

        if (file_exists($path)) {
            $path = str_replace($this->base_folder, "", $path);
        } else {
            $path = "/";
        }

        $path     = trim($path, "/");
        $path_arr = explode("/", $path);

        $result = array();

        // root link
        $result[] = array(
            'link'  => $this->url_prefix . "&dir=/",
            'title' => NETCAT_MODULE_FILEMANAGER_ADMIN_ROOT_LINK
        );

        if ( ! empty($path_arr)) {
            $link = '';
            $path_arr_count = count($path_arr);
            foreach ($path_arr as $i => $dir) {
                if ( ! $dir) continue;
                
                $link .= $dir . '/';
                
                $result[] = array(
                    'link'  => $this->url_prefix . "&dir=" . $link,
                    'title' => $path_arr[$i],
                );
            }
        }

        return $result;
    }

    /*
     * Set chmod function
     *
     * @param string absolute path
     * @param oct mode
     *
     * @return bool
     */

    public function chmod($path, $mode) {

        $this->check_module_permission_for_file('file_property_edit', $path);

        if (!file_exists($path)) {
            throw new Exception(sprintf(NETCAT_MODULE_FILEMANAGER_ADMIN_ERROR_CHMOD, $path));
        }
        return @chmod($path, $mode);
    }

    /*
     * Rename file or folder
     *
     * @param string relative "from" file path
     * @param string relative "to" file path
     *
     * @return bool
     */

    public function rename($from, $destination) {

        $this->check_module_permission_for_file('file_property_edit', $from);
        $this->check_module_permission_for_file('file_property_edit', $destination);

        $from = trim($from, "/");
        $from = $this->base_folder . $from;

        $destination = trim($destination, "/");
        $destination = $this->base_folder . $destination;

        if (!file_exists($from)) {
            if (is_dir($from)) {
                throw new Exception(sprintf(NETCAT_MODULE_FILEMANAGER_ADMIN_ERROR_DIR_NOT_EXIST, $from));
            } else {
                throw new Exception(sprintf(NETCAT_MODULE_FILEMANAGER_ADMIN_ERROR_FILE_NOT_EXIST, $from));
            }
        }

        return @rename($from, $destination);
    }

    /*
     * Recursive delete folder
     *
     * @param string absolute folder path
     *
     * @return bool
     */

    public function delete_dir($path) {

        $this->check_module_permission_for_file('file_delete', $path);

        if (!( is_dir($path) && is_writable($path) ))
            return false;

        $files = array();
        $dh = opendir($path);

        if (!is_resource($dh))
            return false;

        while (false !== ($filename = readdir($dh))) {
            if ($filename == "." || $filename == "..")
                continue;
            if (is_file($path . "/" . $filename))
                unlink($path . "/" . $filename);
            if (is_dir($path . "/" . $filename))
                $this->delete_dir($path . "/" . $filename);
        }
        closedir($dh);

        if (count(glob($path . "/*")) === 0 && is_writable($path)) {
            return rmdir($path);
        }
    }

    /*
     * Get file or folder permission
     *
     * @param string absolute file path
     *
     * @return binary file permissions
     */

    public function get_permission($file) {

        if (!file_exists($file))
            return false;

        $perms = fileperms($file);

        $result = array();

        // Владелец
        $result[] = ($perms & 0x0100) ? 1 : 0;
        $result[] = ($perms & 0x0080) ? 1 : 0;
        $result[] = ($perms & 0x0040) && !($perms & 0x0800) ? 1 : 0;

        // Группа
        $result[] = ($perms & 0x0020) ? 1 : 0;
        $result[] = ($perms & 0x0010) ? 1 : 0;
        $result[] = ($perms & 0x0008) && !($perms & 0x0400) ? 1 : 0;

        // Мир
        $result[] = ($perms & 0x0004) ? 1 : 0;
        $result[] = ($perms & 0x0002) ? 1 : 0;
        $result[] = ($perms & 0x0001) && !($perms & 0x0200) ? 1 : 0;

        return $result;
    }

    /*
     * Get files list from folder
     *
     * @param string relative folder path
     * @param bool sorting
     *
     * @return array files list
     */

    public function get_files($dir = "", $sort = true) {

        if (strlen(trim($dir))) {
            $dir = trim($dir, "/") . "/";
        }

        $dir = $this->base_folder . $dir;

        // clear cache for fileperms
        clearstatcache();

        if (!is_dir($dir)) {
            throw new Exception(sprintf(NETCAT_MODULE_FILEMANAGER_ADMIN_ERROR_DIR_NOT_EXIST, htmlspecialchars($dir)));
        }

        if (!is_readable($dir)) {
            throw new Exception(sprintf(NETCAT_MODULE_FILEMANAGER_ADMIN_ERROR_DIR_READ_PERMISSION, htmlspecialchars($dir)));
        }

        $files_arr = array();

        if ($handle = opendir($dir)) {
            while (($file = readdir($handle)) !== false) {
                if ($file != "." && $file != ".." && $this->can_interact_with_file($dir . $file)) {
                    $files_arr[] = array(
                            'name' => $file,
                            'ext'  => pathinfo($file, PATHINFO_EXTENSION),
                            'path' => $dir . $file,
                            'dir'  => +is_dir($dir . $file),
                    );
                }
            }
            closedir($handle);
        }

        if ($sort && !empty($files_arr)) {
            // helpfull arrays to sorting
            $dirs = $names = array();
            // get arrays
            foreach ($files_arr as $file) {
                $dirs[] = $file['dir'];
                $names[] = $file['name'];
            }
            // sorting
            array_multisort($dirs, SORT_DESC, SORT_NUMERIC, $names, SORT_ASC, SORT_STRING, $files_arr);
        }

        return $files_arr;
    }

    /*
     * Format file or directory "counted" name
     *
     * @param string "folder" or "file"
     * @param int counted value
     *
     * @return string "counted" name
     */

    public function format_name($id, $digit) {
        // validation
        if (!($id == "folder" || $id == "file"))
            return false;

        if ($digit > 19)
            $digit = substr($digit, -1, 1);
        if ($digit == 1)
            $strend = 1;
        if ($digit >= 2 && $digit <= 4)
            $strend = 2;
        if ($digit == 0 || ($digit >= 5 && $digit <= 19))
            $strend = 3;
        if ($id == "folder") {
            $a = array(
                    1 => NETCAT_MODULE_FILEMANAGER_ADMIN_DIR_V1,
                    2 => NETCAT_MODULE_FILEMANAGER_ADMIN_DIR_V2,
                    3 => NETCAT_MODULE_FILEMANAGER_ADMIN_DIR_V3
            );
            return $a[$strend];
        }
        if ($id == "file") {
            $a = array(
                    1 => NETCAT_MODULE_FILEMANAGER_ADMIN_FILE_V1,
                    2 => NETCAT_MODULE_FILEMANAGER_ADMIN_FILE_V2,
                    3 => NETCAT_MODULE_FILEMANAGER_ADMIN_FILE_V3
            );
            return $a[$strend];
        }
    }

    /*
     * Get file or folder permission
     *
     * @param string absolute file path
     *
     * @return string file permissions
     */

    public function format_file_permission($file) {

        if (!file_exists($file))
            return false;

        // clear cache for fileperms
        clearstatcache();

        $perms = fileperms($file);

        if ($this->module_vars['DEC_PERMISSION_FORMAT']) {
            // return octet permission
            return substr(sprintf('%o', $perms), -4);
        }

        switch (true) {
            case ($perms & 0xC000) == 0xC000:
                // Сокет
                $info = 's';
                break;
            case ($perms & 0xA000) == 0xA000:
                // Символическая ссылка
                $info = 'l';
                break;
            case ($perms & 0x8000) == 0x8000:
                // Обычный
                $info = '-';
                break;
            case ($perms & 0x6000) == 0x6000:
                // Специальный блок
                $info = 'b';
                break;
            case ($perms & 0x4000) == 0x4000:
                // Директория
                $info = 'd';
                break;
            case ($perms & 0x2000) == 0x2000:
                // Специальный символ
                $info = 'c';
                break;
            case ($perms & 0x1000) == 0x1000:
                // Поток FIFO
                $info = 'p';
                break;
            default:
                // Неизвестный
                $info = 'u';
        }

        // Владелец
        $info.= ( $perms & 0x0100) ? 'r' : '-';
        $info.= ( $perms & 0x0080) ? 'w' : '-';
        $info.= ( $perms & 0x0040) ? (($perms & 0x0800) ? 's' : 'x' ) : (($perms & 0x0800) ? 'S' : '-');

        // Группа
        $info.= ( $perms & 0x0020) ? 'r' : '-';
        $info.= ( $perms & 0x0010) ? 'w' : '-';
        $info.= ( $perms & 0x0008) ? (($perms & 0x0400) ? 's' : 'x' ) : (($perms & 0x0400) ? 'S' : '-');

        // Мир
        $info.= ( $perms & 0x0004) ? 'r' : '-';
        $info.= ( $perms & 0x0002) ? 'w' : '-';
        $info.= ( $perms & 0x0001) ? (($perms & 0x0200) ? 't' : 'x' ) : (($perms & 0x0200) ? 'T' : '-');

        return $info;
    }

    /**
     * @param string $path
     */
    public function can_interact_with_file($path) {
        /** @var Permission $perm */
        global $perm;
        if ($perm->isSupervisor()) {
            return true;
        }
        $base_folder = preg_quote($this->base_folder, '!');
        return !preg_match("!^$base_folder(?:
           .*\.htaccess |
           .+\.php.* |
           .+\.html.* |
           .+\.js |
           netcat(?:/.*)? |
           netcat_template(?:/.*)?
        )$!xi", $path);
    }

    /**
     * @param string $permission
     * @return bool
     * @throws Exception
     */
    protected function has_module_permission($permission) {
        /** @var Permission $perm */
        global $perm;
        return $perm->isAccess(array(NC_PERM_MODULE, 'filemanager'), $permission);
    }

    /**
     * @param string $permission
     * @param string $path
     * @throws Exception
     */
    public function check_module_permission_for_file($permission, $path) {
        if (!$this->has_module_permission($permission) || !$this->can_interact_with_file($path)) {
            while (@ob_end_clean());
            BeginHtml();
            nc_print_status(NETCAT_MODERATION_ERROR_NORIGHT, 'error');
            EndHtml();
            exit();
        }
    }

}
