<?php

/**
 * Модуль маршрутизации
 */

class nc_routing {

    const DUPLICATE_ROUTE_NO_ACTION = 0;
    const DUPLICATE_ROUTE_REDIRECT = 1;
    const DUPLICATE_ROUTE_ADD_CANONICAL = 2;

    /** @var  nc_routing_route_collection[] */
    static protected $all_routes = array();
    /** @var  nc_routing_route_collection[] */
    static protected $enabled_routes = array();

    /** @var  array[] */
    static protected $routes_by_type = array();

    /** @var  int */
    static protected $default_site_id;

    static public $SUB_FOLDER;

    /**
     * Инициализация модуля:
     * — Инициализация автоматической загрузки классов модуля
     * — Если модуль не разу не запускался, будут созданы стандартные
     *   маршруты для всех сайтов
     * — Привязка слушателей создания и удаления сайтов
     */
    public static function init() {
        nc_core()->register_class_autoload_path('nc_routing_', dirname(__FILE__) . "/classes");

        if (!nc_routing::get_setting('IsInstalled', 0)) {
            nc_routing_route_defaults::create_for_all_sites();
            self::set_setting('IsInstalled', 1, 0);
        }

        nc_routing_listener::init();

        self::$SUB_FOLDER = nc_core::get_object()->SUB_FOLDER;
    }

    /**
     * Возвращает пути для указанного сайта
     *
     * @param int $site_id         Идентификатор сайта
     * @param bool $get_disabled   Если true, также возвращает отключённые пути
     * @param bool $reload         Если true, перезагружает данные из базы данных
     * @return nc_routing_route_collection
     */
    public static function get_routes($site_id, $get_disabled = false, $reload = false) {
        // получить включённые маршруты из коллекции всех маршрутов:
        if (!$reload && !$get_disabled && isset(self::$all_routes[$site_id])) {
            self::$enabled_routes[$site_id] = self::$all_routes[$site_id]->where('enabled', 1);
        }

        $stored = $get_disabled ? 'all_routes' : 'enabled_routes';

        if ($reload || !isset(self::${$stored}[$site_id])) {
            $query = "SELECT * FROM `%t%` WHERE `Site_ID` = " . (int)$site_id;
            if (!$get_disabled) { $query .= " AND `Enabled` = 1"; }
            $query .= " ORDER BY `Priority`";

            self::${$stored}[$site_id] = nc_record_collection::load("nc_routing_route", $query);
        }

        return self::${$stored}[$site_id];
    }

    /**
     * Возвращает массив (не коллекцию) с включёнными путями по типу ресурса (используется для
     * вычисления путей ресурсов)
     *
     * @param int $site_id
     * @param string $resource_type
     * @return nc_routing_route[]
     */
    public static function get_routes_by_resource_type($site_id, $resource_type) {
        $key = "$site_id:$resource_type";

        if (!isset(self::$routes_by_type[$key])) {
            if ($resource_type == 'infoblock') {
                // Для инфоблоков также могут также подходить пути к разделам
                self::$routes_by_type[$key] = self::get_routes($site_id)->where('resource_type', array('folder', 'infoblock'), 'IN')->to_array();
            }
            else {
                self::$routes_by_type[$key] = self::get_routes($site_id)->where('resource_type', $resource_type)->to_array();
            }
        }

        return self::$routes_by_type[$key];
    }

    /**
     * Определяет параметры ресурса для указанного запроса
     *
     * @param nc_routing_request $request
     * @return nc_routing_result|false
     */
    public static function resolve(nc_routing_request $request) {
        return self::get_routes($request->get_site_id())->resolve($request);
    }

    /**
     * Возвращает путь к разделу
     *
     * @param int $folder_id
     * @param string|null $date (формат даты: YYYY. YYYY-mm, YYYY-mm-dd)
     * @param array $query_variables
     * @return nc_routing_path
     */
    public static function get_folder_path($folder_id, $date = null, array $query_variables = null) {
        return new nc_routing_path_folder($folder_id, $date, $query_variables);
    }

    /**
     * Возвращает путь к инфоблоку
     *
     * @param int $infoblock_id
     * @param string $action
     * @param string $format
     * @param string|null $date (формат даты: YYYY, YYYY-mm, YYYY-mm-dd)
     * @param array $query_variables
     * @return nc_routing_path
     */
    public static function get_infoblock_path($infoblock_id, $action = '', $format = 'html', $date = null, array $query_variables = null) {
        return new nc_routing_path_infoblock($infoblock_id, $action, $format, $date, $query_variables);
    }

    /**
     * Возвращает путь к объекту
     *
     * @param int|string $component_id Идентификатор компонента или название системной таблицы
     * @param int|array $object_data Идентификатор или массив с заранее подготовленными параметрами
     * @param string $action
     * @param string $format
     * @param bool $add_date
     * @param array $query_variables
     * @param bool $add_domain (недокументировано, существует для оптимизации — см. nc_object_url())
     *                          Если TRUE, возвращает URL с именем домена
     * @return nc_routing_path
     */
    public static function get_object_path($component_id, $object_data, $action = 'full', $format = 'html', $add_date = false, array $query_variables = null, $add_domain = false) {
        return new nc_routing_path_object($component_id, $object_data, $action, $format, $add_date, $query_variables, $add_domain);
    }

    /**
     * Возвращает путь к ресурсу с указанными свойствами
     *
     * @param $resource_type
     * @param array $resource_parameters
     * @param bool $add_domain
     * @return nc_routing_path
     */
    public static function get_resource_path($resource_type, array $resource_parameters, $add_domain = false) {
        return new nc_routing_path($resource_type, $resource_parameters, $add_domain);
    }

    /**
     * Возвращает настройку модуля маршрутизации
     *
     * @param string $setting
     * @param int|null $site_id   Если NULL, то возвращает значение для текущего сайта
     * @return string|null
     */
    public static function get_setting($setting, $site_id = null) {
        return nc_core::get_object()
                      ->get_settings($setting, 'routing', false, $site_id);
    }

    /**
     * Устанавливает значение настройки модуля
     *
     * @param string $setting
     * @param string $value
     * @param int null $site_id
     * @return bool
     */
    public static function set_setting($setting, $value, $site_id = null) {
        return nc_core::get_object()
                      ->set_settings($setting, $value, 'routing', $site_id);
    }

}