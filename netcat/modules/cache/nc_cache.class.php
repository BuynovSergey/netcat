<?php

/**
 * class nc_cache
 * @package nc_cache
 * @category nc_cache
 * @abstract
 */
abstract class nc_cache {

    protected $core;
    protected $cache_path, $cache_block;
    protected $dir_chmod, $file_chmod;
    protected $admin_mode, $inside_admin;
    protected $nocacheStoreKeys, $module_folder, $root_folder;
    protected $debug_mode, $debug_access, $debug_level_arr;
    protected $db, $essence, $settings, $catalogue;
    protected $io; // ссылка на объект, реализующий чтение/запись в кеш

    protected function __construct() {
        // global variables
        global $db, $perm, $current_catalogue;
        global $DOCUMENT_ROOT, $MODULE_VARS;
        global $SUB_FOLDER, $CACHE_FOLDER;
        global $MODULE_FOLDER, $ROOT_FOLDER;
        global $DIRCHMOD, $FILECHMOD;
        global $admin_mode, $inside_admin;

        // system superior object
        $this->core = nc_Core::get_object();
        // db interface
        if (get_class($db) == "nc_Db") $this->db = &$db;# ezSQL_mysql
        // current catalogue
        if (isset($current_catalogue['Catalogue_ID'])) {
            $this->catalogue = intval($current_catalogue['Catalogue_ID']);
        }
        else {
            $this->catalogue = 0;
        }
        // cache settings from base
        $this->settings = self::_getCacheSettings($this->catalogue, $this->db);
        //access to debug
        $this->debug_access = isset($perm) ? $perm->isAccess(array(NC_PERM_MODULE, 'cache'), 0, 0, 1) : false;
        // set variables from module vars
        if (isset($MODULE_VARS['cache'])) {
            $this->debug_mode = $MODULE_VARS['cache']['DEBUG_MODE'];
            $this->efficiency_low = $MODULE_VARS['cache']['EFFICIENCY_LOW'];
            $this->efficiency_mid = $MODULE_VARS['cache']['EFFICIENCY_MID'];
        }
        // debug level array
        $this->debug_level_arr = array("error" => "#FFE5E5", "info" => "#F0F7FF", "ok" => "#EDFFEB");
        // essence (list, full, browse etc.) for children classes
        $this->essence = "";
        // folders
        $this->module_folder = nc_module_folder();
        $this->root_folder = $ROOT_FOLDER;
        // cache path
        $this->cache_path = isset($CACHE_FOLDER) ? $CACHE_FOLDER : $DOCUMENT_ROOT . '/' . $SUB_FOLDER . 'netcat_cache/';
        // chmods
        $this->dir_chmod = $DIRCHMOD;
        $this->file_chmod = $FILECHMOD;
        // admin mode
        $this->admin_mode = $admin_mode;
        $this->inside_admin = $inside_admin;
        // array with cached blocks
        $this->cache_block = array();
        // parsed values with need sequence for marks
        $this->nocacheStoreKeys = array();
        // debug info acces, in this time only Supervisor and Director
        //$this->debug_access = isset($perm) ? $perm->isAccess(array(NC_PERM_MODULE, 'cache'), 0, 0, 1) : false;
        // cache folder
        //  if ( !is_dir($this->cache_path) ) {
        //   @mkdir($this->cache_path, $this->dir_chmod);
        // }
        $this->loadIO();
    }

    /**
     * Instance self object method
     *
     * @return self object
     */
    public static function getObject() {}

    /**
     * Get cache settings from base
     *
     * @return array cache settings
     */
    private static function _getCacheSettings($catalogue, $db) {
        static $settings;
        if (!$catalogue) {
            try { // сайт может не определиться
                $nc_core = nc_Core::get_object();
                $site = $nc_core->catalogue->get_by_host_name($_SERVER['HTTP_HOST']);
                $catalogue = nc_array_value($site,'Catalogue_ID');
            } catch (Exception $e) {
                return false;
            }
        }
        // validate
        if (!$catalogue || !$db) return false;
        // check protected variable with settings
        if (!empty($settings[$catalogue])) return $settings[$catalogue];
        // settings from base, once for all essences
        $settings[$catalogue] = $db->get_row("SELECT *, UNIX_TIMESTAMP(`Audit_Begin`) AS Audit_Begin
      FROM `Cache_Settings`
      WHERE `Catalogue_ID` = '".intval($catalogue)."'", ARRAY_A);
        // return settings
        return $settings[$catalogue];
    }

    /**
     * Check audit mode status
     *
     * @return bool true or false
     */
    protected function getAuditStatus() {
        // check settings
        if (empty($this->settings)) return false;
        // count audit expiration date
        $AuditEnd = strtotime("+".$this->settings['Audit_Time']." hours", $this->settings['Audit_Begin']);
        // set static variable return result
        if ($AuditEnd > time()) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Append audit info into the base
     *
     * @param array ids ("Catalogue_ID", "Subdivision_ID", "Sub_Class_ID", "Class_ID", "Message_ID")
     * @param string query string parameter
     * @param string unique hash for browse class
     * @param "read" or "write" action
     * @param bool update attempt action or main action
     * @return
     */
    protected function auditUpdateInfo($ids_values, $query_string, $unique_string, $action, $attempt = false) {
        // check audit mode
        if (!$this->getAuditStatus()) return false;
        // validate
        if (
                !is_array($ids_values) ||
                !in_array($action, array("read", "write"))
        ) {
            throw new Exception(NETCAT_MODULE_CACHE_CLASS_UNCORRECT_DATA_FORMAT);
        }
        // columns name
        switch ($action) {
            case "read":
                $actionCol = ($attempt ? "Attempt_Read" : "Readed");
                break;
            case "write":
                $actionCol = ($attempt ? "Attempt_Write" : "Writed");
                break;
        }
        // columns name for ids_values array
        $ids_keys = array("Catalogue_ID", "Subdivision_ID", "Sub_Class_ID", "Class_ID", "Message_ID");
        $ids_values = array_pad(array_map("intval", $ids_values), sizeof($ids_keys), 0);
        // combine
        $ids_arr = array_combine($ids_keys, $ids_values);
        // append parameters before
        switch (true) {
            case $ids_arr['Class_ID'] && $ids_arr['Message_ID']:
                $ids_arr['Sub_Class_ID'] = $this->db->get_var("SELECT `Sub_Class_ID` FROM `Message".$ids_arr['Class_ID']."` WHERE `Message_ID` = '".$ids_arr['Message_ID']."'");
            case $ids_arr['Sub_Class_ID']:
                list($ids_arr['Catalogue_ID'], $ids_arr['Subdivision_ID']) = $this->db->get_row("SELECT `Catalogue_ID`, `Subdivision_ID` FROM `Sub_Class` WHERE `Sub_Class_ID` = '".$ids_arr['Sub_Class_ID']."'", ARRAY_N);
        }
        // compile query
        foreach ($ids_arr AS $key => $value) {
            $query_arr[] = "`".$key."` = '".$value."'";
        }
        $query_arr[] = "`Essence` = '".$this->essence."'";
        $query_arr[] = "`Query_String` = '".$this->db->escape($query_string)."'";
        $query_arr[] = "`Unique_String` = '".$this->db->escape($unique_string)."'";
        // existance checking
        $infoExistID = $this->db->get_var("SELECT `ID` FROM `Cache_Audit` WHERE ".join(" AND ", $query_arr));
        // append info
        if ($infoExistID) {
            // update audit value
            $this->db->query("UPDATE `Cache_Audit` SET `".$actionCol."` = `".$actionCol."` + 1 WHERE `ID` = '".$infoExistID."'");
        } else {
            // append audit startup value
            $this->db->query("INSERT INTO `Cache_Audit` SET ".join(", ", $query_arr).", `".$actionCol."` = 1");
        }
        return;
    }

    /**
     * Function count dirs, files and total size for selected dir
     *
     * @param string dir path
     * @return array (dirs count, files count, total size)
     */
    public function dirStat($dir = "") {
        // validate
        if ($dir && (!strstr($dir, $this->cache_path) || !is_dir($dir) ))
                return false;
        // end slash
        $dir = rtrim(($dir ? $dir : $this->cache_path), "/")."/";
        // startup values
        $total_size = 0;
        $total_files = 0;
        $total_dirs = 0;
        // count all subdirs and files
        if (is_dir($dir) && ($dh = opendir($dir))) {
            // read children
            while (( $file = readdir($dh) ) !== false) {
                if ($file == "." || $file == "..") continue;
                // append full path
                $file = $dir.$file;
                // delete dir or file
                switch (true) {
                    case is_file($file):
                        $total_files++;
                        $total_size+= filesize($file);
                        break;
                    case is_dir($file):
                        $total_dirs++;
                        list($_total_dirs, $_total_files, $_total_size) = $this->dirStat($file);
                        $total_dirs+= $_total_dirs;
                        $total_files+= $_total_files;
                        $total_size+= $_total_size;
                        break;
                }
            }
            closedir($dh);
        }
        // return array with dir stat
        return array($total_dirs, $total_files, $total_size);
    }

    /**
     * Write stat info about cache size in dir
     *
     * @param int content lenght
     * @return int content lenght writed
     */
    protected function updateStat($lenght) {
        // return if no permission to write
        if (!is_writable($this->cache_path)) return false;
        // stat file path
        $stat_file = $this->cache_path."stat.log";
        // append content lenght from file
        if (file_exists($stat_file)) {
            $content_lenght = $this->getStat();
            $lenght+= intval($content_lenght);
        }
        // return writed bytes count
        return @file_put_contents($stat_file, $lenght);
    }

    /**
     * Get stat info about cache size in dir
     *
     * @return int content lenght in dir
     */
    protected function getStat() {
        // stat file path
        $stat_file = $this->cache_path."stat.log";
        // get content lenght from file
        if (file_exists($stat_file) && is_readable($stat_file)) {
            return intval(file_get_contents($stat_file));
        }
        // return
        return false;
    }

    /**
     * Check quota size for this dir
     *
     * @param int $length content length
     * @return bool false if quota expired, true otherwise
     */
    protected function checkQuota($length) {
        if ($this->io instanceof nc_cache_io_memcache) {
            return true;
        }

        $content_quota = $this->settings['Quota_' . $this->essence] * 1024 * 1024;
        $content_length = $this->getStat();

        if ($content_quota >= ($content_length + $length) || $content_quota == 0) {
            return true;
        }

        switch ($this->settings['Overdraft_' . $this->essence]) {
            // don't write new cache
            case false:
            case 1:
                $this->debugMessage("Quota limit {$content_quota}, cache size {$content_length}, data to write {$length}", __FILE__, __LINE__, 'error');
                return false;
            // try to clear low efficiency cache and write new
            case 2:
                $total_bytes = $this->dropEfficiencyCache(1);
                $new_content_length = $this->getStat();
                // check size once more
                if ($content_quota >= ($new_content_length + $length) || $content_quota == 0) {
                    $this->debugMessage("Low efficiency cache {$total_bytes} cleared from disk. Quota limit {$content_quota}, cache size {$new_content_length}, data to write {$length}", __FILE__, __LINE__, 'info');
                    return true;
                }
                $this->debugMessage("Low efficiency cache {$total_bytes} cleared from disk. Quota limit {$content_quota}, cache size {$new_content_length}, data to write {$length}", __FILE__, __LINE__, 'error');
                return false;
        }

        return true;
    }

    /**
     * Return efficiency range
     *
     * @param int efficiency (1 - low, 2- middle, 3 - good)
     * @return array efficiency range
     */
    public function getEfficiency($efficiency) {
        // select efficiency
        switch ($efficiency) {
            case 1:
                // low efficiency
                return array(0, $this->efficiency_low);
            case 2:
                // middle efficiency
                return array($this->efficiency_low, $this->efficiency_mid);
            case 3:
                // good efficiency
                return array($this->efficiency_mid, 1.1);
        }
    }

    /**
     * Get cache with setted efficiency
     *
     * @param int efficiency (1 - low, 2- middle, 3 - good)
     * @return array with data from MySQL `Cache_Clear` table
     */
    protected function getEfficiencyCache($efficiency = 1) {

        $cache_efficiency = $this->getEfficiency($efficiency);

        $result = $this->db->get_results("SELECT `Catalogue_ID`, `Subdivision_ID`, `Sub_Class_ID`, `Class_ID`, `Message_ID`
      FROM `Cache_Clear`
      WHERE `Efficiency` >= '".$cache_efficiency[0]."' AND `Efficiency` < '".$cache_efficiency[1]."'
      AND `Essence` = '".$this->essence."'", ARRAY_A);

        return $result;
    }

    public function dropCache() {
        // this function must be called only from cache/admin.inc.php file
        $debug_backtrace = debug_backtrace();
        // get file from calling this method
        $deb_value = $debug_backtrace[1];
        // validate file permission
        if (
                !( str_replace(array("/", "\\"), "/", $deb_value['file']) == str_replace(array("/", "\\"), "/", $this->module_folder."cache/admin.php") )
        ) {
            throw new Exception(NETCAT_MODULE_CACHE_CLASS_UNRECOGNIZED_OBJECT_CALLING);
        }
        // delete all from this dir
        $total_bytes = $this->io->drop($this->cache_path, true);
        $this->io->update_stat($this->essence, -$total_bytes);
        // return total deleted bytes
        return $total_bytes;
    }

    /**
     * Check file expiration date
     * @param string main content with info in first line
     * this info must be droped before print screen
     * @param int cache file lifetime (minutes)
     */
    protected function checkExpire(&$content, $lifetime) {
        // get first cache info line
        $regExp = "/^\/\* cache created (\d+) \*\/\n/im";
        // if cache not expired
        if (preg_match($regExp, $content, $matches)) {
            $content = nc_preg_replace($regExp, "", $content);
            if ($matches[1] >= ( time() - $lifetime * 60 ) || $lifetime <= 0)
                    return true;
        }
        // default false
        return false;
    }

    /**
     * Drop cache marks
     *
     * @param string data
     *
     * @return string cleared data
     */
    public function nocacheClear($data) {
        // clear cache temporary marks
        return nc_preg_replace("/<\!-- \/?nocache(_[a-z]+_\d+)? -->/is", "", $data);
    }

    public function authAddonString($CacheForUser, $current_user) {
        switch ($CacheForUser) {
            // each user
            case 1:
                $cache_for_user = "&nc_cache_auth_user=".$current_user['User_ID'];
                break;
            // user main group
            case 2:
                $cache_for_user = "&nc_cache_auth_group=".$current_user['PermissionGroup_ID'];
                break;
            // not use
            default:
                $cache_for_user = "";
        }

        return $cache_for_user;
    }

    /**
     * Replace nocache marks with nocache blocks in component fields
     *
     * @param string parsed component data
     * @param mixed parsed data with nocache blocks or false
     */
    protected function nocacheReplace($data) {
        // change descriptor
        $changed = false;
        $matches = array();
        // regex value
        $regex = "|(<\!-- nocache_block_(\d*?) -->(.*?)<\!-- /nocache_block_(\d*?) -->)(.*)|is";

        // check nocache existance
        if (preg_match($regex, $data, $matches)) {
            $data = str_replace('"', '\"', $data);
        }

        // walk
        while (!empty($matches)) {
            // replace string
            $replace = $this->cache_block[$matches[2]]."\$5";
            $data = nc_preg_replace($regex, $replace, $data);
            $changed = true;
            // check other nocache existance
            preg_match($regex, $data, $matches);
        }
        return $changed ? $data : $changed;
    }

    /**
     * Set nocache marks in component fields
     *
     * @param array component data
     * @param int counted nocache block
     */
    public function nocacheStore(&$data) {
        // check parsed values with need sequence
        if (empty($this->nocacheStoreKeys)) return false;
        // walk
        while ($key = array_shift($this->nocacheStoreKeys)) {
            // need field
            $value = &$data[$key];
            // regex value
            $regex = "|(<\!-- nocache -->(.*?)<\!-- /nocache -->)(.*)|is";
            // walk
            while (preg_match($regex, $value, $matches)) {
                // fill array
                $this->cache_block[] = $matches[2];
                $index = count($this->cache_block) - 1;
                // replace string
                $replace = "<!-- nocache_block_".$index." -->\$2<!-- /nocache_block_".$index." -->\$3";
                $value = nc_preg_replace($regex, $replace, $value);
            }
        }
        return count($this->cache_block);
    }

    protected function loadIO() {
        if (isset($this->settings['IO_Interface'])){
            $name = $this->settings['IO_Interface'] ? $this->settings['IO_Interface'] : 'file';
        }
        else {
            $name = 'file';
        }
        $io_class_name = "nc_cache_io_".$name;
        require_once ($this->module_folder."cache/".$io_class_name.".class.php");

        // при ошибке будем использовать файловый кеш
        try {
            $this->io = call_user_func(array($io_class_name, 'get_object'), $this->settings);
        } catch (Exception $e) {
            $this->errorMessage($e);
            require_once ($this->module_folder."cache/nc_cache_io_file.class.php");
            $this->io = nc_cache_io_file::get_object();
        }

        return 0;
    }

    /**
     * Function create dir in setted path
     *
     * @param string dir where need to create new dir
     * @param string new dir name
     *
     * @return true if dir created and writable or false
     */
    protected function createWritableDir($path, $dir) {
        // create directory if no exist
        if (!is_dir($path.$dir) && is_dir($path) && is_writable($path)) {
            mkdir($path.$dir, $this->dir_chmod);
            @chmod($path.$dir, $this->dir_chmod);
        }
        // if directory not writable - return
        if (is_dir($path.$dir) && is_writable($path.$dir)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Collect debug info function
     * for critical errors!
     *
     * @param Exception object
     */
    public function errorMessage(Exception $e) {
        // if disabled - return
        if (!$this->debug_mode) return;
        // append debug message
        $this->debug_arr[] = array(
                "message" => $e->getMessage(),
                "file" => $e->getFile(),
                "line" => $e->getLine(),
                "level" => "error"
        );
    }

    /**
     * Collect debug info function
     *
     * @param Exception object
     */
    public function debugMessage($message, $file = "", $line = 0, $level = "info") {
        // if disabled or no access - return
        if (!($this->debug_mode))
                return; // && $this->debug_access
            // append debug message
 $this->debug_arr[] = array(
                "message" => $message,
                "file" => $file,
                "line" => $line,
                "level" => array_key_exists($level, $this->debug_level_arr) ? $level : "info"
        );
    }

    protected function debugInfo() {
        // check access
        //if (!$this->debug_access) return;
        // compile debug info
        if (!empty($this->debug_arr)) {
            $result = "<div style='padding:10px'>";
            $result.= "<h2 style='padding-bottom:5px'>Cache debug info, <span style='color:#A00'>".get_class($this)."</span> class</h2>";
            $result.= "<table cellpadding=5 cellspacing=1 style='border:none; background:#CCC; width:100%'>";
            $result.= "<col style='width:1%'/><col style='width:45%'/><col style='width:44%'/><col style='width:10%'/>";
            $result.= "<tr><td style='background:#EEE'><b>!</b></td><td style='background:#EEE'><b>Message</b></td><td style='background:#EEE'><b>File</b></td><td style='background:#EEE'><b>Line</b></td></tr>";
            foreach ($this->debug_arr AS $debug) {
                $background = $this->debug_level_arr[$debug['level']] ? $this->debug_level_arr[$debug['level']] : "#FFFFFF";
                $result.= "<tr><td style='background:".$background."'></td><td style='background:#FFF'>".$debug['message']."</td><td style='background:#FFF'>".$debug['file']."</td><td style='background:#FFF'>".$debug['line']."</td></tr>";
            }
            $result.= "</table>";
            $result.= "</div>";
        }
        // return result
        return $result;
    }

    /**
     * Destructor function
     */
    public function __destruct() {

        if ($this->debug_mode && $this->debug_access) {
            echo $this->debugInfo();
        }
    }

}
?>
