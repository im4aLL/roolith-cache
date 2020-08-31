<?php

class CacheTest extends \PHPUnit\Framework\TestCase
{
    use Roolith\Caching\Traits\FileSystem;

    public $cache;

    public function setUp(): void
    {
        $this->cache = new Roolith\Caching\Cache\Cache();
        $this->cache->driver('file', ['dir' => __DIR__. '/cache']);
    }

    public function testShouldStoreCache()
    {
        $this->assertTrue($this->cache->put('foo', 1, 3600));
    }

    public function testShouldCheckWhetherHasCache()
    {
        $this->cache->put('foo', 1, 3600);

        $this->assertTrue($this->cache->has('foo'));
    }

    public function testShouldGetCache()
    {
        $this->cache->put('foo', 1, 3600);

        $this->assertEquals(1, $this->cache->get('foo'));
    }

    public function testShouldDeleteCache()
    {
        $this->cache->put('foo', 1, 3600);

        $this->assertTrue($this->cache->remove('foo'));
    }

    public function testShouldDeleteAllCache()
    {
        $this->cache->put('foo', 1, 3600);
        $this->cache->put('foo2', 2, 3600);

        $this->assertTrue($this->cache->flush());
        $this->assertFalse($this->cache->get('foo'));
    }

    public function tearDown(): void
    {
        $this->deleteDir(__DIR__. '/cache');
    }
}