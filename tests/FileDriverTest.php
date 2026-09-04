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

        $this->fileDriver->store('foo4', null, \Carbon\Carbon::now()->addHours(1));
        $value = $this->fileDriver->get('foo4');
        $this->assertEquals(null, $value);

        $this->clean();
    }

    public function testShouldRejectObjectPayload()
    {
        $this->init();

        $object = new stdClass();
        $object->foo = 1;
        $this->fileDriver->store('foo-object', $object, \Carbon\Carbon::now()->addHours(1));
        $this->assertFalse($this->fileDriver->get('foo-object'));
        $this->assertFalse($this->fileDriver->has('foo-object'));
        $this->assertFalse($this->fileDriver->getRaw('foo-object'));

        $this->fileDriver->store('foo-nested-object', ['nested' => $object], \Carbon\Carbon::now()->addHours(1));
        $this->assertFalse($this->fileDriver->get('foo-nested-object'));
        $this->assertFalse($this->fileDriver->has('foo-nested-object'));

        $this->clean();
    }

    public function testShouldGetRawCachedItem()
    {
        $this->init();

        $carbonInstance = \Carbon\Carbon::now()->addHours(1);
        $this->fileDriver->store('foo', [1, 2, 3], $carbonInstance);
        $value = $this->fileDriver->getRaw('foo');
        $this->assertEquals('foo', $value['key']);
        $this->assertEquals([1, 2, 3], $value['value']);
        $this->assertInstanceOf(\Carbon\Carbon::class, $value['expiration']);
        $this->assertEquals($carbonInstance->getTimestamp(), $value['expiration']->getTimestamp());

        $this->clean();
    }

    public function testShouldStoreExpirationAsTimestamp()
    {
        $this->init();

        $carbonInstance = \Carbon\Carbon::now()->addHours(1);
        $this->fileDriver->store('timestamp-foo', 'bar', $carbonInstance);

        $reflection = new ReflectionMethod(\Roolith\Caching\Driver\FileDriver::class, 'getFilenameByKey');
        $reflection->setAccessible(true);
        $filename = $reflection->invoke($this->fileDriver, 'timestamp-foo');

        $config = $this->fileDriver->getConfig();
        $raw = file_get_contents($config['dir'].'/'.$filename);
        $payload = unserialize($raw, ['allowed_classes' => false]);

        $this->assertIsArray($payload);
        $this->assertEquals('timestamp-foo', $payload['key']);
        $this->assertEquals('bar', $payload['value']);
        $this->assertIsInt($payload['expiration']);
        $this->assertEquals($carbonInstance->getTimestamp(), $payload['expiration']);

        $this->clean();
    }

    public function testShouldReturnFalseForTamperedAndEmptyPayloads()
    {
        $this->init();

        $this->fileDriver->store('tampered-foo', 'valid', \Carbon\Carbon::now()->addHours(1));

        $reflection = new ReflectionMethod(\Roolith\Caching\Driver\FileDriver::class, 'getFilenameByKey');
        $reflection->setAccessible(true);
        $filename = $reflection->invoke($this->fileDriver, 'tampered-foo');
        $config = $this->fileDriver->getConfig();
        $path = $config['dir'].'/'.$filename;

        file_put_contents($path, 'tampered-not-serialized-payload');
        $this->assertFalse($this->fileDriver->get('tampered-foo'));
        $this->assertFalse($this->fileDriver->has('tampered-foo'));
        $this->assertFalse($this->fileDriver->getRaw('tampered-foo'));

        file_put_contents($path, '');
        $this->assertFalse($this->fileDriver->get('tampered-foo'));
        $this->assertFalse($this->fileDriver->has('tampered-foo'));
        $this->assertFalse($this->fileDriver->getRaw('tampered-foo'));

        file_put_contents($path, serialize(new stdClass()));
        $this->assertFalse($this->fileDriver->get('tampered-foo'));
        $this->assertFalse($this->fileDriver->has('tampered-foo'));
        $this->assertFalse($this->fileDriver->getRaw('tampered-foo'));

        file_put_contents($path, serialize(['unexpected' => 'shape']));
        $this->assertFalse($this->fileDriver->get('tampered-foo'));
        $this->assertFalse($this->fileDriver->has('tampered-foo'));
        $this->assertFalse($this->fileDriver->getRaw('tampered-foo'));

        $this->clean();
    }

    public function testDecompressRejectsInvalidShapes()
    {
        $this->assertFalse($this->fileDriver->decompress(''));
        $this->assertFalse($this->fileDriver->decompress(null));
        $this->assertFalse($this->fileDriver->decompress('not-serialized'));
        $this->assertFalse($this->fileDriver->decompress(serialize(null)));
        $this->assertFalse($this->fileDriver->decompress(serialize(new stdClass())));
        $this->assertFalse($this->fileDriver->decompress(serialize(['unexpected' => 'shape'])));
        $this->assertFalse($this->fileDriver->decompress(serialize(['key' => 123, 'value' => 1, 'expiration' => time()])));
        $this->assertFalse($this->fileDriver->decompress(serialize(['key' => 'foo', 'expiration' => time()])));
        $this->assertFalse($this->fileDriver->decompress(serialize(['key' => 'foo', 'value' => 1])));
        $this->assertFalse($this->fileDriver->decompress(serialize(['key' => 'foo', 'value' => 1, 'expiration' => 'not-a-date'])));
    }

    public function testDecompressAcceptsLegacyDateStringExpiration()
    {
        $expiration = \Carbon\Carbon::now()->addHours(1);
        $payload = serialize(['key' => 'legacy', 'value' => 'data', 'expiration' => $expiration->toDateTimeString()]);

        $result = $this->fileDriver->decompress($payload);

        $this->assertIsArray($result);
        $this->assertEquals('legacy', $result['key']);
        $this->assertEquals('data', $result['value']);
        $this->assertInstanceOf(\Carbon\Carbon::class, $result['expiration']);
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

    public function testShouldReturnFalseForHasWhenExpired()
    {
        $this->init();

        $this->fileDriver->store('expired-foo', 1, \Carbon\Carbon::now()->subHours(1));
        $this->assertFalse($this->fileDriver->has('expired-foo'));
        $this->assertFalse($this->fileDriver->get('expired-foo'));

        $this->fileDriver->store('valid-foo', 1, \Carbon\Carbon::now()->addHours(1));
        $this->assertTrue($this->fileDriver->has('valid-foo'));

        $this->clean();
    }

    public function testShouldReturnFalseForCorruptFile()
    {
        $this->init();

        $this->fileDriver->store('corrupt-foo', 1, \Carbon\Carbon::now()->addHours(1));

        $reflection = new ReflectionMethod(\Roolith\Caching\Driver\FileDriver::class, 'getFilenameByKey');
        $reflection->setAccessible(true);
        $filename = $reflection->invoke($this->fileDriver, 'corrupt-foo');

        $config = $this->fileDriver->getConfig();
        file_put_contents($config['dir'].'/'.$filename, 'corrupted-not-serialized');

        $this->assertFalse($this->fileDriver->get('corrupt-foo'));
        $this->assertFalse($this->fileDriver->has('corrupt-foo'));

        $this->clean();
    }

    public function testShouldNotCollideSimilarKeys()
    {
        $this->init();

        $this->fileDriver->store('foo/bar', 'slash', \Carbon\Carbon::now()->addHours(1));
        $this->fileDriver->store('foo-bar', 'dash', \Carbon\Carbon::now()->addHours(1));
        $this->fileDriver->store('FOO-BAR', 'upper', \Carbon\Carbon::now()->addHours(1));

        $this->assertEquals('slash', $this->fileDriver->get('foo/bar'));
        $this->assertEquals('dash', $this->fileDriver->get('foo-bar'));
        $this->assertEquals('upper', $this->fileDriver->get('FOO-BAR'));

        $reflection = new ReflectionMethod(\Roolith\Caching\Driver\FileDriver::class, 'getFilenameByKey');
        $reflection->setAccessible(true);
        $fileSlash = $reflection->invoke($this->fileDriver, 'foo/bar');
        $fileDash = $reflection->invoke($this->fileDriver, 'foo-bar');
        $fileUpper = $reflection->invoke($this->fileDriver, 'FOO-BAR');

        $this->assertNotEquals($fileSlash, $fileDash);
        $this->assertNotEquals($fileSlash, $fileUpper);
        $this->assertNotEquals($fileDash, $fileUpper);

        foreach ([$fileSlash, $fileDash, $fileUpper] as $filename) {
            $this->assertMatchesRegularExpression('/^[a-z0-9-]+\-[0-9a-f]{40}\.rcache$/', $filename);
            $this->assertStringNotContainsString('/', $filename);
        }

        $this->clean();
    }

    public function testShouldWhitelistCacheFileExtension()
    {
        $this->fileDriver->setConfig(['dir' => __DIR__.'/cache', 'ext' => 'datastore']);
        $reflection = new ReflectionMethod(\Roolith\Caching\Driver\FileDriver::class, 'getFilenameByKey');
        $reflection->setAccessible(true);
        $this->assertStringEndsWith('.datastore', $reflection->invoke($this->fileDriver, 'foo'));

        $invalidExts = ['../php', 'r-cache', 'a/b', '.rcache', 'foo.bar', 'foo bar', '', 'php/../', null, 123, []];
        foreach ($invalidExts as $ext) {
            $this->fileDriver->setConfig(['dir' => __DIR__.'/cache', 'ext' => $ext]);
            $extReflection = new ReflectionMethod(\Roolith\Caching\Driver\FileDriver::class, 'getCacheFileExtension');
            $extReflection->setAccessible(true);
            $this->assertEquals('rcache', $extReflection->invoke($this->fileDriver), 'Failed for ext: '.var_export($ext, true));
        }

        $this->fileDriver->setConfig(['dir' => __DIR__.'/cache']);
        $extReflection = new ReflectionMethod(\Roolith\Caching\Driver\FileDriver::class, 'getCacheFileExtension');
        $extReflection->setAccessible(true);
        $this->assertEquals('rcache', $extReflection->invoke($this->fileDriver));
    }

    public function testIsExpiredIsDefensiveAgainstMissingExpiration()
    {
        $this->assertTrue($this->fileDriver->isExpired(false));
        $this->assertTrue($this->fileDriver->isExpired(null));
        $this->assertTrue($this->fileDriver->isExpired([]));
        $this->assertTrue($this->fileDriver->isExpired(['value' => 1]));
        $this->assertTrue($this->fileDriver->isExpired(['expiration' => null]));
        $this->assertTrue($this->fileDriver->isExpired(['expiration' => 'not-a-date']));
    }

    public function testShouldCheckIsValidBeforeIsExpired()
    {
        $this->init();

        $this->assertFalse($this->fileDriver->isValid(['unexpected' => 'shape']));
        $this->assertTrue($this->fileDriver->isExpired(['unexpected' => 'shape']));

        $this->fileDriver->store('tampered-foo', 1, \Carbon\Carbon::now()->addHours(1));

        $reflection = new ReflectionMethod(\Roolith\Caching\Driver\FileDriver::class, 'getFilenameByKey');
        $reflection->setAccessible(true);
        $filename = $reflection->invoke($this->fileDriver, 'tampered-foo');

        $config = $this->fileDriver->getConfig();
        file_put_contents($config['dir'].'/'.$filename, serialize(['unexpected' => 'shape']));

        $this->assertFalse($this->fileDriver->get('tampered-foo'));
        $this->assertFalse($this->fileDriver->has('tampered-foo'));

        $this->clean();
    }

    public function testStoreWritesAtomicallyWithoutTempLeftovers()
    {
        $this->init();
        $config = $this->fileDriver->getConfig();
        $dir = $config['dir'];

        $this->assertTrue($this->fileDriver->store('atomic-foo', 'atomic-value', \Carbon\Carbon::now()->addHours(1)));
        $this->assertEquals('atomic-value', $this->fileDriver->get('atomic-foo'));

        $tmpFiles = glob($dir.'/*.tmp');
        $this->assertIsArray($tmpFiles);
        $this->assertCount(0, $tmpFiles);

        $reflection = new ReflectionMethod(\Roolith\Caching\Driver\FileDriver::class, 'getFilenameByKey');
        $reflection->setAccessible(true);
        $filename = $reflection->invoke($this->fileDriver, 'atomic-foo');
        $this->assertTrue(is_file($dir.'/'.$filename));

        $this->clean();
    }

    public function testConcurrentWriteSmokeTest()
    {
        $this->init();
        $config = $this->fileDriver->getConfig();
        $dir = $config['dir'];

        for ($i = 0; $i < 25; $i++) {
            $this->assertTrue($this->fileDriver->store('concurrent-foo', 'value-'.$i, \Carbon\Carbon::now()->addHours(1)));
            $this->assertEquals('value-'.$i, $this->fileDriver->get('concurrent-foo'));
        }

        $this->assertEquals('value-24', $this->fileDriver->get('concurrent-foo'));

        $tmpFiles = glob($dir.'/*.tmp');
        $this->assertIsArray($tmpFiles);
        $this->assertCount(0, $tmpFiles);

        $this->clean();
    }

    public function testFlushOnlyDeletesRcacheFiles()
    {
        $this->init();
        $config = $this->fileDriver->getConfig();
        $dir = $config['dir'];

        $this->fileDriver->store('foo', 1, \Carbon\Carbon::now()->addHours(1));
        $this->fileDriver->store('foo2', 1, \Carbon\Carbon::now()->addHours(1));

        file_put_contents($dir.'/keep.txt', 'do-not-delete');
        file_put_contents($dir.'/keep.php', '<?php // do-not-delete');
        mkdir($dir.'/subdir');
        file_put_contents($dir.'/subdir/nested.txt', 'nested');

        $this->assertTrue($this->fileDriver->flush());

        $this->assertFalse($this->fileDriver->has('foo'));
        $this->assertFalse($this->fileDriver->has('foo2'));
        $this->assertSame('do-not-delete', file_get_contents($dir.'/keep.txt'));
        $this->assertSame('<?php // do-not-delete', file_get_contents($dir.'/keep.php'));
        $this->assertTrue(is_file($dir.'/subdir/nested.txt'));
        $this->assertSame([], glob($dir.'/*.rcache') ?: []);

        $this->clean();
    }

    public function testFlushScopesToConfiguredExtension()
    {
        $dir = __DIR__.'/cache';
        $this->fileDriver->setConfig(['dir' => $dir, 'ext' => 'datastore']);
        $this->fileDriver->bootstrap();

        $this->fileDriver->store('custom-foo', 1, \Carbon\Carbon::now()->addHours(1));
        file_put_contents($dir.'/plain.rcache', 'other-ext-payload');
        file_put_contents($dir.'/keep.txt', 'do-not-delete');

        $this->assertTrue($this->fileDriver->flush());

        $this->assertFalse($this->fileDriver->has('custom-foo'));
        $this->assertTrue(is_file($dir.'/plain.rcache'));
        $this->assertSame('do-not-delete', file_get_contents($dir.'/keep.txt'));

        $this->clean();
    }

    public function testDeleteFilesInDirSkipsDirectoriesAndDotFiles()
    {
        $this->init();
        $config = $this->fileDriver->getConfig();
        $dir = $config['dir'];

        mkdir($dir.'/fake.rcache');
        file_put_contents($dir.'/.hidden.rcache', 'hidden');

        $this->assertTrue($this->deleteFilesInDir($dir));
        $this->assertTrue(is_dir($dir.'/fake.rcache'));
        $this->assertTrue(is_file($dir.'/.hidden.rcache'));

        unlink($dir.'/.hidden.rcache');
        rmdir($dir.'/fake.rcache');

        $this->clean();
    }
}