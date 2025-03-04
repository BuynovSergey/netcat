<?php

/**
 * Хранит информацию о маршрутах любого типа
 */
class nc_routing_route extends nc_record {

    protected $properties = array(
        'id' => null,
        'site_id' => null,
        'description' => '',
        'allow_get' => true,
        'allow_post' => true,
        'is_builtin' => false, // is built-in? является стандартным (системным) маршрутом системы?
        'pattern' => '',
        'compiled_pattern' => null,
        'resource_type' => '',
        'resource_parameters' => array(),
        'query_variables' => array(),
        'query_variables_required_for_canonical' => 1,
        'priority' => 0,
        'enabled' => true,
    );

    protected $table_name = 'Routing_Route';
    protected $primary_key = 'id';
    protected $serialized_properties = array(
        'compiled_pattern',
        'resource_parameters',
        'query_variables',
    );
    protected $mapping = array(
        'id' => 'Route_ID',
        'site_id' => 'Site_ID',
        'description' => 'Description',
        'allow_get' => 'AllowGET',
        'allow_post' => 'AllowPOST',
        'is_builtin' => 'IsBuiltin',
        'pattern' => 'Pattern',
        'compiled_pattern' => 'CompiledPattern',
        'resource_type' => 'ResourceType',
        'resource_parameters' => 'ResourceParameters',
        'query_variables' => 'QueryVariables',
        'query_variables_required_for_canonical' => 'QueryVariablesRequiredForCanonical',
        'priority' => 'Priority',
        'enabled' => 'Enabled',
    );

    protected $pattern_contains_date;
    protected $pattern_contains_infoblock_action;
    protected $pattern_contains_object_keyword;
    protected $pattern_contains_object_action;

    /**
     * @param string $property
     * @param mixed $value
     * @param bool $add_new_property
     * @return $this
     */
    public function set($property, $value, $add_new_property = false) {
        if ($property == 'pattern') {
            // микро-оптимизация
            $this->pattern_contains_date = (strpos($value, "{date}") !== false || strpos($value, "{date:") !== false);
            $this->pattern_contains_infoblock_action = (strpos($value, "{infoblock_action}") !== false);
            $this->pattern_contains_object_action = (strpos($value, "{object_action}") !== false);
            $this->pattern_contains_object_keyword = (strpos($value, "{object_keyword}") !== false);
        }

        return parent::set($property, $value, $add_new_property);
    }

    /**
     * @return nc_routing_route
     * @throws nc_routing_pattern_parser_exception
     */
    public function save() {
        // Для новых записей установить наивысший (1) приоритет
        if (!$this->get_id()) {
            $this->set('priority', 1);
            $site_id = (int)$this->get('site_id');
            nc_db()->query(
                "UPDATE `{$this->table_name}`
                    SET `Priority` = `Priority` + 1
                  WHERE `Site_ID` = $site_id"
            );
        }

        $this->set('compiled_pattern', new nc_routing_pattern($this->get('pattern')));

        return parent::save();
    }

    /**
     * @throws Exception
     * @throws nc_record_exception
     * @return static
     */
    public function delete() {
        $site_id = (int)$this->get('site_id');
        $priority = (int)$this->get('priority');

        try {
            parent::delete();

            if ($site_id && $priority) {
                nc_db()->query(
                    "UPDATE `{$this->table_name}`
                        SET `Priority` = `Priority` - 1
                      WHERE `Site_ID` = $site_id
                        AND `Priority` > $priority"
                );
            }
        }
        catch (nc_record_exception $e) {
            throw $e;
        }

        return $this;
    }


    /**
     * @return string
     */
    public function get_resource_type_name() {
        return constant("NETCAT_MODULE_ROUTING_RESOURCE_" . strtoupper($this->get('resource_type')));
    }

    /**
     * @return nc_routing_pattern
     * @throws nc_routing_pattern_parser_exception
     */
    public function get_pattern() {
        if ($this->properties['compiled_pattern'] === null) {
            $this->properties['compiled_pattern'] = new nc_routing_pattern($this->get('pattern'));
        }

        return $this->properties['compiled_pattern'];
    }

    /**
     * @param nc_routing_request $request
     * @return nc_routing_result|false
     */
    public function resolve(nc_routing_request $request) {
        $result_class = "nc_routing_result_" . $this->get('resource_type');
        /** @var nc_routing_result $result */
        $result = new $result_class($request->get_path(), $this);
        if (!$this->get_pattern()->match($request, $result) || !$result->is_resolved()) {
            return false;
        }
        return $result;
    }

    /**
     * @param nc_routing_path $path
     * @return bool|string
     */
    public function get_path_string_for(nc_routing_path $path) {
        $requested_resource_parameters = $path->get_resource_parameters();

        // Поскольку генерирование путей, особенно при наличии большого количества маршрутов —
        // затратный процесс, для оптимизации сюда были вынесены знания о функционировании
        // некоторых типов частей шаблонов пути (в основном для генерации путей объектов —
        // их на страницах с длинными списками объектов больше всего)...

        // Оптимизация: не пытаться вычислять значение, если в пути есть {object_keyword},
        // а у объекта нет свойства для подстановки (может быть часто в списках объектов)
        if ($this->pattern_contains_object_keyword) {
            $requested_object_keyword = isset($requested_resource_parameters['object_keyword']) ? $requested_resource_parameters['object_keyword'] : false;
            if (!$requested_object_keyword && $requested_object_keyword !== '0') {
                return false; // --- RETURN ---
            }
        }

        // Эти свойства нам будут дальше нужны:
        $route_resource_type = $this->properties['resource_type'];
        $route_resource_type_is_object = $route_resource_type === 'object';
        $route_resource_parameters = $this->properties['resource_parameters'];

        // Оптимизация: не пытаться вычислять значение, если в пути есть {date},
        // а у ресурса дата не задана (отбрасываем почти половину стандартных путей),
        // и при противоположном раскладе (если не установлено, что дата является опциональной)
        $path_has_date = isset($requested_resource_parameters['date']) && $requested_resource_parameters['date'];
        $path_date_is_optional = $route_resource_type_is_object && isset($requested_resource_parameters['date_is_optional']) && $requested_resource_parameters['date_is_optional'];
        if (($this->pattern_contains_date && !$path_has_date) || (!$path_date_is_optional && $path_has_date && !$this->pattern_contains_date)) {
            return false; // --- RETURN ---
        }

        // Оптимизация: действие = "full", а в шаблоне пути есть {object_action} — совпадения не будет
        // (подразумевается, что это должен быть путь к объекту — дополнительных проверок не проводим)
        if ($this->pattern_contains_object_action && $requested_resource_parameters['action'] === 'full') {
            return false; // --- RETURN ---
        }

        // Оптимизация: путь до объекта, действие ≠ "full", в пути нет {object_action}
        // и action не задан в настройках маршрута
        if (!$this->pattern_contains_object_action && $route_resource_type_is_object && $requested_resource_parameters['action'] !== 'full' && $route_resource_parameters['action'] !== $requested_resource_parameters['action']) {
            return false; // --- RETURN ---
        }

        // То же самое для {infoblock_action} и action = "index"
        if ($this->pattern_contains_infoblock_action && $requested_resource_parameters['action'] === 'index') {
            return false; // -- RETURN ---
        }

        // Если у маршрута задан один из «определяющих» параметров ресурса,
        // он должен совпадать с параметром у $path
        if ($route_resource_parameters) {
            static $parameters_to_check_before_substitution = array('folder_id', 'infoblock_id', 'object_id', 'script_path');

            foreach ($parameters_to_check_before_substitution as $p) {
                if (isset($route_resource_parameters[$p]) && $route_resource_parameters[$p] && $route_resource_parameters[$p] != $requested_resource_parameters[$p]) {
                    return false; // --- RETURN ---
                }
            }
        }

        // «Дополнительные переменные», заданные у маршрута
        $route_variables = $this->get('query_variables');

        if (!isset($requested_resource_parameters['variables_required_for_canonical']) || $requested_resource_parameters['variables_required_for_canonical']) {
            // Если задан параметр 'route_variables', у маршрута должен быть тот же набор переменных
            $path_route_variables = $path->get_resource_parameter('route_variables');
            if ($path_route_variables) {
                foreach ($path_route_variables as $k => $v) {
                    if (!isset($route_variables[$k]) || $route_variables[$k] != $v) {
                        return false; // --- RETURN ---
                    }
                }
            }
        }

        // Подготовка объекта для сбора параметров, накопленных при подстановке частей шаблона пути
        $result_parameters = new nc_routing_pattern_parameters($this);
        if (isset($route_resource_parameters['action'])) {
            $result_parameters->action = $route_resource_parameters['action'];
        }
        if (isset($route_resource_parameters['format'])) {
            $result_parameters->format = $route_resource_parameters['format'];
        }

        // Подстановка параметров пути — вычисление результата:
        $result = $this->get_pattern()->substitute_values_for($path, $result_parameters);

        // Дополнительные проверки на соответствие результата запросу
        if ($result) {
            $requested_resource_type = $path->get_resource_type();

            if ($requested_resource_type === 'infoblock') {
                $nc_core = nc_core::get_object();

                // Если для инфоблока использован путь к разделу, необходимо проверить,
                // является ли инфоблок первым включённым
                if ($route_resource_type === 'folder') {
                    $folder_infoblock_id = $nc_core->sub_class->get_first_checked_id_by_subdivision_id($requested_resource_parameters['folder_id']);

                    if ($requested_resource_parameters['infoblock_id'] != $folder_infoblock_id) {
                        return false; // --- RETURN ---
                    }

                    $result_parameters->format = 'html';
                }

                // Если при генерировании пути не установлен параметр action,
                // то action = DefaultAction инфоблока
                if (!$result_parameters->action) {
                    try {
                        $default_action = $nc_core->sub_class->get_by_id($path->get_resource_parameter('infoblock_id'), 'DefaultAction');
                        $result_parameters->action = $default_action;
                    }
                    catch (Exception $e) {
                        return false;
                    }
                }
            }

            // Сверяем параметры запроса и результата

            // Параметры format, action могут быть у путей объектов, инфоблоков
            // и путей разделов, если последние используются как путь к инфоблоку.
            // Если нужен путь к разделу, а не к инфоблоку, эти параметры
            // не являются обязательными.
            if (!($requested_resource_type === 'folder' && $route_resource_type === 'folder') && $requested_resource_type !== 'script') {
                if ($route_resource_type_is_object && !$result_parameters->action) {
                    $result_parameters->action = 'full';
                }

                if ($requested_resource_parameters['action'] != $result_parameters->action) {
                    return false; // --- RETURN ---
                }

                if ($requested_resource_parameters['format'] != $result_parameters->format) {
                    return false; // --- RETURN ---
                }
            }

            // Добавляем отсутствующие в созданном пути переменные
            $path_variables = $path->get_resource_parameter('variables');
            if ($path_variables) {
                $extra_variables = array_diff_key($path_variables, $result_parameters->used_variables);
                foreach ($extra_variables as $k => $v) {
                    if ($v === false || $v === null || (isset($route_variables[$k]) && $route_variables[$k] == $v)) {
                        unset($extra_variables[$k]);
                    }
                }

                if ($extra_variables) {
                    $result .= "?" . http_build_query($extra_variables, null, '&');
                }
            }

            // Добавляем $SUB_FOLDER
            $result = nc_routing::$SUB_FOLDER . $result;
        }

        return $result;
    }
}