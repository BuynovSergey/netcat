<?php

/**
 * Объект для сбора параметров пути во время генерирования строки пути
 */
class nc_routing_pattern_parameters {
    public $used_variables = array();
    public $action;
    public $format;
}