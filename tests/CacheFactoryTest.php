<?php

use Roolith\Caching\Cache\CacheFactory;

define('ROOLITH_CACHE_DIR', __DIR__. '/cache');

class CacheFactoryTest extends \PHPUnit\Framework\TestCase
{
    use Roolith\Caching\Traits\FileSystem;

    public function tearDown(): void
    {
        $this->deleteDir(ROOLITH_CACHE_DIR);
    }

    public function testShouldStoreCache()
    {
        $this->assertTrue(CacheFactory::put('foo', 1, 3600));
    }

    public function testShouldCheckWhetherHasCache()
    {
        CacheFactory::put('foo', 1, 3600);

        $this->assertTrue(CacheFactory::has('foo'));
        $this->assertFalse(CacheFactory::has('foo2'));
    }

    public function testShouldGetCache()
    {
        CacheFactory::put('foo', 1, 3600);

        $this->assertEquals(1, CacheFactory::get('foo'));
    }

    public function testShouldDeleteCache()
    {
        CacheFactory::put('foo', 1, 3600);

        $this->assertTrue(CacheFactory::remove('foo'));
    }

    public function testShouldDeleteAllCache()
    {
        CacheFactory::put('foo', 1, 3600);
        CacheFactory::put('foo2', 2, 3600);

        $this->assertTrue(CacheFactory::flush());
        $this->assertFalse(CacheFactory::get('foo'));
    }
}