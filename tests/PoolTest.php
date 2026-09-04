<?php
use Roolith\Caching\Cache\Item;
use Roolith\Caching\Cache\Pool;
use Roolith\Caching\Cache\Psr6\InvalidArgumentException;
use Roolith\Caching\Driver\FileDriver;
use Roolith\Caching\Traits\FileSystem;

class PoolTest extends \PHPUnit\Framework\TestCase
{
    use FileSystem;

    public $pool;

    public function setUp(): void
    {
        $this->pool = new Pool(new FileDriver(['dir' => __DIR__. '/cache']));
    }

    public function testShouldGetItem()
    {
        $item = $this->pool->getItem('foo');

        $this->assertInstanceOf(Item::class, $item);
    }

    public function testShouldGetMultipleItems()
    {
        $items = $this->pool->getItems(['foo1', 'foo2']);

        $this->assertCount(2, $items);
    }

    public function testShouldCheckWhetherHasItem()
    {
        $this->assertFalse($this->pool->hasItem('foo'));

        $item = $this->pool->getItem('foo');
        $item->set(1)->expiresAfter(3600);
        $this->pool->save($item);

        $this->assertTrue($this->pool->hasItem('foo'));

        $this->pool->clear();
    }

    public function testShouldClearAllCache()
    {
        $item = $this->pool->getItem('foo1');
        $item->set(1)->expiresAfter(3600);
        $this->pool->save($item);

        $item = $this->pool->getItem('foo2');
        $item->set(1)->expiresAfter(3600);
        $this->pool->save($item);

        $this->pool->clear();
        $this->assertFalse($this->pool->hasItem('foo1'));
    }

    public function testShouldDeleteItem()
    {
        $item = $this->pool->getItem('foo1');
        $item->set(1)->expiresAfter(3600);
        $this->pool->save($item);
        $this->assertTrue($this->pool->hasItem('foo1'));

        $this->pool->deleteItem('foo1');
        $this->assertFalse($this->pool->hasItem('foo1'));

        $this->pool->clear();
    }

    public function testShouldDeleteMultipleItems()
    {
        $item = $this->pool->getItem('foo1');
        $item->set(1)->expiresAfter(3600);
        $this->pool->save($item);

        $item = $this->pool->getItem('foo2');
        $item->set(1)->expiresAfter(3600);
        $this->pool->save($item);

        $this->assertTrue($this->pool->deleteItems(['foo1', 'foo2']));
        $this->assertFalse($this->pool->deleteItems(['foo1', 'foo2']));

        $this->pool->clear();
    }

    public function testShouldSaveCacheItem()
    {
        $item = $this->pool->getItem('foo1');
        $item->set(1)->expiresAfter(3600);

        $this->assertTrue($this->pool->save($item));
        $this->pool->clear();
    }

    public function testShouldSaveMultipleItemsViaCommit()
    {
        $item = $this->pool->getItem('foo1');
        $item->set(1)->expiresAfter(3600);
        $this->pool->saveDeferred($item);

        $item = $this->pool->getItem('foo2');
        $item->set(1)->expiresAfter(3600);
        $this->pool->saveDeferred($item);

        $this->assertTrue($this->pool->commit());
        $this->pool->clear();
    }

    public function testShouldHaveRawDetailsForCacheItem()
    {
        $item = $this->pool->getItem('foo1');
        $item->set(1)->expiresAfter(3600);
        $this->pool->save($item);

        $this->assertIsArray($this->pool->getItemDetails('foo1'));
        $this->pool->clear();
    }

    public function testShouldGiveInvalidArgumentExceptionForInvalidKey()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->pool->getItem('{aaaa');
    }

    public function tearDown(): void
    {
        $this->deleteDir(__DIR__. '/cache');
    }
}
