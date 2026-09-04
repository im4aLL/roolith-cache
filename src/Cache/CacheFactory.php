<?php
namespace Roolith\Caching\Cache;


class CacheFactory
{
    public static $fileDriverCacheDir = null;

    protected static function resolveFileDriverCacheDir()
    {
        if (!empty(self::$fileDriverCacheDir)) {
            return self::$fileDriverCacheDir;
        }

        if (defined('ROOLITH_CACHE_DIR') && is_string(ROOLITH_CACHE_DIR) && ROOLITH_CACHE_DIR !== '') {
            return ROOLITH_CACHE_DIR;
        }

        return rtrim(sys_get_temp_dir(), '/\\') . '/roolith-cache';
    }

    public static function driver($name = 'file', $config = [])
    {
        $cache = new Cache();

        if ($name === 'file') {
            if (!isset($config['dir']) || empty($config['dir'])) {
                $config['dir'] = self::resolveFileDriverCacheDir();
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
