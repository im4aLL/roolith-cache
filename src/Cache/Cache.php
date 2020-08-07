<?php
namespace Roolith\Cache;

use Carbon\Carbon;
use Roolith\Driver\FileDriver;

class Cache
{
    protected $driver;

    public function driver($name, $config = [])
    {
        switch ($name) {
            case 'file':
            default:
                $this->driver = new FileDriver($config);
                break;
        }

        $this->driver->bootstrap();

        return $this;
    }

    public function put($key, $value, $expireAfter = 3600)
    {
        $expiration = Carbon::now()->addSeconds($expireAfter);

        return $this->driver->store($key, $value, $expiration);
    }

    public function has($key)
    {
        return $this->driver->has($key);
    }

    public function get($key)
    {
        return $this->driver->get($key);
    }

    public function remove($key)
    {
        return $this->driver->delete($key);
    }

    public function flush()
    {
        return $this->driver->flush();
    }
}
