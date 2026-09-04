<?php

use Roolith\Caching\Cache\Item;
use Roolith\Caching\Cache\Pool;
use Roolith\Caching\Cache\SimpleCache;

class ComposerPackagingTest extends \PHPUnit\Framework\TestCase
{
    private function readComposerJson()
    {
        $path = __DIR__ . '/../composer.json';
        $this->assertFileExists($path);

        $data = json_decode(file_get_contents($path), true);
        $this->assertIsArray($data, 'composer.json must decode to an array');

        return $data;
    }

    public function testComposerJsonHasNoHardcodedVersion()
    {
        $data = $this->readComposerJson();

        $this->assertArrayNotHasKey('version', $data, 'composer.json must not hardcode version, use tags instead');
    }

    public function testComposerJsonWidensPsrAndCarbonConstraints()
    {
        $data = $this->readComposerJson();

        $require = $data['require'];
        $this->assertArrayHasKey('psr/cache', $require);
        $this->assertArrayHasKey('psr/simple-cache', $require);
        $this->assertArrayHasKey('nesbot/carbon', $require);

        foreach (['psr/cache', 'psr/simple-cache'] as $package) {
            $constraint = $require[$package];
            $this->assertStringContainsString('^1.0', $constraint, $package . ' must still allow v1');
            $this->assertStringContainsString('^2.0', $constraint, $package . ' must allow v2');
            $this->assertStringContainsString('^3.0', $constraint, $package . ' must allow v3');
        }

        $carbon = $require['nesbot/carbon'];
        $this->assertStringContainsString('^2.0', $carbon, 'carbon must allow v2');
        $this->assertStringContainsString('^3.0', $carbon, 'carbon must allow v3');
    }

    public function testComposerJsonWidensProvideConstraints()
    {
        $data = $this->readComposerJson();

        $this->assertArrayHasKey('provide', $data);
        $provide = $data['provide'];

        foreach (['psr/cache-implementation', 'psr/simple-cache-implementation'] as $package) {
            $this->assertArrayHasKey($package, $provide);
            $constraint = $provide[$package];
            $this->assertStringContainsString('^1.0', $constraint);
            $this->assertStringContainsString('^2.0', $constraint);
            $this->assertStringContainsString('^3.0', $constraint);
        }
    }

    public function testGitignoreCoversCachesAndPhpunitArtifacts()
    {
        $path = __DIR__ . '/../.gitignore';
        $this->assertFileExists($path);

        $content = file_get_contents($path);

        $this->assertStringContainsString('tests/cache/', $content);
        $this->assertStringContainsString('demo/cache/', $content);
        $this->assertStringContainsString('.rcache', $content);
        $this->assertStringContainsString('.phpunit', $content);
    }

    public function testNoTrackedRcacheArtifacts()
    {
        $root = realpath(__DIR__ . '/..');
        $output = [];
        $code = 0;
        exec('git -C ' . escapeshellarg($root) . ' ls-files', $output, $code);

        if ($code !== 0) {
            $this->markTestSkipped('git ls-files unavailable');
        }

        foreach ($output as $file) {
            $this->assertStringNotContainsStringIgnoringCase('.rcache', $file, 'tracked file must not be a cache artifact: ' . $file);
            $this->assertDoesNotMatchRegularExpression('#^(demo/cache|tests/cache)/#', $file, 'cache dirs must not be tracked: ' . $file);
        }
    }

    public function testPsrReturnTypesAllowMultiMajorInstalls()
    {
        $item = new ReflectionClass(Item::class);
        $this->assertSame('string', (string) $item->getMethod('getKey')->getReturnType());
        $this->assertSame('mixed', (string) $item->getMethod('get')->getReturnType());
        $this->assertSame('bool', (string) $item->getMethod('isHit')->getReturnType());
        $this->assertSame('static', (string) $item->getMethod('set')->getReturnType());
        $this->assertSame('static', (string) $item->getMethod('expiresAt')->getReturnType());
        $this->assertSame('static', (string) $item->getMethod('expiresAfter')->getReturnType());

        $pool = new ReflectionClass(Pool::class);
        $this->assertSame('bool', (string) $pool->getMethod('clear')->getReturnType());
        $this->assertSame('bool', (string) $pool->getMethod('commit')->getReturnType());
        $this->assertSame('bool', (string) $pool->getMethod('saveDeferred')->getReturnType());
        $this->assertSame('iterable', (string) $pool->getMethod('getItems')->getReturnType());

        $simple = new ReflectionClass(SimpleCache::class);
        $this->assertSame('mixed', (string) $simple->getMethod('get')->getReturnType());
        $this->assertSame('bool', (string) $simple->getMethod('set')->getReturnType());
        $this->assertSame('iterable', (string) $simple->getMethod('getMultiple')->getReturnType());
        $this->assertSame('bool', (string) $simple->getMethod('has')->getReturnType());
    }

    public function testSaveDeferredReturnsBool()
    {
        $dir = sys_get_temp_dir() . '/roolith-packaging-' . uniqid();
        $pool = new Pool(new \Roolith\Caching\Driver\FileDriver(['dir' => $dir]));

        try {
            $item = $pool->getItem('deferred-bool');
            $item->set('value')->expiresAfter(3600);

            $this->assertTrue($pool->saveDeferred($item));
            $this->assertTrue($pool->commit());
            $this->assertTrue($pool->getItem('deferred-bool')->isHit());
        } finally {
            $pool->clear();
            if (is_dir($dir)) {
                $files = glob($dir . '/*') ?: [];
                foreach ($files as $file) {
                    if (is_file($file)) {
                        unlink($file);
                    }
                }
                rmdir($dir);
            }
        }
    }
}
