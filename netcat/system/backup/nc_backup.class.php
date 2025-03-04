<?php

/**
 * Class nc_backup
 *
 * Параметры
 * ─────────────────────┬──────────────┬──────────────────────────────────────────────────────────────────────
 *  Название            │ По умолчанию │ Описание
 * ─────────────────────┴──────────────┴──────────────────────────────────────────────────────────────────────
 *      nc_backup::$settings
 * ───────────────────────────────────────────────────────────────────────────────────────────────────────────
 *
 *  export_limit_size     200            Ограничение на размер хранимых файлов экспорта (в мегабайтах)
 *
 *  export_limit_count    100            Ограничение на кол-во хранимых файлов экспорта
 *
 *  module_settings                      Массив с настройками модулей (из файлов backup_settings.inc.php
 *                                       в папках модулей)
 *
 * ───────────────────────────────────────────────────────────────────────────────────────────────────────────
 *      nc_backup_dumper::$import_settings
 * ───────────────────────────────────────────────────────────────────────────────────────────────────────────
 *
 *  save_ids              false          Сохранять идентификаторы при импорте?
 *
 *  disable_id_check      false          Проверять совпадение ID перед импортом? Если true, то при совпадении
 *                                       ID обновляет существующую запись.
 *
 *  remove_existing       true           Удалять файлы, уже существующие в папках компонентов при save_ids=true
 *
 * ───────────────────────────────────────────────────────────────────────────────────────────────────────────
 *      nc_backup_dumper::$export_settings
 * ───────────────────────────────────────────────────────────────────────────────────────────────────────────
 *
 *  compress              true           Сохранить результат в виде tgz-архива (true)
 *                                       или в виде набора отдельных файлов (false)
 *
 *  path                  null           Путь к сохраняемым файлам. Все файлы в папке
 *                                       будут удалены!
 *
 *  remove_existing       true           Очищать папку-приёмник (path). Если false, то также будет дополнен
 *                                       (а не перезаписан) файл info.php.
 *
 *  save_data_as_xml      false          Сохранять строки таблиц из БД в виде XML (true)
 *                                       или array_values+serialize+base64 (false)
 *
 *  file_name_suffix      null           Использовать указанную строку в качеству суффикса в
 *                                       именах .xml-файлов вместо значения первичного ключа первой записи
 *
 * Только для экспорта сайтов
 * ‾‾‾‾‾‾‾‾‾‾‾‾‾‾‾‾‾‾‾‾‾‾‾‾‾‾
 *  named_entities_path   null           Если задано, при экспорте сайта компоненты и макеты с ключевыми
 *                                       словами, списки и виджеты будут записаны в указанную папку
 *
 */

class nc_backup {

    //--------------------------------------------------------------------------

    const FILENAME_DIVIDER = '-';
    const EXPORT_FOLDER    = 'export';

    //-------------------------------------------------------------------------

    protected static $instance;

    //-------------------------------------------------------------------------

    protected $types = array(
        'site'          => SECTION_CONTROL_CONTENT_CATALOGUE,
        // 'subdivision'   => CONTROL_CONTENT_SUBDIVISION_INDEX_SECTIONS,
        'component'     => SECTION_CONTROL_CLASS,
        'template'      => SECTION_INDEX_DEV_TEMPLATES,
        'widget_class'  => SECTION_INDEX_DEV_WIDGET,
        'classificator' => CONTENT_CLASSIFICATORS,
    );

    protected $settings = array();

    protected $dumper   = null;

    protected $are_module_settings_loaded = false;

    //--------------------------------------------------------------------------

    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new nc_backup;
        }

        return self::$instance;
    }

    //-------------------------------------------------------------------------

    public function __construct() {

        // Ограничение на размер хранимых файлов экспорта
        $this->set_settings('export_limit_size', (nc_core()->get_settings('ExportLimitSize') ?: 200)); // MB

        // Ограничение на кол-во хранимых файлов экспорта
        $this->set_settings('export_limit_count', 100); // Count

        // for remove_dir
        // extract($GLOBALS);
        // require_once nc_core()->ADMIN_FOLDER . 'function.inc.php';

        $backup_class_dir = dirname(__FILE__) . '/';
        require_once $backup_class_dir . 'nc_backup_dumper_listener.interface.php';
        require_once $backup_class_dir . 'nc_backup_result.class.php';
        require_once $backup_class_dir . 'nc_backup_base.class.php';
        require_once $backup_class_dir . 'nc_backup_dumper.class.php';
        require_once $backup_class_dir . 'nc_backup_extension.class.php';
    }

    //--------------------------------------------------------------------------

    /**
     * Возвращает объект импорта/экспорта
     * Example: $nc_core->backup->template->import(...);
     * @param  string $type Тип объекта
     * @return nc_backup_base
     */
    public function __get($type) {
        $this->check_type($type);

        $backup_class_name = $this->get_type_classname($type);

        require_once $this->get_type_filepath($type);

        $this->$type = new $backup_class_name($this);

        return $this->$type;
    }

    //-------------------------------------------------------------------------

    protected function check_type($type, $throw_exception = true) {
        if (file_exists($this->get_type_filepath($type))) {
            return true;
        }

        if ($throw_exception) {
            throw new Exception("Unknown import/export type (" . htmlspecialchars($type) . ")", 1);

        }

        return false;
    }

    //-------------------------------------------------------------------------

    protected function get_type_classname($type) {
        return 'nc_backup_' . $type;
    }

    //-------------------------------------------------------------------------

    protected function get_type_filepath($type) {
        $classname = $this->get_type_classname($type);
        return nc_core()->SYSTEM_FOLDER . 'backup/types/' . $classname . '.class.php';
    }

    //--------------------------------------------------------------------------

    public function set_settings($key, $value) {
        $this->settings[$key] = $value;
    }

    //-------------------------------------------------------------------------

    public function get_settings($key = null, $default = null) {
        if ($this->are_module_settings_loaded === false && $key === 'module_settings') {
            $this->load_module_backup_settings();
        }

        if (!$key) {
            return $this->settings;
        }

        return isset($this->settings[$key]) ? $this->settings[$key] : $default;
    }


    //--------------------------------------------------------------------------

    /**
     * Загружает настройки модулей (из файла backup_settings.inc.php).
     * Использовать извне только при необходимости перезагрузить настройки (в исключительных случаях,
     * например, при установке системы).
     */
    public function load_module_backup_settings() {
        $modules = nc_modules()->get_data();
        $module_settings = array();
        foreach ($modules as $module) {
            $settings_file = nc_module_folder($module['Keyword']) . 'backup_settings.inc.php';
            if (file_exists($settings_file)) {
                $module_settings[$module['Keyword']] = include $settings_file;
            }
        }
        $this->set_settings('module_settings', $module_settings);
        $this->are_module_settings_loaded = true;
    }

    //--------------------------------------------------------------------------

    /**
     * url к папке (файлу) экспорта: "/export/"
     * @param  string $file Имя файл
     * @return string
     */
    public function get_export_http_path($file = null) {
        return nc_core()->HTTP_FILES_PATH . self::EXPORT_FOLDER . '/' . $file;
    }

    //--------------------------------------------------------------------------

    /**
     * Полный путь к папке (файлу) экспорта
     * @param  string $file Имя файл
     * @return string
     */
    public function get_export_path($file = null) {
        return nc_core()->FILES_FOLDER . self::EXPORT_FOLDER . '/' . $file;
    }

    //--------------------------------------------------------------------------

    /**
     * Возвращает массив, экспортированных ранее, файлов.
     * @return array
     */
    public function get_export_files() {
        $result   = array();
        $dir      = $this->get_export_path();
        $type_ids = array();

        if (file_exists($dir)) {
            $files  = scandir($dir);
            foreach ($files as $f) {
                if ($f[0] == '.') continue;
                if (is_dir($dir . $f)) continue;

                $info = $this->parse_filename($f);

                $result[$info['time']] = $info;

                $type = $info['type'];
                $id   = $info['id'];
                $type_ids[$type][$id] = $id;
            }
            krsort($result);
        }
        $type_attributes = array();
        foreach ($type_ids as $type => $ids) {
            $type_attributes[$type] = $this->$type->get_row_attributes($ids);
        }

        foreach($result as $i => $row) {
            $id   = $row['id'];
            $type = $row['type'];

            if (!empty($type_attributes[$type][$id])) {
                $result[$i] += $type_attributes[$type][$id];
            }

            $result[$i]['type_name'] = $this->$type->get_name();
        }

        return $result;
    }

    //--------------------------------------------------------------------------

    /**
     * Возвращает массив типов импорта/экспорта
     * @return array [keyword => title, ...]
     */
    public function get_allowed_types() {
        static $result = array();

        if (!$result) {
            foreach ($this->types as $type => $title) {
                if ($title) {
                    $result[$type] = $title;
                }
            }
        }

        return $result;
    }

    //--------------------------------------------------------------------------

    /**
     * Генерирует имя файла экспорта
     */
    public function make_filename($type, $id, $suffix = '') {
        if ($suffix) {
            $suffix = self::FILENAME_DIVIDER . $suffix . self::FILENAME_DIVIDER . uniqid();
        }

        if (!$id) {
            $id = '0';
        }

        return $type . self::FILENAME_DIVIDER . $id . $suffix;
    }

    //--------------------------------------------------------------------------

    public function parse_filename($filename) {
        global $HTTP_FILES_PATH;

        $info = array();

        $info['ext']      = pathinfo($filename, PATHINFO_EXTENSION);
        $info['filename'] = pathinfo($filename, PATHINFO_FILENAME);
        $info['basename'] = pathinfo($filename, PATHINFO_BASENAME);

        $opt = explode(self::FILENAME_DIVIDER, $info['filename']);

        $info['type'] = $opt[0];
        $info['id']   = $opt[1];

        $export_file = $this->get_export_path($filename);
        if (file_exists($export_file)) {
            $info['time']          = filemtime($export_file);
            $info['size']          = filesize($export_file);
            $info['size_formated'] = nc_bytes2size($info['size']);
            $info['link']          = $HTTP_FILES_PATH . 'export/' . $info['basename'];
        }

        return $info;
    }

    //--------------------------------------------------------------------------

    public function file_rotation() {
        $limit_count = max($this->get_settings('export_limit_count'), 1); // Min: 1
        $limit_size  = max($this->get_settings('export_limit_size'), 5) * 1025 * 1024; // Min: 5MB;

        $files = $this->get_export_files();
        $path  = $this->get_export_path();

        if (count($files) > $limit_count) {
            foreach ($files as $i=>$f) {
                if (--$limit_count < 0) {
                    unlink($path . $f['basename']);
                    unset($files[$i]);
                }
            }
        }

        if (count($files) == 1 || ! $limit_size) {
            return;
        }

        $size = 0;
        foreach ($files as $i => $f) {
            if (count($files) == 1) {
                break;
            }
            $size += $f['size'];
            if ($size > $limit_size) {
                unlink($path . $f['basename']);
                unset($files[$i]);
            }
        }
    }

    //--------------------------------------------------------------------------

    public function remove_export_files() {
        nc_delete_dir($this->get_export_path());
        mkdir($this->get_export_path());
    }

    //-------------------------------------------------------------------------

    /**
     * Возвращает объект работающего с файлами импорта/экспорта
     * @return nc_backup_dumper
     */
    public function get_dumper() {
        if (!$this->dumper) {
            $this->dumper = new nc_backup_dumper();
        }

        return $this->dumper;
    }

    //-------------------------------------------------------------------------


    public function reset_dumper() {
        $this->dumper = null;
    }

    //--------------------------------------------------------------------------
    // EXPORT:
    //--------------------------------------------------------------------------

    public function export($type, $id, array $settings = array()) {
        return $this->$type->export($id, $settings);
    }

    //-------------------------------------------------------------------------

    public function export_download($type, $id) {
        return $this->$type->export_download($type, $id);
    }

    //--------------------------------------------------------------------------

    // public function export_download($type, $id) {
    //     header("Content-type: text/xml");
    //     header("Content-Disposition: attachment; filename=" . $this->file_name($type, $id));
    //     header("Expires: 0");
    //     header("Cache-Control: must-revalidate,post-check=0,pre-check=0");
    //     header("Pragma: public");

    //     echo $this->export($type, $id);
    // }

    //--------------------------------------------------------------------------
    // IMPORT:
    //--------------------------------------------------------------------------

    // public function detect_type($file) {
    //     static $driver;

    //     if (is_array($file) && isset($file['tmp_name'])) {
    //         if ($file['name']) {
    //             $info = $this->parse_filename($file['name']);
    //             $type = $info['type'];
    //         }
    //     }

    //     if ( ! $type) {
    //         if (is_null($driver)) {
    //             $driver = new nc_backup_driver($this);
    //         }
    //         $type = $driver->get_type($file['tmp_name']);
    //     }

    //     return $type;
    // }

    //--------------------------------------------------------------------------

    public function import($file, $settings = array()) {
        $dumper = $this->get_dumper();

        if (is_array($file) && isset($file['tmp_name'])) {
            $file = $file['tmp_name'];
        }

        $info = $dumper->import_init($file);
        $type = isset($info['type']) ? $info['type'] : false;
        $this->check_type($type);

        return $this->$type->import($dumper->get_dump_path(), $settings);
    }

    //--------------------------------------------------------------------------
}