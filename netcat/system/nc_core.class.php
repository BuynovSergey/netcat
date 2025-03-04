<?php

/**
 *
 * Загружаются по запросу:
 * @property nc_ui $ui
 * @property nc_backup $backup
 * @property nc_nav $nav
 * @property nc_dashboard $dashboard
 * @property nc_csv $csv
 * @property nc_gzip $gzip
 * @property nc_mail $mail
 * @property nc_revision $revision
 * @property nc_widget $widget
 * @property nc_trash $trash
 * @property nc_http $http
 * @property nc_cache_service $cache
 */

class nc_Core extends nc_System {

    public $DOCUMENT_ROOT, $SYSTEM_FOLDER, $INCLUDE_FOLDER, $ROOT_FOLDER, $SUB_FOLDER, $MODULE_FOLDER, $ADMIN_FOLDER,
           $FILES_FOLDER, $DUMP_FOLDER, $CACHE_FOLDER, $TRASH_FOLDER, $TEMPLATE_FOLDER, $CLASS_TEMPLATE_FOLDER, $WIDGET_TEMPLATE_FOLDER, $TMP_FOLDER,
           $HTTP_IMAGES_PATH, $HTTP_ROOT_PATH, $HTTP_TMP_PATH, $HTTP_FILES_PATH, $HTTP_DUMP_PATH, $HTTP_CACHE_PATH, $HTTP_TEMPLATE_PATH, $HTTP_TEMPLATE_CACHE_PATH,
           $HTTP_TRASH_PATH, $ADMIN_PATH, $ADMIN_TEMPLATE, $ADMIN_TEMPLATE_FOLDER, $NC_JQUERY_PATH, $JQUERY_FOLDER,
           $ASSET_PATH, $ASSET_FOLDER, $MODULE_TEMPLATE_FOLDER,
           $DIRCHMOD, $FILECHMOD;

    public $NC_UNICODE, $NC_CHARSET, $PHP_AUTH_LANG, $PHP_TYPE,
           $MYSQL_HOST, $MYSQL_USER, $MYSQL_PASSWORD, $MYSQL_DB_NAME,
           $MYSQL_PORT, $MYSQL_SOCKET, $MYSQL_CHARSET, $MYSQL_ENCRYPT, $SHOW_MYSQL_ERRORS,
           $REDIRECT_STATUS, $AUTHORIZATION_TYPE, $NC_ADMIN_HTTPS,
           $NC_DEPRECATED_DISABLED, $NC_REDIRECT_DISABLED,
           $use_gzip_compression, $ADMIN_AUTHTIME, $ADMIN_AUTHTYPE, $AUTHORIZE_BY,
           $HTTP_HOST, $REQUEST_URI, $DOMAIN_NAME, $EDIT_DOMAIN, $DOC_DOMAIN,
           $ADMIN_LANGUAGE;

    public $inside_admin = false;
    public $admin_mode = false;
    public $developer_mode = false;
    public $is_license_found = false;
    public $is_license_activated = false;
    public $is_trial = false; // trial version?
    public $beta = false;
    public $action;
    protected $settings = array();

    /** @var float объём свободной памяти, при которой будет срабатывать очистка кэша */
    protected $free_memory_threshold = 4194304; // 4Mb

    /** @var array  Идентификаторы «системных таблиц» */
    protected $system_tables_ids = array('Catalogue' => 1, 'Subdivision' => 2, 'User' => 3, 'Template' => 4);

    /** @var  nc_db */
    public $db;
    // значение настроек
    /** @var  nc_Page */
    public $page;

    /** @var  nc_Event */
    public $event;

    /** @var  nc_Files */
    public $files;

    /** @var  nc_file_info */
    public $file_info;

    /** @var  nc_Input */
    public $input;

    /** @var  nc_Lang */
    public $lang;

    /** @var  nc_Modules */
    public $modules;

    /** @var  nc_Token */
    public $token;

    /** @var  nc_Url */
    public $url;

    /** @var  nc_Utf8 */
    public $utf8;

    /** @var  nc_Security */
    public $security;

    /** @var  nc_Catalogue */
    public $catalogue;

    /** @var  nc_Subdivision */
    public $subdivision;

    /** @var  nc_Sub_Class */
    public $sub_class;

    /** @var  nc_Component */
    public $component;

    /** @var  nc_Template */
    public $template;

    /** @var  nc_User */
    public $user;

    /** @var  nc_Message */
    public $message;

    /** @var  nc_cookie */
    public $cookie;

    /** @var  nc_List */
    public $list;

    /** @var  nc_version */
    public $version;

    // тип страницы (html, rss, xml)
    protected $page_type;
    protected $macrofuncs;

    /**
     * @var array [ class_prefix => path ]
     */
    protected $class_autoload_paths = array();

    protected $component_instances = array();

    /** @var callable[] */
    protected $output_processors = array();

    protected function __construct() {
        global $ROOT_FOLDER, $SYSTEM_FOLDER, $TEMPLATE_FOLDER, $CLASS_TEMPLATE_FOLDER, $WIDGET_TEMPLATE_FOLDER, $JQUERY_FOLDER, $MODULE_TEMPLATE_FOLDER, $INCLUDE_FOLDER;

        $this->set_variable("ROOT_FOLDER", $ROOT_FOLDER);
        $this->set_variable("SYSTEM_FOLDER", $SYSTEM_FOLDER ?: __DIR__ . '/');
        $this->set_variable("INCLUDE_FOLDER", $INCLUDE_FOLDER);
        $this->set_variable("TEMPLATE_FOLDER", $TEMPLATE_FOLDER);
        $this->set_variable("CLASS_TEMPLATE_FOLDER", $CLASS_TEMPLATE_FOLDER);
        $this->set_variable("WIDGET_TEMPLATE_FOLDER", $WIDGET_TEMPLATE_FOLDER);
        $this->set_variable("JQUERY_FOLDER", $JQUERY_FOLDER);
        $this->set_variable("MODULE_TEMPLATE_FOLDER", $MODULE_TEMPLATE_FOLDER);
        // load parent constructor
        parent::__construct();

        //$this->macrofuncs['NC_OBJECTS_LIST'] = array('func' => 'nc_objects_list');
        //$this->beta = true;
    }

    /**
     * Set object variable
     *
     * @param string $name variable name
     * @param mixed $value variable value
     */
    public function set_variable($name, $value) {
        // set variable
        $this->$name = $value;
    }

    /**
     * Get object variable
     *
     * @param string $name variable name
     * @return mixed variable value
     */
    public function get_variable($name) {
        // return value
        return isset($this->$name) ? $this->$name : NULL;
    }

    /**
     * Load system extension
     *
     * @param string $object   class name for loading
     * @param mixed $args,     arguments for class __construct function
     *
     * @return mixed           instantiated object
     */
    public function load() {

        $args = func_get_args();

        $extension_name = array_shift($args);
        $path = dirname($extension_name);
        $path = ($path && $path != '.' ? "$path/" : '');
        $extension_name = basename($extension_name);
        $full_class_name = 'nc_' . $extension_name;

        if (is_object($this->$full_class_name)) {
            $this->debugMessage("System class \"" . $full_class_name . "\" already loaded", __FILE__, __LINE__, "info");
            return $this->$full_class_name;
        }

        $file_name = $this->SYSTEM_FOLDER . $path . $full_class_name . ".class.php";
        if (file_exists($file_name)) {
            include_once($file_name);
        }

        if (class_exists($full_class_name)) {
            if (sizeof($args)) {
                $this->$extension_name = call_user_func_array(array(new ReflectionClass($full_class_name), 'newInstance'), $args);
            }
            else {
                $this->$extension_name = new $full_class_name;
            }
        }

        if (!$this->$extension_name) {
            throw new Exception("Unable load system class \"" . $full_class_name . "\"!");
        }

        return $this->$extension_name;
    }

    /**
     * @param int $catalogue_id
     * @param bool $reset
     * @return array
     */
    protected function load_all_settings($catalogue_id, $reset) {
        $catalogue_id = (int)$catalogue_id;

        if (!isset($this->settings[$catalogue_id]) || $reset) {
            if ($catalogue_id !== 0) {
                // default settings
                $settings = $this->load_all_settings(0, $reset);
            }
            else {
                $settings = array();
            }

            $res = $this->db->get_results("SELECT `Key`, `Module`, `Value` FROM `Settings` WHERE `Catalogue_ID` = $catalogue_id", ARRAY_A);

            // обработка ошибок
            if ($this->db->is_error) {
                // таблица не существует
                if ($this->db->errno == 1146 || strpos($this->db->last_error, 'exist')) {
                    if ( $this->check_system_install() ) {
                        // DB error
                        print "<p><b>".NETCAT_ERROR_DB_CONNECT."</b></p>";
                        exit;
                    }
                }
                die("Table `Settings`");
            }

            foreach ((array)$res as $row) {
                $settings[$row['Module']][$row['Key']] = $row['Value'];
            }

            $this->settings[$catalogue_id] = $settings;
        }

        return $this->settings[$catalogue_id];
    }

    /**
     * Получить значение параметра из настроек
     * @param string $item  ключ
     * @param string $module имя модуля (system — ядро)
     * @param bool $reset
     * @param int|null $catalogue_id
     *      Если NULL, возвращает настройки для текущего ($nc_core->catalogue->id()) сайта.
     *      Если 0, возвращает настройки «по умолчанию для всех сайтов».
     *      Если другое число — возвращает настройки для указанного сайта.
     * @return mixed значение параметра
     */
    public function get_settings($item = '', $module = '', $reset = false, $catalogue_id = null) {
        if ($catalogue_id === null && $this->catalogue) {
            $catalogue_id = $this->catalogue->id();
        }

        $catalogue_id = (int)$catalogue_id;

        if (!isset($this->settings[$catalogue_id]) || $reset) {
            $this->load_all_settings($catalogue_id, $reset);
        }

        // по умолчанию — ядро (1 и true нужно для обратной совместимости)
        if (!$module || $module === 1 || $module === true) {
            $module = 'system';
        }

        // настроек у запрошенного модуля нет
        if (empty($this->settings[$catalogue_id][$module])) {
            return array();
        }

        // if item requested return item value
        if ($item && is_array($this->settings[$catalogue_id][$module])) {
            return array_key_exists($item, $this->settings[$catalogue_id][$module])
                        ? $this->settings[$catalogue_id][$module][$item]
                        : false;
        }

        // return all settings
        return $this->settings[$catalogue_id][$module];
    }


    /**
     * Установить значение параметра
     * @param string $key ключ
     * @param string $value значение параметра
     * @param string $module модуль
     * @param int $catalogue_id
     * @return bool
     */
    public function set_settings($key, $value, $module = 'system', $catalogue_id = 0) {
        // по умолчанию - ядро системы
        if (!$module) $module = 'system';
        $catalogue_id = (int)$catalogue_id;

        // обновляем состояние
        $this->settings[$catalogue_id][$module][$key] = $value;

        // подготовка записи в БД
        $key          = $this->db->escape($key);
        $value        = $this->db->prepare($value);
        $module       = $this->db->escape($module);

        $id = $this->db->get_var("SELECT `Settings_ID` FROM `Settings` WHERE `Key` = '".$key."' AND `Module` = '".$module."' AND `Catalogue_ID` = '".$catalogue_id."'");

        if ($id) {
            $this->db->query("UPDATE `Settings` SET `Value` = '".$value."' WHERE `Settings_ID` = '".$id."' ");
        } else {
            $this->db->query("INSERT INTO `Settings`(`Key`, `Module`, `Value`, `Catalogue_ID`) VALUES('".$key."','".$module."','".$value."','".$catalogue_id."') ");
        }

        return true;
    }

    /**
     * Удаление параметра
     * @param string ключ
     * @param string модуль
     * @return int
     */
    public function drop_settings($key, $module = 'system') {
        // по умолчанию - ядро системы
        if (!$module) $module = 'system';

        // обновляем состояние
        foreach ($this->settings as $catalogue_id => $data) {
            unset($this->settings[$catalogue_id][$module][$key]);
        }

        // подготовка запроса к БД
        $key = $this->db->escape($key);
        $module = $this->db->escape($module);

        $this->db->query("DELETE FROM `Settings` WHERE `Key` = '".$key."' AND `Module` = '".$module."' ");

        return $this->db->rows_affected;
    }

    /**
     * Инициализация системы — загрузка базовых файлов, инициализация nc_core
     */
    public function init() {
        static $loaded = false;

        if (!$loaded) {
            $this->init_autoload();
            $this->load_core_classes();

            $this->security->init_filters();

            $this->load_system_files();
            $this->load_essence_classes();
            $this->load('version');
            $this->init_event_listeners();

            if (defined('NC_INIT_FUNCTION')) {
                call_user_func(NC_INIT_FUNCTION);
            }

            $loaded = true;
        }
    }

    /**
     * Инициализация слушателей событий для системных классов
     */
    protected function init_event_listeners() {
        nc_infoblock_filter_listener::init();
        nc_permission_listener::init();
    }

    /**
     * Инициализация автозагрузки классов системы
     */
    protected function init_autoload() {
        // Автозагрузка composer
        require_once $this->ROOT_FOLDER . 'vendor/autoload.php';

        // Более длинные префиксы должны идти первыми
        $this->class_autoload_paths += array(
            'nc_content_changer' => array($this->SYSTEM_FOLDER . 'content_changer', true),
            'nc_ImageTransform' => array($this->INCLUDE_FOLDER . 'classes', true),
            'nc_condition_' => array($this->SYSTEM_FOLDER . 'condition', false),
            'nc_multifield' => array($this->INCLUDE_FOLDER . 'classes', true),
            'nc_security_' => array($this->SYSTEM_FOLDER . 'security', true),
            'nc_validator' => array($this->SYSTEM_FOLDER . 'validator', true),
            'nc_selftest' => array($this->SYSTEM_FOLDER . 'selftest', true),
            'nc_partial' => array($this->SYSTEM_FOLDER . 'partial', true),
            'CMIMEMail' => array($this->SYSTEM_FOLDER, true),
            'nc_image_' => array($this->SYSTEM_FOLDER . 'image', true),
            'nc_record' => array($this->SYSTEM_FOLDER . 'record', true),
            'nc_cache' => array($this->SYSTEM_FOLDER . 'cache', true),
            'nc_form' => array($this->SYSTEM_FOLDER . 'form', true),
            'nc_http' => array($this->SYSTEM_FOLDER . 'http', true),
            'nc_a2f' => array($this->SYSTEM_FOLDER . 'a2f', true),
            'nc_tpl' => array($this->SYSTEM_FOLDER . 'templating', true),
            'nc_' => array($this->SYSTEM_FOLDER, true),
        );

        spl_autoload_register(array($this, 'load_class'));
    }

    /**
     * Инициализация nc_core и загрузка файлов, необходимых в админке (включает вызов init()).
     */
    public function init_admin_mode() {
        $this->init();

        static $are_admin_files_loaded = false;
        if ($are_admin_files_loaded) { return; }

        $admin_files = array(
            'nc_adminnotice.class.php',
            'catalog.inc.php',
            'class.inc.php',
            'template.inc.php',
            'field.inc.php',
            'system_table.inc.php',
            'module.inc.php',
            'admin.inc.php'
        );

        foreach ($admin_files as $file) { require_once $this->ADMIN_FOLDER . $file; }
        $are_admin_files_loaded = true;
    }

    /**
     * Загрузка базовых классов системы
     * @throws Exception
     */
    protected function load_core_classes() {
        // load default extensions
        $this->load("security");
        $this->load("files");
        $this->load("file_info");
        $this->load("token");
        $this->load("event");
        $this->load("utf8");
        $this->load("input");
        $this->load("url");
        $this->load("db");
        $this->load("page");
        $this->load("lang");
        $this->load("modules");
        $this->load("cookie");
        // NB: конструктор nc_widget регистрирует «макрофункцию» NC_WIDGET_SHOW
        $this->load("widget/widget");
        $this->load("http");
        $this->load("cache");
    }

    /**
     * Загрузка классов essences/*
     * @throws Exception
     */
    protected function load_essence_classes() {
        // essences
        $this->load("essences/list");
        $this->load("essences/component");
        $this->load("essences/catalogue");
        $this->load("essences/subdivision");
        $this->load("essences/sub_class");
        $this->load("essences/user");
        $this->load("essences/template");
        $this->load("essences/message");
    }

    /**
     * Загрузка файлов системы
     */
    protected function load_system_files() {
        static $are_base_files_loaded = false;

        if (!$are_base_files_loaded) {
            require_once $this->SYSTEM_FOLDER . 'templating/nc_tpl_function.inc.php';

            require_once $this->SYSTEM_FOLDER . 'nc_exception.class.php';

            // файлы из /netcat/require/
            $include_files = array(
                'unicode.inc.php',
                's_e404.inc.php',
                's_auth.inc.php',
                's_browse.inc.php',
                's_list.inc.php',
                's_class.inc.php',
                's_common.inc.php',
                's_files.inc.php',
                'typo.inc.php',
                's_helpers.inc.php',
            );

            // deprecated functions
            if (!$this->NC_DEPRECATED_DISABLED) {
                $include_files[] = 'deprecated.inc.php';
            }

            // файлы из /netcat/admin/
            $admin_files = array(
                'CheckUserFunctions.inc.php',
                'consts.inc.php',
                'user.inc.php',
                'sub_class.inc.php',
                'class.inc.php',
                'subdivision.inc.php',
                'mail.inc.php',
                'permission.class.php'
            );

            foreach ($include_files as $file) {
                require_once $this->INCLUDE_FOLDER . $file;
            }

            foreach ($admin_files as $file) {
                require_once $this->ADMIN_FOLDER . $file;
            }

            $are_base_files_loaded = true;
        }

    }

    /**
     * @deprecated
     */
    public function load_default_extensions() {
        $this->init();
    }

    /**
     * @deprecated
     * @param boolean $in_admin
     */
    public function load_files($in_admin = false) {
        $in_admin ? $this->init_admin_mode() : $this->init();
    }


    /**
     * Возвращает поля системных таблиц (или одной таблицы, если указан параметр $item)
     * @param string $item   Название системной таблицы
     * @return array
     */
    public function get_system_table_fields($item = "") {
        if ($item && isset($this->system_tables_ids[$item])) {
            return $this->get_component($item)->get_fields();
        }
        else if ($item) {
            return array();
        }
        else {
            $result = array();
            foreach ($this->system_tables_ids as $table => $tmp) {
                $result[$table] = $this->get_component($table)->get_fields();
            }
            return $result;
        }
    }

    /**
     * Возвращает название системной таблицы по её ID
     * @param $id
     * @return mixed
     */
    public function get_system_table_name_by_id($id) {
        return array_search($id, $this->system_tables_ids);
    }

    /**
     * Возвращает ID системной таблицы по её названию
     * @param $name
     * @return mixed
     */
    public function get_system_table_id_by_name($name) {
        return nc_array_value($this->system_tables_ids, $name);
    }


    public function load_env($catalogue, $sub, $cc) {
        global $admin_mode;
        global $catalogue, $sub, $cc, $cc_only;
        global $current_catalogue;
        global $current_sub;
        global $current_cc;
        global $cc_array;
        global $use_multi_sub_class;
        global $system_table_fields, $user_table_mode;
        global $parent_sub_tree, $sub_level_count;

        // load catalogue
        if (!$catalogue && !$sub && !$cc && !$cc_only) {
            $current_catalogue = $this->catalogue->get_by_host_name($this->HTTP_HOST, true);
            if (!$current_catalogue) {
                throw new Exception("No sites in the project");
            }
            $catalogue = $current_catalogue['Catalogue_ID'];
        }
        else {
            if (!$catalogue) {
                if ($sub) {
                    $catalogue = $this->subdivision->get_by_id($sub, 'Catalogue_ID');
                }
                else if ($cc || $cc_only) {
                    $catalogue = $this->sub_class->get_by_id($cc ?: $cc_only, 'Catalogue_ID');
                }
            }

            $current_catalogue = $this->catalogue->set_current_by_id($catalogue);
        }

        // load sub
        if (!$sub) {
            if ($cc || $cc_only) {
                $sub = $this->sub_class->get_by_id($cc ?: $cc_only, 'Subdivision_ID');
            }
            else {
                $sub = $this->catalogue->get_by_id($catalogue, "Title_Sub_ID");
            }
            if (!$sub) {
                throw new Exception("Unable to find the index page");
            }
        }

        $this->subdivision->set_current_by_id($sub);


        // load cc
        if (!$cc) {
            $checked_only = $admin_mode ? "" : " AND `Checked` = 1";
            $cc = $this->db->get_var("SELECT `Sub_Class_ID` FROM `Sub_Class` WHERE `Subdivision_ID` = '".intval($sub)."'".$checked_only." ORDER BY `Priority` LIMIT 1");
        }
        if ($cc || $cc_only) {
            try {
                $this->sub_class->set_current_by_id($cc ?: $cc_only);
            } catch (Exception $e) {
                // todo
            }
        }

        // Load all sub_class id's into array, may be exist in
        if (!is_array($cc_array)) {
            $cc_array = array();
            // get cc(s) data
            $res = $this->sub_class->get_by_subdivision_id($sub);
            if (!empty($res)) {
                foreach ($res as $row) {
                    if ($row['Checked'] || $admin_mode) { $cc_array[] = $row['Sub_Class_ID']; }
                }
            }
        }

        // load system table fields
        $system_table_fields = $this->get_system_table_fields();
        // set global variables
        $current_catalogue = $this->catalogue->get_current();
        $current_sub = $this->subdivision->get_current();
        $current_cc = $this->sub_class->get_current();

        if (nc_module_check_by_keyword('auth')) {
            $modify_subs = $this->get_settings('modify_sub', 'auth') ?: '';
        } else {
            $modify_subs = '';
        }

        if ((isset($current_cc['System_Table_ID']) && $current_cc['System_Table_ID'] == 3) || in_array($current_sub['Subdivision_ID'], nc_preg_split("/\s*,\s*/", $modify_subs))) {
//            $action = "message";
            $user_table_mode = true;
        } else {
            $user_table_mode = false;
        }

        $parent_sub_tree[$sub_level_count]["Subdivision_Name"] = $current_catalogue["Catalogue_Name"];
        $parent_sub_tree[$sub_level_count]["Hidden_URL"] = "/";

        return;
    }

    /**
     * Get or instance self object
     *
     * @return self object
     */
    public static function get_object() {
        static $storage;
        // check cache
        if (!isset($storage)) {
            // init object
            $storage = new self();
        }
        // return object
        return is_object($storage) ? $storage : false;
    }

    public function set_page_type($type) {
        if (!in_array($type, array('html', 'rss', 'xml'))) $type = 'html';
        $this->page_type = $type;
    }

    public function get_page_type() {
        return $this->page_type ? $this->page_type : 'html';
    }

    public function get_content_type() {
        $type = $this->get_page_type();
        if ($type == 'rss') $type = 'xml';

        return "text/".$type."; charset=".$this->NC_CHARSET;
    }

    public function replace_macrofunc($str) {
        if ($this->inside_admin || strpos($str, '%') === false) {
            return $str;
        }

        global $action;
        preg_match_all("/%([a-z0-9_]+)\(([^\)]+)\)%/i", $str, $matches, PREG_SET_ORDER);

        foreach ($matches as $v) {
            $v[2] = str_replace('&#39;', '\'', $v[2]);
            if (empty($this->macrofuncs[$v[1]])) continue;
            $func = $this->macrofuncs[$v[1]]['func'];
            $obj = nc_array_value($this->macrofuncs[$v[1]], 'object');
            eval("\$args = \$this->_parse_func_arg(".$v[2].");");
            $res = call_user_func_array($obj ? array($obj, $func) : $func, $args);
            if (($action == 'change' || $action == 'add') && (!isset($args[1]) || !$args[1])) {
                $str = str_replace($v[0], '', $str);
            }
            $str = str_replace($v[0], $res, $str);
        }

        return $str;
    }

    /**
     * Добавляет обработчик содержимого страницы.
     * Указанный обработчик должен принимать аргумент $buffer и возвращать содержимое
     * страницы.
     * @param $callable
     * (typehint 'callable' доступен с PHP 5.4)
     */
    public function add_output_processor($callable) {
        if (!in_array($callable, $this->output_processors)) {
            $this->output_processors[] = $callable;
        }
    }

    /**
     * @param string $template_header
     * @param string $main_content
     * @param string $template_footer
     */
    public function output_page($template_header, $main_content, $template_footer) {
        $page_format = $this->page->get_routing_result('format');

        if ($page_format === 'rss' || $page_format === 'xml' || !empty($GLOBALS['isNaked'])) {
            $output = $main_content;
        } else {
            // Панель вверху страницы для пользователя с соответствующими правами
            if (!$this->inside_admin && $this->AUTHORIZATION_TYPE !== 'http' && $this->get_settings('QuickBar') && nc_quickbar_permission()) {
                require_once $this->INCLUDE_FOLDER . 'quickbar.inc.php';
                $template_header = nc_quickbar_in_template_header($template_header, $this->template->get_current('File_Mode'));
            }

            // Демо-режим
            if ($this->is_trial && !$this->inside_admin) {
                $trial_message = function_exists('nc_demo_expired') ? nc_demo_expired() : '';
                if ($trial_message) {
                    $demo_styles_and_scripts =
                        "\n" . '<link rel="stylesheet" type="text/css" href="https://fonts.googleapis.com/css?family=Roboto:300,400&subset=cyrillic">' .
                        "\n" . '<link rel="stylesheet" type="text/css" href="' . $this->ADMIN_PATH . 'skins/default/css/demo.css">' .
                        "\n" . '<script type="text/javascript" src="' . $this->ADMIN_PATH . 'js/demo.js"></script>';
                    $template_header = nc_insert_in_head($template_header, $demo_styles_and_scripts, true);
                    $template_header = nc_insert_in_body($template_header, "\n" . $trial_message);
                }
            }

            // Миксины страницы (из настроек разделов и сайта)
            if ($this->inside_admin) {
                $template_header = $this->page->alter_body_tag($template_header, 'nc-admin');
            } else {
                $template_header = $this->page->add_page_mixins($template_header);
            }

            // Автоматически добавляемые стили, скрипты и мета-теги
            $template_header = $this->page->add_tags_to_output($template_header);

            // Если где-то в шапке или подвале сайта используется основной контейнер, основную контентную область выводим там
            $header_has_main_area = strpos($template_header, '%NC_AREA_MAIN_CONTENT%') !== false;
            $footer_has_main_area = strpos($template_footer, '%NC_AREA_MAIN_CONTENT%') !== false;

            $output = $header_has_main_area ? str_replace('%NC_AREA_MAIN_CONTENT%', $main_content, $template_header) : $template_header;

            if (!$header_has_main_area && !$footer_has_main_area) {
                $output .= $main_content;
            }

            $output .= $footer_has_main_area ? str_replace('%NC_AREA_MAIN_CONTENT%', $main_content, $template_footer) : $template_footer;

            unset($template_header, $main_content, $template_footer);
        }

        // «Макрофункции»
        if (!$this->inside_admin) {
            $output = $this->replace_macrofunc($output);
        }

        // Кастомные обработчики буфера страницы
        foreach ($this->output_processors as $fn) {
            $output = call_user_func($fn, $output);
        }

        // Проверка XSS-фильтром
        $output = $this->security->xss_filter->filter($output);

        if (!$this->admin_mode) {
            // Отправка заголовка 304, если страница не изменилась с момента последнего запроса
            $this->page->send_and_check_cache_validator_headers($output);
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'HEAD') {
            if ($this->use_gzip_compression && $this->gzip->check()) {
                ob_start('ob_gzhandler');
            }
            echo $output;
        }
    }

    /**
     * Включение или выключение поля
     * @param string $action check - включение, uncheck - выключение
     * @param mixed $class_id номер компонента или имя системной таблицы
     * @param mixed $field номер поля или его имя
     * @return bool изменено поле или нет
     */
    public function edit_field($action, $class_id = 0, $field = '') {
        if (is_string($class_id)) {
            $system_table_id = $this->system_tables_ids[$class_id];
        } else {
            $class_id = intval($class_id);
        }

        $this->db->query("UPDATE `Field`
                      SET `Checked` = '".($action == 'check' ? 1 : 0)."'
                      WHERE
                      ".( is_int($class_id) ? "`Class_ID` = '".$class_id."' AND " : "" )."
                      ".( $system_table_id ? "`System_Table_ID` = '".$system_table_id."' AND " : "" )."
                      ".( is_int($field) ? "`Field_ID` = '".$field."' " :
                        " `Field_Name` = '".$this->db->escape($field)."' "));
        return $this->db->rows_affected;
    }

    /**
     * Включение поля в компоненте или системной таблице
     * @param mixed $class_id номер компонента или имя системной таблицы
     * @param mixed $field номер поля или его имя
     * @return bool изменено поле или нет
     */
    public function check_field($class_id = 0, $field = '') {
        return $this->edit_field('check', $class_id, $field);
    }

    /**
     * Выключение поля в компоненте или системной таблице
     * @param mixed $class_id номер компонента или имя системной таблицы
     * @param mixed $field номер поля или его имя
     * @return bool изменено поле или нет
     */
    public function uncheck_field($class_id = 0, $field = '') {
        return $this->edit_field('uncheck', $class_id, $field);
    }

    /**
     * Метод проверяет, установлено ли расширение php
     * @param string $name имя расширения
     * @return bool
     */
    public function php_ext($name) {
        static $ext = array();
        if (!array_key_exists($name, $ext)) {
            $ext[$name] = extension_loaded($name);
        }

        return $ext[$name];
    }

    /**
     * Строка парсится в аргументы функции
     * @param string
     * @return <type>
     */
    protected function _parse_func_arg($str) {
        return func_get_args();
    }

    /**
     * Регистрация макрофункции
     * @param string $macroname имя макрофункции
     * @param string $func имя функции или метода, результат которой заменяет макрофункцию
     * @param object $object  ссылка на объект, если второй аргумент - метод
     */
    public function register_macrofunc($macroname, $func, $object = null) {
        $this->macrofuncs[$macroname] = array('func' => $func);
        if ($object) {
            $this->macrofuncs[$macroname]['object'] = $object;
        }
    }

    /**
     * Проверяет, была ли зарегистрирована макрофункция
     * @param string $macroname
     * @return bool
     */
    public function has_macrofunc($macroname) {
        return isset($this->macrofuncs[$macroname]);
    }

    public function return_device() {
        $detect = new Mobile_Detect();

        if ($detect->isTablet()) {
            return 'tablet';
        }

        if ($detect->isMobile()) {
            return 'mobile';
        }

        return 'desktop';
    }

    public function is_mobile() {
        return $this->return_device() !== 'desktop';
    }

    public function mobile_screen() {
        return $_COOKIE['mobile'];
    }

    public function InsideAdminAccess() {
        global $current_user, $AUTH_USER_ID;
        return ($this->modules->get_by_keyword('auth') && !empty($current_user['InsideAdminAccess'])) || !$this->modules->get_by_keyword('auth') && $this->db->get_var("SELECT `InsideAdminAccess` FROM `User` WHERE `User_ID`='" . intval($AUTH_USER_ID) . "'");
    }

    public function get_interface() {
        return $this->catalogue->get_current('ncMobile') ? 'mobile' : ($this->catalogue->get_current('ncResponsive') ? 'responsive' : 'web');
    }

    // --- Загрузка расширений nc_core по запросу ---
    /**
     *
     * @return nc_ui
     */
    protected function ui() {
        require_once $this->SYSTEM_FOLDER . 'admin/ui/nc_ui.class.php';
        return nc_ui::get_instance();
    }

    /**
     *
     * @return nc_dashboard
     */
    protected function dashboard() {
        global $ADMIN_FOLDER;
        require_once $ADMIN_FOLDER . 'dashboard/nc_dashboard.class.php';
        return nc_dashboard::get_instance();
    }

    /**
     *
     * @return nc_backup
     */
    protected function backup() {
        require_once $this->SYSTEM_FOLDER . 'backup/nc_backup.class.php';
        return nc_backup::get_instance();
    }

    /**
     *
     * @return nc_nav
     */
    protected function nav() {
        require_once $this->SYSTEM_FOLDER . 'nc_nav.class.php';
        return nc_nav::get_instance();
    }

    /**
     *
     * @return nc_csv
     */
    protected function csv() {
        global $ADMIN_FOLDER;
        require_once $ADMIN_FOLDER . 'csv/nc_csv.class.php';
        return nc_csv::get_instance();
    }

    /**
     * @return nc_trash
     */
    protected function trash() {
        require_once $this->SYSTEM_FOLDER . 'essences/nc_trash.class.php';
        return new nc_trash;
    }

    /**
     * @return nc_gzip
     */
    protected function gzip() {
        return new nc_gzip;
    }

    /**
     * @return nc_mail
     */
    protected function mail() {
        return new nc_mail;
    }

    /**
     * @return nc_revision
     */
    protected function revision() {
        return new nc_revision;
    }

    /**
     * @return nc_widget
     */
    protected function widget() {
        require_once $this->SYSTEM_FOLDER . 'widget/nc_widget.class.php';

        return new nc_widget;
    }

    /**
     * @return nc_http
     */
    protected function http() {
        require_once $this->SYSTEM_FOLDER . 'http/nc_http.class.php';

        return new nc_http();
    }

    /**
     * @return nc_cache_service
     */
    protected function cache() {
        require_once $this->SYSTEM_FOLDER . 'cache/nc_file_cache_service.class.php';

        return new nc_file_cache_service(null, nc_cache_manager::DEFAULT_FILE_CACHE_SERVICE_ID);
    }

    /**
     * При обращении к свойству, которое не определено, и для которого существует
     * одноимённый метод, выполняет этот метод, результат записывает в указанное
     * свойство и возвращается. Используется для загрузки части классов по требованию.
     */
    public function __get($name) {
        $result = null;
        if (isset($this->$name)) {
            $result = $this->$name;
        } else if (method_exists('nc_Core', $name)) {
            $result = $this->$name = $this->$name();
        }
        return $result;
    }

    // ------

    /**
     * Возвращает способ отображения для текущего раздела
     *
     * @return string
     */
    public function get_display_type() {
        $inputDisplayType = $this->input->fetch_get('lsDisplayType');

        if ($inputDisplayType) {
            return $inputDisplayType;
        }

        $catalogue = $this->catalogue->get_current();
        $subdivision = $this->subdivision->get_current();

        $displayType = 'traditional';
        if ($catalogue && $subdivision && $subdivision['Catalogue_ID'] == $catalogue['Catalogue_ID']) {
            if (
                $catalogue['Title_Sub_ID'] == $subdivision['Subdivision_ID'] ||
                $catalogue['E404_Sub_ID'] == $subdivision['Subdivision_ID'] ||
                ($subdivision['DisplayType'] == 'inherit' && !$subdivision['Parent_Sub_ID'])
            ) {
                $displayType = $catalogue['DisplayType'];
            } else {
                $displayType = $subdivision['DisplayType'];

                if ($displayType == 'inherit') {
                    $parentSubdivision = $subdivision;
                    do {
                        try {
                            $parentSubdivision = $parentSubdivision['Parent_Sub_ID'] ?
                                $this->subdivision->get_by_id($parentSubdivision['Parent_Sub_ID']) : null;
                        } catch (Exception $e) {
                            $parentSubdivision = null;
                        }

                        if ($parentSubdivision && $parentSubdivision['DisplayType'] != 'inherit') {
                            $displayType = $parentSubdivision['DisplayType'];
                        }
                    } while ($displayType == 'inherit' && $parentSubdivision);

                    if ($displayType == 'inherit') {
                        $displayType = $catalogue['DisplayType'];
                    }
                }
            }
        }

        return $displayType;
    }


    /**
     * Автозагрузка классов.
     *
     * Например, если prefix = "nc_module_search_", path = "/www/netcat/modules/search/lib", full_name = false
     * то при попытке обращения к классу "nc_module_search_document_parser_html"
     * будет загружен файл "/www/netcat/modules/search/lib/document/parser/html.php"
     *
     * Параметр $full_class_name отвечает за формирование имени файла.
     * Если $full_class_name = true,то будет производиться поиск файлов с именем
     * <full_class_name.class.php>.
     * Если $full_name = false, файл будет производиться поиск файлов по пути
     * <full/class/name.php>.
     *
     * Если префикс не заканчивается на "_", то в соответствующей папке будет производиться
     * и поиск класса с названием, равным префиксу, например:
     *    prefix = "nc_record", path = "/system/data", full_name = true
     *    тогда при поиске класса nc_record будет загружен файл "/system/data/nc_record.class.php"
     *
     * @param $class_prefix    (With a trailing underscore!)
     * @param $class_path      (No trailing slash please)
     * @param bool $full_class_name  Если TRUE, то ищет файлы с именем <full_class_name.class.php>
     */
    public function register_class_autoload_path($class_prefix, $class_path, $full_class_name = false) {
        $this->class_autoload_paths[$class_prefix] = array(rtrim($class_path, '/'), $full_class_name);

        // отсортировать массив таким образом, чтобы первыми шли более длинные ключи,
        // чтобы избежать ненужных проверок на наличие файла в случае использования
        // префикса "nc_" и префиксов, не оканчивающихся на "_"
        krsort($this->class_autoload_paths);
    }


    /**
     * Обработчик для автозагрузки классов (spl_autoload_register)
     * @param $class_name
     */
    public function load_class($class_name) {
        if (!preg_match("/^\w+$/", $class_name)) { return; }

        foreach ($this->class_autoload_paths as $prefix => $options) {
            if (strpos($class_name, $prefix) === 0) {
                list ($path, $full_name) = $options;

                if ($full_name) { // "$path/nc_full_class_name.php"
                    $file_path = $path . '/' . strtolower($class_name) . ".class.php";
                }
                else { // "$path/full/class/name.php"
                    $file_path = $path . '/' . str_replace("_", "/", strtolower(substr($class_name, strlen($prefix)))) . ".php";
                }

                if (file_exists($file_path)) {
                    require_once $file_path;

                    return;
                }
            }
        }
    }

    /**
     * Возвращает объект nc_component для указанного компонента
     * @param int $component_id
     * @return nc_component
     */
    public function get_component($component_id) {
        if (!isset($this->component_instances[$component_id])) {
            $this->component_instances[$component_id] = new nc_component($component_id);
        }
        return $this->component_instances[$component_id];
    }

    /**
     * Возвращает экземпляр объекта nc_component для указанного инфоблока (в т. ч. для системной таблицы пользователей)
     * @param int $infoblock_id
     * @return nc_component
     */
    public function get_component_by_infoblock_id($infoblock_id) {
        $component_id = $this->sub_class->get_by_id($infoblock_id, 'Class_ID');
        $system_table_id = $this->component->get_by_id($component_id, 'System_Table_ID');
        $component_composite_id = $system_table_id ? $this->get_system_table_name_by_id($system_table_id) : $component_id;
        return $this->get_component($component_composite_id);
    }

    public function get_cookie_domain() {
        return $this->cookie->get_domain();
    }

    /**
     * Возвращает ID копии (строка длиной 41 байт), генерирует ID при его отсутствии
     * @return string
     */
    public function get_copy_id() {
        $copy_id = $this->get_settings('CopyID');
        if (!$copy_id) {
            $copy_id = $this->generate_copy_id();
            $this->set_settings('CopyID', $copy_id);
        }
        return $copy_id;
    }

    /**
     * Генерирует ID копии (строка длиной 41 байт)
     * @return string
     */
    protected function generate_copy_id() {
        $parts = array();
        for ($i=0; $i < 6; $i++) {
            $parts[$i] = str_pad(base_convert(mt_rand(0, 2147483647), 10, 36), 6, '0', STR_PAD_LEFT);
        }
        return strtoupper(join("-", $parts));
    }

    /**
     * Возвращает строку с названием редакции системы
     * @return string
     */
    public function get_edition_name() {
        switch ($this->get_settings('SystemID')) {
            case 2: return 'Standard';
            case 6: return 'E-Commerce';
            case 8: return 'Corporate';
            case 12: return 'Business';
            case 13: return 'Standard+';
            default: return 'Extra';
        }
    }

    /**
     * Возвращает строку с полным номером версии Netcat
     * @return string
     */
    public function get_full_version_number() {
        $build_number = $this->get_settings('LastPatchBuildNumber');
        return $this->get_settings('VersionNumber') .
               ($build_number ? ".$build_number" : '');
    }

    /**
     * Возвращает строку с полным номером версии и названием редакции Netcat
     * @return string
     */
    public function get_full_version_string() {
         return $this->get_full_version_number() . ' ' . $this->get_edition_name();
    }

    /**
     * Возвращает значение параметра PHP memory_limit в байтах
     * @return int
     */
    public function get_memory_limit() {
        $memory_limit = nc_size2bytes(ini_get('memory_limit'));
        if ($memory_limit < 0) {
            $memory_limit = 0;
        }
        return $memory_limit;
    }

    /**
     * Устанавливает новое значение размера свободной памяти, при достижении которого
     * будет выполняться очистка внутреннего кэша для сущностей (сайтов, разделов,
     * инфоблоков и т. п.)
     * @param int $free_memory_threshold
     */
    public function set_free_memory_threshold($free_memory_threshold) {
        $this->free_memory_threshold = $free_memory_threshold;
    }

    /**
     * Очищает кэши
     */
    public function clear_cache() {
        $this->component_instances = array();
        $this->component->clear_cache();
        $this->subdivision->clear_cache();
        $this->sub_class->clear_cache();
        $this->message->clear_cache();
        $this->template->clear_cache();
        $this->user->clear_cache();
        $this->file_info->clear_cache();
    }

    /**
     * Очищает кэши, когда объём свободной памяти становится слишком низким
     * @return bool
     */
    public function clear_cache_on_low_memory() {
        static $memory_limit, $memory_threshold;
        if (!isset($memory_limit)) {
            $memory_limit = $this->get_memory_limit();
            $memory_threshold = $memory_limit - $this->free_memory_threshold;
        }

        if ($memory_limit && memory_get_usage() >= $memory_threshold) {
            $this->clear_cache();
            return true;
        }
        else {
            return false;
        }
    }

    /**
     * Возвращает текстовую подсказку к полю логина, в зависимости от выбранного метода авторизации
     *
     * @param bool $to_lower
     * @param bool $escape_html
     *
     * @return string
     */
    public function get_login_field_label($to_lower = false, $escape_html = true) {
        $login_field = $this->AUTHORIZE_BY;

        if ($login_field === 'User_ID') {
            $login_label = NETCAT_MODULE_AUTH_LOGIN;
        } else {
            $login_description = $this->get_component('User')->get_field($login_field, 'description');
            $login_label = $login_description ?: NETCAT_MODULE_AUTH_LOGIN;
        }

        if ($to_lower) {
            $login_label = mb_strtolower($login_label);
        }

        if ($escape_html) {
            $login_label = htmlspecialchars($login_label, ENT_QUOTES);
        }

        return $login_label;
    }
}
