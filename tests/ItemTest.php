<?php


use Carbon\Carbon;

class ItemTest extends \PHPUnit\Framework\TestCase
{
    public $item;

    public function setUp(): void
    {
        $this->item = new \Roolith\Caching\Cache\Item('foo', 1);
    }

    public function testShouldGetKey()
    {
        $this->assertEquals('foo', $this->item->getKey());
    }

    public function testShouldGetValue()
    {
        $this->assertEquals(1, $this->item->get());
    }

    public function testShouldReturnBooleanWhetherValueIsHit()
    {
        $this->assertIsBool($this->item->isHit());
    }

    public function testShouldDefaultToMiss()
    {
        $item = new \Roolith\Caching\Cache\Item('foo', 1);

        $this->assertFalse($item->isHit());
    }

    public function testShouldTrackHitFlagInsteadOfTruthiness()
    {
        $item = new \Roolith\Caching\Cache\Item('foo', 0);

        $this->assertFalse($item->isHit());

        $item->setHit(true);

        $this->assertTrue($item->isHit());
        $this->assertSame(0, $item->get());

        $item->setHit(false);

        $this->assertFalse($item->isHit());
    }

    public function testShouldSetValue()
    {
        $this->item->set(2);

        $this->assertEquals(2, $this->item->get());
    }

    public function testShouldSetExpireAt()
    {
        $this->item->expiresAt(null);
        $this->assertEquals($this->item->getDefaultExpiration(), $this->item->getExpiration());

        $this->item->expiresAt(new DateTime('2020-01-01 00:00:00'));
        $this->assertInstanceOf(Carbon::class, $this->item->getExpiration());

        $this->item->expiresAt(Carbon::now());
        $this->assertInstanceOf(Carbon::class, $this->item->getExpiration());

        $this->expectException(\Roolith\Caching\Cache\Psr6\InvalidArgumentException::class);
        $this->item->expiresAt('222');
    }

    public function testShouldSetExpireAfter()
    {
        $this->item->expiresAfter(3600);
        $this->assertInstanceOf(Carbon::class, $this->item->getExpiration());

        $this->item->expiresAfter(null);
        $this->assertInstanceOf(Carbon::class, $this->item->getExpiration());

        $this->item->expiresAfter(new DateInterval("P1Y2M3DT4H5M6S"));
        $this->assertInstanceOf(Carbon::class, $this->item->getExpiration());
    }

    public function testShouldReturnSelfFromExpiresAfterForChaining()
    {
        $result = $this->item->set(1)->expiresAfter(3600);

        $this->assertSame($this->item, $result);
        $this->assertInstanceOf(Carbon::class, $this->item->getExpiration());
    }

    public function testShouldFallbackToDefaultExpirationWhenExpirationIsNull()
    {
        $item = new \Roolith\Caching\Cache\Item('fresh', 1);

        $this->assertInstanceOf(Carbon::class, $item->getExpiration());
        $this->assertEquals($item->getDefaultExpiration(), $item->getExpiration());

        $item->expiresAfter(null);

        $this->assertEquals($item->getDefaultExpiration(), $item->getExpiration());
    }
}