<?php
namespace Roolith\Cache;


class CacheFactory
{
    public static $fileDriverCacheDir = ROOLITH_CACHE_DIR;

    public static function driver($name = 'file', $config = [])
    {
        $cache = new Cache();

        if ($name === 'file') {
            if (!isset($config['dir'])) {
                $config['dir'] = self::$fileDriverCacheDir;
            }
        }

        return $cache->driver($name, $config);
    }

    public static function put($key, $value, $expireAfter)
    {
        return self::driver()->put($key, $value, $expireAfter);
    }

    public static function has($key)
    {
        return self::driver()->has($key);
    }

    public static function get($key)
    {
        return self::driver()->get($key);
    }

    public static function remove($key)
    {
        return self::driver()->remove($key);
    }

    public static function flush()
    {
        return self::driver()->flush();
    }
}
