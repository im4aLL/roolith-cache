<?php

class FileDriverTest extends \PHPUnit\Framework\TestCase
{
    use Roolith\Caching\Traits\FileSystem;

    public $fileDriver;

    public function setUp(): void
    {
        $this->fileDriver = new \Roolith\Caching\Driver\FileDriver();
    }

    private function init()
    {
        $this->fileDriver->setConfig(['dir' => __DIR__. '/cache']);
        $this->fileDriver->bootstrap();
    }

    private function clean()
    {
        $this->deleteDir(__DIR__. '/cache');
    }

    public function testShouldBeAnInstanceOfDriver()
    {
        $this->assertInstanceOf(\Roolith\Caching\Driver\Driver::class, $this->fileDriver);
    }

    public function testShouldImplementDriverInterface()
    {
        $reflectionClass = new ReflectionClass(\Roolith\Caching\Driver\FileDriver::class);

        $this->assertEquals('Roolith\Caching\Interfaces\DriverInterface', $reflectionClass->getInterfaceNames()[0]);
    }

    public function testShouldAddConfig()
    {
        $this->fileDriver->setConfig(['dir' => __DIR__]);
        $config = $this->fileDriver->getConfig();

        $this->assertEquals(['dir' => __DIR__], $config);
    }

    public function testShouldCreateCacheDir()
    {
        $dir = __DIR__ . '/cache';
        $this->fileDriver->setConfig(['dir' => $dir]);
        $this->fileDriver->bootstrap();

        $this->assertTrue(file_exists($dir));
        $this->deleteDir($dir);
    }

    public function testShouldStoreCache()
    {
        $this->init();

        $isStored = $this->fileDriver->store('foo', 'something', \Carbon\Carbon::now()->addHours(1));
        $this->assertTrue($isStored);

        $this->clean();
    }

    public function testShouldGetCachedItem()
    {
        $this->init();

        $this->fileDriver->store('foo1', 'something', \Carbon\Carbon::now()->addHours(1));
        $value = $this->fileDriver->get('foo1');
        $this->assertEquals('something', $value);

        $this->fileDriver->store('foo2', [1, 2, 3], \Carbon\Carbon::now()->addHours(1));
        $value = $this->fileDriver->get('foo2');
        $this->assertEquals([1, 2, 3], $value);

        $object = new stdClass();
        $object->foo = 1;
        $this->fileDriver->store('foo3', $object, \Carbon\Carbon::now()->addHours(1));
        $value = $this->fileDriver->get('foo3');
        $this->assertEquals($object, $value);

        $this->fileDriver->store('foo4', null, \Carbon\Carbon::now()->addHours(1));
        $value = $this->fileDriver->get('foo4');
        $this->assertEquals(null, $value);

        $this->clean();
    }

    public function testShouldGetRawCachedItem()
    {
        $this->init();

        $carbonInstance = \Carbon\Carbon::now()->addHours(1);
        $this->fileDriver->store('foo', [1, 2, 3], $carbonInstance);
        $value = $this->fileDriver->getRaw('foo');
        $this->assertEquals([
            'key' => 'foo',
            'value' => [1, 2, 3],
            'expiration' => $carbonInstance->toDateTimeString(),
        ], $value);

        $this->clean();
    }

    public function testShouldReturnBooleanWhetherCacheExistsOrNot()
    {
        $this->init();

        $this->fileDriver->store('foo', 1, \Carbon\Carbon::now()->addHours(1));
        $this->assertIsBool($this->fileDriver->has('foo'));
        $this->assertTrue($this->fileDriver->has('foo'));
        $this->assertFalse($this->fileDriver->has('foo1'));

        $this->clean();
    }

    public function testShouldDeleteCacheItem()
    {
        $this->init();

        $this->fileDriver->store('foo', 1, \Carbon\Carbon::now()->addHours(1));
        $this->assertTrue($this->fileDriver->delete('foo'));
        $this->assertFalse($this->fileDriver->delete('foo'));

        $this->clean();
    }

    public function testShouldDeleteAllCachedItem()
    {
        $this->init();

        $this->fileDriver->store('foo', 1, \Carbon\Carbon::now()->addHours(1));
        $this->fileDriver->store('foo2', 1, \Carbon\Carbon::now()->addHours(1));
        $this->assertTrue($this->fileDriver->flush());

        $this->clean();
    }

    public function testShouldReturnFalseForExpiredCacheItem()
    {
        $this->init();

        $this->fileDriver->store('foo', 1, \Carbon\Carbon::now()->subHours(1));
        $this->assertFalse($this->fileDriver->get('foo'));

        $this->clean();
    }
}