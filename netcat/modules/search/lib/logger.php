<?php

/* $Id: logger.php 6209 2012-02-10 10:28:29Z denis $ */

/**
 *
 */
abstract class nc_search_logger {

    protected $level;

    /**
     * Логгер может иметь собственный уровень сообщений об ошибках.
     * @param int $level
     */
    public function __construct($level = null) {
        $this->level = $level ? $level : nc_search::get_setting('LogLevel');
    }

    /**
     * @see nc_search::log()
     * @param integer $level
     * @param string $type_string
     * @param string $message
     */
    public function notify($level, $type_string, $message) {
        if ($level & $this->level) {
            $this->log($type_string, $message);
        }
    }

    /**
     *
     * @return integer
     */
    public function get_level() {
        return $this->level;
    }

    /**
     * 
     * @param string $type_string
     * @param string $message
     */
    abstract protected function log($type_string, $message);
}