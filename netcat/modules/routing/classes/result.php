<?php

abstract class nc_routing_result {

    /** @var string Неразобранная часть пути */
    protected $path_remainder = '';

    /** @var array Параметры ресурса (идентификаторы и т.п.) */
    protected $parameters = array();

    /** @var array Дополнительные переменные */
    protected $variables = array();

    /** @var array Массив с названиями параметров, которые определяют уникальность ресурса */
    protected $match_parameters = array();

    /** @var bool Свойство маршрута 'query_variables_required_for_canonical', будет передано в ответ */
    protected $variables_required_for_canonical = true;

    /** @var bool Точное совпадение (для объектов: совпадает инфоблок) */
    protected $is_exact_match = true;

    /**
     * @param string $path
     * @param nc_routing_route $route
     */
    public function __construct($path, nc_routing_route $route) {
        $this->path_remainder = $path;

        $default_resource_parameters = $route->get('resource_parameters');
        if (is_array($default_resource_parameters)) {
            $this->parameters = array_merge($this->parameters, $default_resource_parameters);
        }

        $default_query_variables = $route->get('query_variables');
        if (is_array($default_query_variables)) {
            $this->variables = $default_query_variables;
        }

        $this->variables_required_for_canonical = (bool)$route->get('query_variables_required_for_canonical');
    }

    /**
     * @return bool
     */
    public function is_resolved() {
        return (strlen($this->get_remainder()) == 0) &&
               $this->has_all_resource_parameters();
    }

    /**
     * @return bool
     */
    abstract protected function has_all_resource_parameters();

    /**
     * @param string $remainder
     */
    public function set_remainder($remainder) {
        $this->path_remainder = $remainder;
    }

    /**
     * @return string
     */
    public function get_remainder() {
        return $this->path_remainder;
    }

    /**
     * @param int $length
     */
    public function truncate_remainder($length) {
        $this->path_remainder = substr($this->path_remainder, $length);
    }

    /**
     * @param string $parameter
     * @param mixed $value
     */
    public function set_resource_parameter($parameter, $value) {
        $this->parameters[$parameter] = $value;
    }

    /**
     * @param $parameter
     * @return mixed
     */
    public function get_resource_parameter($parameter) {
        return nc_array_value($this->parameters, $parameter);
    }

    /**
     *
     */
    public function set_variable($variable, $value) {
        $this->variables[$variable] = $value;
    }

    /**
     *
     */
    public function get_variables() {
        return $this->variables;
    }

    /**
     * @return int|null
     */
    public function get_infoblock_id() {
        $infoblock_id = $this->get_resource_parameter('infoblock_id');

        if (!$infoblock_id) {
            $folder_id = $this->get_resource_parameter('folder_id');
            if ($folder_id) {
                return nc_core::get_object()->sub_class->get_first_checked_id_by_subdivision_id($folder_id);
            }
            // else return null below
        }

        return $infoblock_id;
    }

    /**
     *
     */
    public function get_action() {
        $action = $this->get_resource_parameter('action');
        if ($action) { return $action; }

        // no $action, but there is an $infoblock_id
        $infoblock_id = $this->get_infoblock_id();
        if ($infoblock_id) {
            return nc_core::get_object()->sub_class->get_by_id($infoblock_id, 'DefaultAction');
        }

        return null;
    }

    /**
     * @return bool
     */
    public function is_exact_match() {
        return $this->is_exact_match;
    }

    /**
     * @param bool $is_exact_match
     */
    public function set_is_exact_match($is_exact_match) {
        $this->is_exact_match = (bool)$is_exact_match;
    }

    /**
     *
     */
    public function to_array() {
        $result = array(
                    'resource_type' => substr(get_class($this), strlen(__CLASS__)+1),
                    'site_id' => null,
                    'folder_id' => $this->get_resource_parameter('folder_id'),
                    'infoblock_id' => $this->get_infoblock_id(),
                    'object_id' => $this->get_resource_parameter('object_id'),
                    'object_keyword' => $this->get_resource_parameter('object_keyword'),
                    'action' => $this->get_action(),
                    'format' => $this->get_resource_parameter('format'),
                    'date' => $this->get_resource_parameter('date'),
                    'variables' => $this->get_variables(),
                    'variables_required_for_canonical' => $this->variables_required_for_canonical,
                    'script_path' => $this->get_resource_parameter('script_path'),
                    'redirect_to_url' => null,
        );

        return $result;
    }

}