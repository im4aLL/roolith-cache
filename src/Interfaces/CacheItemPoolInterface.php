<?php
namespace Roolith\Caching\Interfaces;


interface CacheItemPoolInterface extends \Psr\Cache\CacheItemPoolInterface
{
    /**
     * Get item raw value
     *
     * @param $key
     * @return array
     */
    public function getItemDetails($key);
}