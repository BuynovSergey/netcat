<?php

/* $Id: nc_cache_browse.class.php 6206 2012-02-10 10:12:34Z denis $ */
if (!class_exists("nc_System")) die("Unable to load file.");

/**
 * class nc_cache_browse
 * @package nc_cache_browse
 * @category nc_cache
 */
class nc_cache_browse extends nc_cache {

    /**
     * Constructor method
     *
     * Instantiate in getObject() static method
     * Singleton pattern
     */
    protected function __construct() {
        parent::__construct();
        // set essence
        $this->essence = "browse";
        // append path
        $this->cache_path.= $this->essence."/";
        // bind actions
        if (isset($this->settings["Status_".$this->essence])) {
            if ($this->settings["Status_".$this->essence]) {
                $this->core->event->bind($this, array(nc_Event::AFTER_SITE_UPDATED => 'dropCatalogueCache'));
                $this->core->event->bind($this, array(nc_Event::AFTER_SITE_DELETED => 'dropCatalogueCache'));

                $this->core->event->bind($this, array(nc_Event::AFTER_SUBDIVISION_CREATED => 'dropCatalogueCache'));
                $this->core->event->bind($this, array(nc_Event::AFTER_SUBDIVISION_UPDATED => 'dropCatalogueCache'));
                $this->core->event->bind($this, array(nc_Event::AFTER_SUBDIVISION_DELETED => 'dropCatalogueCache'));

                $this->core->event->bind($this, array(nc_Event::AFTER_INFOBLOCK_CREATED => 'dropSubdivisionCache'));
                $this->core->event->bind($this, array(nc_Event::AFTER_INFOBLOCK_UPDATED => 'dropSubClassCache'));
                $this->core->event->bind($this, array(nc_Event::AFTER_INFOBLOCK_DELETED => 'dropSubClassCache'));

                $this->core->event->bind($this, array(nc_Event::AFTER_TEMPLATE_UPDATED => 'dropTemplateCache'));
                $this->core->event->bind($this, array(nc_Event::AFTER_TEMPLATE_DELETED => 'dropTemplateCache'));
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
     * @param array instance data ($current_catalogue, $current_sub, $current_cc)
     * @param array browse template
     * @param string $query_string parameter
     * @param string caching data
     * @param int parent sub for s_browse_sub()
     *
     * @return mixed bytes writed or false
     */
    public function add($instance_data, $template, $query_string, $data, $parent = 0) {
        // don't cache in admin mode
        if ($this->admin_mode || $this->inside_admin) return false;

        // if object set in unactive status
        if (!$this->settings["Status_".$this->essence]) return false;

        // validate
        if (
                !( is_array($instance_data) && !empty($instance_data) ) ||
                !( is_array($template) && !empty($template) ) ||
                ( $query_string && !is_string($query_string) ) ||
                !is_numeric($parent)
        ) {
            throw new Exception(NETCAT_MODULE_CACHE_CLASS_UNCORRECT_DATA_FORMAT);
        }

        $file_path = $this->cache_path;
        $catalogue = $sub = $cc = 0;

        // Catalogue cache dir
        if (isset($instance_data['Catalogue_ID'])) {
            $catalogue = $instance_data['Catalogue_ID'];
            $file_path.= $catalogue."/";
        }
        // Subdivision cache dir
        if (isset($instance_data['Subdivision_ID'])) {
            $sub = $instance_data['Subdivision_ID'];
            $file_path.= $sub."/";
        }
        // Sub_Class cache dir
        if (isset($instance_data['Sub_Class_ID'])) {
            $cc = $instance_data['Sub_Class_ID'];
            $file_path.= $cc."/";
        }

        // unique template string
        $template_str = md5(serialize($template));

        // append audit info
        $this->auditUpdateInfo(array($catalogue, $sub, $cc), $query_string, $template_str, "write", true);

        // cache info prefix for file
        $cache_prefix = "/* cache created ".time()." */\n";

        // check quota and clear old cache or return
        if (!$this->checkQuota(strlen($cache_prefix.$data))) return false;

        $file_path.= $template_str."/";


        if ($parent) $file_path.= $parent."/";

        // cache file path
        $file_path.= md5($query_string).".html.php";

        // get current file size
        $exist_bytes = $this->io->get_size($file_path);

        // write data into the file
        $bytes_writed = $this->io->add($file_path, $cache_prefix.$data);

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
     * @param array browse template
     * @param string $query_string parameter
     * @param int cache lifetime
     * @param int parent sub for s_browse_sub()
     *
     * @return mixed file data or -1 as false
     */
    public function read($instance_data, $template, $query_string, $lifetime = 0, $parent = 0) {
        // if object set in unactive status
        if (!$this->settings["Status_".$this->essence]) return -1;

        // validate
        if (
                !( is_array($instance_data) && !empty($instance_data) ) ||
                !( is_array($template) && !empty($template) ) ||
                ( $query_string && !is_string($query_string) ) ||
                !is_numeric($lifetime) ||
                !is_numeric($parent)
        ) {
            throw new Exception(NETCAT_MODULE_CACHE_CLASS_UNCORRECT_DATA_FORMAT);
        }

        // unique template string
        $template_str = md5(serialize($template));

        $file_path = $this->cache_path;
        $catalogue = $sub = $cc = 0;

        // Catalogue cache dir
        if (isset($instance_data['Catalogue_ID'])) {
            $catalogue = $instance_data['Catalogue_ID'];
            $file_path.= $catalogue."/";
        }
        // Subdivision cache dir
        if (isset($instance_data['Subdivision_ID'])) {
            $sub = $instance_data['Subdivision_ID'];
            $file_path.= $sub."/";
        }
        // Sub_Class cache dir
        if (isset($instance_data['Sub_Class_ID'])) {
            $cc = $instance_data['Sub_Class_ID'];
            $file_path.= $cc."/";
        }

        // append audit info
        $this->auditUpdateInfo(array($catalogue, $sub, $cc), $query_string, $template_str, "read", true);

        // cache file path
        $file_path.= $template_str."/";

        // append parent instance to path
        if ($parent) $file_path.= $parent."/";

        // cache file path
        $file_path.= md5($query_string).".html.php";

        $content = $this->io->read($file_path);

        // no cache data and cache info in the file
        if (!$content) return -1;

        // check cache expiration
        if ($this->checkExpire($content, $lifetime)) {
            // append audit info
            $this->auditUpdateInfo(array($catalogue, $sub, $cc), $query_string, $template_str, "read");
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
     * Delete cache with setted efficiency (low efficiency == 1)
     *
     * @param int efficiency (1 - low, 2- middle, 3 - good)
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
     * @return int total deleted bytes
     */
    public function dropCatalogueCache($catalogue) {
        // validate
        if (
                !( ( is_numeric($catalogue) && $catalogue > 0 ) || is_array($catalogue) )
        ) {
            throw new Exception(NETCAT_MODULE_CACHE_CLASS_UNCORRECT_DATA_FORMAT);
        }
        // total deleted bytes
        $total_bytes = 0;
        foreach ((array) $catalogue AS $catalogue_id) {
            // cache file path
            $path = $this->cache_path.$catalogue_id."/";
            // delete dir
            $total_bytes += $this->io->drop($path, true);
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
        // total deleted bytes
        $total_bytes = 0;
        foreach ((array) $sub AS $sub_id) {
            // cache file path
            $path = $this->cache_path.$catalogue."/".$sub_id."/";
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
     * @param int Catalogue_ID
     * @param int Subdivision_ID
     * @param mixed Sub_Class_ID
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
        // total deleted bytes
        $total_bytes = 0;
        foreach ((array) $cc AS $cc_id) {
            // cache file path
            $path = $this->cache_path.$catalogue."/".$sub."/".$cc_id."/";
            // delete dir
            $total_bytes += $this->io->drop($path, true);
        }
        // return total deleted bytes
        $this->io->update_stat($this->essence, -$total_bytes);
        return $total_bytes;
    }

    /**
     * Delete template cache dir from disk method
     *
     * @param mixed Template_ID
     * @return int total deleted bytes
     */
    public function dropTemplateCache($template) {
        // validate
        if (
                !( ( is_numeric($template) && $template > 0 ) || is_array($template) )
        ) {
            throw new Exception(NETCAT_MODULE_CACHE_CLASS_UNCORRECT_DATA_FORMAT);
        }

        $template = (array) $template;
        if (empty($template)) return 0;
        $template = array_map('intval', $template);
        // total deleted bytes
        $total_bytes = 0;

        // get all catalogues
        $catalogue = $this->db->get_col("SELECT `Catalogue_ID` FROM `Catalogue`
      WHERE `Template_ID` IN (".join(", ", $template).")");

        if (!empty($catalogue))
                $total_bytes+= $this->dropCatalogueCache($catalogue);

        // get all subdivisions
        $sub = $this->db->get_results("SELECT `Catalogue_ID`, `Subdivision_ID` FROM `Subdivision`
      WHERE `Template_ID` IN (".join(", ", $template).")".(!empty($catalogue) ? " AND `Catalogue_ID` NOT IN (".join(", ", $catalogue).")" : "" ), ARRAY_A);

        if (!empty($sub)) {
            foreach ($sub AS $value) {
                // delete subdivision cache
                $total_bytes+= $this->dropSubdivisionCache($value['Catalogue_ID'], $value['Subdivision_ID']);
            }
        }

        // return total deleted bytes
        return $total_bytes;
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
