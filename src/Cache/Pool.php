<?php
namespace Roolith\Caching\Cache;


use Carbon\Carbon;
use Psr\Cache\CacheItemInterface;
use Roolith\Caching\Cache\Psr6\InvalidArgumentException;
use Roolith\Caching\Interfaces\CacheItemPoolInterface;
use Roolith\Caching\Interfaces\DriverInterface;

class Pool implements CacheItemPoolInterface
{
    protected $driver;
    protected $items;

    public function __construct(DriverInterface $driver)
    {
        $this->driver = $driver;
        $this->driver->bootstrap();

        $this->items = [];
    }

    /**
     * @inheritDoc
     */
    public function getItem($key): CacheItemInterface
    {
        $key = $this->validateKey($key);

        $raw = $this->driver->getRaw($key);
        $isHit = false;
        $value = null;

        if ($this->driver->isValid($raw)) {
            if (!$this->driver->isExpired($raw)) {
                $isHit = true;
                $value = $raw['value'];
            }
        }

        $item = new Item($key, $value);
        $item->setHit($isHit);

        return $item;
    }

    /**
     * @inheritDoc
     */
    public function getItems(array $keys = []): iterable
    {
        $result = [];

        foreach ($keys as $key) {
            $result[$key] = $this->getItem($key);
        }

        return $result;
    }

    /**
     * @inheritDoc
     */
    public function hasItem($key): bool
    {
        $key = $this->validateKey($key);

        return $this->driver->has($key);
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
    public function deleteItem($key): bool
    {
        $key = $this->validateKey($key);

        return $this->driver->delete($key);
    }

    /**
     * @inheritDoc
     */
    public function deleteItems(array $keys = []): bool
    {
        foreach ($keys as $key) {
            $this->validateKey($key);
        }

        $result = true;

        foreach ($keys as $key) {
            if (!$this->driver->delete($key)) {
                $result = false;
            }
        }

        return $result;
    }

    /**
     * @inheritDoc
     */
    public function save(CacheItemInterface $item): bool
    {
        $expiration = $item->getExpiration();

        if ($expiration === null) {
            if (method_exists($item, 'getDefaultExpiration')) {
                $expiration = $item->getDefaultExpiration();
            }

            if ($expiration === null) {
                $expiration = Carbon::now()->addMonths(1);
            }
        }

        return $this->driver->store($item->getKey(), $item->get(), $expiration);
    }

    /**
     * @inheritDoc
     */
    public function saveDeferred(CacheItemInterface $item): bool
    {
        $this->items[] = $item;

        return true;
    }

    /**
     * @inheritDoc
     */
    public function commit(): bool
    {
        $result = true;

        foreach ($this->items as $item) {
            if (!$this->save($item)) {
                $result = false;
            }
        }

        $this->items = [];

        return $result;
    }

    /**
     * @inheritDoc
     */
    public function getItemDetails($key)
    {
        return $this->driver->getRaw($key);
    }

    /**
     * Valid key string
     *
     * @param $key
     * @return string
     */
    private function validateKey($key)
    {
        if (!is_string($key) || $key === '' || strpbrk($key, '{}()/\@:')) {
            throw new InvalidArgumentException('Invalid key: '.var_export($key, true));
        }

        return $key;
    }
}
