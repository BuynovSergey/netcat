<?php

interface nc_cache_service {

    const DEFAULT_TTL = 3600;


    /**
     * @return string
     */
    public function get_service_id();

    /**
     * @param string $key
     *
     * @return mixed|null
     */
    public function get($key);

    /**
     * @param string $key
     * @param mixed $value
     * @param int $ttl
     *
     * @return void
     */
    public function set($key, $value, $ttl = nc_cache_service::DEFAULT_TTL);

    /**
     * При наличии кэша возвращает кешированную версию результата выполнения closure, в ином случае сохраняет его в кэш
     *
     * @param string $key
     * @param closure $closure
     * @param int $ttl
     *
     * @return mixed
     */
    public function remember($key, $closure, $ttl = nc_cache_service::DEFAULT_TTL);

    /**
     * @param string $key
     *
     * @return void
     */
    public function delete($key);

    /**
     * @param string $key
     *
     * @return boolean
     */
    public function has($key);

    /**
     * @return void
     */
    public function clear();
}
