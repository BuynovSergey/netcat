<?php

if (!class_exists("nc_System")) die("Unable to load file.");

/**
 * Реализация файлового кэша
 */
class nc_cache_io_memcache extends nc_cache_io {

    protected $memcache;
    protected $host, $port;

    protected function __construct() {
        parent::__construct();

        $host = null;
        $port = null;

        $nc_core = nc_Core::get_object();
        $catalogue = $nc_core->catalogue->get_by_host_name($_SERVER['HTTP_HOST']);

        if ($catalogue) {
            $catalogueID = (int)$catalogue['Catalogue_ID'];

            $settings = $nc_core->db->get_row(
                "SELECT *, UNIX_TIMESTAMP(`Audit_Begin`) AS Audit_Begin 
                 FROM `Cache_Settings`
                 WHERE `Catalogue_ID` = {$catalogueID}",
                ARRAY_A
            );

            $host = $settings['Memcached_Host'];
            $port = $settings['Memcached_Port'];
        }

        $this->set_server($host, $port);

        if (!class_exists('Memcache')) {
            throw new Exception(NETCAT_MODULE_CACHE_ADMIN_SETTINGS_MEMCACHED_DOESNT_EXIST);
        }
        $this->memcache = new Memcache();

        if (!@$this->memcache->connect($this->host, $this->port)) {
            throw new Exception(NETCAT_MODULE_CACHE_ADMIN_SETTINGS_MEMCACHED_ERROR);
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
     * Установить сервер
     *
     * @param string $host хост
     * @param int $port порт
     */
    public function set_server($host, $port) {
        if (!$host) {
            $host = 'localhost';
        }

        $port = (int)$port;
        if (!$port) {
            $port = 11211;
        }

        $this->host = $host;
        $this->port = $port;
    }

    /**
     * Добавлние в кэш данных
     *
     * @param string $key ключ
     * @param string $value значение
     *
     * @return int размер записанных данных, байты
     */
    public function add($key, $value) {
        if (!$this->memcache->add($key, $value)) {
            $this->memcache->replace($key, $value);
        }

        return strlen($value);
    }

    /**
     * Чтение данных из кэша
     *
     * @param string $key ключ
     *
     * @return mixed данные из кэша или false, если их нет
     */
    public function read($key) {
        return $this->memcache->get($key);
    }

    /**
     * Удаление определенной кэш-записи
     *
     * @param string $key ключ
     *
     * @return int количество удаленных байт
     */
    public function delete($key) {
        $size = $this->get_size($key);
        $this->memcache->delete($key);

        return $size;
    }

    /**
     * Очистка кэша
     */
    public function drop() {
        $this->memcache->flush();
    }

    /**
     * Количество занимаемого места определенной кэш-записи
     *
     * @param string $key ключ
     *
     * @return int размер
     */
    public function get_size($key) {
        return strlen($this->read($key));
    }

    /**
     * Получение статистики определенного кэша
     *
     * @param string $essence тип кэша
     *
     * @return int размер кэша
     */
    public function get_stat($essence) {
        return $this->read('netcat_stat_' . $essence);
    }

    /**
     * Обновить статистику
     *
     * @param string $essence тип кэша
     * @param int  $size количество прибавляемых байтах
     */
    public function update_stat($essence, $size) {
        $current = $this->read('netcat_stat_' . $essence);
        $this->add('netcat_stat_' . $essence, $current + $size);
    }
}
