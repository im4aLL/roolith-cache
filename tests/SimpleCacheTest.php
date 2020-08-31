<?php
use PHPUnit\Framework\TestCase;
use Roolith\Cache\SimpleCache;
use Roolith\Driver\FileDriver;
use Roolith\Traits\FileSystem;

class SimpleCacheTest extends TestCase
{
    use FileSystem;
    public $simpleCache;

    public function setUp(): void
    {
        $this->simpleCache = new SimpleCache(new FileDriver(['dir' => __DIR__. '/cache']));
    }

    public function tearDown(): void
    {
        $this->deleteDir(__DIR__. '/cache');
    }

    public function testShouldGetCacheItem()
    {
        $this->simpleCache->set('foo', 1, 3600);
        $this->assertEquals(1, $this->simpleCache->get('foo'));
        $this->assertEquals(2, $this->simpleCache->get('foo1', 2));
    }

    public function testShouldStoreCacheItem()
    {
        $this->assertTrue($this->simpleCache->set('foo', 1, 3600));
    }

    public function testShouldDeleteCacheItem()
    {
        $this->simpleCache->set('foo', 1, 3600);

        $this->assertTrue($this->simpleCache->delete('foo'));
        $this->assertFalse($this->simpleCache->delete('foo'));
    }

    public function testShouldDeleteAllCacheItem()
    {
        $this->simpleCache->set('foo1', 1, 3600);
        $this->simpleCache->set('foo2', 1, 3600);

        $this->simpleCache->clear();
        $this->assertFalse($this->simpleCache->delete('foo1'));
    }

    public function testShouldGetMultipleCacheItem()
    {
        $this->simpleCache->set('foo1', 1, 3600);
        $this->simpleCache->set('foo2', 1, 3600);

        $result = $this->simpleCache->getMultiple(['foo1', 'foo2', 'foo3'], 2);

        $this->assertCount(3, $result);
        $this->assertEquals(1, $result[0]);
        $this->assertEquals(1, $result[1]);
        $this->assertEquals(2, $result[2]);
    }

    public function testShouldStoreMultipleItems()
    {
        $result = $this->simpleCache->setMultiple([
            'foo1' => 1,
            'foo2' => 2,
        ], 3600);

        $this->assertTrue($result);
    }

    public function testShouldDeleteMultipleItems()
    {
        $this->simpleCache->setMultiple([
            'foo1' => 1,
            'foo2' => 2,
        ], 3600);

        $this->assertTrue($this->simpleCache->deleteMultiple(['foo1', 'foo2']));
    }

    public function testShouldCheckWhetherHasCacheItem()
    {
        $this->assertFalse($this->simpleCache->has('foo1'));

        $this->simpleCache->set('foo1', 1, 3600);
        $this->assertTrue($this->simpleCache->has('foo1'));
    }

    public function testShouldGiveInvalidArgumentException()
    {
        $this->expectException(\Roolith\Cache\Psr16\InvalidArgumentException::class);
        $this->simpleCache->get('(123');
    }
}
