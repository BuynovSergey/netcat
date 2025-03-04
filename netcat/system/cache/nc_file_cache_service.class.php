<?php

class nc_file_cache_service implements nc_cache_service {

    /**
     * @var string
     */
    private $directory;

    /**
     * @var string
     */
    private $service_id;

    /**
     * @var int
     */
    private $ttl;


    /**
     * @param string $directory
     * @param string $service_id Уникальный идентификатор сервиса кэширования, для получения доступа к нему через
     *     nc_cache_manager, если не задано, сгенерируется автоматически
     * @param int $ttl
     */
    public function __construct($directory = "", $service_id = "", $ttl = nc_cache_service::DEFAULT_TTL) {
        $this->ttl = $ttl;
        $this->set_directory($directory);
        $this->service_id = $service_id ?: $this->generate_service_id();

        nc_cache_manager::get_instance()->add_cache_service($this);
    }

    /**
     * @return string
     */
    public function get_directory() {
        return $this->directory;
    }

    /**
     * @param string $directory
     */
    public function set_directory($directory) {
        global $CACHE_FOLDER;

        $directory = $directory ?: "shared";
        $full_path = $CACHE_FOLDER . $directory;

        if (!file_exists($full_path)) {
            @mkdir($CACHE_FOLDER . $directory, 0777, true);
        }

        $this->directory = $full_path;
    }

    /**
     * @return string
     */
    public function get_service_id() {
        return $this->service_id;
    }

    /**
     * @return int
     */
    public function get_ttl() {
        return $this->ttl;
    }

    /**
     * @param int $ttl
     */
    public function set_ttl($ttl) {
        $this->ttl = $ttl;
    }

    /**
     * @param $key
     *
     * @return string
     */
    public function get_file_path($key) {
        return $this->get_directory() . "/" . $this->get_hash($key) . ".cache";
    }

    /**
     * @inheritDoc
     */
    public function get($key) {
        $file = $this->get_file_path($key);

        if (file_exists($file) && is_readable($file)) {
            $content = $this->parse_file_contents(file_get_contents($file));

            if ($content["ttl"] === null || $content["ttl"] > time()) {
                return unserialize($content["data"]);
            }
            else {
                unlink($file);
            }
        }

        return null;
    }

    /**
     * @inheritDoc
     */
    public function set($key, $value, $ttl = nc_cache_service::DEFAULT_TTL) {
        $file = $this->get_file_path($key);
        $content = $ttl + time() . "\n";
        $content .= serialize($value);

        file_put_contents($file, $content, LOCK_EX);
    }

    /**
     * @inheritDoc
     */
    public function remember($key, $closure, $ttl = nc_cache_service::DEFAULT_TTL) {
        if ($this->has($key)) {
            return $this->get($key);
        }

        $data = $closure();
        $this->set($key, $data, $ttl);

        return $data;
    }

    /**
     * @inheritDoc
     */
    public function delete($key) {
        $file = $this->get_file_path($key);

        if (file_exists($file)) {
            unlink($file);
        }
    }

    /**
     * @inheritDoc
     */
    public function has($key) {
        $file = $this->get_file_path($key);

        if (file_exists($file) && is_readable($file)) {
            $content = $this->parse_file_contents(file_get_contents($file));

            if ($content["ttl"] === null || $content["ttl"] > time()) {
                return true;
            }
            else {
                unlink($file);
            }
        }

        return false;
    }

    /**
     * @inheritDoc
     */
    public function clear() {
        nc_delete_dir($this->directory);
    }

    /**
     * @param string $value
     *
     * @return string
     */
    private function get_hash($value) {
        return md5($value);
    }

    /**
     * @return string
     */
    private function generate_service_id() {
        return sprintf("%s_%s", get_class($this), nc_generate_random_string(20));
    }


    /**
     * @param string $content
     *
     * @return array
     */
    private function parse_file_contents($content) {
        $lines = explode("\n", $content, 2);

        return array(
            "ttl" => $lines[0],
            "data" => $lines[1],
        );
    }

}
