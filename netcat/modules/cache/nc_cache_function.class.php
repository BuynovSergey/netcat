<?php

/* $Id: nc_cache_function.class.php 6206 2012-02-10 10:12:34Z denis $ */
if (!class_exists("nc_System")) die("Unable to load file.");

/**
 * class nc_cache_function
 * @package nc_cache_function
 * @category nc_cache
 */
class nc_cache_function extends nc_cache {

    /**
     * Constructor method
     *
     * Instantiate in getObject() static method
     * Singleton pattern
     */
    protected function __construct() {

        parent::__construct();
        // set essence
        $this->essence = "function";
        // append path
        $this->cache_path.= $this->essence."/";
    }

    /**
     * Get or instance self object
     *
     * @return self object
     */
    public static function getObject() {
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

    /**
     * Add cache on disk method
     *
     * @param array instance data ($current_catalogue, $current_sub, $current_cc)
     * @param string $query_string parameter
     * @param string caching data
     * @return mixed bytes writed or false
     */
    public function add($instance_data, $query_string, $data) {
        // don't cache in admin mode
        if ($this->admin_mode || $this->inside_admin) return false;

        // if object set in unactive status
        if (!$this->settings["Status_".$this->essence]) return false;

        // validate
        if (
                !( is_array($instance_data) && !empty($instance_data) ) ||
                ( $query_string && !is_string($query_string) )
        ) {
            throw new Exception(NETCAT_MODULE_CACHE_CLASS_UNCORRECT_DATA_FORMAT);
        }

        // get parent function name
        $func_name = strtolower($this->_getFunction());

        $file_path = $this->cache_path.$func_name."/";

        if ($instance_data['Catalogue_ID']) {
            $catalogue = $instance_data['Catalogue_ID'];
            $file_path.= $catalogue."/";
        }

        if ($instance_data['Subdivision_ID']) {
            $sub = $instance_data['Subdivision_ID'];
            $file_path.= $sub."/";
        }

        if ($instance_data['Sub_Class_ID']) {
            $cc = $instance_data['Sub_Class_ID'];
            $file_path.= $cc."/";
        }

        // append audit info
        $this->auditUpdateInfo(array($catalogue, $sub, $cc), $query_string, "", "write", true);

        // cache info prefix for file
        $cache_prefix = "/* cache created ".time()." */\n";

        // check quota and clear old cache or return
        if (!$this->checkQuota(strlen($cache_prefix.$data))) return false;

        // cache file path
        $file_path.= md5($query_string).".html.php";

        // get current file size
        $exist_bytes = $this->io->get_size($file_path);

        $bytes_writed = $this->io->add($file_path, $cache_prefix.$data);

        // write data into the file
        if ($bytes_writed) {
            // update cache stat
            if ($bytes_writed - $exist_bytes)
                    $this->io->update_stat($this->essence, $bytes_writed - $exist_bytes);
            // append audit info
            $this->auditUpdateInfo(array($catalogue, $sub, $cc), $query_string, $template_str, "write");
        }

        // return result
        return $bytes_writed ? $bytes_writed : false;
    }

    /**
     * Read cached file from disk method
     *
     * @param array instance data ($current_catalogue, $current_sub, $current_cc)
     * @param string $query_string parameter
     * @param int cache lifetime
     *
     * @return mixed file data or -1 as false
     */
    public function read($instance_data, $query_string, $lifetime = 0) {
        // if object set in unactive status
        if (!$this->settings["Status_".$this->essence]) return -1;

        // validate
        if (
                !( is_array($instance_data) && !empty($instance_data) ) ||
                ( $query_string && !is_string($query_string) ) ||
                !is_numeric($lifetime)
        ) {
            throw new Exception(NETCAT_MODULE_CACHE_CLASS_UNCORRECT_DATA_FORMAT);
        }

        // get parent function name
        $func_name = strtolower($this->_getFunction());

        $file_path = $this->cache_path.$func_name."/";

        if ($instance_data['Catalogue_ID']) {
            $catalogue = $instance_data['Catalogue_ID'];
            $file_path.= $catalogue."/";
        }

        if ($instance_data['Subdivision_ID']) {
            $sub = $instance_data['Subdivision_ID'];
            $file_path.= $sub."/";
        }

        if ($instance_data['Sub_Class_ID']) {
            $cc = $instance_data['Sub_Class_ID'];
            $file_path.= $cc."/";
        }

        // append audit info
        $this->auditUpdateInfo(array($catalogue, $sub, $cc), $query_string, "", "read", true);

        // cache file path
        $file_path.= md5($query_string).".html.php";

        // check cached file
        $content = $this->io->read($file_path);

        // no cache data and cache info in the file
        if (!$content) return -1;

        // check cache expiration
        if ($this->checkExpire($content, $lifetime)) {
            // append audit info
            $this->auditUpdateInfo(array($catalogue, $sub, $cc), $query_string, "", "read");
            return $content;
        } else {
            // data to delete size
            $unlink_data_size = -$this->io->delete($file_path);
            // update main stat
            $this->io->update_stat($this->essence, -$unlink_data_size);
            return -1;
        }
    }

    /**
     * Function get backtrace function from called this object
     *
     * @return string function name or false
     */
    private function _getFunction() {
        // this function must be called only from browse functions
        $debug_backtrace = debug_backtrace();
        // get function from calling this method
        $deb_value = $debug_backtrace[2];
        // return from function name
        return $deb_value['function'] ? $deb_value['function'] : false;
    }

    /**
     * Get functions names from created dirs in cache directory
     *
     * @return array functions names or false
     */
    private function _getFunctionsFromDir() {
        // end slash
        $dir = rtrim($this->cache_path, "/")."/";
        // startup values
        $dirs_arr = array();
        // count all subdirs and files
        if (is_dir($dir) && $dh = opendir($dir)) {
            // read children
            while (( $file = readdir($dh) ) !== false) {
                if ($file == "." || $file == "..") continue;
                // append func dir to array
                if (is_dir($dir.$file)) $dirs_arr[] = $file;
            }
            closedir($dh);
        }
        // return dirs array
        return!empty($dirs_arr) ? $dirs_arr : false;
    }

    /**
     * Delete cache with setted efficiency (low efficiency == 1)
     *
     * @param int efficiency (1 - low, 2- middle, 3 - good)
     *
     * @return int total deleted bytes
     */
    protected function dropEfficiencyCache($efficiency) {
        // get cache with efficiency == 1 (low)
        $clear_data = $this->getEfficiencyCache($efficiency);
        // return if empty
        if (empty($clear_data)) return false;
        // total deleted bytes
        $total_bytes = 0;
        // try delete cache
        foreach ($clear_data AS $value) {
            switch (true) {
                // s_browse_cc cache
                case $value['Catalogue_ID'] && $value['Subdivision_ID'] && $value['Sub_Class_ID']:
                    $total_bytes+= $this->dropSubClassCache($value['Catalogue_ID'], $value['Subdivision_ID'], $value['Sub_Class_ID']);
                    break;
                // s_browse_sub cache
                case $value['Catalogue_ID'] && $value['Subdivision_ID']:
                    $total_bytes+= $this->dropSubdivisionCache($value['Catalogue_ID'], $value['Subdivision_ID']);
                    break;
                // s_browse_catalogue cache
                case $value['Catalogue_ID']:
                    $total_bytes+= $this->dropCatalogueCache($value['Catalogue_ID']);
                    break;
            }
        }
        // return total deleted bytes
        return $total_bytes;
    }

    /**
     * Delete catalogue cache dir from disk method
     *
     * @param mixed Catalogue_ID
     *
     * @return int total deleted bytes
     */
    public function dropCatalogueCache($catalogue) {
        // validate
        if (
                !( ( is_numeric($catalogue) && $catalogue > 0 ) || is_array($catalogue) )
        ) {
            throw new Exception(NETCAT_MODULE_CACHE_CLASS_UNCORRECT_DATA_FORMAT);
        }
        // functions dirs
        $funcs_dirs = $this->_getFunctionsFromDir();
        // check
        if (!$funcs_dirs) return false;
        // total deleted bytes
        $total_bytes = 0;
        foreach ($funcs_dirs AS $func_name) {
            foreach ((array) $catalogue AS $catalogue_id) {
                // cache file path
                $path = $this->cache_path.$func_name."/".$catalogue_id."/";
                // delete dir
                $total_bytes += $this->io->drop($path, true);
            }
        }
        // return total deleted bytes
        $this->io->update_stat($this->essence, -$total_bytes);
        return $total_bytes;
    }

    /**
     * Delete subdivision cache dir from disk method
     *
     * @param int Catalogue_ID
     * @param mixed Subdivision_ID
     *
     * @return int total deleted bytes
     */
    public function dropSubdivisionCache($catalogue, $sub) {
        // validate
        if (
                !(is_numeric($catalogue) && $catalogue > 0 ) ||
                !( ( is_numeric($sub) && $sub > 0 ) || is_array($sub) )
        ) {
            throw new Exception(NETCAT_MODULE_CACHE_CLASS_UNCORRECT_DATA_FORMAT);
        }
        // functions dirs
        $funcs_dirs = $this->_getFunctionsFromDir();
        // check
        if (!$funcs_dirs) return false;
        // total deleted bytes
        $total_bytes = 0;
        foreach ($funcs_dirs AS $func_name) {
            foreach ((array) $sub AS $sub_id) {
                // cache file path
                $path = $this->cache_path.$func_name."/".$catalogue."/".$sub_id."/";
                // delete dir
                $total_bytes += $this->io->drop($path, true);
            }
        }
        // return total deleted bytes
        $this->io->update_stat($this->essence, -$total_bytes);
        return $total_bytes;
    }

    /**
     * Delete cc cache dir from disk method
     *
     * @param int Catalogue_ID
     * @param int Subdivision_ID
     * @param mixed Sub_Class_ID
     *
     * @return int total deleted bytes
     */
    public function dropSubClassCache($catalogue, $sub, $cc) {
        // validate
        if (
                !(is_numeric($catalogue) && $catalogue > 0 ) ||
                !(is_numeric($sub) && $sub > 0 ) ||
                !( (is_numeric($cc) && $cc > 0 ) || is_array($cc) )
        ) {
            throw new Exception(NETCAT_MODULE_CACHE_CLASS_UNCORRECT_DATA_FORMAT);
        }
        // functions dirs
        $funcs_dirs = $this->_getFunctionsFromDir();
        // check
        if (!$funcs_dirs) return false;
        // total deleted bytes
        $total_bytes = 0;
        foreach ($funcs_dirs AS $func_name) {
            foreach ((array) $cc AS $cc_id) {
                // cache file path
                $path = $this->cache_path.$func_name."/".$catalogue."/".$sub."/".$cc_id."/";
                // delete dir
                $total_bytes += $this->io->drop($path, true);
            }
        }
        // return total deleted bytes
        $this->io->update_stat($this->essence, -$total_bytes);
        return $total_bytes;
    }

    /**
     * Delete component cache dir from disk method
     *
     * @param int Class_ID
     *
     * @return int total deleted bytes
     */
    public function dropClassCache($class) {
        // validate
        if (
                !( (is_numeric($class) && $class > 0 ) || is_array($class) )
        ) {
            return false;
        }

        $class = (array) $class;
        if (empty($class)) return 0;
        $class = array_map('intval', $class);
        // get all catalogues subdivisions
        $catalogue = $this->db->get_col("SELECT DISTINCT `Catalogue_ID` FROM `Sub_Class`
      WHERE `Class_ID` IN (".join(", ", $class).")");

        // return total deleted bytes
        return!empty($catalogue) ? $this->dropCatalogueCache($catalogue) : 0;
    }

    /**
     * Delete component template cache dir from disk method
     *
     * @param int Class_ID main class
     * @param int Class_ID class template id
     *
     * @return int total deleted bytes
     */
    public function dropClassTemplateCache($class, $template_class) {
        // validate
        if (
                !(is_numeric($class) && $class > 0 ) ||
                !( (is_numeric($template_class) && $template_class > 0 ) || is_array($template_class) )
        ) {
            return false;
        }

        $template_class = (array) $template_class;
        if (empty($template_class)) return 0;
        $template_class = array_map('intval', $template_class);
        // get all catalogues subdivisions
        $catalogue = $this->db->get_col("SELECT DISTINCT `Catalogue_ID` FROM `Sub_Class`
      WHERE `Class_ID` = '".$class."'
        AND `Class_Template_ID` IN (".join(", ", $template_class).")");

        // return total deleted bytes
        return!empty($catalogue) ? $this->dropCatalogueCache($catalogue) : 0;
    }

    /**
     * Delete message cache dir from disk method
     *
     * @param int Catalogue_ID
     * @param int Subdivision_ID
     * @param int Sub_Class_ID
     * @param int Class_ID
     * @param mixed Message_ID
     *
     * @return int total deleted bytes
     */
    public function dropMessageCache($catalogue, $sub, $cc, $class, $message) {
        // validate
        if (
                !( is_numeric($class) && $class > 0 )
        ) {
            return false;
        }
        // return total deleted bytes
        return $this->dropClassCache($class);
    }

    /**
     * Replace nocache marks with nocache blocks in component fields
     * Not used in this context!
     *
     * dummy function
     */
    protected function nocacheReplace($data) {
        // dummy body
    }

    /**
     * Set nocache marks in component fields
     * Not used in this context!
     *
     * dummy function
     */
    public function nocacheStore(&$data) {
        // dummy body
    }

}
?>