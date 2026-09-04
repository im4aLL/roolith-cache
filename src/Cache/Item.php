<?php
namespace Roolith\Caching\Cache;

use Carbon\Carbon;
use Carbon\CarbonInterval;
use DateInterval;
use DateTime;
use DateTimeInterface;
use Psr\Cache\CacheItemInterface;
use Roolith\Caching\Cache\Psr6\InvalidArgumentException;

class Item implements CacheItemInterface
{
    protected $key;
    protected $value;
    protected $expiration;
    protected $defaultExpiration;
    protected $isHit = false;

    public function __construct($key, $value = null)
    {
        $this->key = $key;
        $this->value = $value;
        $this->setDefaultExpiration(Carbon::now()->addMonths(1));
    }

    /**
     * @inheritDoc
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * @inheritDoc
     */
    public function get()
    {
        return $this->value;
    }

    /**
     * @inheritDoc
     */
    public function isHit()
    {
        return $this->isHit;
    }

    /**
     * Set hit flag.
     *
     * @param bool $isHit
     * @return $this
     */
    public function setHit($isHit)
    {
        $this->isHit = (bool) $isHit;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function set($value)
    {
        $this->value = $value;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function expiresAt($expiration)
    {
        if (is_null($expiration)) {
            $this->expiration = $this->getDefaultExpiration();
        } elseif (!($expiration instanceof DateTimeInterface) && !($expiration instanceof Carbon) && !($expiration instanceof DateTime)) {
            throw new InvalidArgumentException('expiresAt should be \DateTime or \DateTimeInterface');
        } elseif ($expiration instanceof Carbon) {
            $this->expiration = $expiration;
        } else {
            $this->expiration = Carbon::instance($expiration);
        }

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function expiresAfter($time)
    {
        if ($time instanceof DateInterval) {
            $this->expiration = Carbon::now()->addSeconds(CarbonInterval::instance($time)->seconds);
        } elseif (is_int($time)) {
            $this->expiration = Carbon::now()->addSeconds($time);
        } else {
            $this->expiration = $this->getDefaultExpiration();
        }

        return $this;
    }

    /**
     * @return mixed
     */
    public function getExpiration()
    {
        if ($this->expiration === null) {
            return $this->getDefaultExpiration();
        }

        return $this->expiration;
    }

    /**
     * @return mixed
     */
    public function getDefaultExpiration()
    {
        return $this->defaultExpiration;
    }

    /**
     * @param $defaultExpiration
     * @return $this
     */
    public function setDefaultExpiration($defaultExpiration)
    {
        $this->defaultExpiration = $defaultExpiration;

        return $this;
    }
}
