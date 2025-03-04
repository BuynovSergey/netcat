<?php

/* $Id: chain.php 6209 2012-02-10 10:28:29Z denis $ */

/**
 *
 */
class nc_search_extension_chain implements Countable {

    protected $extensions = array();
    // позволяет остановиться при применении цепочки, если результат равен $stop_value
    protected $has_stop_value = false;
    protected $stop_value;
    protected $stop_class;
    // позволяет не применять некоторые из расширений по их классу
    protected $skipped_classes = array();

    /**
     *
     * @param nc_search_extension $extension
     */
    public function add(nc_search_extension $extension) {
        $this->extensions[] = $extension;
    }

    /**
     *
     * @return integer
     */
    public function count() {
        return count($this->extensions);
    }

    /**
     *
     * @param integer $index
     * @return nc_search_extension
     */
    public function get($index) {
        return $this->extensions[$index];
    }

    /**
     *
     *  @return array
     */
    public function get_all() {
        return $this->extensions;
    }

    /**
     *
     * @return nc_search_extension|NULL
     */
    public function first() {
        return (isset($this->extensions[0]) ? $this->extensions[0] : NULL);
    }

    /**
     *
     * @param mixed $value
     * @return nc_search_extension_chain
     */
    public function stop_on($value) {
        $this->has_stop_value = true;
        $this->stop_value = $value;
        return $this;
    }

    /**
     * Применять цепочку пока не встретится $class_name
     * (удобно для проверки синонимов, стоп-слов в админке)
     *
     * @param string $class_name
     * @return nc_search_extension_chain
     */
    public function until_first($class_name) {
        $this->stop_class = $class_name;
        return $this;
    }

    /**
     * Позволяет не применять некоторые из расширений при вызове apply() по их классу
     *
     * @param string $class_name   можно несколько имён классов (любое количество параметров)
     * @return nc_search_extension_chain
     */
    public function except() {
        $args = func_get_args();
        $this->skipped_classes = array_merge($this->skipped_classes, $args);
        return $this;
    }

    /**
     *
     * @param string $method
     * @param mixed $input
     * @return mixed
     */
    public function apply($method, $input) {
        $result = $input;

        foreach ($this->extensions as $ext) {
            if ($this->stop_class && $ext instanceof $this->stop_class) {
                break;
            }
            if (in_array(get_class($ext), $this->skipped_classes)) {
                continue;
            }
            $result = call_user_func(array($ext, $method), $result);
            if ($this->has_stop_value && $result === $this->stop_value) {
                break;
            }
        }

        return $result;
    }

}