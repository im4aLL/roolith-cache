<?php
namespace Roolith\Caching\Interfaces;


use Carbon\Carbon;

interface DriverInterface
{
    /**
     * Bootstrap method get called once driver initialized
     *
     * @return $this
     */
    public function bootstrap();

    /**
     * Store cache value by key
     *
     * @param $key string
     * @param $value mixed
     * @param Carbon $expiration
     * @return bool
     */
    public function store($key, $value, Carbon $expiration);

    /**
     * Get cache value by key
     *
     * @param $key
     * @return mixed
     */
    public function get($key);

    /**
     * Get key value and expiration
     *
     * @param $key
     * @return false | array (key, value, expiration)
     */
    public function getRaw($key);

    /**
     * If cache exists
     *
     * @param $key
     * @return bool
     */
    public function has($key);

    /**
     * Delete a cache item
     *
     * @param $key
     * @return bool
     */
    public function delete($key);

    /**
     * Delete all cache
     *
     * @return bool
     */
    public function flush();

    /**
     * Whether value is valid or not
     *
     * @param $value
     * @return bool
     */
    public function isValid($value);

    /**
     * Whether cache item expired
     *
     * @param $decompressData
     * @return bool
     */
    public function isExpired($decompressData);
}
