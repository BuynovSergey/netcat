<?php

/**
 * Основной класс модуля поиска
 */
class nc_search {

    /**
     * Настройки модуля
     * @var nc_search_settings
     */
    protected static $settings;
    /**
     * It's a nc_search_ui singleton
     * @var nc_search_ui
     */
    protected static $instance;
    /**
     * Поставщик поиска
     * @var nc_search_provider
     */
    protected static $provider;
    /**
     * Текущий контекст
     * @var nc_search_context
     */
    protected static $current_context;
    /**
     *
     */
    protected static $max_log_level;
    /**
     * @var nc_search_logger[]
     */
    protected static $loggers = array();

    /**
     * Константы
     */
    const LOG_ERROR = 1;
    const LOG_PHP_EXCEPTION = 2;
    const LOG_PHP_ERROR = 4;
    const LOG_PHP_WARNING = 8;
    const LOG_INDEXING_NO_SUB = 32;
    const LOG_INDEXING_BEGIN_END = 64;
    const LOG_CRAWLER_REQUEST = 128;
    const LOG_PARSER_DOCUMENT_BRIEF = 256;
    const LOG_PARSER_DOCUMENT_VERBOSE = 512;
    const LOG_PARSER_DOCUMENT_LINKS = 1024;
    const LOG_SCHEDULER_START = 2048;
    const LOG_INDEXING_CONTENT_ERROR = 4096;
    // NB: при добавлении константы обновить self::$log_strings

    const LOG_NOTHING = 0;
    const LOG_ALL = 69631;
    const LOG_CONSOLE = 68095; // self::LOG_ALL ^ self::LOG_PARSER_DOCUMENT_VERBOSE ^ self::LOG_PARSER_DOCUMENT_LINKS;
    const LOG_ALL_ERRORS = 4103;  // self::LOG_ERROR | self::LOG_PHP_ERROR | self::LOG_PHP_EXCEPTION

    const INDEXING_BROWSER = 1; // запуск в браузере
    const INDEXING_NC_CRON = 2; // то, что называется "кроном" в неткете
    const INDEXING_CONSOLE = 3;
    const INDEXING_CONSOLE_BATCH = 4;

    const SEARCH_RESULT_COMPONENT_KEYWORD = 'netcat_module_search_result_by_type';
    const SEARCH_RESULT_COMPONENT_TEMPLATE_FORM_KEYWORD = 'search_field';
    const SEARCH_RESULT_COMPONENT_TEMPLATE_DEFAULT_OTHERS = 'others';
    const SEARCH_RESULT_COMPONENT_TEMPLATE_DEFAULT_GOODS = 'goods';
    const SEARCH_RESULT_COMPONENT_TEMPLATE_DEFAULT_SUBDIVISIONS = 'subdivisions';

    // запуск из консоли

    protected static $log_strings = array(
        self::LOG_ERROR => 'ERROR',
        self::LOG_PHP_EXCEPTION => 'PHP_EXCEPTION',
        self::LOG_PHP_ERROR => 'PHP_ERROR',
        self::LOG_PHP_WARNING => 'PHP_WARNING',
        self::LOG_SCHEDULER_START => 'SCHEDULER_START',
        self::LOG_INDEXING_BEGIN_END => 'INDEXING_BEGIN_END',
        self::LOG_CRAWLER_REQUEST => 'CRAWLER_REQUEST',
        self::LOG_INDEXING_NO_SUB => 'INDEXING_NO_SUB',
        self::LOG_PARSER_DOCUMENT_LINKS => 'PARSER_DOCUMENT_LINKS',
        self::LOG_PARSER_DOCUMENT_BRIEF => 'PARSER_DOCUMENT_BRIEF',
        self::LOG_PARSER_DOCUMENT_VERBOSE => 'PARSER_DOCUMENT_VERBOSE',
        self::LOG_INDEXING_CONTENT_ERROR => 'INDEXING_CONTENT_ERROR',
    );

    /**
     * Получить экземпляр класса nc_search_ui (который $nc_search)
     */
    public static function get_object() {
        if (!self::$instance) {
            self::$instance = new nc_search_ui();
        }
        return self::$instance;
    }

     /**
     * Создает cron задачу для индексации сайта, если ранее не создана
     * Создает правило индексации всех областей сайта
     * Запускает индексацию в фоновом режиме
     *
     * @param int $site_id
     * @param array $parameters
     *
     * @return void
     */
    public static function process_site_indexing($site_id, array $parameters = array()) {
        $indexer_secret_key = nc_core::get_object()->get_settings("IndexerSecretKey", "search");

        if (!$indexer_secret_key) {
            return;
        }

        try {
            $name = nc_array_value($parameters, "name", "Auto_Indexing_All_Site_$site_id");
            $rules_with_same_name = nc_search::load(
                "nc_search_rule",
                "SELECT * FROM `%t%` WHERE `Name` LIKE '%$name%' AND `Catalogue_ID` = $site_id"
            );

            if ($rules_with_same_name->count()) {
                $rules_with_same_name->each("delete");
            }

            $rule_id = nc_search::get_object()->create_search_rule(
                array(
                    "site_id" => $site_id,
                    "name" => $name,
                    "interval" => nc_array_value($parameters, "interval", 1),
                    "interval_type" => nc_array_value($parameters, "interval_type", "day"),
                    "hour" => nc_array_value($parameters, "hour", 8),
                    "minute" => nc_array_value($parameters, "minute", 0),
                )
            );

            $cron_script_url = "/netcat/modules/search/indexing/netcat_cron.php?secret_key=$indexer_secret_key";

            require_once(nc_core::get_object()->ADMIN_FOLDER . "crontasks.inc.php");

            if (!IsCrontasksExist($cron_script_url)) {
                CronCompleted(0, 0, 1, 0, $cron_script_url);
            }

            nc_search_indexer::index_area((string) $rule_id);
        } catch (nc_search_exception $ignored) {
        }
    }

    /**
     * Первый запуск модуля
     */
    protected static function first_run() {
        // (1) IndexerSecretKey
        self::save_setting('IndexerSecretKey', sha1(mt_rand() . time()), false);

        // (2) robots.txt sitemap link
        $robots = new nc_search_robots;
        $path = nc_core::get_object()->SUB_FOLDER;
        foreach (array_keys(nc_Core::get_object()->catalogue->get_all()) as $site_id) {
            $robots->add_directive($site_id, "Sitemap: $path/sitemap.xml");
            $robots->save_robots_txt($site_id);
        }

        // (3) инициализация поисковой службы
        self::get_provider()->first_run();
    }

    /**
     * Инициализация модуля
     */
    public static function init() {
        // class autoload
        nc_core()->register_class_autoload_path('nc_search_', __DIR__ . '/lib', false);

        // first run?
        if (!self::get_setting('IndexerSecretKey')) {
            self::first_run();
            // загрузить настройки заново после first_run()
            nc_Core::get_object()->get_settings('', 'search', true);
            self::$settings = null;
        }

        // logging:
        self::register_logger(new nc_search_logger_database);

        // events for updating the robots.txt
        /** @var nc_event $event_manager */
        if (nc_core('admin_mode')) {
            $event_manager = nc_Core::get_object()->event;
            $robots = new nc_search_robots;
            $event_manager->bind($robots, array(nc_Event::AFTER_SITE_IMPORTED => 'update_site'));
            $event_manager->bind($robots, array(nc_Event::AFTER_SITE_CREATED => 'update_site'));
            $event_manager->bind($robots, array(nc_Event::AFTER_SITE_UPDATED => 'update_site'));
            $event_manager->bind($robots, array(nc_Event::AFTER_SUBDIVISION_CREATED => 'update_sub'));
            $event_manager->bind($robots, array(nc_Event::AFTER_SUBDIVISION_UPDATED => 'update_sub'));
            $event_manager->bind($robots, array(nc_Event::AFTER_SUBDIVISION_DELETED => 'delete_sub'));
        }

        // global $nc_search variable
        $GLOBALS['nc_search'] = self::get_object();
    }

    /**
     * Load script from the 'lib/3rdparty' folder
     * @param string $path path to the script without the starting slash
     */
    public static function load_3rdparty_script($path) {
        $path = self::get_3rdparty_path()."/$path";
        require_once($path);
    }

    /**
     * Путь к модулю (без trailing slash)
     * @return string
     */
    public static function get_module_path() {
        return __DIR__;
    }

    /**
     * @return string
     */
    public static function get_3rdparty_path() {
        return __DIR__ . '/lib/3rdparty';
    }

    /**
     * Path to the module folder on the site
     * NB, no trailing slash
     */
    public static function get_module_url() {
        return rtrim(nc_module_path('search'), '/');
    }

    /**
     * Метод для передачи текущего контекста в глубины компонентов, работающих
     * внутри сторонних библиотек (фильтры Zend_Search_Lucene тому примером).
     * Некрасиво, но что поделаешь... [Можно будет избавиться, если будет
     * собственный парсер запросов]
     * @param nc_search_context|null $context
     */
    public static function set_current_context(nc_search_context $context = null) {
        self::$current_context = $context;
    }

    /**
     * @throws nc_search_exception
     * @return nc_search_context
     */
    public static function get_current_context() {
        if (!isset(self::$current_context)) {
            throw new nc_search_exception('nc_search::get_current_context(): current context is unknown');
        }
        return self::$current_context;
    }

    /**
     * Возвращает объект, реализующий интерфейс nc_search_provider
     * @return nc_search_provider
     */
    public static function get_provider() {
        if (!self::$provider) {
            $provider_class = self::get_setting('SearchProvider');
            self::$provider = new $provider_class;
        }
        return self::$provider;
    }

    /**
     * Возвращает имя класса, который используется для экспорта и импорта данных,
     * специфичных для поставщика службы поиска.
     * Данный класс должен иметь имя: «ИМЯ_ПРОВАЙДЕРА_backup» (например: nc_search_provider_zend_backup).
     * Класс должен расширять nc_backup_extension.
     * @return null|string
     */
    public static function get_provider_backup_class_name() {
        $provider = self::get_setting('SearchProvider');
        $backup_class_name = $provider . '_backup';
        return class_exists($backup_class_name) ? $backup_class_name : null;
    }

    /**
     * Добавляет в расписания (scheduler_intent) переиндексацию указанной области
     * (или правила) в указанное время
     * @param string $area_string area OR rule_id
     *   (damn it, не нужно было следовать ТЗ)
     * @param string $when   Any string strtotime will understand, e.g. "now", "22:50", "2020-01-01 00:05", "next tuesday"
     * @throws nc_search_exception
     */
    public static function index_area($area_string = 'allsites', $when = 'now') {
        if (self::should('EnableSearch')) {
            self::get_provider()->schedule_indexing($area_string, strtotime($when));
        } else {
            throw new nc_search_exception('Search module is disabled');
        }
    }

    /**
     *
     * @param string|nc_search_query $query
     * @param boolean $highlight_matches
     * @throws nc_search_exception
     * @return nc_search_result
     */
    public static function find($query, $highlight_matches = true) {
        if (self::should('EnableSearch')) {
            if (is_string($query)) { $query = new nc_search_query($query); }
            nc_search_util::set_utf_locale($query->get('language'));
            $result = self::get_provider()->find($query, $highlight_matches);
            nc_search_util::restore_locale();

            return $result;
        }

        throw new nc_search_exception('Search module is disabled');
    }

    //----------------- РАБОТА С НАСТРОЙКАМИ МОДУЛЯ ---------------------------

    /**
     * Инициализация, получение объекта настроек (which is a singleton)
     * @return nc_search_settings
     */
    protected static function get_settings_object() {
        if (!self::$settings) {
            self::$settings = new nc_search_settings();
        }
        return self::$settings;
    }

    /**
     * Получение значения параметра настроек
     *
     * @param string $option_name
     * @return mixed
     */
    public static function get_setting($option_name) {
        return self::get_settings_object()->get($option_name);
    }

    /**
     * Сокращение для проверки значения параметра в настроек на правдивость.
     * Возвращает true, если значение опции равно истине.
     *
     * Нестандартное название обусловлено тем, что оно позволяет составлять короткие
     * условия, относительно правильные с точки зрения грамматики английского
     * языка:
     *    if (nc_search::should('AllowTermBoost')) { do_something()); }
     *    // ≈ "Should we allow the term boost?"
     *
     * @param string $option_name
     * @return boolean
     */
    public static function should($option_name) {
        return self::get_setting($option_name) == true;
    }

    /**
     * Установка параметра
     * Значение не сохраняется в БД, если не вызван метод nc_search::save_settings()
     *
     * @param string $option_name
     * @param mixed $value
     * @return mixed
     */
    public static function set_setting($option_name, $value) {
        self::get_settings_object()->set($option_name, $value);
    }

    /**
     * Сохранение настройки модуля
     *
     * @param string $option_name
     * @param scalar $value
     * @param bool $check_permissions false, если изменения делаются от имени системы, а не пользователя
     */
    public static function save_setting($option_name, $value, $check_permissions = true) {
        self::get_settings_object()->save($option_name, $value, $check_permissions);
    }

    /**
     * shortcut for nc_search_data_persistent_collection::load_all()
     * @param string $data_class
     * @param boolean $force_reload
     * @param string $index_by присвоить ключам элементов коллекции значение опции $index_property
     * @return nc_search_data_persistent_collection
     */
    public static function load_all($data_class, $force_reload = false, $index_by = null) {
        return nc_search_data_persistent_collection::load_all($data_class, $force_reload, $index_by);
    }

    /**
     * shortcut for nc_search_data_persistent_collection::load()
     * @param string $data_class
     * @param string $query SQL query
     * @param string $index_by присвоить ключам элементов коллекции значение опции $index_property
     * @return nc_search_data_persistent_collection
     */
    public static function load($data_class, $query, $index_by = null) {
        return nc_search_data_persistent_collection::load($data_class, $query, $index_by);
    }

    //-------------------------- ОБРАБОТКА ОШИБОК --------------------------------

    /**
     *
     * @param nc_search_logger $logger
     */
    public static function register_logger(nc_search_logger $logger) {
        self::$loggers[] = $logger;
        self::$max_log_level |= $logger->get_level();
    }

    /**
     * Для оптимизации в тех местах, где для логирования выполняются затратные вычисления
     * @param integer $type  log level (self::LOG_* constant)
     * @return boolean       whether this log level is enabled
     */
    public static function will_log($type) {
        return (bool) ($type & self::$max_log_level);
    }

    /**
     *
     * @param integer $type
     * @param string $message
     */
    public static function log($type, $message) {
        foreach (self::$loggers as $logger) {
            $logger->notify($type, self::$log_strings[$type], $message);
        }
    }

    /**
     *
     * @return array;
     */
    public static function get_log_types() {
        return self::$log_strings;
    }

    /**
     * Включить запись ошибок и исключений в лог при выполнении скрипта в "кроне"
     */
    public static function enable_error_logging() {
        set_error_handler(array('nc_search', 'error_handler'), error_reporting());
        set_exception_handler(array('nc_search', 'exception_handler'));
    }

    /**
     * Обработчик ошибок для записи ошибок в лог при выполнении скрипта в "кроне"
     */
    public static function error_handler($errno, $errstr) {
        if (error_reporting() == 0) {
            return false;
        } // error messages suppressed with an @
        if ($errno == E_WARNING || $errno == E_USER_WARNING) {
            $type = self::LOG_PHP_WARNING;
        } else if ($errno == E_ERROR || $errno = E_USER_ERROR) {
            $type = self::LOG_PHP_ERROR;
        } else {
            return false;
        }
        try {
            self::log($type, $errstr);
        } catch (Exception $e) {
            print $errstr;
            print "\nEXCEPTION WHILE TRYING TO LOG THE ERROR: {$e->getMessage()}";
        }
        return false;
    }

    /**
     * Обработчик исключений для записи исключений в лог при выполнении скрипта в "кроне"
     */
    public static function exception_handler($exception) {
        // copied from PHP.NET
        // these are our templates
        $traceline = "#%s %s(%s): %s()";
        $msg = "PHP Fatal error:  Uncaught exception '%s' with message '%s' in %s:%s\nStack trace:\n%s\n  thrown in %s on line %s";

        // alter your trace as you please, here
        $trace = $exception->getTrace();

        // build your tracelines
        $key = 0;
        $result = array();
        foreach ($trace as $key => $stackPoint) {
            $result[] = sprintf(
                $traceline,
                $key,
                $stackPoint['file'],
                $stackPoint['line'],
                $stackPoint['function']
            );
        }
        // trace always ends with {main}
        $result[] = '#' . ++$key . ' {main}';

        // write tracelines into main template
        $msg = sprintf(
            $msg,
            get_class($exception),
            $exception->getMessage(),
            $exception->getFile(),
            $exception->getLine(),
            implode("\n", $result),
            $exception->getFile(),
            $exception->getLine()
        );

        try {
            self::log(self::LOG_PHP_EXCEPTION, $msg);
        } catch (Exception $e) {
            print $msg;
            print "\nEXCEPTION WHILE TRYING TO LOG THE ORIGINAL EXCEPTION: {$e->getMessage()}";
        }
    }

    // ----------------------------- ПРОЧЕЕ --------------------------------------

    /**
     *
     * @param integer $interval
     * @param string $unit   'hours', 'days', 'months'
     */
    public static function purge_history($interval = null, $unit = null) {
        if (!$interval) {
            if (!self::should('AutoPurgeHistory')) {
                return;
            }
            $interval = self::get_setting('AutoPurgeHistoryIntervalValue');
            $unit = self::get_setting('AutoPurgeHistoryIntervalUnit');
        }
        if (!$interval || !$unit) {
            return;
        }

        $time = nc_search_util::sql_datetime(strtotime("-$interval $unit"));
        nc_Core::get_object()->db->query("DELETE FROM `Search_Query` WHERE `Timestamp` < '$time'");
    }

    /**
     *
     */
    public static function purge_log() {
        $days_to_keep = self::get_setting('DaysToKeepEventLog');
        $time = nc_search_util::sql_datetime(strtotime("-$days_to_keep days"));
        nc_Core::get_object()->db->query("DELETE FROM `Search_Log` WHERE `Timestamp` < '$time'");
    }

    /**
     * @return array
     */
    protected static function check_sites_language() {
        $nc_core = nc_Core::get_object();
        $sites_without_language = array();

        $nc_core->catalogue->load_all();
        foreach ($nc_core->catalogue->get_all() as $site) {
            $admin_path = nc_core('ADMIN_PATH');
            if (!$site['Language']) {
                $link_to_catalogue = "$admin_path#catalogue.edit($site[Catalogue_ID])";
                $catalogue_name = htmlspecialchars($site['Catalogue_Name'], ENT_QUOTES);
                $sites_without_language[] = "<a href='$link_to_catalogue' target='_blank'>$catalogue_name</a>";
            }
        }

        if ($sites_without_language) {
            $error_message = (count($sites_without_language) > 1)
                ? NETCAT_MODULE_SEARCH_SITE_WITHOUT_LANGUAGE_ERROR
                : NETCAT_MODULE_SEARCH_SITES_WITHOUT_LANGUAGE_ERROR;
            nc_print_status($error_message, 'error', array(implode(', ', $sites_without_language)));
        }

        return $sites_without_language;
    }

    /**
     * Проверка правильности настроек сервера, выводится на странице «Информация»
     * в панели управления модулем. Метод должен вывести сообщения об ошибках
     * (используйте фунцию nc_print_status())
     */
    public static function check_environment() {
        self::check_sites_language();
        $nc_core = nc_Core::get_object();
        $there_is_https_site = false;
        $catalogue_list = $nc_core->catalogue->get_all();

        foreach ($catalogue_list as $catalogue) {
            if ($catalogue['ncHTTPS']) {
                $there_is_https_site = true;
                break;
            }
        }


        if ($there_is_https_site && !extension_loaded('openssl')) {
            nc_print_status(NETCAT_MODULE_SEARCH_NO_OPENSSL_EXTENSION_ERROR, 'error');
        }
    }
    
    /**
     * @return int|false
     */
    protected static function get_search_result_component_id() {
        try {
            return nc_core::get_object()->component->get_by_id(self::SEARCH_RESULT_COMPONENT_KEYWORD, 'Class_ID');
        } catch (nc_Exception_Class_Doesnt_Exist $e) {
            return false;
        }
    }
    
    /**
     * @return int|null
     */
    protected static function get_search_result_template_id($search_component_id, $type) {
        $nc_core = nc_core::get_object();
        $search_result_template_id = $nc_core->component->get_component_template_by_keyword($search_component_id, $type, 'Class_ID');

        return $search_result_template_id;
    }

     /**
     * Создаёт раздел поиска на сайте на сайте.
     * @return int|false
     * @throws Exception
     */
    public static function create_search_result_subdivision($site_id) {
        $nc_core = nc_core::get_object();
        $search_result_component_id = self::get_search_result_component_id();
        if (!$search_result_component_id) {
            return false;
        }


        if ($nc_core->catalogue->get_by_id($site_id, 'Search_Result_Sub_ID')) {
            return false;
        }

        $search_result_component_form_id = self::get_search_result_template_id($search_result_component_id, self::SEARCH_RESULT_COMPONENT_TEMPLATE_FORM_KEYWORD);
        $search_result_component_pages_id = self::get_search_result_template_id($search_result_component_id, self::SEARCH_RESULT_COMPONENT_TEMPLATE_DEFAULT_OTHERS);

        $subdivision_id = $nc_core->subdivision->create(array(
            'Catalogue_ID' => $site_id,
            'Subdivision_Name' => NETCAT_MODULE_SEARCH_SUBDIVISION_NAME,
            'EnglishName' => 'search',
            'Priority' => 1,
            'Checked' => 0,
        ));

        // форма поиска
        if ($search_result_component_form_id) {
            $nc_core->sub_class->create($search_result_component_id, array(
                'Subdivision_ID' => $subdivision_id,
                'Sub_Class_Name' => NETCAT_MODULE_SEARCH_INFOBLOCK_FORM_NAME,
                'Class_Template_ID' => $search_result_component_form_id,
                'EnglishName' => 'form',
            ));
        }
        // для редакций с интернет-магазином
        if (nc_module_check_by_keyword('netshop')) {
            // поиск по товарам
            $search_result_component_goods_id = self::get_search_result_template_id($search_result_component_id, self::SEARCH_RESULT_COMPONENT_TEMPLATE_DEFAULT_GOODS);
            if ($search_result_component_goods_id) {
                $nc_core->sub_class->create($search_result_component_id, array(
                    'Subdivision_ID' => $subdivision_id,
                    'Sub_Class_Name' => NETCAT_MODULE_SEARCH_INFOBLOCK_GOODS_NAME,
                    'Class_Template_ID' => $search_result_component_goods_id,
                    'EnglishName' => 'goods',
                ));
            }

            // поиск по разделам
            $search_result_component_subdivisions_id = self::get_search_result_template_id($search_result_component_id, self::SEARCH_RESULT_COMPONENT_TEMPLATE_DEFAULT_SUBDIVISIONS);
            if ($search_result_component_subdivisions_id) {
                $nc_core->sub_class->create($search_result_component_id, array(
                    'Subdivision_ID' => $subdivision_id,
                    'Sub_Class_Name' => NETCAT_MODULE_SEARCH_INFOBLOCK_SUBDIVISIONS_NAME,
                    'Class_Template_ID' => $search_result_component_subdivisions_id,
                    'EnglishName' => 'subdivisions',
                ));
            }
        }

        // поиск по страницам
        if ($search_result_component_pages_id) {
            $nc_core->sub_class->create($search_result_component_id, array(
                'Subdivision_ID' => $subdivision_id,
                'Sub_Class_Name' => NETCAT_MODULE_SEARCH_INFOBLOCK_OTHERS_NAME,
                'Class_Template_ID' => $search_result_component_pages_id,
                'EnglishName' => 'others',
            ));
        }

        nc_db_table::make('Catalogue')->where_id($site_id)->update(array(
            'Search_Result_Sub_ID' => $subdivision_id,
        ));

        return $subdivision_id;
    }
}
