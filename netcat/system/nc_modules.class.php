<?php

class nc_Modules extends nc_System {

    //--------------------------------------------------------------------------

    protected $db;

    /** @var array */
    protected $current_site_all_modules_settings;
    protected $current_site_enabled_modules_settings;
    protected $module_vars;

    //--------------------------------------------------------------------------

    public function __construct() {
        // load parent constructor
        parent::__construct();

        // system superior object
        $nc_core = nc_Core::get_object();
        // system db object
        if (is_object($nc_core->db)) $this->db = $nc_core->db;
    }

    //--------------------------------------------------------------------------

    public function __get($keyword)
    {
        static $modules = array();

        if (isset($modules[$keyword])) {
            return $modules[$keyword];
        }

        if ($this->get_by_keyword($keyword)) {
            $class_name = 'nc_' . $keyword;

            if (class_exists($class_name, false) && method_exists($class_name, 'get_instance')) {
                $modules[$keyword] = call_user_func(array($class_name, 'get_instance'));

                return $modules[$keyword];
            }
        }

        return false;
    }

    //--------------------------------------------------------------------------

    public function get_data($reload = false, $ignore_check = false) {
        if ($this->current_site_all_modules_settings === null || $reload) {
            $settings = (array)$this->db->get_results("SELECT * FROM `Module`", ARRAY_A, 'Module_ID');

            // Преобразуем строку 'Parameters' для каждого модуля в массив (формируем $MODULE_VARS):
            foreach ($settings as $row) {
                parse_str(str_replace(array("\n", "\r\n"), '&', $row['Parameters']), $module_vars);
                if (!empty($module_vars)) {
                    foreach ($module_vars as $key => $value) {
                        $this->module_vars[$row['Keyword']][$key] = trim($value);
                    }
                }
            }

            // Загружаем настройки модулей для текущего сайта:
            $site_id = nc_core::get_object()->catalogue->id();
            if ($site_id) {
                $site_settings = (array)$this->db->get_results("SELECT * FROM `Module_Catalog` WHERE `Catalogue_ID` = $site_id", ARRAY_A, 'Module_ID');
                foreach ($site_settings as $module_id => $row) {
                    if (isset($settings[$module_id])) {
                        $settings[$module_id]['Checked'] = $row['Checked'];
                        $settings[$module_id]['Inside_Admin'] = $row['Inside_Admin'];
                    }
                }
            }

            $this->current_site_all_modules_settings = $settings;
            $this->current_site_enabled_modules_settings = array_filter($settings, function($v) { return (bool)$v['Checked']; });
        }

        if (empty($this->current_site_all_modules_settings)) {
            return false;
        }

        return $ignore_check ? $this->current_site_all_modules_settings : $this->current_site_enabled_modules_settings;
    }

    //--------------------------------------------------------------------------

    /**
     * Check installed module by keyword
     *
     * @param string module keyword
     * @param bool ignore `Installed` column
     * @param bool ignore 'Inside_Admin' column
     *
     * @return array|false module data or false
     */
    public function get_by_keyword($keyword, $installed = true, $inside_admin = true) {
        $all_modules_data = $this->get_data();
        if (empty($all_modules_data)) {
            return false;
        }
        // walk on array
        foreach ($all_modules_data AS $module_data) {
            if ($module_data['Keyword'] !== $keyword) {
                continue;
            }
            if ($inside_admin && nc_core::get_object()->inside_admin && $module_data['Inside_Admin'] == 0) {
                return false;
            }
            if ($installed) {
                return $module_data['Installed'] ? $module_data : false;
            }
            return $module_data;
        }

        return false;
    }

    //--------------------------------------------------------------------------

    public function load_env($language = "", $only_inside_admin = false, $reload = false, $ignore_check = false, $only_module = '') {
        // dummy
        // global $MODULE_VARS;
        // system superior object
        $nc_core = nc_Core::get_object();

        // set static variable
        static $result = array();
        static $loaded = array();
        static $before_load_event_fired = false;
        static $after_load_event_fired = false;

        // check
        if (!isset($loaded[$only_module]) || $reload) {
            if (!$before_load_event_fired) {
                $nc_core->event->execute(nc_event::BEFORE_MODULES_LOADED);
                $before_load_event_fired = true;
            }

            $modules_data = $this->get_data($reload, $ignore_check);

            if (empty($modules_data)) {
                return false;
            }

            // determine language
            if (!$language && is_object($nc_core->subdivision)) {
                $language = $nc_core->subdivision->get_current('Language');
            }

            if (!$language && is_object($nc_core->catalogue)) {
                $language = $nc_core->catalogue->get_current('Language');
            }

            if (!$language) {
                $language = $nc_core->lang->detect_lang(1);
            }

            if (!$language) {
                return false;
            }

            // MODULE_VARS должен быть доступен в файлах модуля
            $MODULE_VARS = $this->get_module_vars();

            foreach ($modules_data as $row) {
                // module keyword
                $keyword = $row['Keyword'];
                $module_folder = nc_module_folder($keyword);

                if ($only_module && $only_module != $keyword) {
                    continue;
                }

                // load modules marked as "inside_admin" if only_inside_admin == true
                if ($only_inside_admin && !$row['Inside_Admin']) {
                    continue;
                }

                // load each module language files only once
                if (isset($loaded[$keyword])) {
                    continue;
                }

                // include language file
                if (is_file($module_folder . $language . '.lang.php')) {
                    require_once $module_folder . $language . '.lang.php';
                } else {
                    require_once $module_folder . 'en.lang.php';
                }

                // include the module itself
                if (is_file($module_folder . 'function.inc.php')) {
                    require_once $module_folder . 'function.inc.php';
                }

                $loaded[$keyword] = true;
            }

            // module_vars может измениться в самом модуле
            $result = $MODULE_VARS;

            if (!$only_module && !$after_load_event_fired) {
                $nc_core->event->execute(nc_event::AFTER_MODULES_LOADED);
                $after_load_event_fired = true;
            }
        }

        return $result;
    }

    //--------------------------------------------------------------------------

    public function get_module_vars() {
        if (!$this->module_vars) {
            $this->get_data();
        }
        return $this->module_vars;
    }

    //--------------------------------------------------------------------------

    public function get_vars($module, $item = "") {
        // get data for all modules
        $modules_vars = $this->get_module_vars();
        // vars for this module
        if (!empty($modules_vars[$module])) {
            // if item requested return item value
            if ($item) {
                return is_array($modules_vars[$module]) && array_key_exists($item, $modules_vars[$module]) ? $modules_vars[$module][$item] : false;
            } else {
                return $modules_vars[$module];
            }
        }
        // default
        return false;
    }

    //--------------------------------------------------------------------------

}