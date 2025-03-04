<?php

/* $Id$ */
if (!class_exists("nc_System")) die("Unable to load file.");

/**
 * Реализация файлового кэша
 */
class nc_cache_io_file extends nc_cache_io {
    protected $cache_path;

    protected function __construct() {
        parent::__construct();
        $nc_core = nc_Core::get_object();

        $this->cache_path = $nc_core->DOCUMENT_ROOT . '/' . $nc_core->SUB_FOLDER . 'netcat_cache/';
        if (isset($nc_core->CACHE_FOLDER)) {
            $this->cache_path = $nc_core->CACHE_FOLDER;
        }
    }

    /**
     * Получение экземпляра класса
     *
     * @return self
     */
    public static function get_object() {
        static $storage;

        if (!isset($storage)) {
            $storage = new self();
        }

        return $storage;
    }

    /**
     * Добавление в кэш данных
     *
     * @param string $key ключ
     * @param string $value значение
     *
     * @return int размер записанных данных, байты
     * @throws Exception
     */
    public function add($key, $value) {
        $nc_core = nc_Core::get_object();
        // директория для записи
        $dir = substr($key, 0, strripos($key, '/'));
        $nc_core->files->create_dir($dir);

        if (!($bytes_written = @file_put_contents($key, $value))) {
            throw new Exception(str_replace('%FILE', $key, NETCAT_MODULE_CACHE_CLASS_CANNOT_CREATE_FILE));
        }

        return $bytes_written;
    }

    /**
     * Чтение данных из кэша
     *
     * @param string $key ключ
     *
     * @return mixed данные из кэша или false, если их нет
     */
    public function read($key) {
        if (file_exists($key)) {
            return file_get_contents($key);
        }

        return false;
    }

    /**
     * Удаление определенной кэш-записи
     *
     * @param string $key ключ
     *
     * @return int количество удаленных байт
     */
    public function delete($key) {
        $unlink_data_size = 0;

        if (file_exists($key) && is_writable(dirname($key))) {
            // data to delete size
            $unlink_data_size = filesize($key);
            // delete file
            unlink($key);
        }

        return $unlink_data_size;
    }

    /**
     * Очистка кэша
     *
     * @param string $dir директория
     * @param bool $remove_dir удалять саму директорию
     *
     * @return int количество удаленных байт
     */
    public function drop($dir, $remove_dir = false) {
        $dir = rtrim($dir, '/') . '/';

        if (!is_dir($dir)) {
            return false;
        }

        $deleted_bytes = 0;
        // delete all files from dir
        if ($dh = @opendir($dir)) {
            // read children
            while (( $file = @readdir($dh) ) !== false) {
                if (in_array($file, array('.', '..', 'stat.log'), true)) {
                    continue;
                }
                // append full path
                $file = $dir . $file;

                // delete dir or file
                switch (true) {
                    case is_file($file):
                        // continue if not accessible
                        if (!is_writable(dirname($file))) {
                            continue 2;
                        }

                        $unlink_data_size = filesize($file);

                        if (unlink($file)) {
                            $deleted_bytes += $unlink_data_size;
                        }

                        unset($unlink_data_size);
                        break;
                    case is_dir($file):
                        $deleted_bytes += $this->drop($file, $remove_dir);
                        break;
                }
            }
            closedir($dh);
        }

        if ($remove_dir) {
            @rmdir($dir);
        }

        return $deleted_bytes ?: false;
    }

    /**
     * Количество занимаемого места определенной кэш-записи
     *
     * @param string $key ключ
     *
     * @return int размер
     */
    public function get_size($key) {
        return file_exists($key) ? filesize($key) : 0;
    }

    /**
     * Обновить статистику
     *
     * @param string $essence тип кэша
     * @param int $size количество прибавляемых байтах
     */
    public function update_stat($essence, $size) {
        if (!is_writable($this->cache_path)) {
            return;
        }

        $nc_core = nc_Core::get_object();
        $nc_core->files->create_dir($this->cache_path . $essence);
        $stat_file = $this->cache_path . $essence . '/stat.log';
        $content_length = file_exists($stat_file) ? file_get_contents($stat_file) : 0;
        $content_length += (int)$size;

        if (@is_writable($stat_file)) {
            file_put_contents($stat_file, $content_length);
        }
    }

}
?>