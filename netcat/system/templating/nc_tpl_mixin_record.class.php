<?php

abstract class nc_tpl_mixin_record extends nc_record {

    protected $property_file_loaded = array();

    /** @var array список языкозависимых свойств, считываемых из файлов */
    protected $properties_read_language = array();
    /** @var array список свойств, считываемых из файлов */
    protected $properties_read = array();
    /** @var array список свойств, заданных в виде PHP-кода (return array) */
    protected $properties_include = array();

    /**
     * @param string $property
     * @return mixed
     */
    public function get($property) {
        $value = parent::get($property);
        if ($value === null && !isset($this->property_file_loaded[$property])) {
            $value = $this->load_property($property);
        }

        if ($value === null) {
            // defaults
            if ($property === 'name') {
                return $this->get_default_name();
            }
            if ($property === 'priority') {
                return PHP_INT_MAX;
            }
        }

        return $value;
    }

    /**
     * @return string
     */
    abstract protected function get_default_name();

    /**
     * @param $property
     * @return mixed
     */
    protected function load_property($property) {
        $this->property_file_loaded[$property] = true;

        $path = $this->get('path');
        $value = null;

        if (isset($this->properties_read[$property])) {
            $file_name = $this->properties_read[$property];
            if (substr($file_name, -3) === '.js') {
                $min_file_name = substr($file_name, 0, -3) . '.min.js';
                if (file_exists("$path/$min_file_name")) {
                    $file_name = $min_file_name;
                }
            }
            if (file_exists("$path/$file_name")) {
                $value = file_get_contents("$path/$file_name");
            }
        } else if (isset($this->properties_read_language[$property])) {
            $value = $this->load_language_dependent_property($property);
        } else if (isset($this->properties_include[$property]) && file_exists($path . '/' . $this->properties_include[$property])) {
            $value = include($path . '/' . $this->properties_include[$property]);
        }

        $this->set($property, $value);
        return $value;
    }

    /**
     * @param $property
     * @return null|string
     */
    protected function load_language_dependent_property($property) {
        $nc_core = nc_core::get_object();
        $language = $nc_core->lang->detect_lang(true);

        $path_prefix = $this->get('path') . '/' . $this->properties_read_language[$property];
        $file = null;

        // lang -> en -> ru
        foreach (array($language, 'en', 'ru') as $path_suffix) {
            if (file_exists("$path_prefix.$path_suffix")) {
                $file = "$path_prefix.$path_suffix";
                break;
            }
        }
        if ($file) {
            $value = file_get_contents($file);
            $value = preg_replace('/^\xEF\xBB\xBF/', '', $value); // remove BOM
            if (!$nc_core->NC_UNICODE) {
                $value = $nc_core->utf8->utf2win($value);
            }
        } else {
            $value = null;
        }

        return $value;
    }

}