<?php

/**
 * Класс для «ленивого» вычисления путей
 * (в nc_objects_list() генерируются ссылки для всех возможных
 * действий над объектами, в то время как обычно они не используются)
 */
class nc_routing_path {

    protected $resource_type;
    protected $resource_parameters;
    protected $add_domain; // для оптимизации nc_object_url()
    protected $site_id;

    protected $path_string;

    /**
     * @param string $resource_type
     * @param array $resource_parameters
     * @param bool $add_domain
     */
    public function __construct($resource_type, array $resource_parameters, $add_domain = false) {
        $this->resource_type = $resource_type;
        $this->resource_parameters = $resource_parameters;
        $this->add_domain = $add_domain;
    }

    /**
     * @return string
     */
    public function get_resource_type() {
        return $this->resource_type;
    }

    /**
     * @return array
     */
    public function get_resource_parameters() {
        return $this->resource_parameters;
    }

    /**
     * @param $name
     * @return mixed
     */
    public function get_resource_parameter($name) {
        return isset($this->resource_parameters[$name]) ? $this->resource_parameters[$name] : null;
    }

    /**
     * @param string $name
     * @return mixed
     */
    public function get_variable($name) {
        if (!isset($this->resource_parameters['variables'][$name])) {
            return null;
        }
        return $this->resource_parameters['variables'][$name];
    }

    /**
     * @return array|false
     */
    protected function prepare_resource_parameters() {
        return $this->resource_parameters;
    }

    /**
     *
     */
    protected function get_path_string() {
        if (!$this->resource_parameters) {
            $resource_parameters = $this->prepare_resource_parameters();
            if ($resource_parameters === false) {
                return '';
            }
            else {
                $this->resource_parameters = $resource_parameters;
            }
        }

        if (isset($this->resource_parameters['date'])) {
            $this->resource_parameters['date'] = nc_filter_date_for_path($this->resource_parameters['date']);
        }

        $routes = nc_routing::get_routes_by_resource_type($this->resource_parameters['site_id'], $this->resource_type);

        foreach ($routes as $route) {
            $path_string = $route->get_path_string_for($this);
            if ($path_string !== false) {
                return $path_string;
            }
        }

        return false;
    }

    /**
     *
     */
    public function __toString() {
        if ($this->path_string === null) {
            $this->path_string = $this->get_path_string();
            // сохраняем site_id, он может пригодиться
            $this->site_id = isset($this->resource_parameters['site_id']) ? $this->resource_parameters['site_id'] : null;
            // избавляемся от ставших ненужными параметров
            $this->resource_parameters = null;
        }

        if ($this->add_domain && strlen($this->path_string)) {
            return nc_Core::get_object()->catalogue->get_url_by_id($this->site_id) . $this->path_string;
        }

        return (string)$this->path_string;
    }

}