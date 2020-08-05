<?php
namespace Roolith\Cache;


use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Cache\InvalidArgumentException;
use Roolith\Interfaces\DriverInterface;

class Pool implements CacheItemPoolInterface
{
    protected $driver;
    protected $items;

    public function __construct(DriverInterface $driver)
    {
        $this->driver = $driver;
        $this->items = [];
    }

    /**
     * @inheritDoc
     */
    public function getItem($key)
    {
        return new Item($key, $this->driver->get($key));
    }

    /**
     * @inheritDoc
     */
    public function getItems(array $keys = [])
    {
        $result = [];

        foreach ($keys as $key) {
            $result[] = $this->getItem($key);
        }

        return $result;
    }

    /**
     * @inheritDoc
     */
    public function hasItem($key)
    {
        return $this->driver->has($key);
    }

    /**
     * @inheritDoc
     */
    public function clear()
    {
        $this->driver->flush();

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function deleteItem($key)
    {
        $this->driver->delete($key);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function deleteItems(array $keys = [])
    {
        $this->driver->deleteMany($keys);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function save(CacheItemInterface $item)
    {
        return $this->driver->store($item->getKey(), $item->get(), $item->getExpiration());
    }

    /**
     * @inheritDoc
     */
    public function saveDeferred(CacheItemInterface $item)
    {
        $this->items[] = $item;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function commit()
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
}
