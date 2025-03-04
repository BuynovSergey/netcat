<?php

/* $Id: nc_cache_full.class.php 6206 2012-02-10 10:12:34Z denis $ */
if (!class_exists("nc_System")) die("Unable to load file.");

/**
 * class nc_cache_full
 * @package nc_cache_full
 * @category nc_cache
 */
class nc_cache_full extends nc_cache {

    /**
     * Constructor method
     *
     * Instantiate in getObject() static method
     * Singleton pattern
     */
    protected function __construct() {
        parent::__construct();
        // set essence
        $this->essence = "full";
        // append path
        $this->cache_path.= $this->essence."/";
        // bind actions
        if (isset($this->settings["Status_".$this->essence])) {
            if ($this->settings["Status_".$this->essence]) {
                $this->core->event->bind($this, array(nc_Event::AFTER_SITE_UPDATED => 'dropCatalogueCache'));
                $this->core->event->bind($this, array(nc_Event::AFTER_SITE_DELETED => 'dropCatalogueCache'));

                $this->core->event->bind($this, array(nc_Event::AFTER_SUBDIVISION_UPDATED => 'dropSubdivisionCache'));
                $this->core->event->bind($this, array(nc_Event::AFTER_SUBDIVISION_DELETED => 'dropSubdivisionCache'));

                $this->core->event->bind($this, array(nc_Event::AFTER_INFOBLOCK_UPDATED => 'dropSubClassCache'));
                $this->core->event->bind($this, array(nc_Event::AFTER_INFOBLOCK_DELETED => 'dropSubClassCache'));

                $this->core->event->bind($this, array(nc_Event::AFTER_COMPONENT_UPDATED => 'dropClassCache'));
                $this->core->event->bind($this, array(nc_Event::AFTER_COMPONENT_DELETED => 'dropClassCache'));

                $this->core->event->bind($this, array(nc_Event::AFTER_COMPONENT_TEMPLATE_UPDATED => 'dropClassTemplateCache'));
                $this->core->event->bind($this, array(nc_Event::AFTER_COMPONENT_TEMPLATE_DELETED => 'dropClassTemplateCache'));

                $this->core->event->bind($this, array(nc_Event::AFTER_OBJECT_UPDATED => 'dropMessageCache'));
                $this->core->event->bind($this, array(nc_Event::AFTER_OBJECT_DELETED => 'dropMessageCache'));
                $this->core->event->bind($this, array(nc_Event::AFTER_OBJECT_ENABLED => 'dropMessageCache'));
                $this->core->event->bind($this, array(nc_Event::AFTER_OBJECT_DISABLED => 'dropMessageCache'));

                $this->core->event->bind($this, array(nc_Event::AFTER_COMMENT_CREATED => 'dropMessageCache'));
                $this->core->event->bind($this, array(nc_Event::AFTER_COMMENT_UPDATED => 'dropMessageCache'));
                $this->core->event->bind($this, array(nc_Event::AFTER_COMMENT_DELETED => 'dropMessageCache'));
            }
        }
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
     * @param int Class_ID
     * @param int Message_ID
     * @param string $REQUEST_URI parameter
     * @param string caching data
     *
     * @return mixed bytes writed or false
     */
    public function add($classID, $message, $query_string, $data, $cache_vars) {
        // don't cache in admin mode
        if ($this->admin_mode || $this->inside_admin) return false;
        // if object set in unactive status
        if (!$this->settings["Status_".$this->essence]) return false;

        // validate
        if (
                !(is_numeric($classID) && $classID > 0) ||
                !(is_numeric($message) && $message > 0) ||
                ( $query_string && !is_string($query_string) )
        ) {
            throw new Exception(NETCAT_MODULE_CACHE_CLASS_UNCORRECT_DATA_FORMAT);
        }

        // append audit info
        $this->auditUpdateInfo(array(0, 0, 0, $classID, $message), $query_string, "", "write", true);

        // cache info prefix for file
        $cache_prefix = "/* cache created ".time()." */\n";

        // check quota and clear old cache or return
        $data_size = strlen($cache_prefix.$data) + ( is_array($cache_vars) ? serialize($cache_vars) : 0 );
        if (!$this->checkQuota($data_size)) return false;

        $bytes_writed = 0;


        $file_path = $this->cache_path.$classID."/".$message."/".md5($query_string);
        // variables file path
        $file_vars_path = $file_path.".vars.php";

        // set file extension and update data if need it
        if (($new_data = $this->nocacheReplace($data))) {
            $data = $new_data;
            $file_ext = ".php";
        } else {
            $file_ext = ".html.php";
        }

        if (!empty($cache_vars)) {
            $bytes_writed += $this->io->add($file_vars_path, serialize($cache_vars));
        }

        $file_full_path = $file_path.$file_ext;
        $exist_bytes += $this->io->get_size($file_full_path);

        // write cache
        $bytes_writed+= $this->io->add($file_full_path, $cache_prefix.$data);

        if ($bytes_writed) {
            // update cache stat
            if ($bytes_writed - $exist_bytes)
                    $this->io->update_stat($this->essence, $bytes_writed - $exist_bytes);
            // append audit info
            $this->auditUpdateInfo(array(0, 0, 0, $classID, $message), $query_string, "", "write");
        }
        // return result
        return $bytes_writed ? $bytes_writed : false;
    }

    /**
     * Read cached file from disk method
     *
     * @param int Class_ID
     * @param int Message_ID
     * @param string $query_string parameter
     * @param int cache lifetime
     *
     * @return mixed file data or -1 as false
     */
    public function read($classID, $message, $query_string, $lifetime = 0) {
        // if object set in unactive status
        if (!$this->settings["Status_".$this->essence]) return -1;

        // validate
        if (
                !(is_numeric($classID) && $classID > 0) ||
                !(is_numeric($message) && $message > 0) ||
                ( $query_string && !is_string($query_string) ) ||
                !is_numeric($lifetime)
        ) {
            throw new Exception(NETCAT_MODULE_CACHE_CLASS_UNCORRECT_DATA_FORMAT);
        }
        // append audit info
        $this->auditUpdateInfo(array(0, 0, 0, $classID, $message), $query_string, "", "read", true);

        // startup values
        $result = false;
        $cache_vars = array();

        // cache file path
        $file_path = $this->cache_path.$classID."/".$message."/".md5($query_string);
        // check cached
        switch (true) {
            //case file_exists($file_path.".html.php"):
            case ($content = $this->io->read($file_path.".html.php")):
                $file_exist_path = $file_path.".html.php";
                $content_eval = false;
                break;
            case ($content = $this->io->read($file_path.".php")):
                $file_exist_path = $file_path.".php";
                // read variables file
                $content_eval = true;
                break;
        }

        $cache_vars = $this->io->read($file_path.".vars.php");
        if ($cache_vars) $cache_vars = unserialize($cache_vars);

        // no cache data and cache info in the file
        if (!$content) return -1;
        // check cache expiration
        if ($this->checkExpire($content, $lifetime)) {
            $result = array($content, $content_eval, $cache_vars);
            // append audit info
            $this->auditUpdateInfo(array(0, 0, 0, $classID, $message), $query_string, "", "read");
        } else {
            // delete file and update stat
            $unlink_data_size = -$this->io->delete($file_exist_path);
            $vars_bytes_droped = $this->io->delete($file_path.".vars.php");
            $this->io->update_stat($this->essence, $unlink_data_size - $vars_bytes_droped);
            return -1;
        }
        // return false
        return $result ? $result : -1;
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
            $total_bytes+= $this->dropMessageCache(0, 0, 0, $value['Class_ID'], $value['Message_ID']);
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

        $catalogue = (array) $catalogue;
        if (empty($catalogue)) return 0;
        $catalogue = array_map('intval', $catalogue);

        // get all catalogues classes
        $class = $this->db->get_col("SELECT DISTINCT `Class_ID` FROM `Sub_Class`
      WHERE `Catalogue_ID` IN (".join(", ", $catalogue).")");

        // return total deleted bytes
        return!empty($class) ? $this->dropClassCache($class) : 0;
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
                !( is_numeric($catalogue) && $catalogue > 0 ) ||
                !( ( is_numeric($sub) && $sub > 0 ) || is_array($sub) )
        ) {
            throw new Exception(NETCAT_MODULE_CACHE_CLASS_UNCORRECT_DATA_FORMAT);
        }

        $sub = (array) $sub;
        if (empty($sub)) return 0;
        $sub = array_map('intval', $sub);
        // get all catalogues classes
        $class = $this->db->get_col("SELECT DISTINCT `Class_ID` FROM `Sub_Class`
      WHERE `Catalogue_ID` = '".$catalogue."'
      AND `Subdivision_ID` IN (".join(", ", $sub).")");

        // return total deleted bytes
        return!empty($class) ? $this->dropClassCache($class) : 0;
    }

    /**
     * Delete subclass cache dir from disk method
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
                !( is_numeric($catalogue) && $catalogue > 0 ) ||
                !( is_numeric($sub) && $sub > 0 ) ||
                !( ( is_numeric($cc) && $cc > 0 ) || is_array($cc) )
        ) {
            throw new Exception(NETCAT_MODULE_CACHE_CLASS_UNCORRECT_DATA_FORMAT);
        }

        if (!is_array($cc)) $cc = array($cc);
        $cc = array_map('intval', $cc);

        // get all catalogues classes
        $class = $this->db->get_col("SELECT DISTINCT `Class_ID` FROM `Sub_Class`
      WHERE `Catalogue_ID` = '".$catalogue."'
      AND `Subdivision_ID` = '".$sub."'
      AND `Sub_Class_ID` IN (".join(", ", (array) $cc).")");

        // return total deleted bytes
        return!empty($class) ? $this->dropClassCache($class) : 0;
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
        // total deleted bytes
        $total_bytes = 0;
        foreach ((array) $class AS $classID) {
            // cache file path
            $path = $this->cache_path.$classID."/";
            // delete dir
            $total_bytes+= $this->io->drop($path, true);
        }
        // return total deleted bytes
        $this->io->update_stat($this->essence, -$total_bytes);
        return $total_bytes;
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

        // return total deleted bytes
        return $this->dropClassCache($class);
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
                !( is_numeric($class) && $class > 0 ) ||
                !( (is_numeric($message) && $message > 0 ) || is_array($message) )
        ) {
            return false;
        }
        // total deleted bytes
        $total_bytes = 0;
        foreach ((array) $message AS $mess_id) {
            // cache file path
            $path = $this->cache_path.$class."/".$mess_id."/";
            // delete dir
            $total_bytes+= $this->io->drop($path, true);
        }
        // return total deleted bytes
        $this->io->update_stat($this->essence, -$total_bytes);
        return $total_bytes;
    }

    /**
     * Set nocache marks in component fields
     *
     * @param array component data
     *
     * @param int counted nocache block
     */
    public function nocacheStore(&$data) {
        // parsed values with need sequence
        $this->nocacheStoreKeys = array("RecordTemplateFull");
        // call parent
        return parent::nocacheStore($data);
    }

}
?>
