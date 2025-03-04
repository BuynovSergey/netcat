<?php

/* $Id: nc_cache_list.class.php 6206 2012-02-10 10:12:34Z denis $ */
if (!class_exists("nc_System")) die("Unable to load file.");

/**
 * class nc_cache_list
 * @package nc_cache_list
 * @category nc_cache
 */
class nc_cache_list extends nc_cache {

    /**
     * Constructor method
     *
     * Instantiate in getObject() static method
     * Singleton pattern
     */
    protected function __construct() {
        parent::__construct();
        // set essence
        $this->essence = "list";
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

                $this->core->event->bind($this, array(nc_Event::AFTER_OBJECT_CREATED => 'dropMessageCache'));
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
     * Add cache
     *
     * @param int|string $sub
     * @param int|string $cc
     * @param string $query_string parameter
     * @param string $data
     * @param array $cache_vars
     * @return int|bool bytes written or false
     * @throws Exception
     */
    public function add($sub, $cc, $query_string, $data, $cache_vars) {
        // don't cache in admin mode
        if ($this->admin_mode || $this->inside_admin) {
            return false;
        }

        // if object set in unactive status
        if (!$this->settings['Status_' . $this->essence]) {
            return false;
        }

        if (
            !(is_numeric($sub) && $sub > 0) ||
            !(is_numeric($cc) && $cc > 0) ||
            ($query_string && !is_string($query_string))
        ) {
            throw new Exception(NETCAT_MODULE_CACHE_CLASS_UNCORRECT_DATA_FORMAT);
        }

        // append audit info
        $this->auditUpdateInfo(array(0, $sub, $cc), $query_string, '', 'write', true);

        // cache info prefix for file
        $cache_prefix = '/* cache created ' . time() . " */\n";

        $serialized_cache_vars = serialize($cache_vars);

        // check quota and clear old cache or return
        $data_size = strlen($cache_prefix . $data) + (is_array($cache_vars) ? strlen($serialized_cache_vars) : 0);
        if (!$this->checkQuota($data_size)) {
            return false;
        }

        $file_path = $this->cache_path . $sub . '/' . $cc . '/' . md5($query_string);
        $file_vars_path = $file_path . '.vars.php';

        $bytes_written = 0;
        $exist_bytes = 0;

        // get current file size

        $exist_bytes += $this->io->get_size($file_vars_path);

        // set file extension and update data if need it
        if ($new_data = $this->nocacheReplace($data)) {
            $data = $new_data;
            $file_ext = '.php';
            // append variables file
            if (!empty($cache_vars)) {
                $bytes_written += $this->io->add($file_vars_path, $serialized_cache_vars);
            }
        } else {
            $file_ext = '.html.php';
        }

        // file with extension
        $file_full_path = $file_path . $file_ext;
        $exist_bytes += $this->io->get_size($file_full_path);

        // write cache
        $bytes_written += $this->io->add($file_full_path, $cache_prefix . $data);

        if ($bytes_written) {
            // update cache stat
            if ($bytes_written - $exist_bytes) {
                $this->io->update_stat($this->essence, $bytes_written - $exist_bytes);
            }
            // append audit info
            $this->auditUpdateInfo(array(0, $sub, $cc), $query_string, '', 'write');
        }

        return $bytes_written ?: false;
    }

    /**
     * Read cached file from disk method
     *
     * @param int Subdivision_ID
     * @param int Sub_Class_ID
     * @param string $query_string parameter
     * @param int cache lifetime
     *
     * @return mixed file data or -1 as false
     */
    public function read($sub, $cc, $query_string, $lifetime = 0) {
        // if object set in unactive status
        if (!$this->settings["Status_".$this->essence]) return -1;

        // validate
        if (
                !(is_numeric($sub) && $sub > 0) ||
                !(is_numeric($cc) && $cc > 0) ||
                ( $query_string && !is_string($query_string) ) ||
                !is_numeric($lifetime)
        ) {
            throw new Exception(NETCAT_MODULE_CACHE_CLASS_UNCORRECT_DATA_FORMAT);
        }

        // append audit info
        $this->auditUpdateInfo(array(0, $sub, $cc), $query_string, "", "read", true);

        // startup values
        $result = false;
        $cache_vars = array();

        // cache file path
        $file_path = $this->cache_path.$sub."/".$cc."/".md5($query_string);
        // check cached file

        switch (true) {
            //case file_exists($file_path.".html.php"):
            case ($content = $this->io->read($file_path.".html.php")):
                $file_exist_path = $file_path.".html.php";
                $content_eval = false;
                break;
            case ($content = $this->io->read($file_path.".php")):
                $file_exist_path = $file_path.".php";
                // read variables file
                $cache_vars = $this->io->read($file_path.".vars.php");
                if ($cache_vars) $cache_vars = unserialize($cache_vars);
                $content_eval = true;
                break;
        }

        // no cache data and cache info in the file
        if (!$content) return -1;
        // check cache expiration
        if ($this->checkExpire($content, $lifetime)) {
            $result = array($content, $content_eval, $cache_vars);
            // append audit info
            $this->auditUpdateInfo(array(0, $sub, $cc), $query_string, "", "read");
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
     * Split of the cached data to the prefix, suffix and objects
     *
     * @param string cached data
     *
     * @return array ("prefix" => string, "objects" => array, "suffix" => string)
     */
    public function getCachedBlocks($data) {
        // change descriptor
        $changed = false;
        $matches = array();
        $result = array();

        // prefix and suffix regex value
        $regex = "|^(.*?)<\!-- nocache_object_\d*? -->.*<\!-- /nocache_object_\d*? -->(.*?)$|is";
        // prefix and suffix
        if (nc_preg_match($regex, $data, $ps_matches)) {
            $result['prefix'] = $ps_matches[1];
            $result['suffix'] = $ps_matches[2];
        }

        // regex value
        $regex = "|(<\!-- nocache_object_(\d*?) -->(.*?)<\!-- /nocache_object_(\d*?) -->)(.*)|is";

        // check nocache_object_XX existance
        nc_preg_match($regex, $data, $matches);

        // walk
        while (!empty($matches)) {
            // replace string
            $replace = $this->cache_block[$matches[2]]."\$5";
            $result['objects'][$matches[2]] = $matches[3];
            $data = nc_preg_replace($regex, $replace, $data);
            $changed = true;
            // check other nocache existance
            nc_preg_match($regex, $data, $matches);
        }

        return $result;
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
            $total_bytes+= $this->dropSubClassCache(0, $value['Subdivision_ID'], $value['Sub_Class_ID']);
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
        // get all catalogues subdivisions
        $sub = $this->db->get_col("SELECT DISTINCT `Subdivision_ID` FROM `Subdivision`
      WHERE `Catalogue_ID` IN (".join(", ", $catalogue).")");

        // return total deleted bytes
        return!empty($sub) ? $this->dropSubdivisionCache(0, $sub) : 0;
    }

    /**
     * Delete subdivision cache dir from disk method
     *
     * @param dummy
     * @param int Subdivision_ID
     *
     * @return int total deleted bytes
     */
    public function dropSubdivisionCache($catalogue, $sub) {
        // validate
        if (
                !( ( is_numeric($sub) && $sub > 0 ) || is_array($sub) )
        ) {
            throw new Exception(NETCAT_MODULE_CACHE_CLASS_UNCORRECT_DATA_FORMAT);
        }
        // total deleted bytes
        $total_bytes = 0;
        foreach ((array) $sub AS $sub_id) {
            // cache file path
            $path = $this->cache_path.$sub_id."/";
            // delete dir
            $total_bytes += $this->io->drop($path, true);
        }
        // return total deleted bytes
        $this->io->update_stat($this->essence, -$total_bytes);
        return $total_bytes;
    }

    /**
     * Delete cc cache dir from disk method
     *
     * @param dummy
     * @param int Subdivision_ID
     * @param mixed Sub_Class_ID
     *
     * @return int total deleted bytes
     */
    public function dropSubClassCache($catalogue, $sub, $cc) {
        // validate
        if (
                !(is_numeric($sub) && $sub > 0 ) ||
                !( (is_numeric($cc) && $cc > 0 ) || is_array($cc) )
        ) {
            return false;
        }
        // total deleted bytes
        $total_bytes = 0;
        foreach ((array) $cc AS $cc_id) {
            // cache file path
            $path = $this->cache_path.$sub."/".$cc_id."/";
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
        $data = $this->db->get_results("SELECT DISTINCT `Subdivision_ID`, `Sub_Class_ID` FROM `Sub_Class`
      WHERE `Class_ID` IN (".join(", ", $class).")", ARRAY_A);

        $total_deleted = 0;
        if (!empty($data)) {
            foreach ($data as $value) {
                $total_deleted += $this->dropSubClassCache(0, $value['Subdivision_ID'], $value['Sub_Class_ID']);
            }
        }

        // return total deleted bytes
        return $total_deleted;
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
        $data = $this->db->get_results("SELECT DISTINCT `Subdivision_ID`, `Sub_Class_ID` FROM `Sub_Class`
      WHERE `Class_ID` = '".$class."'
        AND `Class_Template_ID` IN (".join(", ", $template_class).")", ARRAY_A);

        $total_deleted = 0;
        if (!empty($data)) {
            foreach ($data as $value) {
                $total_deleted += $this->dropSubClassCache(0, $value['Subdivision_ID'], $value['Sub_Class_ID']);
            }
        }

        $total_deleted += $this->dropClassCache($class);
        // return total deleted bytes
        return $total_deleted;
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
     * Set nocache marks in component fields
     *
     * @param array component data
     *
     * @param int counted nocache block
     */
    public function nocacheStore(&$data) {
        // parsed values with need sequence
        $this->nocacheStoreKeys = array("FormPrefix", "RecordTemplate", "FormSuffix");
        // call parent
        return parent::nocacheStore($data);
    }

}
?>
