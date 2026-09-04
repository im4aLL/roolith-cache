<?php
namespace Roolith\Caching\Cache;

use Carbon\Carbon;
use DateInterval;
use Psr\SimpleCache\CacheInterface;
use Roolith\Caching\Cache\Psr16\InvalidArgumentException;
use Roolith\Caching\Interfaces\DriverInterface;

class SimpleCache implements CacheInterface
{
    protected $driver;

    const DEFAULT_TTL_HOURS = 5;

    public function __construct(DriverInterface $driver)
    {
        $this->driver = $driver;

        $this->driver->bootstrap();
    }

    /**
     * @inheritDoc
     */
    public function get($key, $default = null): mixed
    {
        $key = $this->validateKey($key);

        $value = $this->driver->get($key);

        if ($value === false) {
            return $default;
        }

        return $value;
    }

    /**
     * @inheritDoc
     */
    public function set($key, $value, $ttl = null): bool
    {
        $key = $this->validateKey($key);

        $expiration = $this->resolveExpiration($ttl);

        return $this->driver->store($key, $value, $expiration);
    }

    /**
     * @inheritDoc
     */
    public function delete($key): bool
    {
        $key = $this->validateKey($key);

        return $this->driver->delete($key);
    }

    /**
     * @inheritDoc
     */
    public function clear(): bool
    {
        return $this->driver->flush();
    }

    /**
     * @inheritDoc
     */
    public function getMultiple($keys, $default = null): iterable
    {
        if (!is_array($keys) && !$keys instanceof \Traversable) {
            throw new InvalidArgumentException('Keys must be iterable: '.var_export($keys, true));
        }

        $result = [];

        foreach ($keys as $key) {
            $key = $this->validateKey($key);
            $result[$key] = $this->get($key, $default);
        }

        return $result;
    }

    /**
     * @inheritDoc
     */
    public function setMultiple($values, $ttl = null): bool
    {
        if (!is_array($values) && !$values instanceof \Traversable) {
            throw new InvalidArgumentException('Values must be iterable: '.var_export($values, true));
        }

        // Validate TTL upfront so invalid TTL fails before partial writes.
        $this->resolveExpiration($ttl);

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
    public function deleteMultiple($keys): bool
    {
        if (!is_array($keys) && !$keys instanceof \Traversable) {
            throw new InvalidArgumentException('Keys must be iterable: '.var_export($keys, true));
        }

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
    public function has($key): bool
    {
        $key = $this->validateKey($key);

        return $this->driver->has($key);
    }

    private function resolveExpiration($ttl)
    {
        if ($ttl === null) {
            return Carbon::now()->addHours(self::DEFAULT_TTL_HOURS);
        }

        if ($ttl instanceof DateInterval) {
            return Carbon::now()->add($ttl);
        }

        if (is_int($ttl)) {
            if ($ttl <= 0) {
                return Carbon::now()->subSecond();
            }

            return Carbon::now()->addSeconds($ttl);
        }

        throw new InvalidArgumentException('Invalid TTL: '.var_export($ttl, true));
    }

    private function validateKey($key)
    {
        if (!$key || is_null($key) || !is_string($key) || strpbrk($key, '{}()/\@:')) {
            throw new InvalidArgumentException('Invalid key: '.var_export($key, true));
        }

        return $key;
    }
}
