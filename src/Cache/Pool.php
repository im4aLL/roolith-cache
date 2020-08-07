<?php
namespace Roolith\Cache;


use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
use Roolith\Interfaces\DriverInterface;

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
        return $this->driver->flush();
    }

    /**
     * @inheritDoc
     */
    public function deleteItem($key)
    {
        return $this->driver->delete($key);
    }

    /**
     * @inheritDoc
     */
    public function deleteItems(array $keys = [])
    {
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

    /**
     * Get item details
     *
     * @param $key
     * @return mixed
     */
    public function getItemDetails($key)
    {
        return $this->driver->getRaw($key);
    }
}
