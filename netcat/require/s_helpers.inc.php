<?php

/**
 * Функции-хелперы для быстрого доступа к объектам системы
 */

/**
 * Быстрый доступ к объекту nc_Core;
 *
 * @param string|null $sub_object
 * @return mixed
 */
function nc_core($sub_object = null) {
    static $nc_core;

    if ($nc_core === null) {
        $nc_core = nc_core::get_object();
    }

    if ($sub_object) {
        return $nc_core->$sub_object;
    }

    return $nc_core;
}

/**
 * Быстрый доступ к объекту nc_Modules;
 *
 * @param string|null $keyword
 * @return mixed
 */
function nc_modules($keyword = null) {
    if ($keyword) {
        return nc_core('modules')->$keyword;
    }

    return nc_core('modules');
}

/**
 * Быстрый доступ к объекту nc_db
 *
 * @return nc_db
 */
function nc_db() {
    return nc_core('db');
}

/**
 * Путь в файловой системе к папке с модулями (или с конкретным модулем)
 */
function nc_module_folder() {
    $path = '';
    if (func_num_args()) {
        $args = func_get_args();
        $path = implode(DIRECTORY_SEPARATOR, $args) . DIRECTORY_SEPARATOR;
    }

    return nc_core('MODULE_FOLDER') . $path;
}

/**
 * @param array $path
 * @param array $http_query
 * @return string
 */
function nc_get_action_url(array $path = array(), array $http_query = array()) {
    $path = $path ? implode('/', $path) . '/?' : '?';
    $http_query = $http_query ? http_build_query($http_query, null, '&') : null;
    return nc_module_path() . $path . $http_query;
}

/**
 * Путь (от корня сайта) к папке с модулями.
 * E.g. nc_module_path('auth') → '/netcat/modules/auth/'
 *
 * @return string
 */
function nc_module_path() {
    $path = '';
    if (func_num_args()) {
        $args = func_get_args();
        $path = implode('/', $args) . '/';
    }

    return nc_core('SUB_FOLDER') . nc_core('HTTP_ROOT_PATH') . 'modules/' . $path;
}

/**
 * Проверяет PHP-код перед eval.
 * Шорткат для nc_core::get_object()->security->php_filter->filter($code).
 *
 * @param string $code
 * @return $code
 */
function nc_check_eval($code) {
    return nc_core::get_object()->security->php_filter->filter($code);
}

/**
 * Путь (от корня сайта) к папке компонента или шаблона компонента (с '/' на конце).
 *
 * @param int $component_or_template_id идентификатор шаблона компонента (или компонента,
 *   если нужен путь к папке компонента)
 * @return string
 */
function nc_component_path($component_or_template_id) {
    $nc_core = nc_core::get_object();
    return $nc_core->SUB_FOLDER .
           $nc_core->HTTP_TEMPLATE_PATH .
           'class' .
           $nc_core->component->get_by_id($component_or_template_id, 'File_Path');
}

/**
 * Проверяет, что переданная строка является датой, подходящей для построения пути (валидные значения месяца и дня,
 * год с 1900 по 2099): если является, возвращает дату в формате "Y-m-d", иначе возвращает null
 *
 * @param string $date
 * @return string|null
 */
function nc_filter_date_for_path($date) {
    if (!$date) {
        return null;
    }

    try {
        $datetime = new DateTime($date);
    } catch (Exception $exception) {
        return null;
    }

    $last_errors = $datetime::getLastErrors();
    if (is_array($last_errors) && ($last_errors['warning_count'] + $last_errors['error_count'])) {
        return null;
    }

    $year = $datetime->format('Y');

    return $year >= 1900 && $year <= 2099 ? $datetime->format('Y-m-d') : null;
}