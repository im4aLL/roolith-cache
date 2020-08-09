<?php
namespace Roolith\Cache;

use Carbon\Carbon;
use DateInterval;
use DateTime;
use DateTimeInterface;
use InvalidArgumentException;
use Psr\Cache\CacheItemInterface;

class Item implements CacheItemInterface
{
    protected $key;
    protected $value;
    protected $expiration;

    public function __construct($key, $value = null)
    {
        $this->key = $key;
        $this->value = $value;
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
        return $this->get() ? true : false;
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
        if (!($expiration instanceof DateTimeInterface) && !($expiration instanceof Carbon) && !($expiration instanceof DateTime)) {
            throw new InvalidArgumentException('expiresAt should be \DateTime or \DateTimeInterface');
        } elseif (is_null($expiration)) {
            $this->expiration = Carbon::now()->addHour(5);
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
            $this->expiration = Carbon::instance($time);
        } elseif (is_int($time)) {
            $this->expiration = Carbon::now()->addSeconds($time);
        } else {
            $this->expiration = Carbon::now()->addHour(5);
        }
    }

    /**
     * @return mixed
     */
    public function getExpiration()
    {
        return $this->expiration;
    }
}
