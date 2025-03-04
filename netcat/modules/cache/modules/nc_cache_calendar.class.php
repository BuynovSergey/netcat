<?php

/* $Id: nc_cache_calendar.class.php 6206 2012-02-10 10:12:34Z denis $ */
if (!class_exists("nc_System")) die("Unable to load file.");

/**
 * class nc_cache_calendar
 * @package nc_cache_calendar
 * @category nc_cache
 */
class nc_cache_calendar extends nc_cache {

    /**
     * Constructor method
     *
     * Instantiate in getObject() static method
     * Singleton pattern
     */
    protected function __construct() {
        parent::__construct();
        // set essence
        $this->essence = "calendar";
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

                $this->core->event->bind($this, array(nc_Event::AFTER_OBJECT_CREATED => 'dropMessageCache'));
                $this->core->event->bind($this, array(nc_Event::AFTER_OBJECT_UPDATED => 'dropMessageCache'));
                $this->core->event->bind($this, array(nc_Event::AFTER_OBJECT_DELETED => 'dropMessageCache'));
                $this->core->event->bind($this, array(nc_Event::AFTER_OBJECT_ENABLED => 'dropMessageCache'));
                $this->core->event->bind($this, array(nc_Event::AFTER_OBJECT_DISABLED => 'dropMessageCache'));
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
     * @param int Sub_Class_ID
     * @param int theme ID from `Calendar_Settings`
     * @param string field (Date, Created...)
     * @param string $query_string parameter (date)
     * @param string caching data
     *
     * @return mixed bytes writed or false
     */
    public function add($cc, $themeID, $field, $query_string, $data) {
        // don't cache in admin mode
        if ($this->admin_mode || $this->inside_admin) return false;
        // if object set in unactive status
        if (!$this->settings["Status_".$this->essence]) return false;

        // validate
        if (
                !( is_numeric($cc) && $cc > 0 ) ||
                !( is_numeric($themeID) && $themeID > 0 ) ||
                ( $field && !is_string($field) ) ||
                !( $query_string && preg_match("/^([0-9]{1,4}-?){1,3}$/", $query_string) )
        ) {
            throw new Exception(NETCAT_MODULE_CACHE_CLASS_UNCORRECT_DATA_FORMAT);
        }

        // unique field string
        $field_str = md5(serialize($field));

        // append audit info
        $this->auditUpdateInfo(array(0, 0, $cc), $query_string, $field_str, "write", true);

        // cache info prefix for file
        $cache_prefix = "/* cache created ".time()." */\n";

        // check quota and clear old cache or return
        if (!$this->checkQuota(strlen($cache_prefix.$data))) return false;

        // cache file path
        $file_path = $this->cache_path.$cc."/".$themeID."/".$field_str."/";
        $file_path.= md5($query_string).".html.php";

        // get current file size
        $exist_bytes = $this->io->get_size($file_path);

        // write data into the file
        $bytes_writed = $this->io->add($file_path, $cache_prefix.$data);

        // write data into the file
        if ($bytes_writed) {
            // update cache stat
            if ($bytes_writed - $exist_bytes)
                    $this->io->update_stat($this->essence, $bytes_writed - $exist_bytes);
            // append audit info
            $this->auditUpdateInfo(array(0, 0, $cc), $query_string, $field_str, "write");
        }

        // return result
        return $bytes_writed ? $bytes_writed : false;
    }

    /**
     * Read cached file from disk method
     *
     * @param int Sub_Class_ID
     * @param int theme ID from `Calendar_Settings`
     * @param string field (Date, Created...)
     * @param string $query_string parameter (date)
     * @param int cache lifetime
     *
     * @return mixed file data or -1 as false
     */
    public function read($cc, $themeID, $field, $query_string, $lifetime = 0) {
        // if object set in unactive status
        if (!$this->settings["Status_".$this->essence]) return -1;

        // validate
        if (
                !( is_numeric($cc) && $cc > 0 ) ||
                !( is_numeric($themeID) && $themeID > 0 ) ||
                ( $field && !is_string($field) ) ||
                !( $query_string && preg_match("/^([0-9]{1,4}-?){1,3}$/", $query_string) ) ||
                !is_numeric($lifetime)
        ) {
            throw new Exception(NETCAT_MODULE_CACHE_CLASS_UNCORRECT_DATA_FORMAT);
        }

        // unique field string
        $field_str = md5(serialize($field));

        // append audit info
        $this->auditUpdateInfo(array(0, 0, $cc), $query_string, $field_str, "read", true);

        // cache file path
        $file_path = $this->cache_path.$cc."/".$themeID."/".$field_str."/".md5($query_string).".html.php";

        // check cached file
        $content = $this->io->read($file_path);

        // no cache data and cache info in the file
        if (!$content) return -1;

        // check cache expiration
        if ($this->checkExpire($content, $lifetime)) {
            // append audit info
            $this->auditUpdateInfo(array(0, 0, $cc), $query_string, $field_str, "read");
            return $content;
        } else {
            $unlink_data_size = -$this->io->delete($file_path);
            // update main stat
            $this->io->update_stat($this->essence, $unlink_data_size - $vars_bytes_droped);
            return -1;
        }
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
            $total_bytes+= $this->dropSubClassCache($value['Sub_Class_ID']);
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
            return false;
        }

        if (!is_array($catalogue)) $catalogue = array($catalogue);
        $catalogue = array_map('intval', $catalogue);

        // get all subclasses
        $cc = $this->db->get_col("SELECT DISTINCT `Sub_Class_ID` FROM `Sub_Class`
      WHERE `Catalogue_ID` IN (".join(", ", $catalogue).")");

        // return total deleted bytes
        return!empty($cc) ? $this->dropSubClassCache(0, 0, $cc) : 0;
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
                !( (is_numeric($sub) && $sub > 0 ) || is_array($sub) )
        ) {
            return false;
        }

        $sub = (array) $sub;
        if (empty($sub)) return 0;
        $sub = array_map('intval', $sub);

        // get all subclasses
        $cc = $this->db->get_col("SELECT DISTINCT `Sub_Class_ID` FROM `Sub_Class`
      WHERE `Catalogue_ID` = '".intval($catalogue)."'
      AND `Subdivision_ID` IN (".join(", ", $sub).")");

        // return total deleted bytes
        return!empty($cc) ? $this->dropSubClassCache(0, 0, $cc) : 0;
    }

    /**
     * Delete component cache dir from disk method
     *
     * @param dummy
     * @param dummy
     * @param mixed Sub_Class_ID
     *
     * @return int total deleted bytes
     */
    public function dropSubClassCache($catalogue, $sub, $cc) {
        // validate
        if (
                !( (is_numeric($cc) && $cc > 0 ) || is_array($cc) )
        ) {
            return false;
        }
        // total deleted bytes
        $total_bytes = 0;
        foreach ((array) $cc AS $cc_id) {
            // cache file path
            $path = $this->cache_path.$cc_id."/";
            // delete dir
            $total_bytes += $this->io->drop($path, true);
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
        $subclasses = $this->db->get_col("SELECT DISTINCT `Sub_Class_ID` FROM `Sub_Class`
      WHERE `Class_ID` IN (".join(", ", $class).")");

        // return total deleted bytes
        return!empty($subclasses) ? $this->dropSubClassCache(0, 0, $subclasses) : 0;
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
     * Delete message cache dir from disk method
     *
     * @param int Sub_Class_ID
     * @param mixed Theme_ID
     *
     * @return int total deleted bytes
     */
    public function dropThemeCache($cc, $theme) {
        // validate
        if (
                !( (is_numeric($cc) && $cc > 0 ) ) ||
                !( (is_numeric($theme) && $theme > 0) || is_array($theme) )
        ) {
            return false;
        }
        // total deleted bytes
        $total_bytes = 0;
        foreach ((array) $theme AS $themeID) {
            // cache file path
            $path = $this->cache_path.$cc."/".$themeID."/";
            // delete dir
            $total_bytes += $this->io->drop($path, true);
        }
        // return total deleted bytes
        $this->io->update_stat($this->essence, -$total_bytes);
        return $total_bytes;
    }

    /**
     * Replace nocache marks with nocache blocks in component fields
     * Not used inthis context!
     *
     * dummy function
     */
    protected function nocacheReplace($data) {
        // dummy body
    }

    /**
     * Set nocache marks in component fields
     * Not used inthis context!
     *
     * dummy function
     */
    public function nocacheStore(&$data) {
        // dummy body
    }

}
?>
