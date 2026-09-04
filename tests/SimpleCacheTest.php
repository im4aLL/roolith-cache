<?php
use PHPUnit\Framework\TestCase;
use Roolith\Caching\Cache\Psr16\InvalidArgumentException;
use Roolith\Caching\Cache\SimpleCache;
use Roolith\Caching\Driver\FileDriver;
use Roolith\Caching\Traits\FileSystem;

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
        $this->assertArrayHasKey('foo1', $result);
        $this->assertArrayHasKey('foo2', $result);
        $this->assertArrayHasKey('foo3', $result);
        $this->assertEquals(1, $result['foo1']);
        $this->assertEquals(1, $result['foo2']);
        $this->assertEquals(2, $result['foo3']);
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
        $this->expectException(InvalidArgumentException::class);
        $this->simpleCache->get('(123');
    }

    public function testShouldPreserveKeysInGetMultiple()
    {
        $this->simpleCache->set('alpha', 'a', 3600);
        $this->simpleCache->set('beta', 'b', 3600);

        $result = $this->simpleCache->getMultiple(['alpha', 'beta', 'missing'], 'fallback');

        $this->assertSame(['alpha', 'beta', 'missing'], array_keys($result));
        $this->assertSame('a', $result['alpha']);
        $this->assertSame('b', $result['beta']);
        $this->assertSame('fallback', $result['missing']);
    }

    public function testShouldHandleDateIntervalTtlAsTotalDuration()
    {
        $this->assertTrue($this->simpleCache->set('p1d-key', 'value', new DateInterval('P1D')));
        $this->assertTrue($this->simpleCache->set('pt2h-key', 'value', new DateInterval('PT2H')));

        $this->assertEquals('value', $this->simpleCache->get('p1d-key'));
        $this->assertEquals('value', $this->simpleCache->get('pt2h-key'));
        $this->assertTrue($this->simpleCache->has('p1d-key'));
        $this->assertTrue($this->simpleCache->has('pt2h-key'));
    }

    public function testShouldUseDefaultTtlWhenNull()
    {
        $this->assertTrue($this->simpleCache->set('null-ttl-key', 'value', null));
        $this->assertEquals('value', $this->simpleCache->get('null-ttl-key'));
        $this->assertTrue($this->simpleCache->has('null-ttl-key'));

        $this->assertTrue($this->simpleCache->set('default-ttl-key', 'value'));
        $this->assertEquals('value', $this->simpleCache->get('default-ttl-key'));
        $this->assertTrue($this->simpleCache->has('default-ttl-key'));
    }

    public function testShouldExpireImmediatelyWithZeroTtl()
    {
        $this->assertTrue($this->simpleCache->set('zero-ttl-key', 'value', 0));
        $this->assertEquals('fallback', $this->simpleCache->get('zero-ttl-key', 'fallback'));
        $this->assertFalse($this->simpleCache->has('zero-ttl-key'));
    }

    public function testShouldExpireImmediatelyWithNegativeTtl()
    {
        $this->assertTrue($this->simpleCache->set('negative-ttl-key', 'value', -10));
        $this->assertEquals('fallback', $this->simpleCache->get('negative-ttl-key', 'fallback'));
        $this->assertFalse($this->simpleCache->has('negative-ttl-key'));
    }

    public function testShouldThrowForStringTtl()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->simpleCache->set('string-ttl-key', 'value', '3600');
    }

    public function testShouldThrowForInvalidTtlTypes()
    {
        foreach ([1.5, true, ['ttl'], new \stdClass()] as $invalidTtl) {
            try {
                $this->simpleCache->set('invalid-ttl-key', 'value', $invalidTtl);
                $this->fail('Expected InvalidArgumentException for TTL: '.var_export($invalidTtl, true));
            } catch (InvalidArgumentException $e) {
                $this->assertInstanceOf(InvalidArgumentException::class, $e);
            }
        }
    }

    public function testShouldThrowForNonIterableBulkInput()
    {
        foreach (['getMultiple', 'setMultiple', 'deleteMultiple'] as $method) {
            try {
                if ($method === 'setMultiple') {
                    $this->simpleCache->$method('not-iterable');
                } else {
                    $this->simpleCache->$method('not-iterable');
                }
                $this->fail('Expected InvalidArgumentException for '.$method.' with string input');
            } catch (InvalidArgumentException $e) {
                $this->assertInstanceOf(InvalidArgumentException::class, $e);
            }
        }
    }

    public function testShouldThrowForInvalidBulkKeys()
    {
        try {
            $this->simpleCache->getMultiple(['valid-key', '(123'], 'fallback');
            $this->fail('Expected InvalidArgumentException for getMultiple with invalid key');
        } catch (InvalidArgumentException $e) {
            $this->assertInstanceOf(InvalidArgumentException::class, $e);
        }

        try {
            $this->simpleCache->setMultiple(['valid-key' => 1, '(123' => 2], 3600);
            $this->fail('Expected InvalidArgumentException for setMultiple with invalid key');
        } catch (InvalidArgumentException $e) {
            $this->assertInstanceOf(InvalidArgumentException::class, $e);
        }

        try {
            $this->simpleCache->deleteMultiple(['valid-key', '(123']);
            $this->fail('Expected InvalidArgumentException for deleteMultiple with invalid key');
        } catch (InvalidArgumentException $e) {
            $this->assertInstanceOf(InvalidArgumentException::class, $e);
        }
    }

    public function testShouldThrowForInvalidTtlInSetMultiple()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->simpleCache->setMultiple(['bulk-key' => 'value'], '3600');
    }

    public function testShouldAcceptTraversableInBulkMethods()
    {
        $values = new \ArrayObject(['trav-foo' => 1, 'trav-bar' => 2]);
        $this->assertTrue($this->simpleCache->setMultiple($values, 3600));

        $result = $this->simpleCache->getMultiple(new \ArrayObject(['trav-foo', 'trav-bar', 'trav-missing']), 'fallback');
        $this->assertSame(1, $result['trav-foo']);
        $this->assertSame(2, $result['trav-bar']);
        $this->assertSame('fallback', $result['trav-missing']);

        $this->assertTrue($this->simpleCache->deleteMultiple(new \ArrayObject(['trav-foo', 'trav-bar'])));
        $this->assertFalse($this->simpleCache->has('trav-foo'));
    }

    public function testShouldRoundTripFalsyValues()
    {
        $cases = [
            'false-key' => false,
            'zero-int' => 0,
            'empty-str' => '',
            'null-val' => null,
            'empty-arr' => [],
        ];

        foreach ($cases as $key => $value) {
            $this->assertTrue($this->simpleCache->set($key, $value, 3600));
            $this->assertTrue($this->simpleCache->has($key), 'Failed has for key: '.$key);
            $this->assertSame($value, $this->simpleCache->get($key, 'fallback'), 'Failed value for key: '.$key);
        }
    }

    public function testShouldAcceptZeroStringAsKey()
    {
        $this->assertTrue($this->simpleCache->set('0', 'zero-value', 3600));
        $this->assertTrue($this->simpleCache->has('0'));
        $this->assertSame('zero-value', $this->simpleCache->get('0'));
    }
}
