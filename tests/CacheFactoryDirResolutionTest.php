<?php

use Roolith\Caching\Cache\Cache;
use Roolith\Caching\Cache\CacheFactory;
use Roolith\Caching\Driver\FileDriver;

class CacheFactoryDirResolutionTest extends \PHPUnit\Framework\TestCase
{
    use Roolith\Caching\Traits\FileSystem;

    private $originalStaticDir;

    private $dirsToClean = [];

    protected function setUp(): void
    {
        $this->originalStaticDir = CacheFactory::$fileDriverCacheDir;
        CacheFactory::$fileDriverCacheDir = null;
        $this->dirsToClean = [];
    }

    protected function tearDown(): void
    {
        CacheFactory::$fileDriverCacheDir = $this->originalStaticDir;

        foreach ($this->dirsToClean as $dir) {
            if (is_dir($dir)) {
                $this->deleteDir($dir);
            }
        }
    }

    private function trackDir($dir)
    {
        $this->dirsToClean[] = $dir;

        return $dir;
    }

    private function resolveDriverCacheDir(Cache $cache)
    {
        $cacheProp = new ReflectionProperty(Cache::class, 'driver');
        $cacheProp->setAccessible(true);
        /** @var FileDriver $driver */
        $driver = $cacheProp->getValue($cache);

        return $driver->cacheDir;
    }

    public function testDefaultPropertyDoesNotRequireConstant()
    {
        $this->assertNull(CacheFactory::$fileDriverCacheDir);
    }

    public function missingDirProvider()
    {
        return [
            'empty config' => [[]],
            'no dir key with other keys' => [['ext' => 'rcache']],
            'empty string' => [['dir' => '']],
            'whitespace' => [['dir' => '   ']],
            'null' => [['dir' => null]],
            'integer' => [['dir' => 123]],
            'array' => [['dir' => ['foo']]],
        ];
    }

    /**
     * @dataProvider missingDirProvider
     */
    public function testFileDriverBootstrapThrowsWhenDirMissing(array $config)
    {
        $driver = new FileDriver($config);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/dir/i');

        $driver->bootstrap();
    }

    public function testFileDriverBootstrapCreatesValidDir()
    {
        $dir = $this->trackDir(sys_get_temp_dir() . '/roolith-test-' . uniqid());
        $driver = new FileDriver(['dir' => $dir]);

        $this->assertSame($driver, $driver->bootstrap());
        $this->assertTrue(is_dir($dir));
        $this->assertSame($dir, $driver->cacheDir);
    }

    public function testFactoryUsesStaticOverrideWhenSet()
    {
        $dir = $this->trackDir(sys_get_temp_dir() . '/roolith-static-' . uniqid());
        CacheFactory::$fileDriverCacheDir = $dir;

        $cache = CacheFactory::driver('file');

        $this->assertSame($dir, $this->resolveDriverCacheDir($cache));
        $this->assertTrue(is_dir($dir));
    }

    public function testFactoryUsesExplicitConfigDirOverStaticOverride()
    {
        $staticDir = $this->trackDir(sys_get_temp_dir() . '/roolith-static-' . uniqid());
        $explicitDir = $this->trackDir(sys_get_temp_dir() . '/roolith-explicit-' . uniqid());
        CacheFactory::$fileDriverCacheDir = $staticDir;

        $cache = CacheFactory::driver('file', ['dir' => $explicitDir]);

        $this->assertSame($explicitDir, $this->resolveDriverCacheDir($cache));
        $this->assertTrue(is_dir($explicitDir));
    }

    public function testFactoryEmptyDirConfigFallsBackInsteadOfThrowing()
    {
        $fallbackDir = $this->trackDir(sys_get_temp_dir() . '/roolith-fallback-' . uniqid());
        CacheFactory::$fileDriverCacheDir = $fallbackDir;

        $cache = CacheFactory::driver('file', ['dir' => '']);

        $this->assertSame($fallbackDir, $this->resolveDriverCacheDir($cache));

        $this->assertTrue($cache->put('foo', 'bar', 3600));
        $this->assertSame('bar', $cache->get('foo'));
    }

    public function testFactoryDriverRoundTripWithEmptyConfig()
    {
        $dir = $this->trackDir(sys_get_temp_dir() . '/roolith-roundtrip-' . uniqid());
        CacheFactory::$fileDriverCacheDir = $dir;

        $cache = CacheFactory::driver();

        $this->assertTrue($cache->put('foo', 1, 3600));
        $this->assertSame(1, $cache->get('foo'));
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testFactoryFallsBackToTempDirWhenConstantUndefined()
    {
        $this->assertFalse(defined('ROOLITH_CACHE_DIR'));

        CacheFactory::$fileDriverCacheDir = null;
        $expected = rtrim(sys_get_temp_dir(), '/\\') . '/roolith-cache';

        $cache = CacheFactory::driver();

        $cacheProp = new ReflectionProperty(Cache::class, 'driver');
        $cacheProp->setAccessible(true);
        $driver = $cacheProp->getValue($cache);

        $this->assertSame($expected, $driver->cacheDir);
        $this->assertTrue(is_dir($expected));

        $this->assertTrue($cache->put('separate-process-key', 'v', 3600));
        $this->assertSame('v', $cache->get('separate-process-key'));

        $cache->remove('separate-process-key');
    }
}
