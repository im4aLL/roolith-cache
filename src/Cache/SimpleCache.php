<?php
namespace Roolith\Cache;

use Carbon\Carbon;
use Psr\SimpleCache\CacheInterface;
use Roolith\Interfaces\DriverInterface;

class SimpleCache implements CacheInterface
{
    protected $driver;

    public function __construct(DriverInterface $driver)
    {
        $this->driver = $driver;

        $this->driver->bootstrap();
    }

    /**
     * @inheritDoc
     */
    public function get($key, $default = null)
    {
        $value = $this->driver->get($key);

        if ($value === false) {
            return $default;
        }

        return $value;
    }

    /**
     * @inheritDoc
     */
    public function set($key, $value, $ttl = null)
    {
        $expiration = Carbon::now()->addHours(5);

        if ($ttl instanceof DateInterval) {
            $expiration = Carbon::instance($ttl);
        } elseif (is_int($ttl)) {
            $expiration = Carbon::now()->addSeconds($ttl);
        }

        return $this->driver->store($key, $value, $expiration);
    }

    /**
     * @inheritDoc
     */
    public function delete($key)
    {
        return $this->driver->delete($key);
    }

    /**
     * @inheritDoc
     */
    public function clear()
    {
        return $this->driver->flush();
    }

    /**
     * @inheritDoc
     */
    public function getMultiple($keys, $default = null)
    {
        $result = [];

        foreach ($keys as $key) {
            $v = $this->get($key);
            $result[] = $v === null ? $default : $v;
        }

        return $result;
    }

    /**
     * @inheritDoc
     */
    public function setMultiple($values, $ttl = null)
    {
        $result = true;

        foreach ($values as $key => $value) {
            if ($this->set($key, $value, $ttl) === false) {
                $result = false;
            }
        }

        return $result;
    }

    /**
     * @inheritDoc
     */
    public function deleteMultiple($keys)
    {
        $result = true;

        foreach ($keys as $key) {
            if (!$this->delete($key)) {
                $result = false;
            }
        }

        return $result;
    }

    /**
     * @inheritDoc
     */
    public function has($key)
    {
        return $this->driver->has($key);
    }
}
